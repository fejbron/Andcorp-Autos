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

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get tickets with pagination
if ($searchQuery) {
    $tickets = $ticketModel->search($searchQuery);
    $totalTickets = count($tickets);
    $tickets = array_slice($tickets, $offset, $perPage);
    $totalPages = ceil($totalTickets / $perPage);
} else {
    // Get total count for pagination
    $db = Database::getInstance()->getConnection();
    $countSql = "SELECT COUNT(*) as total FROM support_tickets";
    if ($statusFilter) {
        $countSql .= " WHERE status = :status";
    }
    $countStmt = $db->prepare($countSql);
    if ($statusFilter) {
        $countStmt->execute([':status' => $statusFilter]);
    } else {
        $countStmt->execute();
    }
    $totalTickets = $countStmt->fetch()['total'];
    $totalPages = ceil($totalTickets / $perPage);
    
    $tickets = $ticketModel->getAll($statusFilter ?: null, $perPage, $offset);
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
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card primary animate-in">
                            <div class="stat-icon">
                                <i class="bi bi-ticket-perforated"></i>
                            </div>
                            <h3><?php echo $stats['total'] ?? 0; ?></h3>
                            <p>Total Tickets</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card info animate-in">
                            <div class="stat-icon">
                                <i class="bi bi-envelope-open"></i>
                            </div>
                            <h3><?php echo $stats['open_count'] ?? 0; ?></h3>
                            <p>Open Tickets</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card warning animate-in">
                            <div class="stat-icon">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                            <h3><?php echo $stats['pending_count'] ?? 0; ?></h3>
                            <p>Pending Response</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card danger animate-in">
                            <div class="stat-icon">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <h3><?php echo $stats['urgent_count'] ?? 0; ?></h3>
                            <p>Urgent Priority</p>
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
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Support Tickets</h5>
                        <span class="badge bg-primary">
                            <?php echo $totalTickets; ?> total
                        </span>
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

                            <!-- Pagination Controls -->
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="Tickets pagination" class="mt-4 px-3 pb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="text-muted">
                                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $perPage, $totalTickets); ?> of <?php echo $totalTickets; ?> tickets
                                        </div>
                                        <ul class="pagination mb-0">
                                            <!-- Previous Button -->
                                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($searchQuery); ?>" 
                                                   aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>

                                            <!-- Page Numbers -->
                                            <?php
                                            $startPage = max(1, $page - 2);
                                            $endPage = min($totalPages, $page + 2);
                                            
                                            // Show first page if not in range
                                            if ($startPage > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=1&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($searchQuery); ?>">1</a>
                                                </li>
                                                <?php if ($startPage > 2): ?>
                                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($searchQuery); ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>

                                            <!-- Show last page if not in range -->
                                            <?php if ($endPage < $totalPages): ?>
                                                <?php if ($endPage < $totalPages - 1): ?>
                                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                                <?php endif; ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $totalPages; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($searchQuery); ?>">
                                                        <?php echo $totalPages; ?>
                                                    </a>
                                                </li>
                                            <?php endif; ?>

                                            <!-- Next Button -->
                                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($searchQuery); ?>" 
                                                   aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </nav>
                            <?php endif; ?>
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

