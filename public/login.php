<?php
require_once 'bootstrap.php';

if (Auth::check()) {
    redirect(url('dashboard.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        $errors['general'] = 'Invalid security token. Please try again.';
        setErrors($errors);
        redirect(url('login.php'));
    }
    
    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!Security::checkRateLimit("login_{$ip}", 5, 900)) { // 5 attempts per 15 minutes
        $errors['login'] = 'Too many login attempts. Please try again in 15 minutes.';
        setErrors($errors);
        setOld($_POST);
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $errors = [];
        
        // Validate email
        $validEmail = Security::validateEmail($email);
        if (!$validEmail) {
            $errors['email'] = 'Please enter a valid email address';
        }
        
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        }
        
        if (empty($errors)) {
            if (Auth::login($validEmail, $password)) {
                $role = Auth::userRole();
                if ($role === 'customer') {
                    redirect(url('dashboard.php'));
                } else {
                    redirect(url('admin/dashboard.php'));
                }
            } else {
                $errors['login'] = 'Invalid email or password';
            }
        }
        
        setErrors($errors);
        setOld($_POST);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Andcorp Autos</title>
    <link rel="icon" type="image/png" href="<?php echo url('assets/images/favicon.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo url('assets/images/logo.png'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
    <style>
        body {
            background: var(--bg-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            max-width: 450px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="card-modern">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <img src="<?php echo url('assets/images/logo.png'); ?>" alt="AndCorp Autos" style="max-width: 250px; width: 100%; height: auto; margin-bottom: 1rem;">
                        <h2 class="mt-3">Welcome Back</h2>
                        <p class="text-muted">Sign in to your AndCorp Autos account</p>
                    </div>
                    
                    <?php if (error('login')): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo error('login'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <?php echo Security::csrfField(); ?>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control <?php echo hasError('email') ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" value="<?php echo old('email'); ?>" required>
                            <?php if (error('email')): ?>
                                <div class="invalid-feedback"><?php echo error('email'); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control <?php echo hasError('password') ? 'is-invalid' : ''; ?>" 
                                   id="password" name="password" required>
                            <?php if (error('password')): ?>
                                <div class="invalid-feedback"><?php echo error('password'); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="bi bi-box-arrow-in-right"></i> Sign In
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-0">Don't have an account? <a href="<?php echo url('register.php'); ?>">Sign up</a></p>
                        <p class="mt-2"><a href="<?php echo url('/'); ?>">Back to Home</a></p>
                    </div>
                </div>
            </div>
            
            <!-- Demo Credentials -->
            <div class="card-modern mt-3">
                <div class="card-body">
                    <p class="mb-2 small"><strong>Demo Credentials:</strong></p>
                    <p class="mb-1 small">Admin: admin@andcorp.com / admin123</p>
                    <p class="mb-0 small">Customer: customer@example.com / customer123</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php clearErrors(); clearOld(); ?>
