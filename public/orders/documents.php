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
    try {
        // CSRF check
        if (!Security::verifyToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid security token';
        } else {
            $documentType = Security::sanitizeString($_POST['document_type'] ?? '', 50);
            
            // Validate document type
            $allowedDocTypes = ['car_image', 'title', 'bill_of_lading', 'bill_of_entry', 'evidence_of_delivery'];
            if (!in_array($documentType, $allowedDocTypes, true)) {
                $_SESSION['error'] = 'Invalid document type selected';
            } else {
                $file = $_FILES['document'];
                
                // Validate file - validateFileUpload expects (file, allowedTypes array, maxSize)
                $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                $maxSize = 10 * 1024 * 1024; // 10MB
                $validation = Security::validateFileUpload($file, $allowedMimeTypes, $maxSize);
                
                if ($validation['valid']) {
                    $uploadDir = __DIR__ . '/../uploads/' . ($documentType === 'car_image' ? 'cars' : 'documents');
                    
                    // Create directory if it doesn't exist
                    if (!is_dir($uploadDir)) {
                        if (!mkdir($uploadDir, 0755, true)) {
                            $_SESSION['error'] = 'Failed to create upload directory. Please contact administrator.';
                            redirect(url('orders/documents.php?id=' . $orderId));
                            exit;
                        }
                    }
                    
                    // Generate secure filename
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $secureFilename = bin2hex(random_bytes(16)) . '.' . $extension;
                    $uploadPath = $uploadDir . '/' . $secureFilename;
                    $relativePath = 'uploads/' . ($documentType === 'car_image' ? 'cars' : 'documents') . '/' . $secureFilename;
                    
                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        // Set proper file permissions
                        @chmod($uploadPath, 0644);
                        
                        try {
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
                            try {
                                Auth::logOrderActivity($user['id'], $orderId, 'document_uploaded', 
                                    "Uploaded {$documentType} document");
                            } catch (Exception $e) {
                                // Don't fail if logging fails
                                error_log("Failed to log document upload: " . $e->getMessage());
                            }
                            
                            redirect(url('orders/documents.php?id=' . $orderId));
                        } catch (PDOException $e) {
                            // Delete uploaded file if database insert fails
                            @unlink($uploadPath);
                            
                            // Check if it's an ENUM value error
                            $errorCode = $e->getCode();
                            $errorMessage = $e->getMessage();
                            if (strpos($errorMessage, 'document_type') !== false || 
                                strpos($errorMessage, 'Data truncated') !== false ||
                                strpos($errorMessage, '1265') !== false ||
                                $errorCode == '1265' ||
                                (is_string($errorCode) && strpos($errorCode, '1265') !== false)) {
                                $_SESSION['error'] = 'The document type "Evidence of Delivery" is not yet enabled in the database. Please contact the administrator to run the database migration.';
                                error_log("Document type ENUM error for 'evidence_of_delivery': " . $errorMessage . " (Code: " . $errorCode . ")");
                            } else {
                                $_SESSION['error'] = 'Failed to save document to database. Please try again or contact support.';
                                error_log("Database error saving document: " . $errorMessage . " (Code: " . $errorCode . ")");
                            }
                        } catch (Exception $e) {
                            // Delete uploaded file if database insert fails
                            @unlink($uploadPath);
                            $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
                            error_log("Error uploading document: " . $e->getMessage());
                        }
                    } else {
                        $_SESSION['error'] = 'Failed to upload file. Please try again.';
                    }
                } else {
                    $_SESSION['error'] = $validation['error'] ?? 'File validation failed';
                }
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'An error occurred during upload: ' . $e->getMessage();
        error_log("Document upload error: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
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
        
        Auth::logOrderActivity($user['id'], $orderId, 'document_deleted', 
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
    'bill_of_entry' => [],
    'evidence_of_delivery' => []
];

foreach ($documents as $doc) {
    if (isset($groupedDocs[$doc['document_type']])) {
        $groupedDocs[$doc['document_type']][] = $doc;
    } else {
        // Handle any unexpected document types
        $groupedDocs[$doc['document_type']] = [$doc];
    }
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
            cursor: pointer;
        }
        .pdf-icon {
            font-size: 5rem;
            color: var(--primary-color);
        }
        
        /* Lightbox Styles */
        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 9999;
            animation: fadeIn 0.3s ease;
        }
        
        .lightbox.active {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .lightbox-content {
            position: relative;
            max-width: 90%;
            max-height: 85vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .lightbox-image {
            max-width: 100%;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }
        
        .lightbox-info {
            position: absolute;
            bottom: -100px;
            left: 0;
            right: 0;
            text-align: center;
            color: white;
            padding: 15px;
        }
        
        .lightbox-info h5 {
            margin: 0 0 10px 0;
            font-size: 1.3rem;
        }
        
        .lightbox-info p {
            margin: 5px 0;
            opacity: 0.9;
        }
        
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 10000;
            background: rgba(0, 0, 0, 0.5);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }
        
        .lightbox-close:hover {
            background: rgba(255, 0, 0, 0.7);
        }
        
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 60px;
            cursor: pointer;
            user-select: none;
            background: rgba(0, 0, 0, 0.5);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
            z-index: 10000;
        }
        
        .lightbox-nav:hover {
            background: rgba(0, 0, 0, 0.8);
        }
        
        .lightbox-nav.disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        
        .lightbox-prev {
            left: 30px;
        }
        
        .lightbox-next {
            right: 30px;
        }
        
        .lightbox-counter {
            position: absolute;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-size: 18px;
            background: rgba(0, 0, 0, 0.5);
            padding: 10px 20px;
            border-radius: 20px;
        }
        
        @media (max-width: 768px) {
            .lightbox-nav {
                font-size: 40px;
                width: 50px;
                height: 50px;
            }
            
            .lightbox-prev {
                left: 10px;
            }
            
            .lightbox-next {
                right: 10px;
            }
            
            .lightbox-close {
                top: 10px;
                right: 10px;
                font-size: 30px;
                width: 40px;
                height: 40px;
            }
            
            .lightbox-info {
                bottom: -120px;
            }
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
                                    <option value="evidence_of_delivery">Evidence of Delivery</option>
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
                                'evidence_of_delivery' => 'check-circle',
                                default => 'file'
                            };
                        ?>"></i>
                        <?php 
                            echo match($type) {
                                'car_image' => 'Car Images',
                                'title' => 'Car Title',
                                'bill_of_lading' => 'Bill of Lading',
                                'bill_of_entry' => 'Bill of Entry / Duty',
                                'evidence_of_delivery' => 'Evidence of Delivery',
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
                                                     class="img-fluid document-thumbnail mb-2 lightbox-trigger" 
                                                     alt="Document"
                                                     data-filename="<?php echo htmlspecialchars($doc['file_name']); ?>"
                                                     data-filesize="<?php echo number_format($doc['file_size'] / 1024, 2); ?>"
                                                     data-date="<?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?>"
                                                     data-type="<?php echo htmlspecialchars($type); ?>"
                                                     onclick="openLightbox(this)">
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
                                                <?php if ($isPdf): ?>
                                                    <a href="<?php echo url($doc['file_path']); ?>" 
                                                       class="btn btn-outline-primary" 
                                                       target="_blank">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                <?php else: ?>
                                                    <button type="button" 
                                                            class="btn btn-outline-primary"
                                                            onclick="openLightbox(document.querySelector('img[data-filename=\'<?php echo htmlspecialchars(addslashes($doc['file_name'])); ?>\']'))">
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                <?php endif; ?>
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

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <!-- Lightbox -->
    <div id="lightbox" class="lightbox">
        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
        <div class="lightbox-counter" id="lightboxCounter"></div>
        <span class="lightbox-nav lightbox-prev" id="lightboxPrev" onclick="changeImage(-1)">
            <i class="bi bi-chevron-left"></i>
        </span>
        <div class="lightbox-content">
            <img id="lightboxImage" class="lightbox-image" src="" alt="">
            <div class="lightbox-info">
                <h5 id="lightboxFilename"></h5>
                <p id="lightboxType"></p>
                <p id="lightboxDetails"></p>
            </div>
        </div>
        <span class="lightbox-nav lightbox-next" id="lightboxNext" onclick="changeImage(1)">
            <i class="bi bi-chevron-right"></i>
        </span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentImageIndex = 0;
        let lightboxImages = [];

        // Initialize lightbox images array on page load
        document.addEventListener('DOMContentLoaded', function() {
            lightboxImages = Array.from(document.querySelectorAll('.lightbox-trigger'));
        });

        function openLightbox(element) {
            const lightbox = document.getElementById('lightbox');
            currentImageIndex = lightboxImages.indexOf(element);
            
            updateLightboxImage();
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closeLightbox() {
            const lightbox = document.getElementById('lightbox');
            lightbox.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        }

        function changeImage(direction) {
            if (lightboxImages.length === 0) return;
            
            currentImageIndex += direction;
            
            // Loop around if at the end or beginning
            if (currentImageIndex >= lightboxImages.length) {
                currentImageIndex = 0;
            } else if (currentImageIndex < 0) {
                currentImageIndex = lightboxImages.length - 1;
            }
            
            updateLightboxImage();
        }

        function updateLightboxImage() {
            if (lightboxImages.length === 0) return;
            
            const currentImage = lightboxImages[currentImageIndex];
            const lightboxImage = document.getElementById('lightboxImage');
            const lightboxFilename = document.getElementById('lightboxFilename');
            const lightboxType = document.getElementById('lightboxType');
            const lightboxDetails = document.getElementById('lightboxDetails');
            const lightboxCounter = document.getElementById('lightboxCounter');
            const prevBtn = document.getElementById('lightboxPrev');
            const nextBtn = document.getElementById('lightboxNext');
            
            // Update image and info
            lightboxImage.src = currentImage.src;
            lightboxImage.alt = currentImage.alt;
            lightboxFilename.textContent = currentImage.dataset.filename;
            
            // Format document type nicely
            const type = currentImage.dataset.type;
            const typeLabel = {
                'car_image': 'Car Image',
                'title': 'Car Title',
                'bill_of_lading': 'Bill of Lading',
                'bill_of_entry': 'Bill of Entry / Duty',
                'evidence_of_delivery': 'Evidence of Delivery'
            }[type] || type;
            
            lightboxType.innerHTML = '<i class="bi bi-tag"></i> ' + typeLabel;
            lightboxDetails.innerHTML = '<i class="bi bi-info-circle"></i> ' + 
                currentImage.dataset.filesize + ' KB • ' + currentImage.dataset.date;
            
            // Update counter
            if (lightboxImages.length > 1) {
                lightboxCounter.textContent = (currentImageIndex + 1) + ' / ' + lightboxImages.length;
                prevBtn.style.display = 'flex';
                nextBtn.style.display = 'flex';
            } else {
                lightboxCounter.textContent = '1 / 1';
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
            }
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(event) {
            const lightbox = document.getElementById('lightbox');
            if (!lightbox.classList.contains('active')) return;
            
            if (event.key === 'ArrowLeft') {
                changeImage(-1);
            } else if (event.key === 'ArrowRight') {
                changeImage(1);
            } else if (event.key === 'Escape') {
                closeLightbox();
            }
        });

        // Close lightbox when clicking outside the image
        document.getElementById('lightbox').addEventListener('click', function(event) {
            if (event.target === this) {
                closeLightbox();
            }
        });

        // Prevent image drag
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('lightboxImage').addEventListener('dragstart', function(e) {
                e.preventDefault();
            });
        });
    </script>
</body>
</html>

