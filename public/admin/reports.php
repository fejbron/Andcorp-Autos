<?php
require_once '../bootstrap.php';
Auth::requireStaff();

$orderModel = new Order();
$customerModel = new Customer();
$db = Database::getInstance()->getConnection();

// Date range filter (sanitized)
$startDate = !empty($_GET['start_date']) ? Security::sanitizeString($_GET['start_date'], 10) : date('Y-m-01');
$endDate = !empty($_GET['end_date']) ? Security::sanitizeString($_GET['end_date'], 10) : date('Y-m-d');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
    $startDate = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    $endDate = date('Y-m-d');
}

// Pagination for orders table
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total count of orders in date range
$countSql = "SELECT COUNT(*) as total
             FROM orders o
             WHERE DATE(o.created_at) BETWEEN :start_date AND :end_date";
$countStmt = $db->prepare($countSql);
$countStmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
$totalOrdersInRange = $countStmt->fetch()['total'];
$totalPages = ceil($totalOrdersInRange / $perPage);

// Get paginated orders in date range
$sql = "SELECT o.*, u.first_name, u.last_name, u.email 
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE DATE(o.created_at) BETWEEN :start_date AND :end_date
        ORDER BY o.created_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
$stmt->bindValue(':start_date', $startDate, PDO::PARAM_STR);
$stmt->bindValue(':end_date', $endDate, PDO::PARAM_STR);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$paginatedOrders = $stmt->fetchAll();

// Get all orders for statistics (without pagination)
$allOrdersSql = "SELECT o.*, u.first_name, u.last_name, u.email 
                 FROM orders o
                 JOIN customers c ON o.customer_id = c.id
                 JOIN users u ON c.user_id = u.id
                 WHERE DATE(o.created_at) BETWEEN :start_date AND :end_date
                 ORDER BY o.created_at DESC";
$allOrdersStmt = $db->prepare($allOrdersSql);
$allOrdersStmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
$orders = $allOrdersStmt->fetchAll();

// Calculate statistics
$totalOrders = count($orders);
// Calculate revenue from verified deposits, not initial deposit_amount
$revenueSql = "
    SELECT COALESCE(SUM(d.amount), 0) as total_revenue 
    FROM deposits d
    INNER JOIN orders o ON d.order_id = o.id
    WHERE d.status = 'verified' 
    AND o.status != 'Cancelled'
    AND DATE(o.created_at) BETWEEN ? AND ?
";
$revenueStmt = $db->prepare($revenueSql);
$revenueStmt->execute([$startDate, $endDate]);
$revenueResult = $revenueStmt->fetch();
$totalRevenue = $revenueResult['total_revenue'] ?? 0;
$totalCost = array_sum(array_column($orders, 'total_cost'));
$totalBalance = array_sum(array_column($orders, 'balance_due'));

// Orders by status
$statusCounts = [];
foreach ($orders as $order) {
    $status = $order['status'];
    if (!isset($statusCounts[$status])) {
        $statusCounts[$status] = 0;
    }
    $statusCounts[$status]++;
}

// Top customers
$customerOrders = [];
foreach ($orders as $order) {
    $customerId = $order['customer_id'];
    if (!isset($customerOrders[$customerId])) {
        $customerOrders[$customerId] = [
            'name' => $order['first_name'] . ' ' . $order['last_name'],
            'email' => $order['email'],
            'count' => 0,
            'total' => 0
        ];
    }
    $customerOrders[$customerId]['count']++;
    // Use total_deposits from order (sum of verified deposits) instead of initial deposit_amount
    $customerOrders[$customerId]['total'] += $order['total_deposits'] ?? 0;
}
usort($customerOrders, fn($a, $b) => $b['total'] <=> $a['total']);
$topCustomers = array_slice($customerOrders, 0, 5);

// Orders by month (last 6 months)
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthlyData[$month] = ['count' => 0, 'revenue' => 0];
}

$monthlySql = "SELECT 
                   DATE_FORMAT(o.created_at, '%Y-%m') as month, 
                   COUNT(DISTINCT o.id) as count, 
                   COALESCE(SUM(d.amount), 0) as revenue
               FROM orders o
               LEFT JOIN deposits d ON o.id = d.order_id AND d.status = 'verified'
               WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                 AND o.status != 'Cancelled'
               GROUP BY month
               ORDER BY month";
