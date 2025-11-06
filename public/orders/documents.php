<?php
require_once __DIR__ . '/../bootstrap.php';

// Security
Auth::requireAuth();

$user = Auth::user();
$orderId = isset($_GET['id']) ? Security::sanitizeInt($_GET['id']) : 0;

if (!$orderId) {
    redirect(url('orders.php'));
}

$db = Database::getInstance()->getConnection();

// Verify access to this order
$query = "SELECT o.*, c.user_id FROM orders o 
          LEFT JOIN customers c ON o.customer_id = c.id 
          WHERE o.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['error'] = 'Order not found.';
    redirect(url('orders.php'));
}

// Check permissions - customers can only view, not upload
$canUpload = Auth::isStaff();
if ($user['role'] === 'customer' && $order['user_id'] != $user['id']) {
    $_SESSION['error'] = 'Access denied.';
    redirect(url('orders.php'));
}

// Handle file upload - only admin/staff can upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document']) && $canUpload) {
    // CSRF check
    if (!Security::verifyToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token';
    } else {
        $documentType = Security::sanitizeString($_POST['document_type'] ?? '', 50);
        
        // Validate document type
        $validator = new Validator();
        $validator->in($documentType, ['car_image', 'title', 'bill_of_lading', 'bill_of_entry'], 'Invalid document type');
        
        if ($validator->fails()) {
            $_SESSION['error'] = implode(', ', $validator->getErrors());
        } else {
            $file = $_FILES['document'];
            
            // Validate file
            $validation = Security::validateFileUpload($file, [
                'allowed_types' => ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'],
                'max_size' => 10 * 1024 * 1024 // 10MB
            ]);
            
            if ($validation['valid']) {
                $uploadDir = __DIR__ . '/../uploads/' . ($documentType === 'car_image' ? 'cars' : 'documents');
                
                // Generate secure filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $secureFilename = bin2hex(random_bytes(16)) . '.' . $extension;
                $uploadPath = $uploadDir . '/' . $secureFilename;
                $relativePath = 'uploads/' . ($documentType === 'car_image' ? 'cars' : 'documents') . '/' . $secureFilename;
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // Save to database
                    $stmt = $db->prepare("
                        INSERT INTO order_documents 
                        (order_id, document_type, file_name, file_path, file_size, uploaded_by)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $orderId,
                        $documentType,
                        Security::sanitizeString($file['name'], 255),
                        Security::sanitizeString($relativePath, 500),
                        (int)$file['size'],
                        $user['id']
                    ]);
                    
                    $_SESSION['success'] = 'Document uploaded successfully!';
                    
                    // Log activity
                    Auth::logOrderActivity($orderId, $user['id'], 'document_uploaded', 
                        "Uploaded {$documentType} document");
                    
                    redirect(url('orders/documents.php?id=' . $orderId));
                } else {
                    $_SESSION['error'] = 'Failed to upload file. Please try again.';
                }
            } else {
                $_SESSION['error'] = $validation['error'];
            }
        }
    }
}

