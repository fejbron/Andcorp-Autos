<?php
require_once 'bootstrap.php';
Auth::requireAuth();

// Redirect staff to admin panel
if (Auth::isStaff()) {
    redirect(url('admin/quote-requests.php'));
}

$customerModel = new Customer();
$quoteRequestModel = new QuoteRequest();

$customer = $customerModel->findByUserId(Auth::userId());
$quoteRequests = $quoteRequestModel->getByCustomer($customer['id']);

$title = "My Quote Requests";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - Andcorp Autos</title>
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
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid my-4">
        <div class="row">
            <div class="col-lg-11 mx-auto">
                <!-- Page Header -->
                <div class="page-header animate-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-5">My Quote Requests</h1>
                            <p class="lead mb-0">View and track your vehicle quote requests</p>
                        </div>
                        <div>
                            <a href="<?php echo url('quotes/request.php'); ?>" class="btn btn-primary btn-modern">
                                <i class="bi bi-plus-circle"></i> New Quote Request
                            </a>
                        </div>
                    </div>
                </div>

                <?php if ($successMsg = success()): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i> <?php echo $successMsg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (error('general')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo error('general'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Quote Requests List -->
                <div class="card-modern">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Requests (<?php echo count($quoteRequests); ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($quoteRequests)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 4rem; color: var(--text-muted);"></i>
                                <h4 class="mt-3">No quote requests yet</h4>
                                <p class="text-muted">Start by requesting a quote for the vehicle you want to import</p>
                                <a href="<?php echo url('quotes/request.php'); ?>" class="btn btn-primary mt-2">
                                    <i class="bi bi-plus-circle"></i> Request Your First Quote
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-modern mb-0">
                                    <thead>
                                        <tr>
                                            <th>Request #</th>
                                            <th>Vehicle</th>
                                            <th>Budget Range</th>
                                            <th>Status</th>
                                            <th>Quote</th>
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
                                                    <strong><?php echo htmlspecialchars($request['make'] . ' ' . $request['model']); ?></strong><br>
                                                    <small class="text-muted">
                                                        <?php if ($request['year']): ?>
                                                            <?php echo $request['year']; ?> • 
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($request['vehicle_type']); ?>
                                                        <?php if ($request['trim']): ?>
                                                            • <?php echo htmlspecialchars($request['trim']); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($request['budget_min'] || $request['budget_max']): ?>
                                                        <?php if ($request['budget_min'] && $request['budget_max']): ?>
                                                            <?php echo formatCurrency($request['budget_min']); ?> - <?php echo formatCurrency($request['budget_max']); ?>
                                                        <?php elseif ($request['budget_max']): ?>
                                                            Up to <?php echo formatCurrency($request['budget_max']); ?>
                                                        <?php else: ?>
                                                            From <?php echo formatCurrency($request['budget_min']); ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not specified</span>
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
                                                        'pending' => 'Pending Review',
                                                        'reviewing' => 'Under Review',
                                                        'quoted' => 'Quote Ready',
                                                        'approved' => 'Approved',
                                                        'rejected' => 'Declined',
                                                        'converted' => 'Order Created',
                                                        default => ucfirst($request['status'])
                                                    };
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>">
                                                        <?php echo $statusLabel; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($request['total_estimate']): ?>
                                                        <strong class="text-success"><?php echo formatCurrency($request['total_estimate']); ?></strong><br>
                                                        <small class="text-muted">Total Estimate</small>
                                                    <?php elseif ($request['status'] === 'quoted'): ?>
                                                        <span class="text-info">Quote ready</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M d, Y', strtotime($request['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <a href="<?php echo url('quotes/view.php?id=' . $request['id']); ?>" 
                                                       class="btn btn-sm btn-outline-info" title="View Details">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                    <?php if ($request['order_id']): ?>
                                                        <a href="<?php echo url('orders/view.php?id=' . $request['order_id']); ?>" 
                                                           class="btn btn-sm btn-outline-success ms-1" title="View Order">
                                                            <i class="bi bi-box-seam"></i> Order
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card-modern">
                            <div class="card-body">
                                <h5><i class="bi bi-question-circle"></i> How It Works</h5>
                                <ol class="small mb-0">
                                    <li class="mb-2">Submit a quote request with your vehicle preferences</li>
                                    <li class="mb-2">Our team reviews and provides a detailed quote</li>
                                    <li class="mb-2">Review the quote and make your decision</li>
                                    <li class="mb-2">Once approved, we create your official order</li>
                                    <li class="mb-0">Make deposits and track your vehicle import</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card-modern">
                            <div class="card-body">
                                <h5><i class="bi bi-info-circle"></i> Status Explained</h5>
                                <ul class="small mb-0">
                                    <li class="mb-2"><strong>Pending:</strong> Request submitted, awaiting review</li>
                                    <li class="mb-2"><strong>Under Review:</strong> Team is reviewing your request</li>
                                    <li class="mb-2"><strong>Quote Ready:</strong> Price quote is available - check details!</li>
                                    <li class="mb-2"><strong>Approved:</strong> Quote approved, order will be created</li>
                                    <li class="mb-0"><strong>Order Created:</strong> Your booking is confirmed</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php clearErrors(); ?>

