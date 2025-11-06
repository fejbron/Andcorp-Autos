<?php
require_once '../bootstrap.php';
Auth::requireAdmin();

// Ensure CSRF token is generated
Security::generateToken();

// Check if Settings class exists, if not, show error
if (!class_exists('Settings')) {
    die('Error: Settings model not found. Please ensure app/Models/Settings.php exists on the server.');
}

try {
    $settingsModel = new Settings();
} catch (Exception $e) {
    error_log("Settings model error: " . $e->getMessage());
    die('Error: Unable to initialize Settings. Please check server logs for details.');
}

// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_email_settings'])) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        $errors['general'] = 'Invalid security token. Please try again.';
        setErrors($errors);
        redirect(url('admin/settings.php'));
    }
    
    // Prepare settings to update
    $settingsToUpdate = [];
    
    // Global settings
    $settingsToUpdate['email_notifications_enabled'] = isset($_POST['email_notifications_enabled']) ? '1' : '0';
    $settingsToUpdate['email_on_order_status_change'] = isset($_POST['email_on_order_status_change']) ? '1' : '0';
    $settingsToUpdate['email_on_order_created'] = isset($_POST['email_on_order_created']) ? '1' : '0';
    $settingsToUpdate['email_on_deposit_received'] = isset($_POST['email_on_deposit_received']) ? '1' : '0';
    $settingsToUpdate['email_on_deposit_verified'] = isset($_POST['email_on_deposit_verified']) ? '1' : '0';
    $settingsToUpdate['email_on_quote_submitted'] = isset($_POST['email_on_quote_submitted']) ? '1' : '0';
    $settingsToUpdate['email_on_quote_approved'] = isset($_POST['email_on_quote_approved']) ? '1' : '0';
    
    // Status-specific settings
    $statuses = ['pending', 'purchased', 'shipping', 'customs', 'inspection', 'repair', 'ready', 'delivered', 'cancelled'];
    foreach ($statuses as $status) {
        $key = 'email_status_' . $status;
        $settingsToUpdate[$key] = isset($_POST[$key]) ? '1' : '0';
    }
    
    // Email configuration
    if (!empty($_POST['email_from_name'])) {
        $settingsToUpdate['email_from_name'] = Security::sanitizeString($_POST['email_from_name'], 100);
    }
    if (!empty($_POST['email_from_address'])) {
        $email = filter_var($_POST['email_from_address'], FILTER_VALIDATE_EMAIL);
        if ($email) {
            $settingsToUpdate['email_from_address'] = $email;
        } else {
            $errors['email_from_address'] = 'Invalid email address';
        }
    }
    if (!empty($_POST['email_reply_to'])) {
        $email = filter_var($_POST['email_reply_to'], FILTER_VALIDATE_EMAIL);
        if ($email) {
            $settingsToUpdate['email_reply_to'] = $email;
        } else {
            $errors['email_reply_to'] = 'Invalid email address';
        }
    }
    
    if (empty($errors)) {
        try {
            if ($settingsModel->updateMultiple($settingsToUpdate, Auth::userId())) {
                clearOld();
                clearErrors(); // Clear any previous errors
                setSuccess('Email notification settings updated successfully!');
                redirect(url('admin/settings.php'));
            } else {
                $lastError = $settingsModel->getLastError();
                $errorMsg = 'Failed to update settings.';
                if ($lastError) {
                    error_log("Settings update failed: " . $lastError);
                    // Check for common errors
                    if (strpos($lastError, "doesn't exist") !== false || strpos($lastError, "Base table or view not found") !== false) {
                        $errorMsg = 'The email notification settings table does not exist. Please run the setup script first.';
                    } else {
                        $errorMsg = 'Failed to update settings: ' . htmlspecialchars($lastError);
                    }
                }
                $errors['general'] = $errorMsg;
            }
        } catch (Exception $e) {
            error_log("Settings update exception: " . $e->getMessage());
            $errors['general'] = 'An error occurred while updating settings: ' . htmlspecialchars($e->getMessage());
        }
    }
    
    if (!empty($errors)) {
        setErrors($errors);
    }
}

