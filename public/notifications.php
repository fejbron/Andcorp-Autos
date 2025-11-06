<?php
require_once 'bootstrap.php';
Auth::requireAuth();

$notificationModel = new Notification();
$notifications = $notificationModel->getUserNotifications(Auth::userId(), false);

// Mark notification as read if ID is provided
if (isset($_GET['mark_read'])) {
    $notificationId = Security::sanitizeInt($_GET['mark_read']);
    if ($notificationId > 0) {
        // Verify ownership
        $notification = $notificationModel->getById($notificationId);
        if ($notification && $notification['user_id'] == Auth::userId()) {
            $notificationModel->markAsRead($notificationId);
            setSuccess('Notification marked as read');
        }
    }
    redirect(url('notifications.php'));
}

// Mark all as read
if (isset($_POST['mark_all_read'])) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        $errors['general'] = 'Invalid security token. Please try again.';
        setErrors($errors);
        redirect(url('notifications.php'));
    }
    
    foreach ($notifications as $notification) {
        if (!$notification['read_at'] && $notification['user_id'] == Auth::userId()) {
            $notificationModel->markAsRead($notification['id']);
        }
    }
    setSuccess('All notifications marked as read');
    redirect(url('notifications.php'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Andcorp Autos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
    <style>
        .notification-item {
            transition: background-color 0.3s;
        }
        .notification-item:hover {
            background-color: var(--primary-lighter);
        }
        .notification-unread {
            background-color: var(--primary-lighter);
            border-left: 4px solid var(--primary);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-4">
        <div class="page-header animate-in">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-5">Notifications</h1>
                    <p class="lead">Stay updated on your orders</p>
                </div>
                <div>
                    <?php if (count(array_filter($notifications, fn($n) => !$n['read_at'])) > 0): ?>
                        <form method="POST" action="<?php echo url('notifications.php'); ?>" class="d-inline">
                            <?php echo Security::csrfField(); ?>
                            <button type="submit" name="mark_all_read" class="btn btn-outline-primary btn-modern">
                                <i class="bi bi-check-all"></i> Mark All as Read
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($successMsg = success()): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?php echo $successMsg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?php if (empty($notifications)): ?>
                    <div class="card-modern animate-in">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-bell-slash" style="font-size: 4rem; color: #ccc;"></i>
                            <h3 class="mt-3">No Notifications</h3>
                            <p class="text-muted">You're all caught up! You'll receive notifications here when there are updates on your orders.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card-modern animate-in">
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="list-group-item notification-item <?php echo !$notification['read_at'] ? 'notification-unread' : ''; ?>">
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="bi bi-<?php echo $notification['type'] === 'email' ? 'envelope' : 'chat-dots'; ?> me-2"></i>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($notification['subject']); ?></h6>
                                                    <?php if (!$notification['read_at']): ?>
                                                        <span class="badge bg-primary ms-2">New</span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="bi bi-clock"></i> <?php echo formatDateTime($notification['created_at']); ?>
                                                        <?php if ($notification['order_id']): ?>
                                                            | <a href="<?php echo url('orders/view.php?id=' . $notification['order_id']); ?>">View Order</a>
                                                        <?php endif; ?>
                                                    </small>
                                                    <?php if (!$notification['read_at']): ?>
                                                        <a href="<?php echo url('notifications.php?mark_read=' . $notification['id']); ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-check"></i> Mark as Read
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (count($notifications) > 10): ?>
                        <div class="text-center mt-3">
                            <p class="text-muted">Showing last 50 notifications</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
