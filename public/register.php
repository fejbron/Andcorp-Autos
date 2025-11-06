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
        redirect(url('register.php'));
    }
    
    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!Security::checkRateLimit("register_{$ip}", 3, 3600)) { // 3 attempts per hour
        $errors['general'] = 'Too many registration attempts. Please try again later.';
        setErrors($errors);
        setOld($_POST);
    } else {
        $validator = new Validator();
        
        // Validate all fields
        $validator->required('first_name', $_POST['first_name'] ?? '')
                  ->maxLength('first_name', $_POST['first_name'] ?? '', 100);
                  
        $validator->required('last_name', $_POST['last_name'] ?? '')
                  ->maxLength('last_name', $_POST['last_name'] ?? '', 100);
                  
        $validator->required('email', $_POST['email'] ?? '')
                  ->email('email', $_POST['email'] ?? '')
                  ->maxLength('email', $_POST['email'] ?? '', 255);
                  
        $validator->required('phone', $_POST['phone'] ?? '')
                  ->phone('phone', $_POST['phone'] ?? '');
                  
        $validator->required('password', $_POST['password'] ?? '')
                  ->password('password', $_POST['password'] ?? '');
                  
        $validator->required('password_confirmation', $_POST['password_confirmation'] ?? '')
                  ->match('password_confirmation', $_POST['password_confirmation'] ?? '', $_POST['password'] ?? '');
        
        $errors = $validator->getErrors();
        
        // Check if email already exists
        if (!isset($errors['email']) && !empty($_POST['email'])) {
            $userModel = new User();
            if ($userModel->findByEmail($_POST['email'])) {
                $errors['email'] = 'Email already exists';
            }
        }
    
    if (empty($errors)) {
        try {
            // Create user (inputs already validated)
            $userModel = new User();
            $userId = $userModel->create([
                'email' => $_POST['email'],
                'password' => $_POST['password'],
                'role' => 'customer',
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'phone' => $_POST['phone']
            ]);
            
            // Create customer profile
            $customerModel = new Customer();
            $customerModel->create([
                'user_id' => $userId,
                'address' => $_POST['address'] ?? null,
                'city' => $_POST['city'] ?? null,
                'country' => 'Ghana'
            ]);
            
            // Auto login (use validated email)
            $validEmail = Security::validateEmail($_POST['email']);
            if ($validEmail && Auth::login($validEmail, $_POST['password'])) {
                clearOld();
                setSuccess('Account created successfully! Welcome to AndCorp.');
                redirect(url('dashboard.php'));
            } else {
                $errors['general'] = 'Account created but login failed. Please try logging in.';
            }
        } catch (InvalidArgumentException $e) {
            $errors['general'] = $e->getMessage();
        } catch (Exception $e) {
            $errors['general'] = 'An error occurred. Please try again.';
            error_log("Registration error: " . $e->getMessage());
        }
    }
    
    setErrors($errors);
    setOld($_POST);
    } // Close the else block from line 22
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Andcorp Autos</title>
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
            padding: 50px 0;
        }
        .register-card {
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-card">
            <div class="card-modern">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <img src="<?php echo url('assets/images/logo.png'); ?>" alt="AndCorp Autos" style="max-width: 250px; width: 100%; height: auto; margin-bottom: 1rem;">
                        <h2 class="mt-3">Create Account</h2>
                        <p class="text-muted">Start your car import journey today</p>
                    </div>
                    
                    <?php if (error('general')): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo error('general'); ?>
                        </div>
                    <?php endif; ?>
                    
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <?php echo Security::csrfField(); ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control <?php echo hasError('first_name') ? 'is-invalid' : ''; ?>" 
                                       id="first_name" name="first_name" value="<?php echo old('first_name'); ?>" required>
                                <?php if (error('first_name')): ?>
                                    <div class="invalid-feedback"><?php echo error('first_name'); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control <?php echo hasError('last_name') ? 'is-invalid' : ''; ?>" 
                                       id="last_name" name="last_name" value="<?php echo old('last_name'); ?>" required>
                                <?php if (error('last_name')): ?>
                                    <div class="invalid-feedback"><?php echo error('last_name'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control <?php echo hasError('email') ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" value="<?php echo old('email'); ?>" required>
                            <?php if (error('email')): ?>
                                <div class="invalid-feedback"><?php echo error('email'); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control <?php echo hasError('phone') ? 'is-invalid' : ''; ?>" 
                                   id="phone" name="phone" value="<?php echo old('phone'); ?>" 
                                   placeholder="+233 123 456 789" required>
                            <?php if (error('phone')): ?>
                                <div class="invalid-feedback"><?php echo error('phone'); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address (Optional)</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?php echo old('address'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="city" class="form-label">City (Optional)</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo old('city'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control <?php echo hasError('password') ? 'is-invalid' : ''; ?>" 
                                   id="password" name="password" required>
                            <?php if (error('password')): ?>
                                <div class="invalid-feedback"><?php echo error('password'); ?></div>
                            <?php endif; ?>
                            <div class="form-text">Minimum 8 characters</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control <?php echo hasError('password_confirmation') ? 'is-invalid' : ''; ?>" 
                                   id="password_confirmation" name="password_confirmation" required>
                            <?php if (error('password_confirmation')): ?>
                                <div class="invalid-feedback"><?php echo error('password_confirmation'); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="bi bi-person-plus"></i> Create Account
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-0">Already have an account? <a href="<?php echo url('login.php'); ?>">Sign in</a></p>
                        <p class="mt-2"><a href="<?php echo url('/'); ?>">Back to Home</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php clearErrors(); clearOld(); ?>