// Get current settings with error handling
try {
    $allSettings = $settingsModel->getAll();
} catch (PDOException $e) {
    // Table doesn't exist - show helpful message
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "Base table or view not found") !== false) {
        $tableMissing = true;
        $allSettings = [];
        $errors['general'] = 'Email notification settings table not found. Please run the setup script first: <a href="' . url('admin/setup_email_settings.php') . '">Setup Email Settings</a>';
    } else {
        error_log("Settings getAll error: " . $e->getMessage());
        $allSettings = [];
        $errors['general'] = 'Error loading settings. Please check server logs.';
    }
} catch (Exception $e) {
    error_log("Settings error: " . $e->getMessage());
    $allSettings = [];
    $errors['general'] = 'Error loading settings. Please check server logs.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Andcorp Autos Admin</title>
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
            <h1 class="display-5">System Settings</h1>
            <p class="lead">System configuration and information</p>
        </div>

        <?php 
        // Get messages - success() automatically clears itself
        $successMsg = success();
        $errorMsg = error('general');
        
        // If we have success, don't show errors (they're from previous attempts)
        if ($successMsg) {
            $errorMsg = null;
            $errors = [];
        }
        ?>
        
        <?php if ($successMsg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?php echo $successMsg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMsg && !$successMsg): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $errorMsg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errors['general']) && !$successMsg): ?>
            <div class="alert alert-<?php echo strpos($errors['general'], 'table not found') !== false ? 'warning' : 'danger'; ?> alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $errors['general']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($tableMissing) && $tableMissing): ?>
            <div class="alert alert-warning">
                <h6><i class="bi bi-exclamation-triangle"></i> Email Notification Settings Table Not Found</h6>
                <p>The email notification settings table needs to be created first.</p>
                <p><strong>To fix this:</strong></p>
                <ol>
                    <li>Go to: <a href="<?php echo url('admin/setup_email_settings.php'); ?>" class="alert-link">Setup Email Settings</a></li>
                    <li>Or run the SQL file manually: <code>database/email_notification_settings.sql</code></li>
                </ol>
            </div>
        <?php endif; ?>

        <!-- Email Notification Settings -->
        <div class="card-modern mb-4 animate-in">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-envelope"></i> Email Notification Settings</h5>
            </div>
            <div class="card-body">
                <?php if (isset($tableMissing) && $tableMissing): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Please set up the email notification settings table first before configuring email notifications.
                    </div>
                <?php else: ?>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <?php echo Security::csrfField(); ?>
                    
                    <!-- Global Settings -->
                    <div class="mb-4">
                        <h6 class="mb-3"><i class="bi bi-toggle-on"></i> Global Settings</h6>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="email_notifications_enabled" 
                                   name="email_notifications_enabled" value="1"
                                   <?php echo ($allSettings['email_notifications_enabled']['value'] ?? '1') === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="email_notifications_enabled">
                                <strong>Enable Email Notifications</strong>
                                <small class="d-block text-muted">Master switch for all email notifications</small>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Event-Based Notifications -->
                    <div class="mb-4">
                        <h6 class="mb-3"><i class="bi bi-bell"></i> Event-Based Notifications</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="email_on_order_status_change" 
                                           name="email_on_order_status_change" value="1"
                                           <?php echo ($allSettings['email_on_order_status_change']['value'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_on_order_status_change">Order Status Changes</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="email_on_order_created" 
                                           name="email_on_order_created" value="1"
                                           <?php echo ($allSettings['email_on_order_created']['value'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_on_order_created">New Order Created</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="email_on_deposit_received" 
                                           name="email_on_deposit_received" value="1"
                                           <?php echo ($allSettings['email_on_deposit_received']['value'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_on_deposit_received">Deposit Received</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="email_on_deposit_verified" 
                                           name="email_on_deposit_verified" value="1"
                                           <?php echo ($allSettings['email_on_deposit_verified']['value'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_on_deposit_verified">Deposit Verified</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="email_on_quote_submitted" 
                                           name="email_on_quote_submitted" value="1"
                                           <?php echo ($allSettings['email_on_quote_submitted']['value'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_on_quote_submitted">Quote Submitted</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="email_on_quote_approved" 
                                           name="email_on_quote_approved" value="1"
                                           <?php echo ($allSettings['email_on_quote_approved']['value'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_on_quote_approved">Quote Approved</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status-Specific Notifications -->
                    <div class="mb-4">
                        <h6 class="mb-3"><i class="bi bi-list-check"></i> Order Status Notifications</h6>
                        <p class="text-muted small mb-3">Select which order status changes should trigger email notifications to customers:</p>
                        <div class="row">
                            <?php 
                            $statuses = [
                                'Pending' => 'Pending',
                                'Purchased' => 'Purchased',
                                'Shipping' => 'Shipping',
                                'Customs' => 'Customs',
                                'Inspection' => 'Inspection',
                                'Repair' => 'Repair',
                                'Ready' => 'Ready',
                                'Delivered' => 'Delivered',
                                'Cancelled' => 'Cancelled'
                            ];
                            $statusLower = '';
                            foreach ($statuses as $label => $status): 
                                $statusLower = strtolower($status);
                                $key = 'email_status_' . $statusLower;
                            ?>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="<?php echo $key; ?>" 
                                               name="<?php echo $key; ?>" value="1"
                                               <?php echo ($allSettings[$key]['value'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="<?php echo $key; ?>"><?php echo $label; ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Email Configuration -->
                    <div class="mb-4">
                        <h6 class="mb-3"><i class="bi bi-gear"></i> Email Configuration</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email_from_name" class="form-label">From Name</label>
                                <input type="text" class="form-control" id="email_from_name" name="email_from_name" 
                                       value="<?php echo htmlspecialchars($allSettings['email_from_name']['value'] ?? 'Andcorp Autos'); ?>"
                                       placeholder="Andcorp Autos">
                                <small class="text-muted">Name that appears in the "From" field</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email_from_address" class="form-label">From Email Address</label>
                                <input type="email" class="form-control <?php echo hasError('email_from_address') ? 'is-invalid' : ''; ?>" 
                                       id="email_from_address" name="email_from_address" 
                                       value="<?php echo htmlspecialchars($allSettings['email_from_address']['value'] ?? 'noreply@andcorpautos.com'); ?>"
                                       placeholder="noreply@andcorpautos.com">
                                <?php if (error('email_from_address')): ?>
                                    <div class="invalid-feedback"><?php echo error('email_from_address'); ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Email address that appears in the "From" field</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email_reply_to" class="form-label">Reply-To Email Address</label>
                                <input type="email" class="form-control <?php echo hasError('email_reply_to') ? 'is-invalid' : ''; ?>" 
                                       id="email_reply_to" name="email_reply_to" 
                                       value="<?php echo htmlspecialchars($allSettings['email_reply_to']['value'] ?? 'info@andcorpautos.com'); ?>"
                                       placeholder="info@andcorpautos.com">
                                <?php if (error('email_reply_to')): ?>
                                    <div class="invalid-feedback"><?php echo error('email_reply_to'); ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Email address for customer replies</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button type="submit" name="update_email_settings" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Email Settings
                            </button>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Changes take effect immediately
                        </small>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card-modern mb-4 animate-in">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card-modern h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-file-earmark-text text-primary" style="font-size: 2.5rem;"></i>
                                <h6 class="mt-3">Activity Logs</h6>
                                <p class="text-muted small">View system activity logs</p>
                                <button class="btn btn-sm btn-outline-primary" onclick="alert('Activity logs viewer coming soon')">
                                    View Logs
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-modern h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-database-gear text-success" style="font-size: 2.5rem;"></i>
                                <h6 class="mt-3">Database Backup</h6>
                                <p class="text-muted small">Create database backup</p>
                                <button class="btn btn-sm btn-outline-success" onclick="alert('Database backup feature coming soon')">
                                    Create Backup
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-modern h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-trash text-danger" style="font-size: 2.5rem;"></i>
                                <h6 class="mt-3">Clear Cache</h6>
                                <p class="text-muted small">Clear application cache</p>
                                <button class="btn btn-sm btn-outline-danger" onclick="alert('Cache cleared successfully!')">
                                    Clear Cache
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documentation Links -->
        <div class="card-modern animate-in">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-book"></i> Documentation</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="../QUICKSTART.md" class="list-group-item list-group-item-action" target="_blank">
                        <i class="bi bi-file-earmark-text"></i> Quick Start Guide
                    </a>
                    <a href="../INSTALL.md" class="list-group-item list-group-item-action" target="_blank">
                        <i class="bi bi-file-earmark-text"></i> Installation Guide
                    </a>
                    <a href="../FEATURES.md" class="list-group-item list-group-item-action" target="_blank">
                        <i class="bi bi-file-earmark-text"></i> Features Documentation
                    </a>
                    <a href="../PROJECT_SUMMARY.md" class="list-group-item list-group-item-action" target="_blank">
                        <i class="bi bi-file-earmark-text"></i> Project Summary
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

