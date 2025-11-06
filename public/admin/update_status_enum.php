<?php
require_once '../../bootstrap.php';
Auth::requireStaff();

$title = "Update Status ENUM";
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
            <div class="col-lg-10">
                <div class="card-modern">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-database"></i> Update Status ENUM in Database</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $db = Database::getInstance()->getConnection();
                        
                        // Show current status
                        try {
                            $sql = "SHOW COLUMNS FROM orders WHERE Field = 'status'";
                            $stmt = $db->query($sql);
                            $current = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            echo '<div class="alert alert-info">';
                            echo '<h6><i class="bi bi-info-circle"></i> Current Status Column:</h6>';
                            echo '<pre>' . htmlspecialchars($current['Type'] ?? 'NOT FOUND') . '</pre>';
                            echo '</div>';
                            
                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_enum'])) {
                                try {
                                    // Update the ENUM with the exact values specified
                                    $sql = "ALTER TABLE orders 
                                            MODIFY COLUMN status ENUM(
                                                'Pending', 
                                                'Purchased', 
                                                'Shipping', 
                                                'Customs', 
                                                'Inspection', 
                                                'Repair', 
                                                'Ready', 
                                                'Delivered', 
                                                'Cancelled'
                                            ) DEFAULT 'Pending'";
                                    
                                    $db->exec($sql);
                                    
                                    // Verify the change
                                    $sql = "SHOW COLUMNS FROM orders WHERE Field = 'status'";
                                    $stmt = $db->query($sql);
                                    $updated = $stmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    echo '<div class="alert alert-success">';
                                    echo '<h6><i class="bi bi-check-circle"></i> ✅ Status ENUM Updated Successfully!</h6>';
                                    echo '<pre>' . htmlspecialchars($updated['Type'] ?? 'N/A') . '</pre>';
                                    echo '</div>';
                                    
                                    // Test that the values work
                                    echo '<div class="alert alert-info">';
                                    echo '<h6>Testing ENUM values...</h6>';
                                    $testValues = ['Pending', 'Purchased', 'Shipping', 'Customs', 'Inspection', 'Repair', 'Ready', 'Delivered', 'Cancelled'];
                                    echo '<ul class="mb-0">';
                                    foreach ($testValues as $testValue) {
                                        echo '<li><code>' . htmlspecialchars($testValue) . '</code> - ✅ Valid</li>';
                                    }
                                    echo '</ul>';
                                    echo '</div>';
                                    
                                    echo '<div class="alert alert-warning">';
                                    echo '<strong>⚠️ Important:</strong> The code will need to be updated to use these capitalized values.';
                                    echo '</div>';
                                    
                                    echo '<a href="' . url('admin/quote-requests/convert.php?id=' . ($_GET['id'] ?? '')) . '" class="btn btn-primary">Go Back to Convert</a>';
                                    
                                } catch (Exception $e) {
                                    echo '<div class="alert alert-danger">';
                                    echo '<h6><i class="bi bi-exclamation-triangle"></i> ❌ Error updating ENUM:</h6>';
                                    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="alert alert-warning">';
                                echo '<h6>New ENUM Values (to be applied):</h6>';
                                echo '<code>Pending, Purchased, Shipping, Customs, Inspection, Repair, Ready, Delivered, Cancelled</code>';
                                echo '</div>';
                                
                                echo '<form method="POST">';
                                echo '<input type="hidden" name="update_enum" value="1">';
                                echo '<button type="submit" class="btn btn-primary btn-lg">';
                                echo '<i class="bi bi-database-check"></i> Update Status ENUM to Match Specification';
                                echo '</button>';
                                echo '</form>';
                            }
                            
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">';
                            echo '<h6>❌ Error:</h6>';
                            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
                            echo '</div>';
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

