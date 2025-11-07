<?php
require_once '../../bootstrap.php';
Auth::requireStaff();

$ticketModel = new SupportTicket();

// Get ticket ID
$ticketId = Security::sanitizeInt($_GET['id'] ?? 0);

if (!$ticketId) {
    setErrors(['general' => 'Invalid ticket ID.']);
    redirect(url('admin/tickets.php'));
}

// Get ticket
$ticket = $ticketModel->findById($ticketId);

if (!$ticket) {
    setErrors(['general' => 'Ticket not found.']);
    redirect(url('admin/tickets.php'));
}

// Handle staff reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reply'])) {
    Security::generateToken();
    
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        setErrors(['general' => 'Invalid security token. Please try again.']);
        redirect(url('admin/tickets/view.php?id=' . $ticketId));
    }
    
    $message = Security::sanitizeString($_POST['message'] ?? '', 5000);
    
    if (empty($message)) {
        setErrors(['general' => 'Message is required']);
    } else {
        if ($ticketModel->addReply($ticketId, Auth::userId(), $message, true)) {
            setSuccess('Your reply has been sent successfully.');
            clearOld();
            redirect(url('admin/tickets/view.php?id=' . $ticketId));
        } else {
            setErrors(['general' => 'Failed to add reply. Please try again.']);
        }
    }
}

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        setErrors(['general' => 'Invalid security token']);
    } else {
        $newStatus = Security::sanitizeString($_POST['status'] ?? '', 20);
        $validStatuses = ['open', 'pending', 'resolved', 'closed'];
        
        if (in_array($newStatus, $validStatuses)) {
            if ($ticketModel->updateStatus($ticketId, $newStatus, Auth::userId())) {
                setSuccess('Ticket status updated to ' . ucfirst($newStatus));
                // Refresh ticket data
                $ticket = $ticketModel->findById($ticketId);
            } else {
                setErrors(['general' => 'Failed to update status']);
            }
        }
    }
}

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_ticket']) && Auth::isAdmin()) {
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        setErrors(['general' => 'Invalid security token']);
    } else {
        $staffId = Security::sanitizeInt($_POST['assigned_to'] ?? 0);
        
        if ($ticketModel->assignTo($ticketId, $staffId ?: null, Auth::userId())) {
            setSuccess('Ticket assignment updated');
            // Refresh ticket data
            $ticket = $ticketModel->findById($ticketId);
        } else {
            setErrors(['general' => 'Failed to update assignment']);
        }
    }
}

// Get ticket replies
$replies = $ticketModel->getReplies($ticketId);

// Get all staff users for assignment
$db = Database::getInstance()->getConnection();
$staffQuery = $db->query("SELECT id, first_name, last_name FROM users WHERE role IN ('admin', 'staff') ORDER BY first_name");
$staffUsers = $staffQuery->fetchAll(PDO::FETCH_ASSOC);

// Generate token for forms
Security::generateToken();