$monthlyStmt = $db->query($monthlySql);
$monthlyResults = $monthlyStmt->fetchAll();

foreach ($monthlyResults as $result) {
    $monthlyData[$result['month']] = [
        'count' => $result['count'],
        'revenue' => $result['revenue']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Andcorp Autos Admin</title>
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
            <h1 class="display-5">Business Reports</h1>
            <p class="lead">Analytics and insights</p>
        </div>

        <!-- Date Range Filter -->
        <div class="card-modern mb-4 animate-in">
            <div class="card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter"></i> Apply Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
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
                <div class="stat-card success animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <h3><?php echo formatCurrency($totalRevenue); ?></h3>
                    <p>Revenue (Deposits)</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card info animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <h3><?php echo formatCurrency($totalCost); ?></h3>
                    <p>Total Value</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <h3><?php echo formatCurrency($totalBalance); ?></h3>
                    <p>Pending Balance</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Orders by Status -->
            <div class="col-lg-6 mb-4">
                <div class="card-modern animate-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Orders by Status</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($statusCounts)): ?>
                            <p class="text-muted text-center">No data available</p>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th class="text-end">Count</th>
                                        <th class="text-end">Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($statusCounts as $status => $count): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusBadgeClass($status); ?>">
                                                    <?php echo getStatusLabel($status); ?>
                                                </span>
                                            </td>
                                            <td class="text-end"><?php echo $count; ?></td>
                                            <td class="text-end"><?php echo round(($count / $totalOrders) * 100, 1); ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top Customers -->
            <div class="col-lg-6 mb-4">
                <div class="card-modern animate-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-trophy"></i> Top Customers</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($topCustomers)): ?>
                            <p class="text-muted text-center">No data available</p>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th class="text-end">Orders</th>
                                        <th class="text-end">Total Spent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topCustomers as $customer): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $customer['name']; ?></strong><br>
                                                <small class="text-muted"><?php echo $customer['email']; ?></small>
                                            </td>
                                            <td class="text-end"><?php echo $customer['count']; ?></td>
                                            <td class="text-end"><strong><?php echo formatCurrency($customer['total']); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Trend -->
        <div class="card-modern mb-4 animate-in">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> 6-Month Trend</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="text-end">Orders</th>
                                <th class="text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthlyData as $month => $data): ?>
                                <tr>
                                    <td><strong><?php echo date('F Y', strtotime($month . '-01')); ?></strong></td>
                                    <td class="text-end"><?php echo $data['count']; ?></td>
                                    <td class="text-end"><?php echo formatCurrency($data['revenue']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="card-modern animate-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Orders in Selected Period</h5>
                <?php if (!empty($paginatedOrders)): ?>
                    <span class="badge bg-primary">
                        <?php echo $totalOrdersInRange; ?> total orders
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($paginatedOrders)): ?>
                    <p class="text-muted text-center">No orders in this date range</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Total Paid</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paginatedOrders as $order): ?>
                                    <tr>
                                        <td><strong><?php echo $order['order_number']; ?></strong></td>
                                        <td>
                                            <?php echo $order['first_name'] . ' ' . $order['last_name']; ?><br>
                                            <small class="text-muted"><?php echo $order['email']; ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusBadgeClass($order['status']); ?>">
                                                <?php echo getStatusLabel($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatCurrency($order['total_cost'], $order['currency']); ?></td>
                                        <td><?php echo formatCurrency($order['total_deposits'] ?? 0, $order['currency']); ?></td>
                                        <td><?php echo formatDate($order['created_at']); ?></td>
                                        <td>
                                            <a href="<?php echo url('admin/orders/edit.php?id=' . $order['id']); ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Orders pagination" class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="text-muted">
                                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $perPage, $totalOrdersInRange); ?> of <?php echo $totalOrdersInRange; ?> orders
                                </div>
                                <ul class="pagination mb-0">
                                    <!-- Previous Button -->
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" 
                                           aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>

                                    <!-- Page Numbers -->
                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    // Show first page if not in range
                                    if ($startPage > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>">1</a>
                                        </li>
                                        <?php if ($startPage > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <!-- Show last page if not in range -->
                                    <?php if ($endPage < $totalPages): ?>
                                        <?php if ($endPage < $totalPages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $totalPages; ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>">
                                                <?php echo $totalPages; ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Next Button -->
                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" 
                                           aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