// Handle document deletion - only admin/staff can delete
if (isset($_GET['delete']) && $canUpload) {
    $docId = Security::sanitizeInt($_GET['delete']);
    
    // Verify document belongs to this order
    $stmt = $db->prepare("SELECT * FROM order_documents WHERE id = ? AND order_id = ?");
    $stmt->execute([$docId, $orderId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($doc) {
        // Delete file
        $filePath = __DIR__ . '/../' . $doc['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete from database
        $stmt = $db->prepare("DELETE FROM order_documents WHERE id = ?");
        $stmt->execute([$docId]);
        
        $_SESSION['success'] = 'Document deleted successfully!';
        
        Auth::logOrderActivity($orderId, $user['id'], 'document_deleted', 
            "Deleted {$doc['document_type']} document");
    }
    
    redirect(url('orders/documents.php?id=' . $orderId));
}

// Fetch all documents for this order
$stmt = $db->prepare("
    SELECT od.*, u.first_name, u.last_name
    FROM order_documents od
    LEFT JOIN users u ON od.uploaded_by = u.id
    WHERE od.order_id = ?
    ORDER BY od.document_type, od.uploaded_at DESC
");
$stmt->execute([$orderId]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group documents by type
$groupedDocs = [
    'car_image' => [],
    'title' => [],
    'bill_of_lading' => [],
    'bill_of_entry' => []
];

foreach ($documents as $doc) {
    $groupedDocs[$doc['document_type']][] = $doc;
}

$title = "Order Documents - " . $order['order_number'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
    <style>
        .document-card {
            transition: transform 0.2s;
        }
        .document-card:hover {
            transform: translateY(-3px);
        }
        .document-thumbnail {
            height: 150px;
            object-fit: cover;
        }
        .pdf-icon {
            font-size: 5rem;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo url('orders.php'); ?>">Orders</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo url('orders/view.php?id=' . $orderId); ?>">
                            <?php echo htmlspecialchars($order['order_number']); ?>
                        </a></li>
                        <li class="breadcrumb-item active">Documents</li>
                    </ol>
                </nav>

                <h1 class="text-primary">
                    <i class="bi bi-file-earmark-text"></i> Order Documents
                </h1>
                <p class="text-muted">Order #<?php echo htmlspecialchars($order['order_number']); ?></p>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php 
                echo htmlspecialchars($_SESSION['success']); 
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php 
                echo htmlspecialchars($_SESSION['error']); 
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($canUpload): ?>
            <!-- Upload Form (Admin/Staff Only) -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-cloud-upload"></i> Upload Document</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <?php echo Security::csrfField(); ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Document Type *</label>
                                <select name="document_type" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="car_image">Car Image</option>
                                    <option value="title">Car Title</option>
                                    <option value="bill_of_lading">Bill of Lading</option>
                                    <option value="bill_of_entry">Bill of Entry / Duty</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">File *</label>
                                <input type="file" name="document" class="form-control" accept="image/*,.pdf" required>
                                <small class="text-muted">Max 10MB. Accepted: JPG, PNG, PDF</small>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Upload Document
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Customer Info Message -->
            <div class="alert alert-info mb-4">
                <i class="bi bi-info-circle"></i> <strong>Note:</strong> Documents and images for your order will be uploaded by our admin team. You can view them below as they become available.
            </div>
        <?php endif; ?>

        <!-- Documents Grid -->
        <?php foreach ($groupedDocs as $type => $docs): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-<?php 
                            echo match($type) {
                                'car_image' => 'image',
                                'title' => 'file-text',
                                'bill_of_lading' => 'ship',
                                'bill_of_entry' => 'receipt',
                                default => 'file'
                            };
                        ?>"></i>
                        <?php 
                            echo match($type) {
                                'car_image' => 'Car Images',
                                'title' => 'Car Title',
                                'bill_of_lading' => 'Bill of Lading',
                                'bill_of_entry' => 'Bill of Entry / Duty',
                                default => ucwords(str_replace('_', ' ', $type))
                            };
                        ?>
                        <span class="badge bg-primary"><?php echo count($docs); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($docs)): ?>
                        <p class="text-muted">No documents uploaded yet.</p>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($docs as $doc): ?>
                                <div class="col-md-3">
                                    <div class="card document-card">
                                        <div class="card-body text-center">
                                            <?php 
                                            $isPdf = pathinfo($doc['file_path'], PATHINFO_EXTENSION) === 'pdf';
                                            ?>
                                            
                                            <?php if ($isPdf): ?>
                                                <i class="bi bi-file-pdf pdf-icon"></i>
                                            <?php else: ?>
                                                <img src="<?php echo url($doc['file_path']); ?>" 
                                                     class="img-fluid document-thumbnail mb-2" 
                                                     alt="Document">
                                            <?php endif; ?>
                                            
                                            <p class="small mb-1">
                                                <strong><?php echo htmlspecialchars($doc['file_name']); ?></strong>
                                            </p>
                                            <p class="small text-muted mb-2">
                                                <?php echo number_format($doc['file_size'] / 1024, 2); ?> KB
                                                <br>
                                                <?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?>
                                            </p>
                                            
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="<?php echo url($doc['file_path']); ?>" 
                                                   class="btn btn-outline-primary" 
                                                   target="_blank">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                <?php if ($canUpload): ?>
                                                    <a href="<?php echo url('orders/documents.php?id=' . $orderId . '&delete=' . $doc['id']); ?>" 
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('Are you sure you want to delete this document?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="mt-4">
            <a href="<?php echo url('orders/view.php?id=' . $orderId); ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Order
            </a>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

