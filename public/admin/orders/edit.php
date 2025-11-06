<?php
require_once '../../bootstrap.php';
Auth::requireStaff();

$orderModel = new Order();
$vehicleModel = new Vehicle();

$orderId = Security::sanitizeInt($_GET['id'] ?? 0);
if (!$orderId || $orderId <= 0) {
    redirect(url('admin/orders.php'));
}

$order = $orderModel->findById($orderId);
if (!$order) {
    setErrors(['general' => 'Order not found']);
    redirect(url('admin/orders.php'));
}

$vehicle = $vehicleModel->findByOrderId($orderId);
$depositModel = new Deposit();
$deposits = $depositModel->getByOrder($orderId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        $errors['general'] = 'Invalid security token. Please try again.';
        setErrors($errors);
        redirect(url('admin/orders/edit.php?id=' . $orderId));
    }
    
    if (isset($_POST['update_order'])) {
        try {
            // Get purchase price from vehicle if exists
            $vehiclePurchasePrice = 0;
            if ($vehicle && !empty($vehicle['purchase_price'])) {
                $vehiclePurchasePrice = floatval($vehicle['purchase_price']);
            }
            
            // Calculate total USD from cost breakdown (including vehicle purchase price)
            $carCost = Security::sanitizeFloat($_POST['car_cost'] ?? 0, 0);
            $transportationCost = Security::sanitizeFloat($_POST['transportation_cost'] ?? 0, 0);
            $dutyCost = Security::sanitizeFloat($_POST['duty_cost'] ?? 0, 0);
            $clearingCost = Security::sanitizeFloat($_POST['clearing_cost'] ?? 0, 0);
            $fixingCost = Security::sanitizeFloat($_POST['fixing_cost'] ?? 0, 0);
            $totalUsd = $vehiclePurchasePrice + $carCost + $transportationCost + $dutyCost + $clearingCost + $fixingCost;
            
            // Get current total_deposits (sum of all verified deposits)
            $depositModel = new Deposit();
            $deposits = $depositModel->getByOrder($orderId);
            $totalDeposits = 0;
            foreach ($deposits as $deposit) {
                if ($deposit['status'] === 'verified') {
                    $totalDeposits += floatval($deposit['amount']);
                }
            }
            
            // Update order
            $totalCost = Security::sanitizeFloat($_POST['total_cost'] ?? 0, 0);
            $balanceDue = $totalCost - $totalDeposits;
            
            $orderModel->update($orderId, [
                'status' => Security::sanitizeStatus($_POST['status'] ?? $order['status']),
                'total_cost' => $totalCost,
                'deposit_amount' => Security::sanitizeFloat($_POST['deposit_amount'] ?? 0, 0),
                'total_deposits' => $totalDeposits,
                'balance_due' => $balanceDue,
                'currency' => 'GHS', // Always use GHS (Ghana Cedis)
                'notes' => !empty($_POST['notes']) ? Security::sanitizeString($_POST['notes'], 5000) : null,
                'car_cost' => $carCost,
                'transportation_cost' => $transportationCost,
                'duty_cost' => $dutyCost,
                'clearing_cost' => $clearingCost,
                'fixing_cost' => $fixingCost,
                'total_usd' => $totalUsd
            ]);
            
            // Update vehicle if exists
            if ($vehicle && !empty($_POST['make']) && !empty($_POST['model']) && !empty($_POST['year'])) {
                $vehicleModel->update($orderId, [
                    'auction_source' => Security::validateEnum($_POST['auction_source'] ?? 'copart', ['copart', 'iaa']) ? $_POST['auction_source'] : 'copart',
                    'listing_url' => !empty($_POST['listing_url']) ? Security::sanitizeUrl($_POST['listing_url']) : null,
                    'lot_number' => !empty($_POST['lot_number']) ? Security::sanitizeString($_POST['lot_number'], 100) : null,
                    'vin' => !empty($_POST['vin']) ? Security::sanitizeString(strtoupper($_POST['vin']), 17) : null,
                    'make' => Security::sanitizeString($_POST['make'], 100),
                    'model' => Security::sanitizeString($_POST['model'], 100),
                    'year' => Security::sanitizeInt($_POST['year'], 1990, date('Y') + 1),
                    'color' => !empty($_POST['color']) ? Security::sanitizeString($_POST['color'], 50) : null,
                    'mileage' => !empty($_POST['mileage']) ? Security::sanitizeInt($_POST['mileage'], 0) : null,
                    'engine_type' => !empty($_POST['engine_type']) ? Security::sanitizeString($_POST['engine_type'], 100) : null,
                    'transmission' => !empty($_POST['transmission']) && Security::validateEnum($_POST['transmission'], ['Automatic', 'Manual', 'CVT']) ? $_POST['transmission'] : null,
                    'condition_description' => !empty($_POST['condition_description']) ? Security::sanitizeString($_POST['condition_description'], 2000) : null,
                    'purchase_price' => !empty($_POST['purchase_price']) ? Security::sanitizeFloat($_POST['purchase_price'], 0) : null,
                    'purchase_date' => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null
                ]);
            }
            
            // Log activity
            Auth::logOrderActivity(Auth::userId(), $orderId, 'order_updated', 'Order updated by staff');
            
            // Send notification if status changed
            $newStatus = Security::sanitizeStatus($_POST['status'] ?? $order['status']);
            if ($newStatus !== $order['status']) {
                $notificationModel = new Notification();
                $notificationModel->sendOrderUpdate($orderId, $newStatus);
            }
            
            clearOld();
            setSuccess('Order updated successfully!');
            redirect(url('admin/orders/edit.php?id=' . $orderId));
        } catch (Exception $e) {
            $errors['general'] = 'An error occurred while updating the order. Please try again.';
            error_log("Order update error: " . $e->getMessage());
            setErrors($errors);
        }
    }
}

