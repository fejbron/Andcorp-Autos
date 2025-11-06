<?php
require_once '../bootstrap.php';
Auth::requireStaff();

$customerModel = new Customer();
$orderModel = new Order();
$db = Database::getInstance()->getConnection();

$searchQuery = Security::sanitizeString($_GET['search'] ?? '', 255);

// Get customers
if ($searchQuery) {
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT c.*, u.email, u.first_name, u.last_name, u.phone, u.is_active, u.created_at
            FROM customers c
            JOIN users u ON c.user_id = u.id
            WHERE u.first_name LIKE :search 
            OR u.last_name LIKE :search
            OR u.email LIKE :search
            OR u.phone LIKE :search
            ORDER BY u.created_at DESC
            LIMIT 100";
    $stmt = $db->prepare($sql);
    $stmt->execute([':search' => "%{$searchQuery}%"]);
    $customers = $stmt->fetchAll();
} else {
    $customers = $customerModel->getAll();
}

// Optimize: Get order counts in one query instead of N+1
$customerIds = array_column($customers, 'id');
$customersWithOrders = [];

if (!empty($customerIds)) {
    $placeholders = implode(',', array_fill(0, count($customerIds), '?'));
    $orderSql = "SELECT customer_id, COUNT(*) as order_count, SUM(deposit_amount) as total_spent 
                 FROM orders 
                 WHERE customer_id IN ($placeholders)
                 GROUP BY customer_id";
    $orderStmt = $db->prepare($orderSql);
    $orderStmt->execute($customerIds);
    $orderStats = $orderStmt->fetchAll();
    
    // Create lookup array
    $statsLookup = [];
    foreach ($orderStats as $stat) {
        $statsLookup[$stat['customer_id']] = [
            'order_count' => (int)$stat['order_count'],
            'total_spent' => (float)($stat['total_spent'] ?? 0)
        ];
    }
    
    // Merge stats with customers
    foreach ($customers as $customer) {
        $customer['order_count'] = $statsLookup[$customer['id']]['order_count'] ?? 0;
        $customer['total_spent'] = $statsLookup[$customer['id']]['total_spent'] ?? 0;
        $customersWithOrders[] = $customer;
    }
} else {
    $customersWithOrders = $customers;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - Andcorp Autos Admin</title>
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
            <h1 class="display-5">Manage Customers</h1>
            <p class="lead">View and manage customer accounts</p>
        </div>

        <?php if ($successMsg = success()): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?php echo $successMsg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Search -->
        <div class="card-modern mb-4 animate-in">
            <div class="card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                    <div class="col-md-8">
                        <label for="search" class="form-label">Search Customers</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($searchQuery); ?>" 
                               placeholder="Name, email, phone...">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="<?php echo url('admin/customers.php'); ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card primary animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3><?php echo count($customersWithOrders); ?></h3>
                    <p>Total Customers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <h3><?php echo count(array_filter($customersWithOrders, fn($c) => $c['is_active'])); ?></h3>
                    <p>Active Customers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card info animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-cart"></i>
                    </div>
                    <h3><?php echo count(array_filter($customersWithOrders, fn($c) => $c['order_count'] > 0)); ?></h3>
                    <p>With Orders</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning animate-in">
                    <div class="stat-icon">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <h3><?php echo formatCurrency(array_sum(array_column($customersWithOrders, 'total_spent'))); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="card-modern animate-in">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-people"></i> Customers (<?php echo count($customersWithOrders); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($customersWithOrders)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                        <h4 class="mt-3">No Customers Found</h4>
                        <p class="text-muted">
                            <?php echo $searchQuery ? 'Try adjusting your search criteria' : 'No customers registered yet'; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Contact</th>
                                    <th>Location</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customersWithOrders as $customer): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $customer['first_name'] . ' ' . $customer['last_name']; ?></strong>
                                            <?php if ($customer['identification_number']): ?>
                                                <br><small class="text-muted">ID: <?php echo $customer['identification_number']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <i class="bi bi-envelope"></i> <?php echo $customer['email']; ?><br>
                                            <?php if ($customer['phone']): ?>
                                                <i class="bi bi-phone"></i> <?php echo $customer['phone']; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($customer['city'] || $customer['country']): ?>
                                                <?php echo ($customer['city'] ? $customer['city'] . ', ' : '') . $customer['country']; ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-modern bg-info"><?php echo $customer['order_count']; ?> orders</span>
                                        </td>
                                        <td>
                                            <strong><?php echo formatCurrency($customer['total_spent']); ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($customer['is_active']): ?>
                                                <span class="badge badge-modern bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-modern bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($customer['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button class="btn btn-outline-info" 
                                                        onclick="viewCustomer(<?php echo $customer['id']; ?>)" 
                                                        title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-primary" 
                                                        onclick="viewOrders(<?php echo $customer['id']; ?>)" 
                                                        title="View Orders">
                                                    <i class="bi bi-cart"></i>
                                                </button>
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

    <!-- Customer Details Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Customer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="customerDetails">
                    <p class="text-center">Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewCustomer(customerId) {
            // Find customer data
            const customers = <?php echo json_encode($customersWithOrders); ?>;
            const customer = customers.find(c => c.id == customerId);
            
            if (customer) {
                const details = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Personal Information</h6>
                            <p><strong>Name:</strong> ${customer.first_name} ${customer.last_name}</p>
                            <p><strong>Email:</strong> ${customer.email}</p>
                            <p><strong>Phone:</strong> ${customer.phone || 'N/A'}</p>
                            <p><strong>ID Number:</strong> ${customer.identification_number || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Address Information</h6>
                            <p><strong>Address:</strong> ${customer.address || 'N/A'}</p>
                            <p><strong>City:</strong> ${customer.city || 'N/A'}</p>
                            <p><strong>Country:</strong> ${customer.country}</p>
                            <p><strong>Preferred Contact:</strong> ${customer.preferred_contact}</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Account Statistics</h6>
                            <p><strong>Total Orders:</strong> ${customer.order_count}</p>
                            <p><strong>Total Spent:</strong> $${customer.total_spent.toFixed(2)}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Account Status</h6>
                            <p><strong>Status:</strong> <span class="badge bg-${customer.is_active ? 'success' : 'danger'}">${customer.is_active ? 'Active' : 'Inactive'}</span></p>
                            <p><strong>Joined:</strong> ${new Date(customer.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                `;
                
                document.getElementById('customerDetails').innerHTML = details;
                new bootstrap.Modal(document.getElementById('customerModal')).show();
            }
        }

        function viewOrders(customerId) {
            window.location.href = '<?php echo url('admin/orders.php'); ?>?customer_id=' + customerId;
        }
    </script>
</body>
</html>

