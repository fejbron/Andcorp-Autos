<?php
require_once 'bootstrap.php';

// Redirect if already logged in
if (Auth::check()) {
    redirect(url('dashboard.php'));
}

Security::generateToken();

$token = Security::sanitizeString($_GET['token'] ?? '', 64);
$tokenValid = false;
$tokenError = null;
$user = null;

// Validate token
if (empty($token)) {
    $tokenError = 'Invalid or missing reset token.';
} else {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Check if token exists and is valid
        $stmt = $db->prepare("
            SELECT pr.*, u.email, u.first_name, u.last_name 
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $resetData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resetData) {
            $tokenValid = true;
            $user = $resetData;
        } else {
            // Check if token was used or expired
            $checkStmt = $db->prepare("SELECT used, expires_at FROM password_resets WHERE token = ?");
            $checkStmt->execute([$token]);
            $tokenInfo = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tokenInfo) {
                if ($tokenInfo['used'] == 1) {
                    $tokenError = 'This password reset link has already been used. Please request a new one.';
                } elseif (strtotime($tokenInfo['expires_at']) < time()) {
                    $tokenError = 'This password reset link has expired. Please request a new one.';
                }
            } else {
                $tokenError = 'Invalid password reset link.';
            }
        }
    } catch (PDOException $e) {
        error_log("Token validation error: " . $e->getMessage());
        $tokenError = 'An error occurred. Please try again.';
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        setErrors(['general' => 'Invalid security token. Please try again.']);
        redirect(url('reset-password.php?token=' . $token));
    }

    // Rate limiting - 5 attempts per 15 minutes per IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!Security::checkRateLimit("reset_password_{$ip}", 5, 900)) {
        setErrors(['general' => 'Too many password reset attempts. Please try again in 15 minutes.']);
        redirect(url('reset-password.php?token=' . $token));
    }

    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $errors = [];

    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    } elseif ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        try {
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();
            
            // Update user password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->execute([$hashedPassword, $user['user_id']]);
            
            // Mark token as used
            $markUsedStmt = $db->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $markUsedStmt->execute([$token]);
            
            $db->commit();
            
            // Log activity (non-critical, don't fail if this errors)
            try {
                Auth::logActivity($user['user_id'], 'password_reset', 'Password reset completed');
            } catch (Exception $logError) {
                error_log("Failed to log password reset activity: " . $logError->getMessage());
            }
            
            error_log("Password reset successful for user ID {$user['user_id']}");
            
            setSuccess('Your password has been reset successfully. You can now log in with your new password.');
            redirect(url('login.php'));
            
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Password reset PDO error: " . $e->getMessage());
            error_log("Password reset error trace: " . $e->getTraceAsString());
            $errors['general'] = 'An error occurred while resetting your password. Please try again.';
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Password reset general error: " . $e->getMessage());
            error_log("Password reset error trace: " . $e->getTraceAsString());
            $errors['general'] = 'An unexpected error occurred. Please try again or contact support.';
        }
    }
    
    if (!empty($errors)) {
        setErrors($errors);
    }
}

