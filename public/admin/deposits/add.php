<?php
require_once '../../bootstrap.php';
Auth::requireStaff();

$orderId = Security::sanitizeInt($_GET['order_id'] ?? 0);

if (!$orderId) {
    redirect(url('admin/deposits.php'));
}

$orderModel = new Order();
$order = $orderModel->findById($orderId);

if (!$order) {
    setErrors(['general' => 'Order not found']);
    redirect(url('admin/deposits.php'));
}

$customerModel = new Customer();
$customer = $customerModel->findById($order['customer_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        $errors['general'] = 'Invalid security token. Please try again.';
        setErrors($errors);
        redirect(url('admin/deposits/add.php?order_id=' . $orderId));
    }
    
    $validator = new Validator();
    $validator->required('amount', $_POST['amount'] ?? '')
              ->required('transaction_date', $_POST['transaction_date'] ?? '')
              ->required('transaction_time', $_POST['transaction_time'] ?? '')
              ->required('payment_method', $_POST['payment_method'] ?? '');
    
    $errors = $validator->getErrors();
    
    if (empty($errors)) {
        try {
            $depositModel = new Deposit();
            
            $depositData = [
                'order_id' => $orderId,
                'customer_id' => $order['customer_id'],
                'amount' => $_POST['amount'],
                'currency' => 'GHS', // Ghana Cedis only
                'payment_method' => $_POST['payment_method'],
                'bank_name' => $_POST['bank_name'] ?? null,
                'account_number' => $_POST['account_number'] ?? null,
                'reference_number' => $_POST['reference_number'] ?? null,
                'transaction_date' => $_POST['transaction_date'],
                'transaction_time' => $_POST['transaction_time'],
                'deposit_slip' => null, // File upload not implemented yet
                'status' => $_POST['status'] ?? 'verified', // Admin adds deposits as verified by default
                'notes' => $_POST['notes'] ?? null,
                'created_by' => Auth::userId()
            ];
            
            $depositId = $depositModel->create($depositData);
            
            // Log activity
            Auth::logOrderActivity(Auth::userId(), $orderId, 'deposit_added', 'Deposit of ' . formatCurrency($_POST['amount']) . ' added');
            
            clearOld(); // Clear form data after successful submission
            setSuccess('Deposit added successfully!');
            redirect(url('admin/orders/edit.php?id=' . $orderId));
        } catch (Exception $e) {
            $errors['general'] = 'An error occurred while adding the deposit. Please try again.';
            error_log("Deposit creation error: " . $e->getMessage());
        }
    }
    
    setErrors($errors);
    setOld($_POST);
}

$title = "Add Deposit";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Deposit - Andcorp Autos</title>
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
            <div class="col-lg-8 mx-auto">
                <!-- Page Header -->
                <div class="page-header animate-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-5">Record Customer Deposit</h1>
                            <p class="lead mb-0">Order #<?php echo $order['order_number']; ?></p>
                        </div>
                        <div>
                            <a href="<?php echo url('admin/orders/edit.php?id=' . $orderId); ?>" class="btn btn-secondary btn-modern">
                                <i class="bi bi-arrow-left"></i> Back to Order
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (error('general')): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo error('general'); ?>
                    </div>
                <?php endif; ?>

                <!-- Order Info -->
                <div class="card-modern mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Order Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Customer:</strong> <?php echo $customer['first_name'] . ' ' . $customer['last_name']; ?></p>
                                <p><strong>Order Total:</strong> <?php echo formatCurrency($order['total_cost'], $order['currency']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Total Deposits:</strong> <?php echo formatCurrency($order['total_deposits'] ?? 0, $order['currency']); ?></p>
                                <p><strong>Balance Due:</strong> <span class="text-warning"><?php echo formatCurrency($order['balance_due'], $order['currency']); ?></span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Deposit Form -->
                <div class="card-modern">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Deposit Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?order_id=' . $orderId; ?>">
                            <?php echo Security::csrfField(); ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="amount" class="form-label">Amount (GHS) *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">GHS</span>
                                        <input type="number" step="0.01" class="form-control <?php echo hasError('amount') ? 'is-invalid' : ''; ?>" 
                                               id="amount" name="amount" value="<?php echo old('amount'); ?>" 
                                               placeholder="0.00" required>
                                    </div>
                                    <?php if (error('amount')): ?>
                                        <div class="invalid-feedback d-block"><?php echo error('amount'); ?></div>
                                    <?php endif; ?>
                                    <div class="form-text">Enter amount in Ghana Cedis (GHS)</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="payment_method" class="form-label">Payment Method *</label>
                                    <select class="form-select <?php echo hasError('payment_method') ? 'is-invalid' : ''; ?>" 
                                            id="payment_method" name="payment_method" required>
                                        <option value="">Select Method</option>
                                        <option value="bank_transfer" <?php echo old('payment_method') === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                        <option value="mobile_money" <?php echo old('payment_method') === 'mobile_money' ? 'selected' : ''; ?>>Mobile Money</option>
                                        <option value="cash" <?php echo old('payment_method') === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                        <option value="cheque" <?php echo old('payment_method') === 'cheque' ? 'selected' : ''; ?>>Cheque</option>
                                        <option value="card" <?php echo old('payment_method') === 'card' ? 'selected' : ''; ?>>Card Payment</option>
                                        <option value="other" <?php echo old('payment_method') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                    <?php if (error('payment_method')): ?>
                                        <div class="invalid-feedback"><?php echo error('payment_method'); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="transaction_date" class="form-label">Transaction Date *</label>
                                    <input type="date" class="form-control <?php echo hasError('transaction_date') ? 'is-invalid' : ''; ?>" 
                                           id="transaction_date" name="transaction_date" 
                                           value="<?php echo old('transaction_date', date('Y-m-d')); ?>" required>
                                    <?php if (error('transaction_date')): ?>
                                        <div class="invalid-feedback"><?php echo error('transaction_date'); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="transaction_time" class="form-label">Transaction Time *</label>
                                    <input type="time" class="form-control <?php echo hasError('transaction_time') ? 'is-invalid' : ''; ?>" 
                                           id="transaction_time" name="transaction_time" 
                                           value="<?php echo old('transaction_time', date('H:i')); ?>" required>
                                    <?php if (error('transaction_time')): ?>
                                        <div class="invalid-feedback"><?php echo error('transaction_time'); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="bank_name" class="form-label">Bank Name</label>
                                    <input type="text" class="form-control" id="bank_name" name="bank_name" 
                                           value="<?php echo old('bank_name'); ?>" placeholder="e.g., GCB Bank">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="account_number" class="form-label">Account Number</label>
                                    <input type="text" class="form-control" id="account_number" name="account_number" 
                                           value="<?php echo old('account_number'); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="reference_number" class="form-label">Reference/Transaction Number</label>
                                <input type="text" class="form-control" id="reference_number" name="reference_number" 
                                       value="<?php echo old('reference_number'); ?>" 
                                       placeholder="Bank reference or transaction ID">
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="verified" <?php echo old('status', 'verified') === 'verified' ? 'selected' : ''; ?>>Verified</option>
                                    <option value="pending" <?php echo old('status') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                </select>
                                <div class="form-text">Select "Verified" if you've confirmed this deposit</div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="Additional notes about this deposit..."><?php echo old('notes'); ?></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Record Deposit
                                </button>
                                <a href="<?php echo url('admin/orders/edit.php?id=' . $orderId); ?>" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

