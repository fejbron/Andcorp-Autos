<?php
require_once 'bootstrap.php';
Auth::requireAuth();

// Ensure CSRF token is generated for forms
Security::generateToken();

$userModel = new User();
$user = Auth::user();

if (Auth::isCustomer()) {
    $customerModel = new Customer();
    $customer = $customerModel->findByUserId(Auth::userId());
    
    // Get customer's deposits
    $depositModel = new Deposit();
    $deposits = $depositModel->getByCustomer($customer['id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token'])) {
        $errors['general'] = 'Security token missing. Please try again.';
        setErrors($errors);
        redirect(url('profile.php'));
    }
    
    if (!Security::verifyToken($_POST['csrf_token'])) {
        error_log("CSRF token mismatch. Session ID: " . session_id());
        error_log("Session token exists: " . (isset($_SESSION['csrf_token']) ? 'YES' : 'NO'));
        if (isset($_SESSION['csrf_token'])) {
            error_log("Session token (first 20 chars): " . substr($_SESSION['csrf_token'], 0, 20));
        }
        error_log("POST token exists: " . (isset($_POST['csrf_token']) ? 'YES' : 'NO'));
        if (isset($_POST['csrf_token'])) {
            error_log("POST token (first 20 chars): " . substr($_POST['csrf_token'], 0, 20));
        }
        error_log("Session data: " . print_r($_SESSION, true));
        $errors['general'] = 'Invalid security token. Please refresh the page and try again.';
        setErrors($errors);
        redirect(url('profile.php'));
    }
    
    // Regenerate token after successful validation for next request
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    $errors = [];
    
    if (isset($_POST['update_profile'])) {
        $validator = new Validator();
        $validator->required('first_name', $_POST['first_name'] ?? '')
                  ->maxLength('first_name', $_POST['first_name'] ?? '', 100);
        $validator->required('last_name', $_POST['last_name'] ?? '')
                  ->maxLength('last_name', $_POST['last_name'] ?? '', 100);
        $validator->required('email', $_POST['email'] ?? '')
                  ->email('email', $_POST['email'] ?? '')
                  ->maxLength('email', $_POST['email'] ?? '', 255);
        
        if (!empty($_POST['phone'])) {
            $validator->phone('phone', $_POST['phone'] ?? '');
        }
        
        $errors = $validator->getErrors();
        
        // Check if email is already taken by another user
        if (!isset($errors['email']) && !empty($_POST['email']) && $_POST['email'] !== $user['email']) {
            $existingUser = $userModel->findByEmail($_POST['email']);
            if ($existingUser) {
                $errors['email'] = 'Email already in use';
            }
        }
        
        if (empty($errors)) {
            try {
                $updateResult = $userModel->update(Auth::userId(), [
                    'first_name' => $_POST['first_name'],
                    'last_name' => $_POST['last_name'],
                    'email' => $_POST['email'],
                    'phone' => $_POST['phone'] ?? null
                ]);
                
                if (!$updateResult) {
                    throw new Exception('Update failed - no rows affected');
                }
                
                if (Auth::isCustomer() && isset($customer)) {
                    $customerModel->update($customer['id'], [
                        'address' => !empty($_POST['address']) ? Security::sanitizeString($_POST['address'], 500) : null,
                        'city' => !empty($_POST['city']) ? Security::sanitizeString($_POST['city'], 100) : null,
                        'ghana_card_number' => !empty($_POST['ghana_card_number']) ? Security::sanitizeString($_POST['ghana_card_number'], 100) : null
                    ]);
                }
                
                // Refresh user data from database
                $user = $userModel->findById(Auth::userId());
                
                if (!$user) {
                    throw new Exception('Failed to refresh user data');
                }
                
                // Update session with new user name
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                
                clearOld();
                setSuccess('Profile updated successfully!');
                redirect(url('profile.php'));
            } catch (Exception $e) {
                error_log("Profile update error: " . $e->getMessage());
                error_log("Profile update error trace: " . $e->getTraceAsString());
                $errors['general'] = 'An error occurred while updating your profile: ' . $e->getMessage();
                setErrors($errors);
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $validator = new Validator();
        $validator->required('current_password', $_POST['current_password'] ?? '');
        $validator->required('new_password', $_POST['new_password'] ?? '')
                  ->password('new_password', $_POST['new_password'] ?? '');
        $validator->required('confirm_password', $_POST['confirm_password'] ?? '')
                  ->match('confirm_password', $_POST['confirm_password'] ?? '', $_POST['new_password'] ?? '');
        
        $errors = $validator->getErrors();
        
        if (!isset($errors['current_password']) && !$userModel->verifyPassword($_POST['current_password'], $user['password'])) {
            $errors['current_password'] = 'Current password is incorrect';
        }
        
        if (empty($errors)) {
            try {
                $userModel->update(Auth::userId(), [
                    'password' => $_POST['new_password']
                ]);
                
                clearOld();
                setSuccess('Password changed successfully!');
                redirect(url('profile.php'));
            } catch (Exception $e) {
                $errors['general'] = 'An error occurred. Please try again.';
                error_log("Password change error: " . $e->getMessage());
            }
        }
    }
    
    setErrors($errors);
    setOld($_POST);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Andcorp Autos</title>
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
            <h1 class="display-5">My Profile</h1>
            <p class="lead">Manage your account information</p>
        </div>

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
            <div class="col-lg-8">
                <!-- Profile Information -->
                <div class="card-modern mb-4 animate-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-person"></i> Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <?php echo Security::csrfField(); ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control <?php echo hasError('first_name') ? 'is-invalid' : ''; ?>" 
                                           id="first_name" name="first_name" value="<?php echo old('first_name', $user['first_name']); ?>" required>
                                    <?php if (error('first_name')): ?>
                                        <div class="invalid-feedback"><?php echo error('first_name'); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control <?php echo hasError('last_name') ? 'is-invalid' : ''; ?>" 
                                           id="last_name" name="last_name" value="<?php echo old('last_name', $user['last_name']); ?>" required>
                                    <?php if (error('last_name')): ?>
                                        <div class="invalid-feedback"><?php echo error('last_name'); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control <?php echo hasError('email') ? 'is-invalid' : ''; ?>" 
                                       id="email" name="email" value="<?php echo old('email', $user['email']); ?>" required>
                                <?php if (error('email')): ?>
                                    <div class="invalid-feedback"><?php echo error('email'); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo old('phone', $user['phone'] ?? ''); ?>">
                            </div>

                            <?php if (Auth::isCustomer() && isset($customer)): ?>
                                <div class="mb-3">
                                    <label for="ghana_card_number" class="form-label">Ghana Card Number</label>
                                    <input type="text" class="form-control" id="ghana_card_number" name="ghana_card_number" 
                                           value="<?php echo $customer['ghana_card_number'] ?? ''; ?>"
                                           placeholder="GHA-XXXXXXXXX-X">
                                    <div class="form-text">Your Ghana Card identification number</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" value="<?php echo $customer['address'] ?? ''; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?php echo $customer['city'] ?? ''; ?>">
                                </div>
                            <?php endif; ?>

                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card-modern animate-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <?php echo Security::csrfField(); ?>
                            <input type="hidden" name="change_password" value="1">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password *</label>
                                <input type="password" class="form-control <?php echo hasError('current_password') ? 'is-invalid' : ''; ?>" 
                                       id="current_password" name="current_password" required>
                                <?php if (error('current_password')): ?>
                                    <div class="invalid-feedback"><?php echo error('current_password'); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password *</label>
                                <input type="password" class="form-control <?php echo hasError('new_password') ? 'is-invalid' : ''; ?>" 
                                       id="new_password" name="new_password" required>
                                <?php if (error('new_password')): ?>
                                    <div class="invalid-feedback"><?php echo error('new_password'); ?></div>
                                <?php endif; ?>
                                <div class="form-text">Minimum 8 characters</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                <input type="password" class="form-control <?php echo hasError('confirm_password') ? 'is-invalid' : ''; ?>" 
                                       id="confirm_password" name="confirm_password" required>
                                <?php if (error('confirm_password')): ?>
                                    <div class="invalid-feedback"><?php echo error('confirm_password'); ?></div>
                                <?php endif; ?>
                            </div>

                            <button type="submit" name="change_password" class="btn btn-warning">
                                <i class="bi bi-key"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Account Info -->
                <div class="card-modern mb-4 animate-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Account Info</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Role:</strong> <?php echo ucfirst($user['role']); ?></p>
                        <p><strong>Member Since:</strong> <?php echo formatDate($user['created_at']); ?></p>
                        <p class="mb-0"><strong>Status:</strong> 
                            <span class="badge bg-success">Active</span>
                        </p>
                    </div>
                </div>

                <?php if (Auth::isCustomer() && isset($customer)): ?>
                    <!-- Customer Stats -->
                    <div class="card-modern mb-4 animate-in">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-bar-chart"></i> My Stats</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $orderModel = new Order();
                            $orders = $orderModel->getByCustomer($customer['id']);
                            $deliveredCount = count(array_filter($orders, fn($o) => $o['status'] === 'delivered'));
                            ?>
                            <p><strong>Total Orders:</strong> <?php echo count($orders); ?></p>
                            <p class="mb-0"><strong>Delivered:</strong> <?php echo $deliveredCount; ?></p>
                        </div>
                    </div>
                    
                    <!-- Cost Breakdown Summary -->
                    <div class="card-modern animate-in">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-currency-dollar"></i> Total Spending</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Calculate total spending across all orders
                            $db = Database::getInstance()->getConnection();
                            $stmt = $db->prepare("
                                SELECT 
                                    COUNT(*) as order_count,
                                    COALESCE(SUM(car_cost), 0) as total_car_cost,
                                    COALESCE(SUM(transportation_cost), 0) as total_transport,
                                    COALESCE(SUM(duty_cost), 0) as total_duty,
                                    COALESCE(SUM(clearing_cost), 0) as total_clearing,
                                    COALESCE(SUM(fixing_cost), 0) as total_fixing,
                                    COALESCE(SUM(total_usd), 0) as grand_total
                                FROM orders
                                WHERE customer_id = ?
                            ");
                            $stmt->execute([Security::sanitizeInt($customer['id'])]);
                            $spending = $stmt->fetch(PDO::FETCH_ASSOC);
                            ?>
                            
                            <?php if ($spending['order_count'] > 0): ?>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Car Costs</small>
                                    <strong>$<?php echo number_format($spending['total_car_cost'], 2); ?></strong>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block">Transportation to Ghana</small>
                                    <strong>$<?php echo number_format($spending['total_transport'], 2); ?></strong>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block">Import Duty</small>
                                    <strong>$<?php echo number_format($spending['total_duty'], 2); ?></strong>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block">Clearing Fees</small>
                                    <strong>$<?php echo number_format($spending['total_clearing'], 2); ?></strong>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block">Repair/Fixing Costs</small>
                                    <strong>$<?php echo number_format($spending['total_fixing'], 2); ?></strong>
                                </div>
                                
                                <hr>
                                
                                <div class="alert alert-primary mb-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>Total Spent (USD)</strong>
                                        <h4 class="mb-0">$<?php echo number_format($spending['grand_total'], 2); ?></h4>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">No orders yet to display spending summary.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Deposits History -->
                    <div class="card-modern mt-4 animate-in">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-wallet2"></i> My Deposits</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($deposits)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Order</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $totalVerified = 0;
                                            foreach ($deposits as $dep): 
                                                if ($dep['status'] === 'verified') {
                                                    $totalVerified += $dep['amount'];
                                                }
                                            ?>
                                                <tr>
                                                    <td>
                                                        <small><?php echo date('M d, Y', strtotime($dep['transaction_date'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <a href="<?php echo url('orders/view.php?id=' . $dep['order_id']); ?>">
                                                            <small><?php echo $dep['order_number']; ?></small>
                                                        </a>
                                                    </td>
                                                    <td><strong><?php echo formatCurrency($dep['amount'], $dep['currency']); ?></strong></td>
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
                                        <tfoot>
                                            <tr class="table-success">
                                                <td colspan="2"><strong>Total Verified:</strong></td>
                                                <td colspan="2"><strong><?php echo formatCurrency($totalVerified); ?></strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0 text-center">No deposits recorded yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php clearErrors(); clearOld(); ?>
