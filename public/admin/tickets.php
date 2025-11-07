<?php
require_once '../bootstrap.php';
Auth::requireStaff();

$ticketModel = new SupportTicket();

// Handle delete request (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ticket']) && Auth::isAdmin()) {
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        setErrors(['general' => 'Invalid security token']);
    } else {
        $ticketId = Security::sanitizeInt($_POST['ticket_id'] ?? 0);
        if ($ticketId > 0) {
            try {
                if ($ticketModel->delete($ticketId)) {
                    setSuccess('Ticket deleted successfully');
                } else {
                    setErrors(['general' => 'Failed to delete ticket']);
                }
            } catch (Exception $e) {
                setErrors(['general' => 'Error deleting ticket: ' . $e->getMessage()]);
            }
        }
    }
    redirect(url('admin/tickets.php'));
}

// Get filter parameters
$statusFilter = Security::sanitizeString($_GET['status'] ?? '', 20);
$searchQuery = Security::sanitizeString($_GET['search'] ?? '', 255);

// Get tickets
if ($searchQuery) {
    $tickets = $ticketModel->search($searchQuery);
} else {
    $tickets = $ticketModel->getAll($statusFilter ?: null, 100, 0);
}

// Get statistics
$stats = $ticketModel->getStats();

$title = "Support Tickets Management";
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
    <?php include '../includes/navbar.php'; ?>

    <div class="container-modern">
        <div class="row">
            <div class="col-lg-12">
                <!-- Page Header -->
                <div class="page-header animate-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-5">Support Tickets</h1>
                            <p class="lead mb-0">Manage customer support requests</p>
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

                <!-- Statistics Cards -->
                <div class="row g-3 mb-4 animate-in">
                    <div class="col-lg-3 col-md-6">
                        <div class="card-modern border-0 h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="card-body text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50 mb-1">Total Tickets</h6>
                                        <h2 class="mb-0 fw-bold"><?php echo $stats['total'] ?? 0; ?></h2>
                                    </div>
                                    <div class="fs-1 opacity-50">
                                        <i class="bi bi-ticket-perforated"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="card-modern border-0 h-100" style="background: linear-gradient(135deg, #5e72e4 0%, #825ee4 100%);">
                            <div class="card-body text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50 mb-1">Open</h6>
                                        <h2 class="mb-0 fw-bold"><?php echo $stats['open_count'] ?? 0; ?></h2>
                                    </div>
                                    <div class="fs-1 opacity-50">
                                        <i class="bi bi-envelope-open"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="card-modern border-0 h-100" style="background: linear-gradient(135deg, #fb6340 0%, #fbb140 100%);">
                            <div class="card-body text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50 mb-1">Pending</h6>
                                        <h2 class="mb-0 fw-bold"><?php echo $stats['pending_count'] ?? 0; ?></h2>
                                    </div>
                                    <div class="fs-1 opacity-50">
                                        <i class="bi bi-hourglass-split"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="card-modern border-0 h-100" style="background: linear-gradient(135deg, #f5365c 0%, #f56036 100%);">
                            <div class="card-body text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50 mb-1">Urgent</h6>
                                        <h2 class="mb-0 fw-bold"><?php echo $stats['urgent_count'] ?? 0; ?></h2>
                                    </div>
                                    <div class="fs-1 opacity-50">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card-modern mb-4 animate-in">
                    <div class="card-body">
                        <div class="row g-3 align-items-center">
                            <div class="col-lg-7">
                                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? 'tickets.php'); ?>" class="d-flex gap-2">
                                    <input type="text" 
                                           class="form-control" 
                                           name="search" 
                                           placeholder="Search by ticket #, customer name, email, or order #..." 
                                           value="<?php echo htmlspecialchars($searchQuery); ?>">
                                    <button type="submit" class="btn btn-primary flex-shrink-0">
                                        <i class="bi bi-search"></i> Search
                                    </button>
                                </form>
                            </div>
                            <div class="col-lg-5">
                                <div class="btn-group w-100" role="group">
                                    <a href="<?php echo url('admin/tickets.php'); ?>" 
                                       class="btn btn-outline-primary btn-sm <?php echo empty($statusFilter) && empty($searchQuery) ? 'active' : ''; ?>">
                                        All
                                    </a>
                                    <a href="<?php echo url('admin/tickets.php?status=open'); ?>" 
                                       class="btn btn-outline-primary btn-sm <?php echo $statusFilter === 'open' ? 'active' : ''; ?>">
                                        Open
                                    </a>
                                    <a href="<?php echo url('admin/tickets.php?status=pending'); ?>" 
                                       class="btn btn-outline-primary btn-sm <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                                        Pending
                                    </a>
                                    <a href="<?php echo url('admin/tickets.php?status=resolved'); ?>" 
                                       class="btn btn-outline-primary btn-sm <?php echo $statusFilter === 'resolved' ? 'active' : ''; ?>">
                                        Resolved
                                    </a>
                                    <a href="<?php echo url('admin/tickets.php?status=closed'); ?>" 
                                       class="btn btn-outline-primary btn-sm <?php echo $statusFilter === 'closed' ? 'active' : ''; ?>">
                                        Closed
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tickets Table -->
                <div class="card-modern animate-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Support Tickets</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($tickets)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox display-1 text-muted"></i>
                                <p class="text-muted mt-3">No tickets found</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-modern table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Ticket #</th>
                                            <th>Customer</th>
                                            <th>Subject</th>
                                            <th>Category</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Assigned To</th>
                                            <th>Replies</th>
                                            <th>Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tickets as $ticket): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($ticket['ticket_number']); ?></strong>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($ticket['customer_first_name'] . ' ' . $ticket['customer_last_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($ticket['customer_email']); ?></small>
                                                    <?php if ($ticket['order_number']): ?>
                                                        <br><small class="badge bg-light text-dark">Order: <?php echo htmlspecialchars($ticket['order_number']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo ucfirst($ticket['category']); ?></span>
                                                </td>
                                                <td>
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
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = match($ticket['status']) {
                                                        'open' => 'primary',
                                                        'pending' => 'warning',
                                                        'resolved' => 'success',
                                                        'closed' => 'secondary',
                                                        default => 'secondary'
                                                    };
                                                    ?>
                                                    <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($ticket['status']); ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($ticket['assigned_first_name']): ?>
                                                        <small><?php echo htmlspecialchars($ticket['assigned_first_name'] . ' ' . $ticket['assigned_last_name']); ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted">Unassigned</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark"><?php echo $ticket['reply_count']; ?></span>
                                                </td>
                                                <td>
                                                    <small><?php echo formatDate($ticket['created_at']); ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="<?php echo url('admin/tickets/view.php?id=' . $ticket['id']); ?>" 
                                                           class="btn btn-outline-info" 
                                                           title="View & Reply">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <?php if (Auth::isAdmin()): ?>
                                                            <button type="button" class="btn btn-outline-danger" 
                                                                    onclick="confirmDelete(<?php echo $ticket['id']; ?>, '<?php echo htmlspecialchars($ticket['ticket_number'], ENT_QUOTES); ?>')" 
                                                                    title="Delete">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <?php echo Security::csrfField(); ?>
        <input type="hidden" name="delete_ticket" value="1">
        <input type="hidden" name="ticket_id" id="deleteTicketId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(ticketId, ticketNumber) {
            if (confirm(`Are you sure you want to delete ticket "${ticketNumber}"?\n\nThis action cannot be undone and will permanently remove the ticket and all its replies.\n\nConfirm deletion?`)) {
                document.getElementById('deleteTicketId').value = ticketId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
<?php clearErrors(); clearOld(); ?>

