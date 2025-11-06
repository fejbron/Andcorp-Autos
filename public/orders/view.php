<?php
require_once '../bootstrap.php';
Auth::requireAuth();

$orderModel = new Order();
$vehicleModel = new Vehicle();

$orderId = Security::sanitizeInt($_GET['id'] ?? 0);
if (!$orderId || $orderId <= 0) {
    redirect(url('orders.php'));
}

$order = $orderModel->findById($orderId);
if (!$order) {
    redirect(url('orders.php'));
}

// Check permission - customers can only view their own orders
if (Auth::isCustomer()) {
    $customerModel = new Customer();
    $customer = $customerModel->findByUserId(Auth::userId());
    if ($order['customer_id'] != $customer['id']) {
        redirect(url('dashboard.php'));
    }
}

$vehicle = $vehicleModel->findByOrderId($orderId);

// Get all related data
$db = Database::getInstance()->getConnection();

// Purchase updates
$purchaseStmt = $db->prepare("SELECT * FROM purchase_updates WHERE order_id = :order_id ORDER BY created_at DESC");
$purchaseStmt->execute([':order_id' => $orderId]);
$purchaseUpdates = $purchaseStmt->fetchAll();

// Shipping updates
$shippingStmt = $db->prepare("SELECT * FROM shipping_updates WHERE order_id = :order_id ORDER BY created_at DESC LIMIT 1");
$shippingStmt->execute([':order_id' => $orderId]);
$shipping = $shippingStmt->fetch();

// Customs
$customsStmt = $db->prepare("SELECT * FROM customs_clearing WHERE order_id = :order_id");
$customsStmt->execute([':order_id' => $orderId]);
$customs = $customsStmt->fetch();

// Inspection reports
$inspectionModel = new InspectionReport();
$inspections = $inspectionModel->findByOrderId($orderId);

// Repair updates
$repairStmt = $db->prepare("SELECT * FROM repair_updates WHERE order_id = :order_id ORDER BY created_at DESC");
$repairStmt->execute([':order_id' => $orderId]);
$repairUpdates = $repairStmt->fetchAll();

// Delivery info
$deliveryStmt = $db->prepare("SELECT * FROM deliveries WHERE order_id = :order_id");
$deliveryStmt->execute([':order_id' => $orderId]);
$delivery = $deliveryStmt->fetch();

