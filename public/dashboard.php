<?php
require_once 'bootstrap.php';
Auth::requireAuth();

// Redirect admin/staff to admin dashboard
if (Auth::isStaff()) {
    redirect(url('admin/dashboard.php'));
}

$customerModel = new Customer();
$orderModel = new Order();
$notificationModel = new Notification();
$depositModel = new Deposit();

$customer = $customerModel->findByUserId(Auth::userId());
$orders = $orderModel->getByCustomer($customer['id']);
$notifications = $notificationModel->getUserNotifications(Auth::userId(), true);
$deposits = $depositModel->getByCustomer($customer['id']);

// Calculate deposit totals
$totalDeposits = 0;
$verifiedDeposits = 0;
$pendingDeposits = 0;
foreach ($deposits as $deposit) {
    if ($deposit['status'] === 'verified') {
        $verifiedDeposits += $deposit['amount'];
    } elseif ($deposit['status'] === 'pending') {
        $pendingDeposits += $deposit['amount'];
    }
    $totalDeposits += $deposit['amount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Andcorp Autos</title>
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
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-4">
        <?php if ($successMsg = success()): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?php echo $successMsg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="page-header animate-in">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-5">Welcome back, <?php echo htmlspecialchars($customer['first_name']); ?>!</h1>
                    <p class="lead">Track your car imports and stay updated</p>
                </div>
                <div>
                    <a href="<?php echo url('quotes/request.php'); ?>" class="btn btn-primary btn-modern">
                        <i class="bi bi-file-earmark-text"></i> Request Quote
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card primary animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-cart"></i>
                    </div>
                    <h3><?php echo count($orders); ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <h3><?php echo count(array_filter($orders, fn($o) => !in_array($o['status'], ['delivered', 'cancelled']))); ?></h3>
                    <p>In Progress</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h3><?php echo count(array_filter($orders, fn($o) => $o['status'] === 'delivered')); ?></h3>
                    <p>Delivered</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card info animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <h3><?php echo formatCurrency($verifiedDeposits); ?></h3>
                    <p>Total Paid</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-modern animate-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-3">No orders yet</p>
                                <a href="<?php echo url('orders/create.php'); ?>" class="btn btn-primary btn-modern">Place Your First Order</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Status</th>
                                            <th>Total</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($orders, 0, 5) as $order): ?>
                                            <tr>
                                                <td><strong><?php echo $order['order_number']; ?></strong></td>
                                                <td>
                                                    <span class="badge badge-modern bg-<?php echo getStatusBadgeClass($order['status']); ?>">
                                                        <?php echo getStatusLabel($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatCurrency($order['total_cost'], $order['currency']); ?></td>
                                                <td><?php echo formatDate($order['created_at']); ?></td>
                                                <td>
                                                    <a href="<?php echo url('orders/view.php?id=' . $order['id']); ?>" class="btn btn-sm btn-outline-primary">
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($orders) > 5): ?>
                                <div class="text-center">
                                    <a href="<?php echo url('orders.php'); ?>" class="btn btn-outline-primary btn-modern">View All Orders</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-modern mb-4 animate-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-bell"></i> Recent Notifications</h5>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <?php if (empty($notifications)): ?>
                            <p class="text-muted text-center">No new notifications</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($notifications, 0, 5) as $notification): ?>
                                    <div class="list-group-item" style="border: 1px solid var(--border-color);">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['subject']); ?></h6>
                                            <small><?php echo formatDate($notification['created_at']); ?></small>
                                        </div>
                                        <p class="mb-1 small"><?php echo nl2br(htmlspecialchars(substr($notification['message'], 0, 100))); ?>...</p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="<?php echo url('notifications.php'); ?>" class="btn btn-sm btn-outline-info">View All</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Deposits -->
                <div class="card-modern mb-4 animate-in">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-wallet2"></i> Recent Deposits</h5>
                        <a href="<?php echo url('profile.php'); ?>" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($deposits)): ?>
                            <div class="text-center py-3">
                                <i class="bi bi-wallet" style="font-size: 2.5rem; color: var(--text-muted);"></i>
                                <p class="text-muted mt-2 mb-0 small">No deposits recorded yet</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        <?php 
                                        $recentDeposits = array_slice($deposits, 0, 5);
                                        foreach ($recentDeposits as $deposit): 
                                        ?>
                                            <tr>
                                                <td style="width: 40%;">
                                                    <small class="text-muted">
                                                        <?php echo date('M d, Y', strtotime($deposit['transaction_date'])); ?>
                                                    </small>
                                                </td>
                                                <td style="width: 35%;">
                                                    <strong class="small"><?php echo formatCurrency($deposit['amount'], $deposit['currency']); ?></strong>
                                                </td>
                                                <td style="width: 25%;" class="text-end">
                                                    <span class="badge badge-sm <?php 
                                                        echo match($deposit['status']) {
                                                            'verified' => 'bg-success',
                                                            'pending' => 'bg-warning',
                                                            'rejected' => 'bg-danger',
                                                            default => 'bg-secondary'
                                                        };
                                                    ?>">
                                                        <?php echo ucfirst($deposit['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light">
                                            <td colspan="2" class="small"><strong>Total Verified:</strong></td>
                                            <td class="text-end"><strong class="small"><?php echo formatCurrency($verifiedDeposits); ?></strong></td>
                                        </tr>
                                        <?php if ($pendingDeposits > 0): ?>
                                        <tr class="table-warning">
                                            <td colspan="2" class="small"><strong>Pending:</strong></td>
                                            <td class="text-end"><strong class="small"><?php echo formatCurrency($pendingDeposits); ?></strong></td>
                                        </tr>
                                        <?php endif; ?>
                                    </tfoot>
                                </table>
                            </div>
                            <?php if (count($deposits) > 5): ?>
                                <div class="text-center mt-3">
                                    <a href="<?php echo url('profile.php'); ?>" class="btn btn-sm btn-outline-info">View All Deposits</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-modern animate-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> How It Works</h5>
                    </div>
                    <div class="card-body">
                        <ol class="small">
                            <li class="mb-2">Select your vehicle from Copart/IAA</li>
                            <li class="mb-2">We purchase on your behalf</li>
                            <li class="mb-2">Track shipping to Ghana</li>
                            <li class="mb-2">Pay customs & clearing fees</li>
                            <li class="mb-2">Receive inspection report</li>
                            <li class="mb-2">Vehicle repair & preparation</li>
                            <li class="mb-0">Delivery to your location</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
