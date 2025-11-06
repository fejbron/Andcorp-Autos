<?php
require_once '../bootstrap.php';
Auth::requireStaff();

$depositModel = new Deposit();

// Get filter parameters
$statusFilter = !empty($_GET['status']) ? $_GET['status'] : null;
$searchQuery = Security::sanitizeString($_GET['search'] ?? '', 255);

// Get deposits
if ($searchQuery) {
    $deposits = $depositModel->search($searchQuery);
} else {
    $deposits = $depositModel->getAll($statusFilter, 100, 0);
}

// Get stats
$stats = $depositModel->getStats();

$title = "Deposit Management";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposits - Andcorp Autos</title>
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
        <div class="row">
            <div class="col-lg-11 mx-auto">
                <!-- Page Header -->
                <div class="page-header animate-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-5">Deposit Management</h1>
                            <p class="lead mb-0">Track and verify customer deposits</p>
                        </div>
                    </div>
                </div>

                <?php if ($successMsg = success()): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i> <?php echo $successMsg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card primary animate-in">
                            <div class="stat-icon">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                            <h3><?php echo formatCurrency($stats['total_verified']); ?></h3>
                            <p>Total Verified</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card warning animate-in">
                            <div class="stat-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <h3><?php echo $stats['pending_count']; ?></h3>
                            <p>Pending (<?php echo formatCurrency($stats['total_pending']); ?>)</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card success animate-in">
                            <div class="stat-icon">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <h3><?php echo $stats['verified_count']; ?></h3>
                            <p>Verified Deposits</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card danger animate-in">
                            <div class="stat-icon">
                                <i class="bi bi-x-circle"></i>
                            </div>
                            <h3><?php echo $stats['rejected_count']; ?></h3>
                            <p>Rejected</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card-modern mb-4">
                    <div class="card-body">
                        <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="verified" <?php echo $statusFilter === 'verified' ? 'selected' : ''; ?>>Verified</option>
                                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" name="search" id="search" class="form-control" 
                                       placeholder="Order #, Reference, Bank, Customer..." 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-search"></i> Filter
                                </button>
                                <a href="<?php echo url('admin/deposits.php'); ?>" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Deposits Table -->
                <div class="card-modern">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Deposit Records (<?php echo count($deposits); ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($deposits)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: var(--text-muted);"></i>
                                <p class="text-muted mt-3">No deposits found</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-modern mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Order #</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Bank</th>
                                            <th>Reference</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($deposits as $deposit): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo date('M d, Y', strtotime($deposit['transaction_date'])); ?></strong><br>
                                                    <small class="text-muted"><?php echo date('g:i A', strtotime($deposit['transaction_time'])); ?></small>
                                                </td>
                                                <td>
                                                    <a href="<?php echo url('orders/view.php?id=' . $deposit['order_id']); ?>">
                                                        <strong><?php echo $deposit['order_number']; ?></strong>
                                                    </a>
                                                </td>
                                                <td>
                                                    <?php echo $deposit['customer_first_name'] . ' ' . $deposit['customer_last_name']; ?><br>
                                                    <small class="text-muted"><?php echo $deposit['customer_email']; ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo formatCurrency($deposit['amount'], $deposit['currency']); ?></strong><br>
                                                    <small class="text-muted"><?php echo ucwords(str_replace('_', ' ', $deposit['payment_method'])); ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($deposit['bank_name']): ?>
                                                        <strong><?php echo $deposit['bank_name']; ?></strong><br>
                                                        <?php if ($deposit['account_number']): ?>
                                                            <small class="text-muted"><?php echo $deposit['account_number']; ?></small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($deposit['reference_number']): ?>
                                                        <code><?php echo $deposit['reference_number']; ?></code>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $badgeClass = match($deposit['status']) {
                                                        'verified' => 'bg-success',
                                                        'pending' => 'bg-warning',
                                                        'rejected' => 'bg-danger',
                                                        default => 'bg-secondary'
                                                    };
                                                    ?>
                                                    <span class="badge <?php echo $badgeClass; ?>">
                                                        <?php echo ucfirst($deposit['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="<?php echo url('admin/deposits/view.php?id=' . $deposit['id']); ?>" 
                                                           class="btn btn-outline-info" title="View Details">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <?php if ($deposit['status'] === 'pending'): ?>
                                                            <a href="<?php echo url('admin/deposits/verify.php?id=' . $deposit['id']); ?>" 
                                                               class="btn btn-outline-success" title="Verify">
                                                                <i class="bi bi-check-circle"></i>
                                                            </a>
                                                        <?php endif; ?>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