// Payments
$paymentsStmt = $db->prepare("SELECT * FROM payments WHERE order_id = :order_id ORDER BY payment_date DESC");
$paymentsStmt->execute([':order_id' => $orderId]);
$payments = $paymentsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order <?php echo $order['order_number']; ?> - Andcorp Autos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
    <style>
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        .timeline-item {
            position: relative;
            padding-left: 40px;
            margin-bottom: 30px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 0.25rem;
            background: var(--text-muted);
        }
        .timeline-item.active::before {
            background: var(--success);
        }
        .timeline-item.current::before {
            background: var(--primary);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(74, 144, 226, 0.7); }
            50% { box-shadow: 0 0 0 10px rgba(74, 144, 226, 0); }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container my-4">
        <div class="page-header animate-in">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-5">Order Details</h1>
                    <p class="lead mb-0">Order #<?php echo $order['order_number']; ?></p>
                </div>
                <div>
                    <a href="<?php echo url('orders.php'); ?>" class="btn btn-outline-secondary btn-modern">
                        <i class="bi bi-arrow-left"></i> Back to Orders
                    </a>
                </div>
            </div>
        </div>

        <!-- Order Status Timeline -->
        <div class="card-modern mb-4 animate-in">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Order Progress</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php
                    $statuses = ['Pending', 'Purchased', 'Shipping', 'Customs', 'Inspection', 'Repair', 'Ready', 'Delivered'];
                    $statusLabels = [
                        'Pending' => 'Order Placed',
                        'Purchased' => 'Vehicle Purchased',
                        'Shipping' => 'Shipping to Ghana',
                        'Customs' => 'Customs Clearance',
                        'Inspection' => 'Vehicle Inspection',
                        'Repair' => 'Repair & Preparation',
                        'Ready' => 'Ready for Delivery',
                        'Delivered' => 'Delivered'
                    ];
                    
                    $currentIndex = array_search($order['status'], $statuses);
                    
                    foreach ($statuses as $index => $status):
                        $isActive = $index < $currentIndex;
                        $isCurrent = $index === $currentIndex;
                        $class = $isActive ? 'active' : ($isCurrent ? 'current' : '');
                    ?>
                        <div class="timeline-item <?php echo $class; ?>">
                            <h6><?php echo $statusLabels[$status]; ?></h6>
                            <?php if ($isActive || $isCurrent): ?>
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-check-circle text-success"></i>
                                    <?php echo $isCurrent ? 'In Progress' : 'Completed'; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Vehicle Information -->
                <?php if ($vehicle): ?>
                    <div class="card-modern mb-4 animate-in">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-car-front"></i> Vehicle Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Make & Model:</strong> <?php echo $vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model']; ?></p>
                                    <p><strong>Color:</strong> <?php echo $vehicle['color'] ?? 'N/A'; ?></p>
                                    <p><strong>VIN:</strong> <?php echo $vehicle['vin'] ?? 'N/A'; ?></p>
                                    <p><strong>Mileage:</strong> <?php echo $vehicle['mileage'] ? number_format($vehicle['mileage']) . ' miles' : 'N/A'; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Auction Source:</strong> <?php echo strtoupper($vehicle['auction_source']); ?></p>
                                    <p><strong>Lot Number:</strong> <?php echo $vehicle['lot_number'] ?? 'N/A'; ?></p>
                                    <p><strong>Purchase Price:</strong> <?php echo $vehicle['purchase_price'] ? formatCurrency($vehicle['purchase_price']) : 'N/A'; ?></p>
                                    <p><strong>Purchase Date:</strong> <?php echo $vehicle['purchase_date'] ? formatDate($vehicle['purchase_date']) : 'N/A'; ?></p>
                                </div>
                            </div>
                            <?php if ($vehicle['listing_url']): ?>
                                <a href="<?php echo $vehicle['listing_url']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-link-45deg"></i> View Original Listing
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Purchase Updates -->
                <?php if (!empty($purchaseUpdates)): ?>
                    <div class="card-modern mb-4 animate-in">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-cart-check"></i> Purchase Updates</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($purchaseUpdates as $update): ?>
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between">
                                        <h6><?php echo htmlspecialchars($update['title']); ?></h6>
                                        <span class="text-muted small"><?php echo formatDateTime($update['created_at']); ?></span>
                                    </div>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($update['description'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Shipping Information -->
                <?php if ($shipping): ?>
                    <div class="card-modern mb-4 animate-in">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-truck"></i> Shipping Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Company:</strong> <?php echo $shipping['shipping_company'] ?? 'N/A'; ?></p>
                                    <p><strong>Tracking #:</strong> <?php echo $shipping['tracking_number'] ?? 'N/A'; ?></p>
                                    <p><strong>Container #:</strong> <?php echo $shipping['container_number'] ?? 'N/A'; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>From:</strong> <?php echo $shipping['departure_port'] ?? 'N/A'; ?></p>
                                    <p><strong>To:</strong> <?php echo $shipping['arrival_port']; ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="badge bg-<?php echo getStatusBadgeClass($shipping['status']); ?>">
                                            <?php echo getStatusLabel($shipping['status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <?php if ($shipping['expected_arrival_date']): ?>
                                <p><strong>Expected Arrival:</strong> <?php echo formatDate($shipping['expected_arrival_date']); ?></p>
                            <?php endif; ?>
                            <?php if ($shipping['actual_arrival_date']): ?>
                                <p><strong>Actual Arrival:</strong> <?php echo formatDate($shipping['actual_arrival_date']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Customs & Clearing -->
                <?php if ($customs): ?>
                    <div class="card-modern mb-4 animate-in">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Customs & Clearing</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <td>Duty Amount:</td>
                                    <td class="text-end"><strong><?php echo formatCurrency($customs['duty_amount'], $customs['currency']); ?></strong></td>
                                </tr>
                                <tr>
                                    <td>VAT:</td>
                                    <td class="text-end"><strong><?php echo formatCurrency($customs['vat_amount'], $customs['currency']); ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Processing Fee:</td>
                                    <td class="text-end"><strong><?php echo formatCurrency($customs['processing_fee'], $customs['currency']); ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Other Fees:</td>
                                    <td class="text-end"><strong><?php echo formatCurrency($customs['other_fees'], $customs['currency']); ?></strong></td>
                                </tr>
                                <tr class="table-primary">
                                    <td><strong>Total Clearing Cost:</strong></td>
                                    <td class="text-end"><strong><?php echo formatCurrency($customs['total_clearing_cost'], $customs['currency']); ?></strong></td>
                                </tr>
                            </table>
                            <p class="mb-0">
                                <strong>Payment Status:</strong> 
                                <span class="badge bg-<?php echo $customs['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                    <?php echo getStatusLabel($customs['payment_status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Inspection Reports -->
                <?php if (!empty($inspections)): ?>
                    <div class="card-modern mb-4 animate-in">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Inspection Reports</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($inspections as $inspection): ?>
                                <div class="mb-4 pb-4 border-bottom">
                                    <div class="d-flex justify-content-between mb-3">
                                        <div>
                                            <h6>Inspection Date: <?php echo formatDate($inspection['inspection_date']); ?></h6>
                                            <p class="mb-0 text-muted small">Inspector: <?php echo $inspection['inspector_name'] ?? 'N/A'; ?></p>
                                        </div>
                                        <div>
                                            <span class="badge bg-<?php 
                                                echo $inspection['overall_condition'] === 'excellent' ? 'success' : 
                                                    ($inspection['overall_condition'] === 'good' ? 'info' : 
                                                    ($inspection['overall_condition'] === 'fair' ? 'warning' : 'danger')); 
                                            ?>">
                                                <?php echo ucfirst($inspection['overall_condition']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Exterior:</strong><br><?php echo nl2br(htmlspecialchars($inspection['exterior_condition'] ?? 'N/A')); ?></p>
                                            <p><strong>Interior:</strong><br><?php echo nl2br(htmlspecialchars($inspection['interior_condition'] ?? 'N/A')); ?></p>
                                            <p><strong>Engine:</strong><br><?php echo nl2br(htmlspecialchars($inspection['engine_condition'] ?? 'N/A')); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Mechanical Issues:</strong><br><?php echo nl2br(htmlspecialchars($inspection['mechanical_issues'] ?? 'None reported')); ?></p>
                                            <p><strong>Cosmetic Issues:</strong><br><?php echo nl2br(htmlspecialchars($inspection['cosmetic_issues'] ?? 'None reported')); ?></p>
                                            <p><strong>Recommendations:</strong><br><?php echo nl2br(htmlspecialchars($inspection['recommendations'] ?? 'N/A')); ?></p>
                                        </div>
                                    </div>
                                    
                                    <?php if ($inspection['estimated_repair_cost']): ?>
                                        <p><strong>Estimated Repair Cost:</strong> <?php echo formatCurrency($inspection['estimated_repair_cost']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $photos = $inspectionModel->getPhotos($inspection['id']);
                                    if (!empty($photos)):
                                    ?>
                                        <h6 class="mt-3">Photos:</h6>
                                        <div class="row">
                                            <?php foreach ($photos as $photo): ?>
                                                <div class="col-md-3 mb-3">
                                                    <img src="<?php echo url('storage/' . $photo['photo_path']); ?>" class="img-fluid rounded" alt="Inspection photo">
                                                    <?php if ($photo['caption']): ?>
                                                        <p class="small text-muted mt-1"><?php echo htmlspecialchars($photo['caption']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Repair Updates -->
                <?php if (!empty($repairUpdates)): ?>
                    <div class="card-modern mb-4 animate-in">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-tools"></i> Repair Updates</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($repairUpdates as $repair): ?>
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between mb-2">
                                        <h6><?php echo getStatusLabel($repair['repair_category']); ?></h6>
                                        <span class="badge bg-<?php echo getStatusBadgeClass($repair['status']); ?>">
                                            <?php echo getStatusLabel($repair['status']); ?>
                                        </span>
                                    </div>
                                    <p><?php echo nl2br(htmlspecialchars($repair['description'])); ?></p>
                                    <?php if ($repair['cost']): ?>
                                        <p class="mb-0"><strong>Cost:</strong> <?php echo formatCurrency($repair['cost']); ?></p>
                                    <?php endif; ?>
                                    <p class="text-muted small mb-0"><?php echo formatDateTime($repair['created_at']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Order Summary -->
                <div class="card-modern mb-4 animate-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-receipt"></i> Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?php echo getStatusBadgeClass($order['status']); ?>">
                                <?php echo getStatusLabel($order['status']); ?>
                            </span>
                        </p>
                        <p><strong>Customer:</strong> <?php echo $order['first_name'] . ' ' . $order['last_name']; ?></p>
                        <p><strong>Email:</strong> <?php echo $order['email']; ?></p>
                        <p><strong>Phone:</strong> <?php echo $order['phone'] ?? 'N/A'; ?></p>
                        <hr>
                        <p><strong>Order Date:</strong> <?php echo formatDate($order['created_at']); ?></p>
                        <p><strong>Last Updated:</strong> <?php echo formatDateTime($order['updated_at']); ?></p>
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="card-modern mb-4 animate-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-cash"></i> Financial Summary</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td>Total Cost:</td>
                                <td class="text-end"><strong><?php echo formatCurrency($order['total_cost'], $order['currency']); ?></strong></td>
                            </tr>
                            <tr>
                                <td>Deposit Paid:</td>
                                <td class="text-end text-success"><strong><?php echo formatCurrency($order['deposit_amount'], $order['currency']); ?></strong></td>
                            </tr>
                            <tr class="table-warning">
                                <td><strong>Balance Due:</strong></td>
                                <td class="text-end"><strong><?php echo formatCurrency($order['balance_due'], $order['currency']); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Payment History -->
                <?php if (!empty($payments)): ?>
                    <div class="card-modern mb-4 animate-in">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-credit-card"></i> Payments</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($payments as $payment): ?>
                                <div class="mb-2 pb-2 border-bottom">
                                    <div class="d-flex justify-content-between">
                                        <span><?php echo getStatusLabel($payment['payment_type']); ?></span>
                                        <strong><?php echo formatCurrency($payment['amount'], $payment['currency']); ?></strong>
                                    </div>
                                    <small class="text-muted"><?php echo formatDate($payment['payment_date']); ?> - <?php echo ucfirst($payment['payment_method']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Contact Support -->
                <div class="card-modern animate-in">
                    <div class="card-body text-center">
                        <i class="bi bi-headset" style="font-size: 2rem; color: var(--primary);"></i>
                        <h6 class="mt-2">Need Help?</h6>
                        <p class="small text-muted">Contact our support team</p>
                        <a href="mailto:info@andcorpautos.com" class="btn btn-sm btn-primary btn-modern">
                            <i class="bi bi-envelope"></i> Email Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