$title = "Reset Password";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <title><?php echo htmlspecialchars($title); ?> - Andcorp Autos</title>
    <style>
        body {
            background: var(--bg-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .auth-page-card {
            max-width: 450px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-page-card">
            <div class="card-modern">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <img src="<?php echo url('assets/images/logo.png'); ?>" alt="Andcorp Autos" style="max-width: 200px; width: 100%; height: auto; margin-bottom: 1rem;">
                        <h2 class="mt-3">Reset Your Password</h2>
                        <?php if ($tokenValid): ?>
                            <p class="text-muted">Enter your new password below.</p>
                        <?php endif; ?>
                    </div>

                    <?php if ($tokenError): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($tokenError); ?>
                        </div>
                        <div class="text-center">
                            <a href="<?php echo url('forgot-password.php'); ?>" class="btn btn-primary mb-3">
                                <i class="bi bi-arrow-clockwise"></i> Request New Reset Link
                            </a>
                            <hr class="my-4">
                            <p class="mb-0">
                                <a href="<?php echo url('login.php'); ?>" class="text-decoration-none">
                                    <i class="bi bi-arrow-left"></i> Back to Login
                                </a>
                            </p>
                        </div>
                    <?php elseif ($tokenValid): ?>
                        <?php if ($generalError = error('general')): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo $generalError; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle"></i> Resetting password for: <strong><?php echo htmlspecialchars($user['email']); ?></strong>
                        </div>

                        <form action="<?php echo htmlspecialchars(url('reset-password.php?token=' . $token)); ?>" method="POST">
                            <?php echo Security::csrfField(); ?>

                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control <?php echo error('password') ? 'is-invalid' : ''; ?>" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Enter new password (min. 8 characters)"
                                           minlength="8"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                                <?php if (error('password')): ?>
                                    <div class="invalid-feedback d-block"><?php echo error('password'); ?></div>
                                <?php endif; ?>
                                
                                <!-- Password Strength Indicator -->
                                <div class="mt-2">
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted" id="passwordStrengthText">Password strength: <span id="strengthLabel">None</span></small>
                                </div>
                                
                                <div class="form-text mt-2">
                                    <small>
                                        Password must contain:
                                        <ul class="mb-0 mt-1" style="font-size: 0.875rem;">
                                            <li id="length" class="text-muted">At least 8 characters</li>
                                            <li id="uppercase" class="text-muted">One uppercase letter</li>
                                            <li id="lowercase" class="text-muted">One lowercase letter</li>
                                            <li id="number" class="text-muted">One number</li>
                                        </ul>
                                    </small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Confirm New Password</label>
                                <input type="password" 
                                       class="form-control <?php echo error('password_confirm') ? 'is-invalid' : ''; ?>" 
                                       id="password_confirm" 
                                       name="password_confirm" 
                                       placeholder="Re-enter new password"
                                       minlength="8"
                                       required>
                                <?php if (error('password_confirm')): ?>
                                    <div class="invalid-feedback"><?php echo error('password_confirm'); ?></div>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                                <i class="bi bi-shield-check"></i> Reset Password
                            </button>

                            <hr class="my-4">

                            <div class="text-center">
                                <p class="mb-0">
                                    <a href="<?php echo url('login.php'); ?>" class="text-decoration-none">
                                        <i class="bi bi-arrow-left"></i> Back to Login
                                    </a>
                                </p>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php clearErrors(); clearOld(); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                toggleIcon.classList.toggle('bi-eye');
                toggleIcon.classList.toggle('bi-eye-slash');
            });
        }

        // Password strength indicator
        const password = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrength');
        const strengthLabel = document.getElementById('strengthLabel');
        const lengthCheck = document.getElementById('length');
        const uppercaseCheck = document.getElementById('uppercase');
        const lowercaseCheck = document.getElementById('lowercase');
        const numberCheck = document.getElementById('number');

        if (password) {
            password.addEventListener('input', function() {
                const value = this.value;
                let strength = 0;
                let checks = {
                    length: value.length >= 8,
                    uppercase: /[A-Z]/.test(value),
                    lowercase: /[a-z]/.test(value),
                    number: /[0-9]/.test(value),
                    special: /[^A-Za-z0-9]/.test(value)
                };

                // Update check marks
                updateCheck(lengthCheck, checks.length);
                updateCheck(uppercaseCheck, checks.uppercase);
                updateCheck(lowercaseCheck, checks.lowercase);
                updateCheck(numberCheck, checks.number);

                // Calculate strength
                if (checks.length) strength += 25;
                if (checks.uppercase) strength += 20;
                if (checks.lowercase) strength += 20;
                if (checks.number) strength += 20;
                if (checks.special) strength += 15;

                // Update progress bar
                strengthBar.style.width = strength + '%';
                
                // Update color and label
                if (strength === 0) {
                    strengthBar.className = 'progress-bar';
                    strengthLabel.textContent = 'None';
                    strengthLabel.className = 'text-muted';
                } else if (strength < 40) {
                    strengthBar.className = 'progress-bar bg-danger';
                    strengthLabel.textContent = 'Weak';
                    strengthLabel.className = 'text-danger fw-bold';
                } else if (strength < 70) {
                    strengthBar.className = 'progress-bar bg-warning';
                    strengthLabel.textContent = 'Fair';
                    strengthLabel.className = 'text-warning fw-bold';
                } else if (strength < 90) {
                    strengthBar.className = 'progress-bar bg-info';
                    strengthLabel.textContent = 'Good';
                    strengthLabel.className = 'text-info fw-bold';
                } else {
                    strengthBar.className = 'progress-bar bg-success';
                    strengthLabel.textContent = 'Strong';
                    strengthLabel.className = 'text-success fw-bold';
                }
            });
        }

        function updateCheck(element, passed) {
            if (passed) {
                element.innerHTML = element.innerHTML.replace(/^.*?(?=<|$)/, '✓ ' + element.textContent.replace('✓ ', '').replace('✗ ', ''));
                element.className = 'text-success';
            } else {
                element.innerHTML = element.innerHTML.replace(/^.*?(?=<|$)/, '✗ ' + element.textContent.replace('✓ ', '').replace('✗ ', ''));
                element.className = 'text-muted';
            }
        }
    </script>
</body>
</html>