// Refresh order data
$order = $orderModel->findById($orderId);
$vehicle = $vehicleModel->findByOrderId($orderId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order <?php echo $order['order_number']; ?> - Andcorp Autos Admin</title>
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
                <div class="page-header animate-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-5">Edit Order</h1>
                            <p class="lead mb-0">Order #<?php echo $order['order_number']; ?></p>
                        </div>
                        <div>
                            <a href="<?php echo url('orders/view.php?id=' . $orderId); ?>" class="btn btn-info btn-modern me-2">
                                <i class="bi bi-eye"></i> View Order
                            </a>
                            <a href="<?php echo url('admin/orders.php'); ?>" class="btn btn-secondary btn-modern">
                                <i class="bi bi-arrow-left"></i> Back to Orders
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

                <div class="row">
                    <div class="col-lg-8">
                        <!-- Order Information -->
                        <div class="card-modern mb-4 animate-in">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Order Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . urlencode($orderId); ?>">
                                    <?php echo Security::csrfField(); ?>
                                    <input type="hidden" name="update_order" value="1">
                                    <!-- Status -->
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Order Status *</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="purchased" <?php echo $order['status'] === 'purchased' ? 'selected' : ''; ?>>Purchased</option>
                                            <option value="shipping" <?php echo $order['status'] === 'shipping' ? 'selected' : ''; ?>>Shipping</option>
                                            <option value="customs" <?php echo $order['status'] === 'customs' ? 'selected' : ''; ?>>Customs</option>
                                            <option value="inspection" <?php echo $order['status'] === 'inspection' ? 'selected' : ''; ?>>Inspection</option>
                                            <option value="repair" <?php echo $order['status'] === 'repair' ? 'selected' : ''; ?>>Repair</option>
                                            <option value="ready" <?php echo $order['status'] === 'ready' ? 'selected' : ''; ?>>Ready</option>
                                            <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <div class="form-text">Changing status will send notification to customer</div>
                                    </div>

                                    <!-- Financial -->
                                    <h6 class="mt-4 mb-3">Financial Information</h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="total_cost" class="form-label">Total Cost</label>
                                            <input type="number" step="0.01" class="form-control" id="total_cost" 
                                                   name="total_cost" value="<?php echo $order['total_cost']; ?>">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="deposit_amount" class="form-label">Deposit Amount</label>
                                            <input type="number" step="0.01" class="form-control" id="deposit_amount" 
                                                   name="deposit_amount" value="<?php echo $order['deposit_amount']; ?>">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="currency" class="form-label">Currency</label>
                                            <input type="text" class="form-control" id="currency" name="currency" value="GHS" readonly>
                                            <small class="form-text text-muted">All transactions are in Ghana Cedis (GHS)</small>
                                        </div>
                                    </div>

                                    <!-- Cost Breakdown -->
                                    <h6 class="mt-4 mb-3">Cost Breakdown (GHS - Ghana Cedis)</h6>
                                    <p class="text-muted small mb-3">Detailed breakdown of costs for customer transparency</p>
                                    
                                    <?php if ($vehicle && !empty($vehicle['purchase_price'])): ?>
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <div class="alert alert-success">
                                                <i class="bi bi-cash-coin"></i> <strong>Vehicle Purchase Price (from auction):</strong> 
                                                GHS <?php echo number_format($vehicle['purchase_price'], 2); ?>
                                                <small class="d-block mt-1 text-muted">This amount is automatically included in the total cost calculation</small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="car_cost" class="form-label">Additional Car Costs</label>
                                            <input type="number" step="0.01" class="form-control" id="car_cost" 
                                                   name="car_cost" value="<?php echo $order['car_cost'] ?? 0; ?>" 
                                                   placeholder="0.00">
                                            <small class="form-text text-muted">Any additional costs related to the vehicle purchase</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="transportation_cost" class="form-label">Transportation to Ghana</label>
                                            <input type="number" step="0.01" class="form-control" id="transportation_cost" 
                                                   name="transportation_cost" value="<?php echo $order['transportation_cost'] ?? 0; ?>" 
                                                   placeholder="0.00">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="duty_cost" class="form-label">Duty/Customs Fees</label>
                                            <input type="number" step="0.01" class="form-control" id="duty_cost" 
                                                   name="duty_cost" value="<?php echo $order['duty_cost'] ?? 0; ?>" 
                                                   placeholder="0.00">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="clearing_cost" class="form-label">Clearing Fees</label>
                                            <input type="number" step="0.01" class="form-control" id="clearing_cost" 
                                                   name="clearing_cost" value="<?php echo $order['clearing_cost'] ?? 0; ?>" 
                                                   placeholder="0.00">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="fixing_cost" class="form-label">Fixing/Repair Cost</label>
                                            <input type="number" step="0.01" class="form-control" id="fixing_cost" 
                                                   name="fixing_cost" value="<?php echo $order['fixing_cost'] ?? 0; ?>" 
                                                   placeholder="0.00">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <div class="alert alert-info">
                                                <i class="bi bi-calculator"></i> <strong>Total Cost (GHS):</strong> 
                                                GHS <span id="total_usd_display"><?php echo number_format($order['total_usd'] ?? 0, 2); ?></span>
                                                <input type="hidden" id="total_usd" name="total_usd" value="<?php echo $order['total_usd'] ?? 0; ?>">
                                                <small class="d-block mt-1 text-muted">This total will be used as the order's total cost</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Vehicle Information -->
                                    <?php if ($vehicle): ?>
                                        <h6 class="mt-4 mb-3">Vehicle Information</h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="auction_source" class="form-label">Auction Source</label>
                                                <select class="form-select" id="auction_source" name="auction_source">
                                                    <option value="copart" <?php echo $vehicle['auction_source'] === 'copart' ? 'selected' : ''; ?>>Copart</option>
                                                    <option value="iaa" <?php echo $vehicle['auction_source'] === 'iaa' ? 'selected' : ''; ?>>IAA (Insurance Auto Auctions)</option>
                                                    <option value="sca" <?php echo $vehicle['auction_source'] === 'sca' ? 'selected' : ''; ?>>SCA Auction</option>
                                                    <option value="tgna" <?php echo $vehicle['auction_source'] === 'tgna' ? 'selected' : ''; ?>>The Great Northern Auction (TGNA)</option>
                                                    <option value="manheim" <?php echo $vehicle['auction_source'] === 'manheim' ? 'selected' : ''; ?>>Manheim Auctions</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="lot_number" class="form-label">Lot Number</label>
                                                <input type="text" class="form-control" id="lot_number" name="lot_number" 
                                                       value="<?php echo $vehicle['lot_number'] ?? ''; ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="listing_url" class="form-label">Listing URL</label>
                                            <input type="url" class="form-control" id="listing_url" name="listing_url" 
                                                   value="<?php echo $vehicle['listing_url'] ?? ''; ?>">
                                        </div>

                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="year" class="form-label">Year</label>
                                                <input type="number" class="form-control" id="year" name="year" 
                                                       value="<?php echo $vehicle['year']; ?>">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="make" class="form-label">Make</label>
                                                <input type="text" class="form-control" id="make" name="make" 
                                                       value="<?php echo $vehicle['make']; ?>">
                                            </div>

                                            <div class="col-md-5 mb-3">
                                                <label for="model" class="form-label">Model</label>
                                                <input type="text" class="form-control" id="model" name="model" 
                                                       value="<?php echo $vehicle['model']; ?>">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="vin" class="form-label">VIN</label>
                                                <input type="text" class="form-control" id="vin" name="vin" 
                                                       value="<?php echo $vehicle['vin'] ?? ''; ?>" maxlength="17">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="color" class="form-label">Color</label>
                                                <input type="text" class="form-control" id="color" name="color" 
                                                       value="<?php echo $vehicle['color'] ?? ''; ?>">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="mileage" class="form-label">Mileage</label>
                                                <input type="number" class="form-control" id="mileage" name="mileage" 
                                                       value="<?php echo $vehicle['mileage'] ?? ''; ?>">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="engine_type" class="form-label">Engine Type</label>
                                                <input type="text" class="form-control" id="engine_type" name="engine_type" 
                                                       value="<?php echo $vehicle['engine_type'] ?? ''; ?>">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="transmission" class="form-label">Transmission</label>
                                                <select class="form-select" id="transmission" name="transmission">
                                                    <option value="">Select</option>
                                                    <option value="Automatic" <?php echo ($vehicle['transmission'] ?? '') === 'Automatic' ? 'selected' : ''; ?>>Automatic</option>
                                                    <option value="Manual" <?php echo ($vehicle['transmission'] ?? '') === 'Manual' ? 'selected' : ''; ?>>Manual</option>
                                                    <option value="CVT" <?php echo ($vehicle['transmission'] ?? '') === 'CVT' ? 'selected' : ''; ?>>CVT</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="purchase_price" class="form-label">Purchase Price</label>
                                                <input type="number" step="0.01" class="form-control" id="purchase_price" 
                                                       name="purchase_price" value="<?php echo $vehicle['purchase_price'] ?? ''; ?>">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="purchase_date" class="form-label">Purchase Date</label>
                                                <input type="date" class="form-control" id="purchase_date" 
                                                       name="purchase_date" value="<?php echo $vehicle['purchase_date'] ?? ''; ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="condition_description" class="form-label">Condition Description</label>
                                            <textarea class="form-control" id="condition_description" name="condition_description" 
                                                      rows="3"><?php echo $vehicle['condition_description'] ?? ''; ?></textarea>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Notes -->
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Order Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo $order['notes'] ?? ''; ?></textarea>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" name="update_order" class="btn btn-primary btn-lg">
                                            <i class="bi bi-check-circle"></i> Update Order
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Order Summary -->
                        <div class="card-modern mb-4 animate-in">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Order Info</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Order Number:</strong><br><?php echo $order['order_number']; ?></p>
                                <p><strong>Customer:</strong><br><?php echo $order['first_name'] . ' ' . $order['last_name']; ?></p>
                                <p><strong>Email:</strong><br><?php echo $order['email']; ?></p>
                                <p><strong>Phone:</strong><br><?php echo $order['phone'] ?? 'N/A'; ?></p>
                                <p><strong>Status:</strong><br>
                                    <span class="badge bg-<?php echo getStatusBadgeClass($order['status']); ?>">
                                        <?php echo getStatusLabel($order['status']); ?>
                                    </span>
                                </p>
                                <p><strong>Created:</strong><br><?php echo formatDateTime($order['created_at']); ?></p>
                                <p class="mb-0"><strong>Last Updated:</strong><br><?php echo formatDateTime($order['updated_at']); ?></p>
                            </div>
                        </div>

                        <!-- Financial Summary -->
                        <div class="card-modern mb-4 animate-in">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-cash"></i> Financial Summary (GHS)</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td>Total Cost:</td>
                                        <td class="text-end"><strong><?php echo formatCurrency($order['total_cost'], 'GHS'); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td>Total Deposits:</td>
                                        <td class="text-end text-success"><strong><?php echo formatCurrency($order['total_deposits'] ?? 0, 'GHS'); ?></strong></td>
                                    </tr>
                                    <tr class="table-warning">
                                        <td><strong>Balance Due:</strong></td>
                                        <td class="text-end"><strong><?php echo formatCurrency($order['balance_due'], 'GHS'); ?></strong></td>
                                    </tr>
                                </table>
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-info-circle"></i> Deposits automatically update the balance due when verified.
                                </small>
                            </div>
                        </div>

                        <!-- Deposits Tracking -->
                        <div class="card-modern mb-4 animate-in">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-wallet2"></i> Deposits (<?php echo count($deposits); ?>)</h5>
                                <a href="<?php echo url('admin/deposits/add.php?order_id=' . $orderId); ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-plus-circle"></i> Add Deposit
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($deposits)): ?>
                                    <p class="text-muted text-center mb-0">No deposits recorded yet</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Date/Time</th>
                                                    <th>Amount</th>
                                                    <th>Method</th>
                                                    <th>Bank</th>
                                                    <th>Reference</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($deposits as $dep): ?>
                                                    <tr>
                                                        <td>
                                                            <small>
                                                                <?php echo date('M d, Y', strtotime($dep['transaction_date'])); ?><br>
                                                                <?php echo date('g:i A', strtotime($dep['transaction_time'])); ?>
                                                            </small>
                                                        </td>
                                                        <td><strong><?php echo formatCurrency($dep['amount'], $dep['currency']); ?></strong></td>
                                                        <td><small><?php echo ucwords(str_replace('_', ' ', $dep['payment_method'])); ?></small></td>
                                                        <td><small><?php echo $dep['bank_name'] ?? '-'; ?></small></td>
                                                        <td><small><?php echo $dep['reference_number'] ? '<code>' . substr($dep['reference_number'], 0, 15) . '</code>' : '-'; ?></small></td>
                                                        <td>
                                                            <span class="badge badge-sm <?php 
                                                                echo match($dep['status']) {
                                                                    'verified' => 'bg-success',
                                                                    'pending' => 'bg-warning',
                                                                    'rejected' => 'bg-danger',
                                                                    default => 'bg-secondary'
                                                                };
                                                            ?>">
                                                                <?php echo ucfirst($dep['status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Quick Links -->
                        <div class="card-modern animate-in">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-link-45deg"></i> Quick Links</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="<?php echo url('orders/view.php?id=' . $orderId); ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye"></i> View Full Order
                                    </a>
                                    <a href="<?php echo url('orders/documents.php?id=' . $orderId); ?>" class="btn btn-outline-warning btn-sm">
                                        <i class="bi bi-cloud-upload"></i> Manage Documents
                                    </a>
                                    <button class="btn btn-outline-info btn-sm" onclick="alert('Add updates feature coming soon')">
                                        <i class="bi bi-plus"></i> Add Update
                                    </button>
                                    <button class="btn btn-outline-success btn-sm" onclick="alert('Send notification feature coming soon')">
                                        <i class="bi bi-envelope"></i> Send Notification
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-calculate total USD from cost breakdown
        function calculateTotalUsd() {
            // Get vehicle purchase price (if exists)
            const vehiclePurchasePrice = <?php echo $vehicle && !empty($vehicle['purchase_price']) ? floatval($vehicle['purchase_price']) : 0; ?>;
            
            const carCost = parseFloat(document.getElementById('car_cost').value) || 0;
            const transportationCost = parseFloat(document.getElementById('transportation_cost').value) || 0;
            const dutyCost = parseFloat(document.getElementById('duty_cost').value) || 0;
            const clearingCost = parseFloat(document.getElementById('clearing_cost').value) || 0;
            const fixingCost = parseFloat(document.getElementById('fixing_cost').value) || 0;
            
            const totalCost = vehiclePurchasePrice + carCost + transportationCost + dutyCost + clearingCost + fixingCost;
            
            // Update display
            document.getElementById('total_usd_display').textContent = totalCost.toFixed(2);
            document.getElementById('total_usd').value = totalCost.toFixed(2);
            
            // Auto-update the total_cost field in the Order Configuration section
            const totalCostInput = document.getElementById('total_cost');
            if (totalCostInput) {
                totalCostInput.value = totalCost.toFixed(2);
            }
        }
        
        // Attach event listeners to all cost breakdown fields
        ['car_cost', 'transportation_cost', 'duty_cost', 'clearing_cost', 'fixing_cost'].forEach(function(fieldId) {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', calculateTotalUsd);
                field.addEventListener('change', calculateTotalUsd);
            }
        });
        
        // Calculate total on page load
        calculateTotalUsd();
    </script>
</body>
</html>

