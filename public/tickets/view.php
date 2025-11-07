<?php
require_once '../bootstrap.php';
Auth::requireAuth();

$ticketModel = new SupportTicket();
$customerModel = new Customer();

// Get customer
$customer = $customerModel->findByUserId(Auth::userId());

if (!$customer) {
    setErrors(['general' => 'Customer account not found.']);
    redirect(url('dashboard.php'));
}

// Get ticket ID
$ticketId = Security::sanitizeInt($_GET['id'] ?? 0);

if (!$ticketId) {
    setErrors(['general' => 'Invalid ticket ID.']);
    redirect(url('tickets.php'));
}

// Get ticket
$ticket = $ticketModel->findById($ticketId);

if (!$ticket) {
    setErrors(['general' => 'Ticket not found.']);
    redirect(url('tickets.php'));
}

// Verify ticket belongs to customer
if ($ticket['customer_id'] != $customer['id']) {
    setErrors(['general' => 'Access denied.']);
    redirect(url('tickets.php'));
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reply'])) {
    Security::generateToken();
    
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        setErrors(['general' => 'Invalid security token. Please try again.']);
        redirect(url('tickets/view.php?id=' . $ticketId));
    }
    
    $message = Security::sanitizeString($_POST['message'] ?? '', 5000);
    
    if (empty($message)) {
        setErrors(['general' => 'Message is required']);
    } else {
        if ($ticketModel->addReply($ticketId, Auth::userId(), $message, false)) {
            setSuccess('Your reply has been added successfully.');
            clearOld();
            redirect(url('tickets/view.php?id=' . $ticketId));
        } else {
            setErrors(['general' => 'Failed to add reply. Please try again.']);
        }
    }
}

// Get ticket replies
$replies = $ticketModel->getReplies($ticketId);

// Generate token for form
Security::generateToken();

$title = "Ticket #" . $ticket['ticket_number'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - Andcorp Autos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container-modern">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <!-- Page Header -->
                <div class="page-header animate-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-5">Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?></h1>
                            <p class="lead mb-0"><?php echo htmlspecialchars($ticket['subject']); ?></p>
                        </div>
                        <div>
                            <a href="<?php echo url('tickets.php'); ?>" class="btn btn-secondary btn-modern">
                                <i class="bi bi-arrow-left"></i> Back to Tickets
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

                <?php if ($errorMsg = error('general')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $errorMsg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Main Content -->
                    <div class="col-lg-8">
                        <!-- Ticket Conversation -->
                        <div class="card-modern mb-4 animate-in">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-chat-left-text"></i> Conversation</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($replies)): ?>
                                    <p class="text-muted">No messages yet.</p>
                                <?php else: ?>
                                    <div class="ticket-conversation">
                                        <?php foreach ($replies as $reply): ?>
                                            <div class="message-item <?php echo $reply['is_staff_reply'] ? 'staff-message' : 'customer-message'; ?> mb-4">
                                                <div class="d-flex gap-3">
                                                    <div class="flex-shrink-0">
                                                        <div class="avatar-circle <?php echo $reply['is_staff_reply'] ? 'bg-primary' : 'bg-secondary'; ?>">
                                                            <i class="bi bi-<?php echo $reply['is_staff_reply'] ? 'person-badge' : 'person'; ?>"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="message-header mb-2">
                                                            <strong><?php echo htmlspecialchars($reply['first_name'] . ' ' . $reply['last_name']); ?></strong>
                                                            <?php if ($reply['is_staff_reply']): ?>
                                                                <span class="badge bg-primary ms-2">Staff</span>
                                                            <?php endif; ?>
                                                            <span class="text-muted ms-2">
                                                                <small><i class="bi bi-clock"></i> <?php echo formatDate($reply['created_at']); ?></small>
                                                            </span>
                                                        </div>
                                                        <div class="message-body p-3 bg-light rounded">
                                                            <?php echo nl2br(htmlspecialchars($reply['message'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Reply Form -->
                        <?php if ($ticket['status'] !== 'closed'): ?>
                            <div class="card-modern animate-in">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-reply"></i> Add Reply</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="<?php echo htmlspecialchars(url('tickets/view.php?id=' . $ticketId)); ?>">
                                        <?php echo Security::csrfField(); ?>
                                        <input type="hidden" name="add_reply" value="1">
                                        
                                        <div class="mb-3">
                                            <label for="message" class="form-label">Your Message</label>
                                            <textarea class="form-control" 
                                                      id="message" 
                                                      name="message" 
                                                      rows="5" 
                                                      required 
                                                      placeholder="Type your message here..."><?php echo old('message'); ?></textarea>
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-send"></i> Send Reply
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> This ticket is closed. You cannot add new replies.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <!-- Ticket Details -->
                        <div class="card-modern mb-4 animate-in">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Ticket Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="text-muted small">Status</label>
                                    <div>
                                        <?php
                                        $statusClass = match($ticket['status']) {
                                            'open' => 'primary',
                                            'pending' => 'warning',
                                            'resolved' => 'success',
                                            'closed' => 'secondary',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?> badge-lg"><?php echo ucfirst($ticket['status']); ?></span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Priority</label>
                                    <div>
                                        <?php
                                        $priorityClass = match($ticket['priority']) {
                                            'urgent' => 'danger',
                                            'high' => 'warning',
                                            'normal' => 'info',
                                            'low' => 'secondary',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $priorityClass; ?> badge-lg"><?php echo ucfirst($ticket['priority']); ?></span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Category</label>
                                    <div><span class="badge bg-secondary badge-lg"><?php echo ucfirst($ticket['category']); ?></span></div>
                                </div>

                                <?php if ($ticket['order_number']): ?>
                                    <div class="mb-3">
                                        <label class="text-muted small">Related Order</label>
                                        <div>
                                            <a href="<?php echo url('orders/view.php?id=' . $ticket['order_id']); ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-box-seam"></i> <?php echo htmlspecialchars($ticket['order_number']); ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label class="text-muted small">Created</label>
                                    <div><?php echo formatDate($ticket['created_at']); ?></div>
                                </div>

                                <div class="mb-0">
                                    <label class="text-muted small">Last Updated</label>
                                    <div><?php echo formatDate($ticket['updated_at']); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Help -->
                        <div class="card-modern animate-in" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="card-body text-white">
                                <h6 class="text-white mb-3"><i class="bi bi-info-circle"></i> Need Help?</h6>
                                <p class="small mb-3">Our support team typically responds within 24 hours.</p>
                                <p class="small mb-0">
                                    <i class="bi bi-envelope"></i> info@andcorpautos.com<br>
                                    <i class="bi bi-telephone"></i> +233 24 949 4091
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        .avatar-circle {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }
        
        .message-body {
            background: #f8f9fa;
            border-left: 3px solid #dee2e6;
        }
        
        .staff-message .message-body {
            background: #e7f1ff;
            border-left-color: #0d6efd;
        }
        
        .badge-lg {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }
    </style>
</body>
</html>
<?php clearErrors(); clearOld(); ?>

