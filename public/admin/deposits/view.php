<?php
require_once '../../bootstrap.php';
Auth::requireStaff();

// Get deposit ID
$depositId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$depositId) {
    setErrors(['general' => 'Invalid deposit ID.']);
    redirect(url('admin/deposits.php'));
    exit;
}

$depositModel = new Deposit();
$deposit = $depositModel->findById($depositId);

if (!$deposit) {
    setErrors(['general' => 'Deposit not found.']);
    redirect(url('admin/deposits.php'));
    exit;
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        setErrors(['general' => 'Invalid security token. Please try again.']);
        redirect(url('admin/deposits/view.php?id=' . $depositId));
    }
    
    try {
        switch ($_POST['action']) {
            case 'verify':
                $depositModel->verify($depositId, Auth::userId());
                setSuccess('Deposit verified successfully!');
                break;
                
            case 'reject':
                $depositModel->updateStatus($depositId, 'rejected');
                setSuccess('Deposit rejected.');
                break;
                
            case 'pending':
                $depositModel->updateStatus($depositId, 'pending');
                setSuccess('Deposit status updated to pending.');
                break;
        }
        
        // Refresh deposit data
        $deposit = $depositModel->findById($depositId);
        
    } catch (Exception $e) {
        setErrors(['general' => 'An error occurred: ' . $e->getMessage()]);
        error_log("Deposit status update error: " . $e->getMessage());
    }
}

// Get related order
$orderModel = new Order();
$order = $orderModel->findById($deposit['order_id']);

// Get deposit history for this order
$orderDeposits = $depositModel->getByOrder($deposit['order_id']);

