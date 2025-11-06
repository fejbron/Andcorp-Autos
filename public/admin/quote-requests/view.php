<?php
require_once '../../bootstrap.php';
Auth::requireStaff();

$quoteRequestModel = new QuoteRequest();

$requestId = Security::sanitizeInt($_GET['id'] ?? 0);
if (!$requestId) {
    redirect(url('../../admin/quote-requests.php'));
}

$request = $quoteRequestModel->findById($requestId);
if (!$request) {
    setErrors(['general' => 'Quote request not found']);
    redirect(url('../../admin/quote-requests.php'));
}

// Handle quote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_quote'])) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        $errors['general'] = 'Invalid security token. Please try again.';
        setErrors($errors);
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $requestId);
        exit;
    }
    
    $validator = new Validator();
    $validator->required('quoted_price', $_POST['quoted_price'] ?? '')
              ->required('shipping_cost', $_POST['shipping_cost'] ?? '')
              ->required('duty_estimate', $_POST['duty_estimate'] ?? '');
    
    $errors = $validator->getErrors();
    
    if (empty($errors)) {
        try {
            // Calculate total
            $quotedPrice = floatval($_POST['quoted_price']);
            $shippingCost = floatval($_POST['shipping_cost']);
            $dutyEstimate = floatval($_POST['duty_estimate']);
            $totalEstimate = $quotedPrice + $shippingCost + $dutyEstimate;
            
            $quoteData = [
                'quoted_price' => $quotedPrice,
                'shipping_cost' => $shippingCost,
                'duty_estimate' => $dutyEstimate,
                'total_estimate' => $totalEstimate,
                'admin_notes' => $_POST['admin_notes'] ?? null
            ];
            
            $quoteRequestModel->addQuote($requestId, $quoteData, Auth::userId());
            
            // Create notification for customer
            $notificationModel = new Notification();
            $notificationModel->create(
                $request['customer_user_id'],
                null, // No order ID yet (quote request)
                'email',
                'Quote Ready for ' . $request['request_number'],
                'Your quote for ' . $request['make'] . ' ' . $request['model'] . ' is ready! Total estimate: ' . formatCurrency($totalEstimate)
            );
            
            clearOld();
            setSuccess('Quote added successfully and customer has been notified!');
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $requestId);
            exit;
        } catch (Exception $e) {
            $errors['general'] = 'An error occurred while adding the quote. Please try again.';
            error_log("Quote addition error: " . $e->getMessage());
        }
    }
    
    setErrors($errors);
    setOld($_POST);
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        $errors['general'] = 'Invalid security token. Please try again.';
        setErrors($errors);
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $requestId);
        exit;
    }
    
    try {
        $quoteRequestModel->update($requestId, [
            'status' => $_POST['status']
        ]);
        
        setSuccess('Status updated successfully!');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $requestId);
        exit;
    } catch (Exception $e) {
        setErrors(['general' => 'An error occurred while updating status.']);
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $requestId);
        exit;
    }
}

// Refresh request data
$request = $quoteRequestModel->findById($requestId);

