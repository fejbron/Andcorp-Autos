<?php
/**
 * Migration script to add 'evidence_of_delivery' to order_documents ENUM
 * Access: https://app.andcorpautos.com/public/admin/migrate_evidence_of_delivery.php
 * 
 * This script will:
 * 1. Check current ENUM values
 * 2. Attempt to add 'evidence_of_delivery' to the ENUM
 * 3. Show success or error messages
 */
require_once '../bootstrap.php';
Auth::requireAdmin(); // Only admins can run migrations

$db = Database::getInstance()->getConnection();
$errors = [];
$success = false;
$currentEnumValues = [];
$newEnumValues = ['car_image', 'title', 'bill_of_lading', 'bill_of_entry', 'evidence_of_delivery'];

// Get current ENUM values
try {
    $stmt = $db->query("SHOW COLUMNS FROM order_documents WHERE Field = 'document_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column && isset($column['Type'])) {
        preg_match("/^enum\((.*)\)$/i", $column['Type'], $matches);
        if (isset($matches[1])) {
            $currentEnumValues = array_map(function($value) {
                return trim($value, "'\"");
            }, explode(',', $matches[1]));
        }
    }
} catch (Exception $e) {
    $errors[] = "Error reading current ENUM values: " . $e->getMessage();
}

// Check if migration is needed
$needsMigration = !in_array('evidence_of_delivery', $currentEnumValues);

// Run migration if needed
if ($needsMigration && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    try {
        // CSRF protection
        if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
            $errors[] = 'Invalid security token. Please try again.';
        } else {
            $sql = "ALTER TABLE order_documents 
                    MODIFY COLUMN document_type ENUM(
                        'car_image', 
                        'title', 
                        'bill_of_lading', 
                        'bill_of_entry',
                        'evidence_of_delivery'
                    ) NOT NULL";
            
            $db->exec($sql);
            $success = true;
            
            // Refresh ENUM values
            $stmt = $db->query("SHOW COLUMNS FROM order_documents WHERE Field = 'document_type'");
            $column = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($column && isset($column['Type'])) {
                preg_match("/^enum\((.*)\)$/i", $column['Type'], $matches);
                if (isset($matches[1])) {
                    $currentEnumValues = array_map(function($value) {
                        return trim($value, "'\"");
                    }, explode(',', $matches[1]));
                }
            }
        }
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
        $errors[] = "Error code: " . $e->getCode();
        
        // Try alternative method if direct ALTER fails
        if (strpos($e->getMessage(), 'Duplicate') === false) {
            $errors[] = "<br><strong>Alternative method:</strong> You may need to use the step-by-step migration in the SQL file.";
        }
    } catch (Exception $e) {
        $errors[] = "Error: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrate Evidence of Delivery - Andcorp Autos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <h1>Add Evidence of Delivery Document Type</h1>
        <p class="text-muted">This migration adds 'evidence_of_delivery' to the order_documents document_type ENUM.</p>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h5>Errors:</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <h5>✓ Migration Successful!</h5>
                <p class="mb-0">The 'evidence_of_delivery' document type has been added to the database.</p>
            </div>
        <?php endif; ?>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Current ENUM Values</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($currentEnumValues)): ?>
                            <p class="text-muted">Could not retrieve current values.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($currentEnumValues as $value): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <code><?php echo htmlspecialchars($value); ?></code>
                                        <?php if ($value === 'evidence_of_delivery'): ?>
                                            <span class="badge bg-success">✓ Present</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Migration Status</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($needsMigration): ?>
                            <div class="alert alert-warning">
                                <strong>Migration Needed:</strong> The 'evidence_of_delivery' type is not in the database.
                            </div>
                            
                            <form method="POST">
                                <?php echo Security::csrfField(); ?>
                                <p>Click the button below to run the migration:</p>
                                <button type="submit" name="run_migration" class="btn btn-primary">
                                    Run Migration
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <strong>✓ Already Migrated:</strong> The 'evidence_of_delivery' type is already in the database.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>SQL Statement</h5>
            </div>
            <div class="card-body">
                <p>If the automatic migration fails, you can run this SQL manually in phpMyAdmin:</p>
                <pre class="bg-light p-3 border">ALTER TABLE order_documents 
MODIFY COLUMN document_type ENUM(
    'car_image', 
    'title', 
    'bill_of_lading', 
    'bill_of_entry',
    'evidence_of_delivery'
) NOT NULL;</pre>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="<?php echo url('admin/dashboard.php'); ?>" class="btn btn-secondary">Back to Dashboard</a>
            <a href="<?php echo url('admin/check_document_types.php'); ?>" class="btn btn-info">Check Document Types</a>
        </div>
    </div>
</body>
</html>

