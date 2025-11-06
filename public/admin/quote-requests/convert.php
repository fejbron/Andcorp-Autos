<?php
require_once '../../bootstrap.php';
Auth::requireStaff();

$quoteRequestModel = new QuoteRequest();
$orderModel = new Order();
$vehicleModel = new Vehicle();

$requestId = Security::sanitizeInt($_GET['id'] ?? 0);
if (!$requestId) {
    redirect(url('../../admin/quote-requests.php'));
}

$request = $quoteRequestModel->findById($requestId);
if (!$request) {
    setErrors(['general' => 'Quote request not found']);
    redirect(url('../../admin/quote-requests.php'));
}

// Check if already converted
if ($request['order_id']) {
    setErrors(['general' => 'This quote request has already been converted to an order']);
    redirect('../../admin/quote-requests/view.php?id=' . $requestId);
}

// Check if quote is ready
if (!$request['quoted_price'] || !$request['shipping_cost'] || !$request['duty_estimate']) {
    setErrors(['general' => 'Please add a quote before converting to an order']);
    redirect('../../admin/quote-requests/view.php?id=' . $requestId);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        setErrors(['general' => 'Invalid security token. Please try again.']);
        redirect('../../admin/quote-requests/convert.php?id=' . $requestId);
    }
    
    // Validate status first
    $rawStatus = trim($_POST['status'] ?? 'Pending');
    // Capitalize first letter to match database ENUM
    $rawStatus = ucfirst(strtolower($rawStatus));
    $allowedStatuses = ['Pending', 'Purchased', 'Shipping', 'Customs', 'Inspection', 'Repair', 'Ready', 'Delivered', 'Cancelled'];
    
    // Log the received status value
    error_log("convert.php - Raw status received: '" . $rawStatus . "' (length: " . strlen($rawStatus) . ")");
    error_log("convert.php - POST data: " . json_encode(['status' => $_POST['status'] ?? 'NOT SET']));
    
    $validator = new Validator();
    $validator->required('status', $rawStatus);
    
    $errors = $validator->getErrors();
    
    // Additional validation: ensure status is in allowed list
    if (empty($errors) && !in_array($rawStatus, $allowedStatuses, true)) {
        error_log("convert.php - Status not in allowed list: '$rawStatus'");
        $errors['status'] = 'Invalid order status selected. Received: ' . htmlspecialchars($rawStatus);
    }
    
    if (empty($errors)) {
        try {
            // Start transaction
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();
            
            // 1. Generate unique order number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // 2. Final validation of status - must be in allowed list
            $status = in_array($rawStatus, $allowedStatuses, true) ? $rawStatus : 'Pending';
            
            // 3. Create Order first (simplified schema)
            $orderData = [
                'customer_id' => $request['customer_id'],
                'order_number' => $orderNumber,
                'status' => $status, // Validated and guaranteed to be valid ENUM value
                'total_cost' => $request['total_estimate'],
                'deposit_amount' => 0,
                'balance_due' => $request['total_estimate'],
                'currency' => 'GHS',
                'notes' => $_POST['notes'] ?? null
            ];
            
            $orderId = $orderModel->create($orderData);
            
            // Verify order was created
            if (!$orderId || $orderId <= 0) {
                throw new Exception('Failed to create order');
            }
            
            // 3. Create Vehicle (if details are provided)
            $vehicleId = null;
            if (!empty($request['make']) && !empty($request['model']) && !empty($request['year'])) {
                try {
                    $vehicleData = [
                        'order_id' => $orderId,
                        'auction_source' => 'copart', // Default
                        'listing_url' => $request['auction_link'] ?? null,
                        'lot_number' => $request['lot_number'] ?? null,
                        'vin' => $request['vin'] ?? null,
                        'make' => $request['make'],
                        'model' => $request['model'],
                        'year' => $request['year'],
                        'color' => $request['preferred_color'] ?? null,
                        'purchase_price' => $request['quoted_price']
                    ];
                    $vehicleId = $vehicleModel->create($vehicleData);
                } catch (Exception $ve) {
                    // Log vehicle creation error but don't fail the whole conversion
                    error_log("Vehicle creation error during quote conversion: " . $ve->getMessage());
                }
            }
            
            // 4. Update quote request with order_id and status
            $quoteRequestModel->update($requestId, [
                'order_id' => $orderId,
                'status' => 'converted', // Match quote_requests ENUM value
                'converted_by' => Auth::userId(),
                'converted_at' => date('Y-m-d H:i:s')
            ]);
            
            // 5. Create notification for customer
            $notificationModel = new Notification();
            $notificationModel->create(
                $request['customer_user_id'],
                $orderId,
                'email',
                'Your Quote Has Been Converted to Order #' . $orderNumber,
                'Great news! Your quote request has been approved and converted to an order. You can now start making deposits and track your vehicle import.'
            );
            
            // Commit transaction
            $db->commit();
            
            clearOld();
            setSuccess('Quote request successfully converted to order #' . $orderNumber . '!');
            redirect(url('admin/orders/edit.php?id=' . $orderId));
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            $errorMessage = $e->getMessage();
            
            // Provide more helpful error messages
            if (strpos($errorMessage, 'Data truncated for column') !== false) {
                $errorMessage = 'Invalid order status value. The database rejected the status: "' . htmlspecialchars($rawStatus ?? 'unknown') . '". Please try again with a valid status.';
                error_log("Quote conversion error - Status truncation: Raw status was '$rawStatus', Allowed: " . implode(', ', $allowedStatuses));
            }
            
            $errors['general'] = 'An error occurred while converting to order: ' . $errorMessage;
            error_log("Quote conversion error: " . $e->getMessage());
            error_log("Quote conversion error - Stack trace: " . $e->getTraceAsString());
        }
    }
    
    if (!empty($errors)) {
        setErrors($errors);
        setOld($_POST);
    }
}