$title = "Ticket #" . $ticket['ticket_number'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - Andcorp Autos Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
</head>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container-modern">
        <div class="row">
            <div class="col-lg-12">
                <!-- Page Header -->
                <div class="page-header animate-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-5">Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?></h1>
                            <p class="lead mb-0"><?php echo htmlspecialchars($ticket['subject']); ?></p>
                        </div>
                        <div>
                            <a href="<?php echo url('admin/tickets.php'); ?>" class="btn btn-secondary btn-modern">
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
                                                        <div class="avatar-circle <?php echo $reply['is_staff_reply'] ? 'bg-primary' : 'bg-success'; ?>">
                                                            <i class="bi bi-<?php echo $reply['is_staff_reply'] ? 'person-badge' : 'person'; ?>"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="message-header mb-2">
                                                            <strong><?php echo htmlspecialchars($reply['first_name'] . ' ' . $reply['last_name']); ?></strong>
                                                            <?php if ($reply['is_staff_reply']): ?>
                                                                <span class="badge bg-primary ms-2">Staff</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-success ms-2">Customer</span>
                                                            <?php endif; ?>
                                                            <span class="text-muted ms-2">
                                                                <small><i class="bi bi-clock"></i> <?php echo formatDate($reply['created_at']); ?></small>
                                                            </span>
                                                        </div>
                                                        <div class="message-body p-3 rounded">
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
                        <div class="card-modern animate-in">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-reply"></i> Staff Reply</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?php echo htmlspecialchars(url('admin/tickets/view.php?id=' . $ticketId)); ?>">
                                    <?php echo Security::csrfField(); ?>
                                    <input type="hidden" name="add_reply" value="1">
                                    
                                    <div class="mb-3">
                                        <label for="message" class="form-label">Your Response</label>
                                        <textarea class="form-control" 
                                                  id="message" 
                                                  name="message" 
                                                  rows="5" 
                                                  required 
                                                  placeholder="Type your response to the customer..."><?php echo old('message'); ?></textarea>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-send"></i> Send Reply
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <!-- Customer Info -->
                        <div class="card-modern mb-3 animate-in">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-person"></i> Customer</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong><?php echo htmlspecialchars($ticket['customer_first_name'] . ' ' . $ticket['customer_last_name']); ?></strong></p>
                                <p class="mb-1 small"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($ticket['customer_email']); ?></p>
                                <?php if ($ticket['order_number']): ?>
                                    <p class="mb-0 small">
                                        <i class="bi bi-box-seam"></i> 
                                        <a href="<?php echo url('admin/orders/edit.php?id=' . $ticket['order_id']); ?>">
                                            <?php echo htmlspecialchars($ticket['order_number']); ?>
                                        </a>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Status Management -->
                        <div class="card-modern mb-3 animate-in">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-gear"></i> Manage Status</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <?php echo Security::csrfField(); ?>
                                    <input type="hidden" name="change_status" value="1">
                                    
                                    <div class="mb-3">
                                        <label class="form-label small">Current Status</label>
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
                                        <label for="status" class="form-label small">Change To</label>
                                        <select class="form-select form-select-sm" name="status" id="status">
                                            <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                            <option value="pending" <?php echo $ticket['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                            <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="btn btn-sm btn-primary w-100">
                                        <i class="bi bi-check-circle"></i> Update Status
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Assignment (Admin Only) -->
                        <?php if (Auth::isAdmin()): ?>
                            <div class="card-modern mb-3 animate-in">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bi bi-person-plus"></i> Assignment</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <?php echo Security::csrfField(); ?>
                                        <input type="hidden" name="assign_ticket" value="1">
                                        
                                        <div class="mb-3">
                                            <label class="form-label small">Assigned To</label>
                                            <div class="mb-2">
                                                <?php if ($ticket['assigned_first_name']): ?>
                                                    <span class="badge bg-info">
                                                        <?php echo htmlspecialchars($ticket['assigned_first_name'] . ' ' . $ticket['assigned_last_name']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted small">Unassigned</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="assigned_to" class="form-label small">Assign To</label>
                                            <select class="form-select form-select-sm" name="assigned_to" id="assigned_to">
                                                <option value="">-- Unassigned --</option>
                                                <?php foreach ($staffUsers as $staff): ?>
                                                    <option value="<?php echo $staff['id']; ?>" 
                                                            <?php echo $ticket['assigned_to'] == $staff['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <button type="submit" class="btn btn-sm btn-primary w-100">
                                            <i class="bi bi-check-circle"></i> Update Assignment
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Ticket Details -->
                        <div class="card-modern mb-3 animate-in">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
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
                                        <span class="badge bg-<?php echo $priorityClass; ?>"><?php echo ucfirst($ticket['priority']); ?></span>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="text-muted small">Category</label>
                                    <div><span class="badge bg-secondary"><?php echo ucfirst($ticket['category']); ?></span></div>
                                </div>

                                <div class="mb-2">
                                    <label class="text-muted small">Created</label>
                                    <div class="small"><?php echo formatDate($ticket['created_at']); ?></div>
                                </div>

                                <div class="mb-0">
                                    <label class="text-muted small">Last Updated</label>
                                    <div class="small"><?php echo formatDate($ticket['updated_at']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
        
        .customer-message .message-body {
            background: #d4edda;
            border-left-color: #28a745;
        }
        
        .badge-lg {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }
    </style>
</body>
</html>
<?php clearErrors(); clearOld(); ?>