$title = "Quote Request #" . $request['request_number'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Request #<?php echo htmlspecialchars($request['request_number']); ?> - Andcorp Autos</title>
    <link rel="icon" type="image/png" href="<?php echo url('assets/images/favicon.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo url('assets/images/logo.png'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container-fluid my-4">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <!-- Page Header -->
                <div class="page-header animate-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-5">Quote Request #<?php echo htmlspecialchars($request['request_number']); ?></h1>
                            <p class="lead mb-0">Review and provide quote for customer request</p>
                        </div>
                        <div>
                            <a href="<?php echo url('admin/quote-requests.php'); ?>" class="btn btn-secondary btn-modern">
                                <i class="bi bi-arrow-left"></i> Back to Requests
                            </a>
                        </div>
                    </div>
                </div>

                <?php if ($successMsg = success()): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i> <?php echo $successMsg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (error('general')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo error('general'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <!-- Customer Information -->
                        <div class="card-modern mb-4 animate-in">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-person"></i> Customer Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($request['customer_first_name'] . ' ' . $request['customer_last_name']); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($request['customer_email']); ?></p>
                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($request['customer_phone'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <?php if ($request['ghana_card_number']): ?>
                                            <p><strong>Ghana Card:</strong> <?php echo htmlspecialchars($request['ghana_card_number']); ?></p>
                                        <?php endif; ?>
                                        <p><strong>Requested:</strong> <?php echo date('M d, Y g:i A', strtotime($request['created_at'])); ?></p>
                                        <p><strong>Status:</strong> 
                                            <?php
                                            $statusClass = match($request['status']) {
                                                'pending' => 'bg-warning',
                                                'reviewing' => 'bg-info',
                                                'quoted' => 'bg-primary',
                                                'approved' => 'bg-success',
                                                'rejected' => 'bg-danger',
                                                'converted' => 'bg-success',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Vehicle Details -->
                        <div class="card-modern mb-4 animate-in">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-car-front"></i> Vehicle Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Type:</strong> <?php echo htmlspecialchars($request['vehicle_type']); ?></p>
                                        <p><strong>Make:</strong> <?php echo htmlspecialchars($request['make']); ?></p>
                                        <p><strong>Model:</strong> <?php echo htmlspecialchars($request['model']); ?></p>
                                        <?php if ($request['year']): ?>
                                            <p><strong>Year:</strong> <?php echo $request['year']; ?></p>
                                        <?php endif; ?>
                                        <?php if ($request['trim']): ?>
                                            <p><strong>Trim:</strong> <?php echo htmlspecialchars($request['trim']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?php if ($request['vin']): ?>
                                            <p><strong>VIN:</strong> <code><?php echo htmlspecialchars($request['vin']); ?></code></p>
                                        <?php endif; ?>
                                        <?php if ($request['lot_number']): ?>
                                            <p><strong>Lot Number:</strong> <code><?php echo htmlspecialchars($request['lot_number']); ?></code></p>
                                        <?php endif; ?>
                                        <?php if ($request['preferred_color']): ?>
                                            <p><strong>Preferred Color:</strong> <?php echo htmlspecialchars($request['preferred_color']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($request['auction_link']): ?>
                                            <p><strong>Auction Link:</strong><br>
                                                <a href="<?php echo htmlspecialchars($request['auction_link']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-box-arrow-up-right"></i> View on Copart/IAA
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($request['additional_requirements']): ?>
                                    <hr>
                                    <p class="mb-0"><strong>Additional Requirements:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($request['additional_requirements'])); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Budget Information -->
                        <?php if ($request['budget_min'] || $request['budget_max']): ?>
                        <div class="card-modern mb-4 animate-in">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Customer Budget</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($request['budget_min'] && $request['budget_max']): ?>
                                    <p class="mb-0"><strong>Budget Range:</strong> <?php echo formatCurrency($request['budget_min']); ?> - <?php echo formatCurrency($request['budget_max']); ?></p>
                                <?php elseif ($request['budget_max']): ?>
                                    <p class="mb-0"><strong>Maximum Budget:</strong> <?php echo formatCurrency($request['budget_max']); ?></p>
                                <?php else: ?>
                                    <p class="mb-0"><strong>Minimum Budget:</strong> <?php echo formatCurrency($request['budget_min']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Add Quote Form -->
                        <?php if ($request['status'] === 'pending' || $request['status'] === 'reviewing'): ?>
                        <div class="card-modern mb-4 animate-in">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-calculator"></i> Provide Quote</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $requestId; ?>">
                                    <?php echo Security::csrfField(); ?>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="quoted_price" class="form-label">Vehicle Price (GHS) *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">GHS</span>
                                                <input type="number" step="0.01" class="form-control <?php echo hasError('quoted_price') ? 'is-invalid' : ''; ?>" 
                                                       id="quoted_price" name="quoted_price" value="<?php echo old('quoted_price'); ?>" required>
                                            </div>
                                            <?php if (error('quoted_price')): ?>
                                                <div class="invalid-feedback d-block"><?php echo error('quoted_price'); ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="shipping_cost" class="form-label">Shipping Cost (GHS) *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">GHS</span>
                                                <input type="number" step="0.01" class="form-control <?php echo hasError('shipping_cost') ? 'is-invalid' : ''; ?>" 
                                                       id="shipping_cost" name="shipping_cost" value="<?php echo old('shipping_cost'); ?>" required>
                                            </div>
                                            <?php if (error('shipping_cost')): ?>
                                                <div class="invalid-feedback d-block"><?php echo error('shipping_cost'); ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="duty_estimate" class="form-label">Duty Estimate (GHS) *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">GHS</span>
                                                <input type="number" step="0.01" class="form-control <?php echo hasError('duty_estimate') ? 'is-invalid' : ''; ?>" 
                                                       id="duty_estimate" name="duty_estimate" value="<?php echo old('duty_estimate'); ?>" required>
                                            </div>
                                            <?php if (error('duty_estimate')): ?>
                                                <div class="invalid-feedback d-block"><?php echo error('duty_estimate'); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="admin_notes" class="form-label">Notes for Customer</label>
                                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" 
                                                  placeholder="Additional notes, conditions, or information..."><?php echo old('admin_notes'); ?></textarea>
                                    </div>

                                    <button type="submit" name="add_quote" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Submit Quote to Customer
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Quote Information (if exists) -->
                        <?php if ($request['quoted_price']): ?>
                        <div class="card-modern mb-4 animate-in">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-file-earmark-check"></i> Quote Provided</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Vehicle Price:</strong> <?php echo formatCurrency($request['quoted_price']); ?></p>
                                        <p><strong>Shipping Cost:</strong> <?php echo formatCurrency($request['shipping_cost']); ?></p>
                                        <p><strong>Duty Estimate:</strong> <?php echo formatCurrency($request['duty_estimate']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="alert alert-success mb-0">
                                            <h5 class="mb-0">Total Estimate</h5>
                                            <h3 class="mb-0"><?php echo formatCurrency($request['total_estimate']); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($request['admin_notes']): ?>
                                    <hr>
                                    <p class="mb-0"><strong>Admin Notes:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <hr>
                                <p class="small text-muted mb-0">
                                    <i class="bi bi-person"></i> Quoted by: <?php echo htmlspecialchars($request['quoted_by_first_name'] . ' ' . $request['quoted_by_last_name']); ?><br>
                                    <i class="bi bi-clock"></i> Quote Date: <?php echo date('M d, Y g:i A', strtotime($request['quoted_at'])); ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-lg-4">
                        <!-- Quick Actions -->
                        <div class="card-modern mb-4 animate-in">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <?php if ($request['status'] === 'quoted' || $request['status'] === 'approved'): ?>
                                        <a href="<?php echo url('admin/quote-requests/convert.php?id=' . $requestId); ?>" class="btn btn-success">
                                            <i class="bi bi-arrow-right-circle"></i> Convert to Order
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['order_id']): ?>
                                        <a href="<?php echo url('admin/orders/edit.php?id=' . $request['order_id']); ?>" class="btn btn-primary">
                                            <i class="bi bi-box-seam"></i> View Order
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo url('admin/quote-requests.php'); ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left"></i> Back to Requests
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Update Status -->
                        <div class="card-modern animate-in">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-toggles"></i> Update Status</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $requestId; ?>">
                                    <?php echo Security::csrfField(); ?>
                                    
                                    <div class="mb-3">
                                        <select name="status" class="form-select" required>
                                            <option value="pending" <?php echo $request['status'] === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                                            <option value="reviewing" <?php echo $request['status'] === 'reviewing' ? 'selected' : ''; ?>>Under Review</option>
                                            <option value="quoted" <?php echo $request['status'] === 'quoted' ? 'selected' : ''; ?>>Quote Ready</option>
                                            <option value="approved" <?php echo $request['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="rejected" <?php echo $request['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" name="update_status" class="btn btn-primary w-100">
                                        <i class="bi bi-check-circle"></i> Update Status
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Convert to Order -->
                        <?php if ($request['quoted_price'] && !$request['order_id'] && $request['status'] !== 'rejected'): ?>
                        <div class="card-modern animate-in mt-3">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="bi bi-arrow-right-circle"></i> Convert to Order</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-3 small">
                                    <i class="bi bi-info-circle me-1"></i>
                                    This will create a new order with the quote details and notify the customer.
                                </p>
                                <a href="<?php echo url('admin/quote-requests/convert.php?id=' . $requestId); ?>" 
                                   class="btn btn-success w-100">
                                    <i class="bi bi-box-seam"></i> Create Order from Quote
                                </a>
                            </div>
                        </div>
                        <?php elseif ($request['order_id']): ?>
                        <div class="card-modern animate-in mt-3">
                            <div class="card-header bg-dark text-white">
                                <h5 class="mb-0"><i class="bi bi-check-circle"></i> Converted to Order</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-3 small">
                                    <i class="bi bi-info-circle me-1"></i>
                                    This quote has been converted to an order.
                                </p>
                                <a href="<?php echo url('admin/orders/edit.php?id=' . $request['order_id']); ?>" 
                                   class="btn btn-dark w-100">
                                    <i class="bi bi-box-seam"></i> View Order
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-calculate total
        document.getElementById('quoted_price')?.addEventListener('input', calculateTotal);
        document.getElementById('shipping_cost')?.addEventListener('input', calculateTotal);
        document.getElementById('duty_estimate')?.addEventListener('input', calculateTotal);
        
        function calculateTotal() {
            const price = parseFloat(document.getElementById('quoted_price')?.value || 0);
            const shipping = parseFloat(document.getElementById('shipping_cost')?.value || 0);
            const duty = parseFloat(document.getElementById('duty_estimate')?.value || 0);
            const total = price + shipping + duty;
            
            // You can display this total if needed
            console.log('Total Estimate: GHS ' + total.toFixed(2));
        }
    </script>
</body>
</html>
<?php clearErrors(); clearOld(); ?>

