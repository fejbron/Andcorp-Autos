<?php
require_once 'bootstrap.php';

// Redirect if already logged in
if (Auth::check()) {
    redirect(url('dashboard.php'));
}

Security::generateToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        setErrors(['general' => 'Invalid security token. Please try again.']);
        redirect(url('forgot-password.php'));
    }

    // Rate limiting - 3 attempts per 15 minutes per IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!Security::checkRateLimit("forgot_password_{$ip}", 3, 900)) {
        setErrors(['general' => 'Too many password reset requests. Please try again in 15 minutes.']);
        redirect(url('forgot-password.php'));
    }

    $email = Security::sanitizeEmail($_POST['email'] ?? '');
    
    if (empty($email)) {
        setErrors(['email' => 'Email address is required.']);
    } elseif (!Security::validateEmail($email)) {
        setErrors(['email' => 'Please enter a valid email address.']);
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Find user by email
            $stmt = $db->prepare("SELECT id, email, first_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Generate secure random token
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Delete any existing unused tokens for this user
                $deleteStmt = $db->prepare("DELETE FROM password_resets WHERE user_id = ? AND used = 0");
                $deleteStmt->execute([$user['id']]);
                
                // Insert new reset token
                $insertStmt = $db->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $insertStmt->execute([$user['id'], $token, $expiresAt]);
                
                // Send reset email
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'app.andcorpautos.com';
                $resetLink = $protocol . '://' . $host . '/public/reset-password.php?token=' . $token;
                
                try {
                    $notification = new Notification();
                    $subject = "Password Reset Request - Andcorp Autos";
                    $message = "Hello {$user['first_name']},\n\n";
                    $message .= "We received a request to reset your password for your Andcorp Autos account.\n\n";
                    $message .= "To reset your password, click the link below:\n";
                    $message .= $resetLink . "\n\n";
                    $message .= "This link will expire in 1 hour.\n\n";
                    $message .= "If you did not request a password reset, please ignore this email and your password will remain unchanged.\n\n";
                    $message .= "For security reasons, never share this link with anyone.";
                    
                    $notification->create($user['id'], null, 'email', $subject, $message);
                    error_log("Password reset email sent to user ID {$user['id']}, email: {$email}");
                } catch (Exception $emailError) {
                    error_log("Failed to send password reset email: " . $emailError->getMessage());
                    // Don't fail the whole process if email fails
                }
                
                error_log("Password reset token generated for user ID {$user['id']}, email: {$email}");
            } else {
                error_log("Password reset requested for non-existent email: {$email}");
            }
            
            // Always show success message (security: don't reveal if email exists)
            setSuccess('If an account exists with that email address, you will receive a password reset link shortly. Please check your email.');
            clearOld();
            redirect(url('forgot-password.php'));
            
        } catch (PDOException $e) {
            error_log("Password reset PDO error: " . $e->getMessage());
            error_log("Password reset error trace: " . $e->getTraceAsString());
            setErrors(['general' => 'An error occurred. Please try again later.']);
        } catch (Exception $e) {
            error_log("Password reset general error: " . $e->getMessage());
            error_log("Password reset error trace: " . $e->getTraceAsString());
            setErrors(['general' => 'An unexpected error occurred. Please try again or contact support.']);
        }
    }
}

$title = "Forgot Password";
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
                        <h2 class="mt-3">Forgot Password?</h2>
                        <p class="text-muted">Enter your email address and we'll send you a link to reset your password.</p>
                    </div>

                    <?php if ($successMsg = success()): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="bi bi-check-circle"></i> <?php echo $successMsg; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($generalError = error('general')): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $generalError; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars(url('forgot-password.php')); ?>" method="POST">
                        <?php echo Security::csrfField(); ?>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" 
                                   class="form-control <?php echo error('email') ? 'is-invalid' : ''; ?>" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo old('email'); ?>" 
                                   placeholder="your.email@example.com"
                                   required>
                            <?php if (error('email')): ?>
                                <div class="invalid-feedback"><?php echo error('email'); ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                            <i class="bi bi-envelope"></i> Send Reset Link
                        </button>

                        <hr class="my-4">

                        <div class="text-center">
                            <p class="mb-2">
                                <a href="<?php echo url('login.php'); ?>" class="text-decoration-none">
                                    <i class="bi bi-arrow-left"></i> Back to Login
                                </a>
                            </p>
                            <p class="mb-0">
                                Don't have an account? <a href="<?php echo url('register.php'); ?>">Sign up</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php clearErrors(); clearOld(); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