$pageTitle = "Deposit Details #" . $depositId;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Andcorp Autos</title>
    <link rel="icon" type="image/png" href="<?php echo url('assets/images/favicon.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo url('assets/images/logo.png'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="<?php echo url('assets/css/modern-theme.css'); ?>" rel="stylesheet">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container-fluid my-4">
        <div class="row">
            <div class="col-lg-11 mx-auto">
                <!-- Page Header -->
                <div class="page-header animate-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-5">Deposit Details #<?php echo $depositId; ?></h1>
                            <p class="lead mb-0"><i class="bi bi-receipt-cutoff"></i> View and manage deposit information</p>
                        </div>
                        <div>
                            <a href="<?php echo url('admin/deposits.php'); ?>" class="btn btn-secondary btn-modern">
                                <i class="bi bi-arrow-left"></i> Back to Deposits
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Flash Messages -->
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
                    <!-- Main Deposit Details -->
                    <div class="col-lg-8">
                        <div class="card-modern mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Deposit Information</h5>
                                <?php
                                $badgeClass = match($deposit['status']) {
                                    'verified' => 'bg-success',
                                    'pending' => 'bg-warning',
                                    'rejected' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?php echo $badgeClass; ?> fs-6">
                                    <?php echo ucfirst($deposit['status']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Amount</label>
                                        <h4 class="text-primary">
                                            <?php echo formatCurrency($deposit['amount'], $deposit['currency']); ?>
                                        </h4>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Payment Method</label>
                                        <p class="mb-0">
                                            <i class="bi bi-credit-card"></i>
                                            <?php echo ucwords(str_replace('_', ' ', $deposit['payment_method'])); ?>
                                        </p>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Transaction Date</label>
                                        <p class="mb-0">
                                            <i class="bi bi-calendar"></i>
                                            <?php echo date('l, F j, Y', strtotime($deposit['transaction_date'])); ?>
                                        </p>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Transaction Time</label>
                                        <p class="mb-0">
                                            <i class="bi bi-clock"></i>
                                            <?php echo date('g:i A', strtotime($deposit['transaction_time'])); ?>
                                        </p>
                                    </div>

                                    <?php if ($deposit['bank_name']): ?>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Bank Name</label>
                                        <p class="mb-0">
                                            <i class="bi bi-bank"></i>
                                            <?php echo $deposit['bank_name']; ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($deposit['account_number']): ?>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Account Number</label>
                                        <p class="mb-0">
                                            <i class="bi bi-hash"></i>
                                            <?php echo $deposit['account_number']; ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($deposit['reference_number']): ?>
                                    <div class="col-md-12">
                                        <label class="form-label text-muted small">Reference Number</label>
                                        <p class="mb-0">
                                            <code class="fs-6"><?php echo $deposit['reference_number']; ?></code>
                                        </p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($deposit['notes']): ?>
                                    <div class="col-md-12">
                                        <label class="form-label text-muted small">Notes</label>
                                        <div class="alert alert-info mb-0">
                                            <i class="bi bi-info-circle"></i>
                                            <?php echo nl2br($deposit['notes']); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($deposit['deposit_slip']): ?>
                                    <div class="col-md-12">
                                        <label class="form-label text-muted small">Deposit Slip</label>
                                        <div>
                                            <a href="<?php echo url('uploads/deposits/' . $deposit['deposit_slip']); ?>" 
                                               class="btn btn-outline-primary" target="_blank">
                                                <i class="bi bi-file-earmark-pdf"></i> View Deposit Slip
                                            </a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <hr class="my-4">

                                <!-- Status Actions -->
                                <div class="d-flex gap-2 flex-wrap">
                                    <?php if ($deposit['status'] === 'pending'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to verify this deposit?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo Security::generateToken(); ?>">
                                            <input type="hidden" name="action" value="verify">
                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-check-circle"></i> Verify Deposit
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reject this deposit?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo Security::generateToken(); ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger">
                                                <i class="bi bi-x-circle"></i> Reject Deposit
                                            </button>
                                        </form>
                                    <?php elseif ($deposit['status'] === 'verified'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to mark this as pending again?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo Security::generateToken(); ?>">
                                            <input type="hidden" name="action" value="pending">
                                            <button type="submit" class="btn btn-warning">
                                                <i class="bi bi-arrow-counterclockwise"></i> Mark as Pending
                                            </button>
                                        </form>
                                    <?php elseif ($deposit['status'] === 'rejected'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to mark this as pending?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo Security::generateToken(); ?>">
                                            <input type="hidden" name="action" value="pending">
                                            <button type="submit" class="btn btn-warning">
                                                <i class="bi bi-arrow-counterclockwise"></i> Mark as Pending
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to verify this deposit?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo Security::generateToken(); ?>">
                                            <input type="hidden" name="action" value="verify">
                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-check-circle"></i> Verify Deposit
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Verification Info -->
                        <?php if ($deposit['status'] === 'verified' && $deposit['verified_by']): ?>
                        <div class="card-modern mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-shield-check"></i> Verification Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Verified By</label>
                                        <p class="mb-0">
                                            <i class="bi bi-person"></i>
                                            <?php 
                                            if ($deposit['verifier_first_name']) {
                                                echo $deposit['verifier_first_name'] . ' ' . $deposit['verifier_last_name'];
                                            } else {
                                                echo 'Unknown';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Verified At</label>
                                        <p class="mb-0">
                                            <i class="bi bi-clock-history"></i>
                                            <?php echo date('M d, Y g:i A', strtotime($deposit['verified_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Audit Trail -->
                        <div class="card-modern">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Audit Trail</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Created By</label>
                                        <p class="mb-0">
                                            <i class="bi bi-person"></i>
                                            <?php 
                                            if ($deposit['creator_first_name']) {
                                                echo $deposit['creator_first_name'] . ' ' . $deposit['creator_last_name'];
                                            } else {
                                                echo 'Unknown';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Created At</label>
                                        <p class="mb-0">
                                            <i class="bi bi-calendar-plus"></i>
                                            <?php echo date('M d, Y g:i A', strtotime($deposit['created_at'])); ?>
                                        </p>
                                    </div>
                                    <?php if ($deposit['updated_at'] !== $deposit['created_at']): ?>
                                    <div class="col-md-12">
                                        <label class="form-label text-muted small">Last Updated</label>
                                        <p class="mb-0">
                                            <i class="bi bi-pencil-square"></i>
                                            <?php echo date('M d, Y g:i A', strtotime($deposit['updated_at'])); ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <!-- Related Order -->
                        <div class="card-modern mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-box"></i> Related Order</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-2">
                                    <strong>Order Number:</strong><br>
                                    <a href="<?php echo url('admin/orders/edit.php?id=' . $order['id']); ?>" class="text-primary">
                                        <?php echo $order['order_number']; ?>
                                    </a>
                                </p>
                                <p class="mb-2">
                                    <strong>Customer:</strong><br>
                                    <?php echo $deposit['customer_first_name'] . ' ' . $deposit['customer_last_name']; ?>
                                </p>
                                <p class="mb-2">
                                    <strong>Order Total:</strong><br>
                                    <span class="text-success fw-bold">
                                        <?php echo formatCurrency($order['total_cost'], $order['currency']); ?>
                                    </span>
                                </p>
                                <p class="mb-0">
                                    <strong>Total Deposits:</strong><br>
                                    <span class="text-info fw-bold">
                                        <?php echo formatCurrency($order['total_deposits'], $order['currency']); ?>
                                    </span>
                                </p>
                                <hr>
                                <p class="mb-0">
                                    <strong>Balance Due:</strong><br>
                                    <span class="text-danger fw-bold fs-5">
                                        <?php echo formatCurrency($order['total_cost'] - $order['total_deposits'], $order['currency']); ?>
                                    </span>
                                </p>
                                <div class="mt-3">
                                    <a href="<?php echo url('admin/orders/edit.php?id=' . $order['id']); ?>" class="btn btn-primary btn-sm w-100">
                                        <i class="bi bi-box-arrow-up-right"></i> View Order
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Order Deposit History -->
                        <?php if (count($orderDeposits) > 1): ?>
                        <div class="card-modern">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Order Deposits</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($orderDeposits as $d): ?>
                                        <div class="list-group-item <?php echo $d['id'] == $depositId ? 'bg-light' : ''; ?>">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong><?php echo formatCurrency($d['amount'], $d['currency']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo date('M d, Y', strtotime($d['transaction_date'])); ?>
                                                    </small>
                                                </div>
                                                <div class="text-end">
                                                    <?php
                                                    $statusBadge = match($d['status']) {
                                                        'verified' => 'bg-success',
                                                        'pending' => 'bg-warning',
                                                        'rejected' => 'bg-danger',
                                                        default => 'bg-secondary'
                                                    };
                                                    ?>
                                                    <span class="badge <?php echo $statusBadge; ?> mb-1">
                                                        <?php echo ucfirst($d['status']); ?>
                                                    </span>
                                                    <?php if ($d['id'] != $depositId): ?>
                                                    <br>
                                                    <a href="<?php echo url('admin/deposits/view.php?id=' . $d['id']); ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        View
                                                    </a>
                                                    <?php else: ?>
                                                    <br>
                                                    <span class="badge bg-info">Current</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="card-footer bg-light">
                                    <div class="d-flex justify-content-between">
                                        <strong>Total Verified:</strong>
                                        <strong class="text-success">
                                            <?php 
                                            $totalVerified = array_sum(array_column(
                                                array_filter($orderDeposits, fn($d) => $d['status'] === 'verified'), 
                                                'amount'
                                            ));
                                            echo formatCurrency($totalVerified, 'GHS');
                                            ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>
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

