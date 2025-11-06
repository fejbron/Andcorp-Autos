<?php
require_once __DIR__ . '/bootstrap.php';

// Security headers and session
Auth::requireAuth();

$title = "Gallery - Car Imports";
$user = Auth::user();
$isCustomer = ($user['role'] === 'customer');

// Get customer ID for filtering
$customerId = null;
if ($isCustomer) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([Security::sanitizeInt($user['id'])]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    $customerId = $customer['id'] ?? null;
}

// Fetch orders with car images
$db = Database::getInstance()->getConnection();

$query = "
    SELECT 
        o.id,
        o.order_number,
        o.status,
        o.created_at,
        v.make,
        v.model,
        v.year,
        v.color,
        v.vin,
        c.user_id,
        u.first_name,
        u.last_name,
        (SELECT file_path FROM order_documents 
         WHERE order_id = o.id AND document_type = 'car_image' 
         LIMIT 1) as car_image
    FROM orders o
    LEFT JOIN vehicles v ON o.id = v.order_id
    LEFT JOIN customers c ON o.customer_id = c.id
    LEFT JOIN users u ON c.user_id = u.id
";

if ($isCustomer && $customerId) {
    $query .= " WHERE o.customer_id = ?";
}

$query .= " ORDER BY o.created_at DESC";

$stmt = $db->prepare($query);
if ($isCustomer && $customerId) {
    $stmt->execute([Security::sanitizeInt($customerId)]);
} else {
    $stmt->execute();
}

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - Andcorp Autos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
    <style>
        .gallery-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            overflow: hidden;
            background: white;
        }
        .gallery-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        .car-image {
            height: 250px;
            object-fit: cover;
            width: 100%;
        }
        .placeholder-image {
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            font-size: 3rem;
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container my-4">
        <div class="page-header animate-in mb-4">
            <h1 class="display-5 text-primary mb-3">
                <i class="bi bi-images"></i> Car Gallery
            </h1>
            <p class="lead text-muted">
                <?php if ($isCustomer): ?>
                    View all your imported vehicles with photos and documents
                <?php else: ?>
                    View all customer vehicles with photos and documents
                <?php endif; ?>
            </p>
        </div>

        <?php if ($isCustomer): ?>
            <div class="alert alert-info mb-4">
                <i class="bi bi-info-circle"></i> <strong>Note:</strong> Vehicle images and documents are uploaded by our admin team after your car has been purchased and processed.
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No vehicles found in the gallery yet.
                <?php if ($isCustomer): ?>
                    <a href="<?php echo url('orders/create.php'); ?>" class="alert-link">Create your first order</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="row g-4 mb-5">
                <?php foreach ($orders as $order): ?>
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="card gallery-card animate-in">
                            <div class="position-relative">
                                <?php if ($order['car_image']): ?>
                                    <img src="<?php echo url(htmlspecialchars($order['car_image'])); ?>" 
                                         class="card-img-top car-image" 
                                         alt="<?php echo htmlspecialchars("{$order['year']} {$order['make']} {$order['model']}"); ?>">
                                <?php else: ?>
                                    <div class="placeholder-image">
                                        <i class="bi bi-car-front"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <span class="badge status-badge 
                                    <?php 
                                        echo match($order['status']) {
                                            'delivered' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            'ready' => 'bg-info',
                                            default => 'bg-warning text-dark'
                                        };
                                    ?>">
                                    <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
                                </span>
                            </div>
                            
                            <div class="card-body">
                                <h5 class="card-title text-primary">
                                    <?php echo htmlspecialchars("{$order['year']} {$order['make']} {$order['model']}"); ?>
                                </h5>
                                
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="bi bi-hash"></i> <?php echo htmlspecialchars($order['order_number']); ?>
                                    </small>
                                </p>
                                
                                <?php if (!$isCustomer): ?>
                                    <p class="card-text">
                                        <small>
                                            <i class="bi bi-person"></i> 
                                            <?php echo htmlspecialchars("{$order['first_name']} {$order['last_name']}"); ?>
                                        </small>
                                    </p>
                                <?php endif; ?>
                                
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> 
                                        <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                    </small>
                                </p>
                                
                                <div class="d-grid gap-2">
                                    <a href="<?php echo url('orders/view.php?id=' . $order['id']); ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="bi bi-eye"></i> View Details
                                    </a>
                                    <a href="<?php echo url('orders/documents.php?id=' . $order['id']); ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-file-earmark-text"></i> Documents
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

