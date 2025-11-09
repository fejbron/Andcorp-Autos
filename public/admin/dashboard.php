<?php
require_once '../bootstrap.php';
Auth::requireStaff();

$orderModel = new Order();
$customerModel = new Customer();

// Pagination for recent orders
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get statistics
$statusCounts = $orderModel->getStatusCounts();
$allOrders = $orderModel->getAll(null, $perPage, $offset);
$customers = $customerModel->getAll();

// Get total orders count for pagination
$totalOrdersCount = array_sum($statusCounts);
$totalPages = ceil($totalOrdersCount / $perPage);

$totalOrders = $totalOrdersCount; // Use the same value
$totalCustomers = count($customers);
$activeOrders = count(array_filter($allOrders, fn($o) => !in_array($o['status'], ['Delivered', 'Cancelled'])));

// Calculate total value from order costs (total_cost field)
$db = Database::getInstance()->getConnection();
$totalValueStmt = $db->query("
    SELECT COALESCE(SUM(total_cost), 0) as total_value 
    FROM orders
    WHERE status != 'Cancelled'
");
$totalValueResult = $totalValueStmt->fetch();
$totalValue = $totalValueResult['total_value'] ?? 0;

// Get deposit statistics
$depositModel = new Deposit();
$depositStats = $depositModel->getStats();

// Calculate pending balance from business report (balance_due from all non-cancelled orders)
$pendingBalanceStmt = $db->query("
    SELECT COALESCE(SUM(balance_due), 0) as pending_balance
    FROM orders
    WHERE status != 'Cancelled'
");
$pendingBalanceResult = $pendingBalanceStmt->fetch();
$pendingBalance = $pendingBalanceResult['pending_balance'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Andcorp Autos</title>
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
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid my-4">
        <div class="page-header animate-in">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-5">Admin Dashboard</h1>
                    <p class="lead">Manage orders, customers, and operations</p>
                </div>
                <div>
                    <a href="<?php echo url('admin/orders/create.php'); ?>" class="btn btn-primary btn-modern">
                        <i class="bi bi-plus-circle"></i> New Order
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card primary animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h3><?php echo $totalOrders; ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <h3><?php echo $activeOrders; ?></h3>
                    <p>Active Orders</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card info animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3><?php echo $totalCustomers; ?></h3>
                    <p>Total Customers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <h3><?php echo formatCurrency($totalValue); ?></h3>
                    <p>Total Value</p>
                </div>
            </div>
        </div>

        <!-- Deposit Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card success animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <h3><?php echo formatCurrency($depositStats['total_verified']); ?></h3>
                    <p>Verified Deposits</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h3><?php echo $depositStats['pending_count']; ?></h3>
                    <p>Pending Deposits</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card info animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <h3><?php echo formatCurrency($pendingBalance); ?></h3>
                    <p>Pending Balance</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card primary animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <h3><?php echo $depositStats['verified_count']; ?></h3>
                    <p>Total Transactions</p>
                </div>
            </div>
        </div>

        <!-- Status Breakdown -->
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card-modern">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Orders by Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <?php
                            $allStatuses = ['Pending', 'Purchased', 'Delivered to Port of Load', 'Origin customs clearance', 'Shipping', 'Arrived in Ghana', 'Ghana Customs Clearance', 'Inspection', 'Repair', 'Ready', 'Delivered'];
                            foreach ($allStatuses as $status):
                                $count = $statusCounts[$status] ?? 0;
                                $statusLower = strtolower($status);
                            ?>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="p-3" style="border: 1px solid #e0eef9; border-radius: 0.5rem; background: #ffffff;">
                                        <h4><?php echo $count; ?></h4>
                                        <span class="badge badge-modern bg-<?php echo getStatusBadgeClass($statusLower); ?>">
                                            <?php echo getStatusLabel($statusLower); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Recent Orders -->
            <div class="col-lg-8">
                <div class="card-modern animate-in">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Recent Orders</h5>
                        <a href="<?php echo url('admin/orders.php'); ?>" class="btn btn-sm btn-light">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern mb-0">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($allOrders)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No orders yet</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($allOrders as $order): ?>
                                            <tr>
                                                <td><strong><?php echo $order['order_number']; ?></strong></td>
                                                <td><?php echo $order['first_name'] . ' ' . $order['last_name']; ?></td>
                                                <td>
                                                    <span class="badge badge-modern bg-<?php echo getStatusBadgeClass($order['status']); ?>">
                                                        <?php echo getStatusLabel($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatCurrency($order['total_cost'], $order['currency']); ?></td>
                                                <td><?php echo formatDate($order['created_at']); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="<?php echo url('orders/view.php?id=' . $order['id']); ?>" class="btn btn-outline-info" title="View">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="<?php echo url('admin/orders/edit.php?id=' . $order['id']); ?>" class="btn btn-outline-primary" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="<?php echo url('orders/documents.php?id=' . $order['id']); ?>" class="btn btn-outline-warning" title="Documents">
                                                            <i class="bi bi-file-earmark-image"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="card-footer">
                                <nav aria-label="Orders pagination">
                                    <ul class="pagination pagination-sm justify-content-center mb-0">
                                        <!-- Previous Button -->
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo url('admin/dashboard.php?page=' . ($page - 1)); ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        
                                        <?php
                                        // Show page numbers with ellipsis for large page counts
                                        $startPage = max(1, $page - 2);
                                        $endPage = min($totalPages, $page + 2);
                                        
                                        // Show first page
                                        if ($startPage > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?php echo url('admin/dashboard.php?page=1'); ?>">1</a>
                                            </li>
                                            <?php if ($startPage > 2): ?>
                                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                            <?php endif;
                                        endif;
                                        
                                        // Show page numbers
                                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="<?php echo url('admin/dashboard.php?page=' . $i); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor;
                                        
                                        // Show last page
                                        if ($endPage < $totalPages): ?>
                                            <?php if ($endPage < $totalPages - 1): ?>
                                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                            <?php endif; ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?php echo url('admin/dashboard.php?page=' . $totalPages); ?>">
                                                    <?php echo $totalPages; ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <!-- Next Button -->
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo url('admin/dashboard.php?page=' . ($page + 1)); ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                                <div class="text-center mt-2">
                                    <small class="text-muted">
                                        Page <?php echo $page; ?> of <?php echo $totalPages; ?> 
                                        (<?php echo $totalOrdersCount; ?> total orders)
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card-modern mb-4 animate-in" style="animation-delay: 0.1s;">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <a href="<?php echo url('admin/orders/create.php'); ?>" class="btn btn-primary btn-modern">
                                <i class="bi bi-plus-circle"></i> Create New Order
                            </a>
                            <a href="<?php echo url('admin/customers.php'); ?>" class="btn btn-info btn-modern">
                                <i class="bi bi-people"></i> View Customers
                            </a>
                            <a href="<?php echo url('admin/reports.php'); ?>" class="btn btn-outline-secondary btn-modern">
                                <i class="bi bi-file-earmark-bar-graph"></i> Generate Reports
                            </a>
                            <a href="<?php echo url('admin/settings.php'); ?>" class="btn btn-outline-dark btn-modern">
                                <i class="bi bi-gear"></i> Settings
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-modern animate-in" style="animation-delay: 0.2s;">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> System Info</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                        <p class="mb-2"><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                        <p class="mb-0"><strong>User Role:</strong> <?php echo ucfirst(Auth::userRole()); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
