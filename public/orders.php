<?php
require_once 'bootstrap.php';
Auth::requireAuth();

// Redirect admin/staff to admin orders page
if (Auth::isStaff()) {
    redirect(url('admin/orders.php'));
}

$customerModel = new Customer();
$orderModel = new Order();

$customer = $customerModel->findByUserId(Auth::userId());
$orders = $orderModel->getByCustomer($customer['id']);

// Optimize: Get all vehicles in one query instead of N+1
$vehicleModel = new Vehicle();
$ordersWithVehicles = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $db = Database::getInstance()->getConnection();
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $vehicleSql = "SELECT * FROM vehicles WHERE order_id IN ($placeholders)";
    $vehicleStmt = $db->prepare($vehicleSql);
    $vehicleStmt->execute($orderIds);
    $vehicles = $vehicleStmt->fetchAll();
    
    // Create lookup array
    $vehicleLookup = [];
    foreach ($vehicles as $vehicle) {
        $vehicleLookup[$vehicle['order_id']] = $vehicle;
    }
    
    // Merge vehicles with orders
    foreach ($orders as $order) {
        $order['vehicle'] = $vehicleLookup[$order['id']] ?? null;
        $ordersWithVehicles[] = $order;
    }
} else {
    $ordersWithVehicles = $orders;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Andcorp Autos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-4">
        <div class="page-header animate-in">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-5">My Orders</h1>
                    <p class="lead">Track all your vehicle imports</p>
                </div>
                <div>
                    <a href="<?php echo url('orders/create.php'); ?>" class="btn btn-primary btn-modern">
                        <i class="bi bi-plus-circle"></i> New Order
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

        <?php if (empty($ordersWithVehicles)): ?>
            <div class="card-modern animate-in">
                <div class="card-body text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                    <h3 class="mt-3">No Orders Yet</h3>
                    <p class="text-muted">Start your first car import from Copart or IAA</p>
                    <a href="<?php echo url('orders/create.php'); ?>" class="btn btn-primary btn-modern mt-3">
                        <i class="bi bi-plus-circle"></i> Create Your First Order
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Filter Tabs -->
            <ul class="nav nav-tabs mb-3" id="orderTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button">
                        All Orders (<?php echo count($ordersWithVehicles); ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button">
                        Active (<?php echo count(array_filter($ordersWithVehicles, fn($o) => !in_array($o['status'], ['delivered', 'cancelled']))); ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="delivered-tab" data-bs-toggle="tab" data-bs-target="#delivered" type="button">
                        Delivered (<?php echo count(array_filter($ordersWithVehicles, fn($o) => $o['status'] === 'delivered')); ?>)
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="orderTabsContent">
                <!-- All Orders -->
                <div class="tab-pane fade show active" id="all" role="tabpanel">
                    <?php foreach ($ordersWithVehicles as $order): ?>
                        <?php include 'includes/order-card.php'; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Active Orders -->
                <div class="tab-pane fade" id="active" role="tabpanel">
                    <?php
                    $activeOrders = array_filter($ordersWithVehicles, fn($o) => !in_array($o['status'], ['delivered', 'cancelled']));
                    if (empty($activeOrders)):
                    ?>
                        <div class="alert alert-info">No active orders</div>
                    <?php else: ?>
                        <?php foreach ($activeOrders as $order): ?>
                            <?php include 'includes/order-card.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Delivered Orders -->
                <div class="tab-pane fade" id="delivered" role="tabpanel">
                    <?php
                    $deliveredOrders = array_filter($ordersWithVehicles, fn($o) => $o['status'] === 'delivered');
                    if (empty($deliveredOrders)):
                    ?>
                        <div class="alert alert-info">No delivered orders yet</div>
                    <?php else: ?>
                        <?php foreach ($deliveredOrders as $order): ?>
                            <?php include 'includes/order-card.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
