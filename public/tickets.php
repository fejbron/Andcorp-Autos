<?php
require_once 'bootstrap.php';
Auth::requireAuth();

$ticketModel = new SupportTicket();
$customerModel = new Customer();

// Get customer
$customer = $customerModel->findByUserId(Auth::userId());

if (!$customer) {
    setErrors(['general' => 'Customer account not found.']);
    redirect(url('dashboard.php'));
}

// Get filter
$statusFilter = Security::sanitizeString($_GET['status'] ?? '', 20);

// Get tickets
$tickets = $ticketModel->getByCustomer($customer['id'], $statusFilter ?: null, 50);

$title = "My Support Tickets";
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
    <?php include 'includes/navbar.php'; ?>

    <div class="container-modern">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <!-- Page Header -->
                <div class="page-header animate-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-5">Support Tickets</h1>
                            <p class="lead mb-0">View and manage your support requests</p>
                        </div>
                        <div>
                            <a href="<?php echo url('tickets/create.php'); ?>" class="btn btn-primary btn-modern">
                                <i class="bi bi-plus-circle"></i> New Ticket
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

                <!-- Status Filter -->
                <div class="card-modern mb-4 animate-in">
                    <div class="card-body">
                        <div class="btn-group" role="group">
                            <a href="<?php echo url('tickets.php'); ?>" 
                               class="btn btn-outline-primary <?php echo empty($statusFilter) ? 'active' : ''; ?>">
                                All Tickets
                            </a>
                            <a href="<?php echo url('tickets.php?status=open'); ?>" 
                               class="btn btn-outline-primary <?php echo $statusFilter === 'open' ? 'active' : ''; ?>">
                                Open
                            </a>
                            <a href="<?php echo url('tickets.php?status=pending'); ?>" 
                               class="btn btn-outline-primary <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                                Pending
                            </a>
                            <a href="<?php echo url('tickets.php?status=resolved'); ?>" 
                               class="btn btn-outline-primary <?php echo $statusFilter === 'resolved' ? 'active' : ''; ?>">
                                Resolved
                            </a>
                            <a href="<?php echo url('tickets.php?status=closed'); ?>" 
                               class="btn btn-outline-primary <?php echo $statusFilter === 'closed' ? 'active' : ''; ?>">
                                Closed
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tickets List -->
                <div class="card-modern animate-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-ticket-perforated"></i> Your Tickets</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($tickets)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox display-1 text-muted"></i>
                                <p class="text-muted mt-3">No tickets found</p>
                                <a href="<?php echo url('tickets/create.php'); ?>" class="btn btn-primary">
                                    Create Your First Ticket
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-modern table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Ticket #</th>
                                            <th>Subject</th>
                                            <th>Category</th>
                                            <th>Priority</th>
                                            <th>Status</th>
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
                                                    <?php echo htmlspecialchars($ticket['subject']); ?>
                                                    <?php if ($ticket['order_number']): ?>
                                                        <br><small class="text-muted">Order: <?php echo htmlspecialchars($ticket['order_number']); ?></small>
                                                    <?php endif; ?>
                                                </td>
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
                                                    <span class="badge bg-light text-dark"><?php echo $ticket['reply_count']; ?></span>
                                                </td>
                                                <td>
                                                    <small><?php echo formatDate($ticket['created_at']); ?></small>
                                                </td>
                                                <td>
                                                    <a href="<?php echo url('tickets/view.php?id=' . $ticket['id']); ?>" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
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

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php clearErrors(); clearOld(); ?>

