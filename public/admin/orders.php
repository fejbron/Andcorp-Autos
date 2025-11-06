<?php
require_once '../bootstrap.php';
Auth::requireStaff();

$orderModel = new Order();
$vehicleModel = new Vehicle();
$db = Database::getInstance()->getConnection();

// Get filter parameters (sanitized)
$statusFilter = !empty($_GET['status']) ? Security::sanitizeStatus($_GET['status']) : null;
$searchQuery = Security::sanitizeString($_GET['search'] ?? '', 255);

// Get orders
if ($searchQuery) {
    $sql = "SELECT o.*, u.first_name, u.last_name, u.email 
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            JOIN users u ON c.user_id = u.id
            WHERE o.order_number LIKE :search 
            OR u.first_name LIKE :search 
            OR u.last_name LIKE :search
            OR u.email LIKE :search
            ORDER BY o.created_at DESC
            LIMIT 100";
    $stmt = $db->prepare($sql);
    $stmt->execute([':search' => "%{$searchQuery}%"]);
    $orders = $stmt->fetchAll();
} else {
    $orders = $orderModel->getAll($statusFilter, 100, 0);
}

// Optimize: Get all vehicles in one query instead of N+1
$orderIds = array_column($orders, 'id');
$ordersWithVehicles = [];
if (!empty($orderIds)) {
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
    <title>Manage Orders - Andcorp Autos Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid my-4">
        <div class="page-header animate-in">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-5">Manage Orders</h1>
                    <p class="lead">View and manage all customer orders</p>
                </div>
                <div>
                    <a href="<?php echo url('admin/orders/create.php'); ?>" class="btn btn-primary btn-modern">
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

        <!-- Filters -->
        <div class="card-modern mb-4 animate-in">
            <div class="card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($searchQuery); ?>" 
                               placeholder="Order #, customer name, email...">
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="purchased" <?php echo $statusFilter === 'purchased' ? 'selected' : ''; ?>>Purchased</option>
                            <option value="shipping" <?php echo $statusFilter === 'shipping' ? 'selected' : ''; ?>>Shipping</option>
                            <option value="customs" <?php echo $statusFilter === 'customs' ? 'selected' : ''; ?>>Customs</option>
                            <option value="inspection" <?php echo $statusFilter === 'inspection' ? 'selected' : ''; ?>>Inspection</option>
                            <option value="repair" <?php echo $statusFilter === 'repair' ? 'selected' : ''; ?>>Repair</option>
                            <option value="ready" <?php echo $statusFilter === 'ready' ? 'selected' : ''; ?>>Ready</option>
                            <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="<?php echo url('admin/orders.php'); ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card-modern animate-in">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-box-seam"></i> Orders (<?php echo count($ordersWithVehicles); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($ordersWithVehicles)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                        <h4 class="mt-3">No Orders Found</h4>
                        <p class="text-muted">
                            <?php echo $searchQuery ? 'Try adjusting your search criteria' : 'No orders in the system yet'; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Status</th>
                                    <th>Total Cost</th>
                                    <th>Balance</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordersWithVehicles as $order): ?>
                                    <tr>
                                        <td><strong><?php echo $order['order_number']; ?></strong></td>
                                        <td>
                                            <?php echo $order['first_name'] . ' ' . $order['last_name']; ?><br>
                                            <small class="text-muted"><?php echo $order['email']; ?></small>
                                        </td>
                                        <td>
                                            <?php if ($order['vehicle']): ?>
                                                <?php echo $order['vehicle']['year'] . ' ' . $order['vehicle']['make'] . ' ' . $order['vehicle']['model']; ?><br>
                                                <small class="text-muted"><?php echo strtoupper($order['vehicle']['auction_source']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-modern bg-<?php echo getStatusBadgeClass($order['status']); ?>">
                                                <?php echo getStatusLabel($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatCurrency($order['total_cost'], $order['currency']); ?></td>
                                        <td>
                                            <?php if ($order['balance_due'] > 0): ?>
                                                <span class="text-warning"><?php echo formatCurrency($order['balance_due'], $order['currency']); ?></span>
                                            <?php else: ?>
                                                <span class="text-success">Paid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($order['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="<?php echo url('orders/view.php?id=' . $order['id']); ?>" 
                                                   class="btn btn-outline-info" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?php echo url('admin/orders/edit.php?id=' . $order['id']); ?>" 
                                                   class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="<?php echo url('orders/documents.php?id=' . $order['id']); ?>" 
                                                   class="btn btn-outline-warning" title="Manage Documents">
                                                    <i class="bi bi-file-earmark-image"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