$title = "Convert Quote Request #" . $request['request_number'] . " to Order";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - Andcorp Autos</title>
    <link rel="icon" type="image/png" href="<?php echo url('assets/images/favicon.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo url('assets/images/logo.png'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1"><i class="bi bi-arrow-right-circle"></i> Convert to Order</h2>
                        <p class="text-muted mb-0">Create an order from quote request #<?php echo $request['request_number']; ?></p>
                    </div>
                    <a href="<?php echo url('admin/quote-requests/view.php?id=' . $requestId); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Quote Request
                    </a>
                </div>

                <?php if (error('general')): ?>
                    <div class="alert alert-danger alert-dismissible fade show animate-in">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo error('general'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Quote Summary -->
                    <div class="col-md-5 mb-4">
                        <div class="card-modern h-100 animate-in">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Quote Summary</h5>
                            </div>
                            <div class="card-body">
                                <h6 class="text-muted mb-3">Customer Information</h6>
                                <table class="table table-sm table-borderless mb-4">
                                    <tbody>
                                        <tr>
                                            <td class="text-muted" style="width: 40%;">Name:</td>
                                            <td><strong><?php echo $request['customer_first_name'] . ' ' . $request['customer_last_name']; ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Email:</td>
                                            <td><?php echo $request['customer_email']; ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Phone:</td>
                                            <td><?php echo $request['customer_phone'] ?? 'N/A'; ?></td>
                                        </tr>
                                    </tbody>
                                </table>

                                <h6 class="text-muted mb-3">Vehicle Details</h6>
                                <table class="table table-sm table-borderless mb-4">
                                    <tbody>
                                        <tr>
                                            <td class="text-muted" style="width: 40%;">Vehicle:</td>
                                            <td><strong><?php echo $request['make'] . ' ' . $request['model']; ?></strong></td>
                                        </tr>
                                        <?php if ($request['year']): ?>
                                        <tr>
                                            <td class="text-muted">Year:</td>
                                            <td><?php echo $request['year']; ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if ($request['trim']): ?>
                                        <tr>
                                            <td class="text-muted">Trim:</td>
                                            <td><?php echo $request['trim']; ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if ($request['vin']): ?>
                                        <tr>
                                            <td class="text-muted">VIN:</td>
                                            <td><code><?php echo $request['vin']; ?></code></td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>

                                <h6 class="text-muted mb-3">Price Breakdown</h6>
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        <tr>
                                            <td>Vehicle Price:</td>
                                            <td class="text-end"><strong><?php echo formatCurrency($request['quoted_price']); ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>Shipping Cost:</td>
                                            <td class="text-end"><strong><?php echo formatCurrency($request['shipping_cost']); ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>Duty Estimate:</td>
                                            <td class="text-end"><strong><?php echo formatCurrency($request['duty_estimate']); ?></strong></td>
                                        </tr>
                                        <tr class="table-success">
                                            <td><strong>Total Estimate:</strong></td>
                                            <td class="text-end"><strong><?php echo formatCurrency($request['total_estimate']); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Order Configuration -->
                    <div class="col-md-7 mb-4">
                        <div class="card-modern animate-in">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-gear"></i> Order Configuration</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $requestId; ?>">
                                    <?php echo Security::csrfField(); ?>

                                    <div class="mb-3">
                                        <label for="status" class="form-label">Order Status *</label>
                                        <select class="form-select <?php echo hasError('status') ? 'is-invalid' : ''; ?>" 
                                                id="status" name="status" required autocomplete="off">
                                            <?php 
                                            $selectedStatus = old('status', 'Pending');
                                            // Ensure selected status is valid (handle both cases)
                                            $selectedStatus = ucfirst(strtolower($selectedStatus));
                                            $validStatuses = ['Pending', 'Purchased', 'Shipping', 'Customs', 'Inspection', 'Repair', 'Ready', 'Delivered', 'Cancelled'];
                                            if (!in_array($selectedStatus, $validStatuses, true)) {
                                                $selectedStatus = 'Pending';
                                            }
                                            ?>
                                            <option value="Pending" <?php echo $selectedStatus === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Purchased" <?php echo $selectedStatus === 'Purchased' ? 'selected' : ''; ?>>Purchased</option>
                                            <option value="Shipping" <?php echo $selectedStatus === 'Shipping' ? 'selected' : ''; ?>>Shipping</option>
                                            <option value="Customs" <?php echo $selectedStatus === 'Customs' ? 'selected' : ''; ?>>Customs</option>
                                            <option value="Inspection" <?php echo $selectedStatus === 'Inspection' ? 'selected' : ''; ?>>Inspection</option>
                                            <option value="Repair" <?php echo $selectedStatus === 'Repair' ? 'selected' : ''; ?>>Repair</option>
                                            <option value="Ready" <?php echo $selectedStatus === 'Ready' ? 'selected' : ''; ?>>Ready</option>
                                            <option value="Delivered" <?php echo $selectedStatus === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="Cancelled" <?php echo $selectedStatus === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <?php if (error('status')): ?>
                                            <div class="invalid-feedback d-block"><?php echo error('status'); ?></div>
                                        <?php endif; ?>
                                        <div class="form-text">The order will start as "Pending". Payment tracking is handled via deposits.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Order Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                                  placeholder="Add any additional notes or instructions..."><?php echo old('notes', $request['admin_notes'] ?? ''); ?></textarea>
                                        <div class="form-text">These notes will be visible to the customer</div>
                                    </div>

                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Note:</strong> After creating the order, you can add deposits, upload documents, and manage the order details from the order management page.
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="bi bi-check-circle"></i> Create Order
                                        </button>
                                        <a href="<?php echo url('admin/quote-requests/view.php?id=' . $requestId); ?>" class="btn btn-outline-secondary">
                                            Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

