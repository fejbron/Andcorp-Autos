<?php
require_once '../../bootstrap.php';
Auth::requireStaff();

$title = "Fix Status ENUM";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - AndCorp Autos</title>
    <link rel="icon" type="image/png" href="<?php echo url('assets/images/favicon.png'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card-modern">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-tools"></i> Fix Status ENUM</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_enum'])) {
                            try {
                                $db = Database::getInstance()->getConnection();
                                
                                // Get current ENUM values
                                $sql = "SHOW COLUMNS FROM orders WHERE Field = 'status'";
                                $stmt = $db->query($sql);
                                $current = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                echo '<div class="alert alert-info"><h6>Current Status Column:</h6><pre>' . htmlspecialchars($current['Type'] ?? 'N/A') . '</pre></div>';
                                
                                // Update the ENUM
                                $sql = "ALTER TABLE orders 
                                        MODIFY COLUMN status ENUM(
                                            'pending', 
                                            'purchased', 
                                            'shipping', 
                                            'customs', 
                                            'inspection', 
                                            'repair', 
                                            'ready', 
                                            'delivered', 
                                            'cancelled'
                                        ) DEFAULT 'pending'";
                                
                                $db->exec($sql);
                                
                                // Verify the change
                                $sql = "SHOW COLUMNS FROM orders WHERE Field = 'status'";
                                $stmt = $db->query($sql);
                                $updated = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                echo '<div class="alert alert-success"><h6>✅ Status Column Updated!</h6><pre>' . htmlspecialchars($updated['Type'] ?? 'N/A') . '</pre></div>';
                                
                                echo '<div class="alert alert-info">';
                                echo '<strong>Valid status values now:</strong><br>';
                                echo '<code>pending, purchased, shipping, customs, inspection, repair, ready, delivered, cancelled</code>';
                                echo '</div>';
                                
                                echo '<a href="' . url('admin/quote-requests/convert.php?id=' . ($_GET['id'] ?? '')) . '" class="btn btn-primary">Go Back to Convert</a>';
                                
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger">';
                                echo '<h6>❌ Error:</h6>';
                                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
                                echo '</div>';
                            }
                        } else {
                            // Show current status
                            try {
                                $db = Database::getInstance()->getConnection();
                                $sql = "SHOW COLUMNS FROM orders WHERE Field = 'status'";
                                $stmt = $db->query($sql);
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                echo '<div class="alert alert-warning">';
                                echo '<h6>Current Status Column Type:</h6>';
                                echo '<pre>' . htmlspecialchars($result['Type'] ?? 'NOT FOUND') . '</pre>';
                                echo '</div>';
                                
                                echo '<div class="alert alert-info">';
                                echo '<h6>Expected Values:</h6>';
                                echo '<code>pending, purchased, shipping, customs, inspection, repair, ready, delivered, cancelled</code>';
                                echo '</div>';
                                
                                echo '<form method="POST">';
                                echo '<input type="hidden" name="fix_enum" value="1">';
                                echo '<button type="submit" class="btn btn-warning btn-lg">';
                                echo '<i class="bi bi-tools"></i> Fix Status ENUM Now';
                                echo '</button>';
                                echo '</form>';
                                
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger">';
                                echo '<h6>❌ Error checking current status:</h6>';
                                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

