<?php
require_once '../bootstrap.php';
Auth::requireStaff();

$quoteRequestModel = new QuoteRequest();

// Get filter parameters
$statusFilter = Security::sanitizeString($_GET['status'] ?? '', 20);
$searchQuery = Security::sanitizeString($_GET['search'] ?? '', 255);

// Get quote requests
if ($searchQuery) {
    $quoteRequests = $quoteRequestModel->search($searchQuery);
} else {
    $quoteRequests = $quoteRequestModel->getAll($statusFilter ?: null, 100, 0);
}

// Get status counts
$statusCounts = $quoteRequestModel->getStatusCounts();
$pendingCount = $quoteRequestModel->getPendingCount();

$title = "Quote Requests Management";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Requests - Andcorp Autos</title>
    <link rel="icon" type="image/png" href="<?php echo url('assets/images/favicon.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo url('assets/images/logo.png'); ?>">
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
        <div class="row">
            <div class="col-lg-11 mx-auto">
                <!-- Page Header -->
                <div class="page-header animate-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-5">Quote Requests</h1>
                            <p class="lead mb-0">Manage customer quote requests and create orders</p>
                        </div>
                    </div>
                </div>

                <?php if ($successMsg = success()): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i> <?php echo $successMsg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card warning animate-in">
                            <div class="stat-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <h3><?php echo $statusCounts['pending'] ?? 0; ?></h3>
                            <p>Pending Review</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card info animate-in">
                            <div class="stat-icon">
                                <i class="bi bi-eye"></i>
                            </div>
                            <h3><?php echo $statusCounts['reviewing'] ?? 0; ?></h3>
                            <p>Under Review</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card primary animate-in">
                            <div class="stat-icon">
                                <i class="bi bi-file-earmark-check"></i>
                            </div>
                            <h3><?php echo $statusCounts['quoted'] ?? 0; ?></h3>
                            <p>Quote Ready</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card success animate-in">
                            <div class="stat-icon">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <h3><?php echo $statusCounts['converted'] ?? 0; ?></h3>
                            <p>Converted to Orders</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card-modern mb-4">
                    <div class="card-body">
                        <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                                    <option value="reviewing" <?php echo $statusFilter === 'reviewing' ? 'selected' : ''; ?>>Under Review</option>
                                    <option value="quoted" <?php echo $statusFilter === 'quoted' ? 'selected' : ''; ?>>Quote Ready</option>
                                    <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="converted" <?php echo $statusFilter === 'converted' ? 'selected' : ''; ?>>Converted</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" name="search" id="search" class="form-control" 
                                       placeholder="Request #, VIN, Make, Model, Customer..." 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-search"></i> Filter
                                </button>
                                <a href="<?php echo url('admin/quote-requests.php'); ?>" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Quote Requests Table -->
                <div class="card-modern">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Quote Requests (<?php echo count($quoteRequests); ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($quoteRequests)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: var(--text-muted);"></i>
                                <p class="text-muted mt-3">No quote requests found</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-modern mb-0">
                                    <thead>
                                        <tr>
                                            <th>Request #</th>
                                            <th>Customer</th>
                                            <th>Vehicle</th>
                                            <th>Budget</th>
                                            <th>Status</th>
                                            <th>Requested</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($quoteRequests as $request): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($request['request_number']); ?></strong>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($request['customer_first_name'] . ' ' . $request['customer_last_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($request['customer_email']); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($request['year'] . ' ' . $request['make'] . ' ' . $request['model']); ?></strong><br>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($request['vehicle_type']); ?>
                                                        <?php if ($request['trim']): ?>
                                                            • <?php echo htmlspecialchars($request['trim']); ?>
                                                        <?php endif; ?>
                                                        <?php if ($request['vin']): ?>
                                                            <br>VIN: <?php echo htmlspecialchars($request['vin']); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($request['budget_min'] || $request['budget_max']): ?>
                                                        <?php if ($request['budget_min'] && $request['budget_max']): ?>
                                                            <small><?php echo formatCurrency($request['budget_min']); ?><br>to<br><?php echo formatCurrency($request['budget_max']); ?></small>
                                                        <?php elseif ($request['budget_max']): ?>
                                                            <small>Up to<br><?php echo formatCurrency($request['budget_max']); ?></small>
                                                        <?php else: ?>
                                                            <small>From<br><?php echo formatCurrency($request['budget_min']); ?></small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = match($request['status']) {
                                                        'pending' => 'bg-warning',
                                                        'reviewing' => 'bg-info',
                                                        'quoted' => 'bg-primary',
                                                        'approved' => 'bg-success',
                                                        'rejected' => 'bg-danger',
                                                        'converted' => 'bg-success',
                                                        default => 'bg-secondary'
                                                    };
                                                    $statusLabel = match($request['status']) {
                                                        'pending' => 'Pending',
                                                        'reviewing' => 'Reviewing',
                                                        'quoted' => 'Quoted',
                                                        'approved' => 'Approved',
                                                        'rejected' => 'Rejected',
                                                        'converted' => 'Converted',
                                                        default => ucfirst($request['status'])
                                                    };
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>">
                                                        <?php echo $statusLabel; ?>
                                                    </span>
                                                    <?php if ($request['total_estimate']): ?>
                                                        <br><small class="text-success"><strong><?php echo formatCurrency($request['total_estimate']); ?></strong></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M d, Y', strtotime($request['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="<?php echo url('admin/quote-requests/view.php?id=' . $request['id']); ?>" 
                                                           class="btn btn-outline-info" title="View & Quote">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <?php if ($request['status'] === 'quoted' || $request['status'] === 'approved'): ?>
                                                            <a href="<?php echo url('admin/quote-requests/convert.php?id=' . $request['id']); ?>" 
                                                               class="btn btn-outline-success" title="Convert to Order">
                                                                <i class="bi bi-arrow-right-circle"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if ($request['order_id']): ?>
                                                            <a href="<?php echo url('admin/orders/edit.php?id=' . $request['order_id']); ?>" 
                                                               class="btn btn-outline-primary" title="View Order">
                                                                <i class="bi bi-box-seam"></i>
                                                            </a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

