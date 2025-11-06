<?php
/**
 * Check if email_notification_settings table exists
 */

require_once '../bootstrap.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();
$tableExists = false;
$tableError = null;
$rowCount = 0;

try {
    // Check if table exists
    $result = $db->query("SHOW TABLES LIKE 'email_notification_settings'")->fetch();
    $tableExists = $result !== false;
    
    if ($tableExists) {
        // Count rows
        $countResult = $db->query("SELECT COUNT(*) as count FROM email_notification_settings")->fetch();
        $rowCount = $countResult['count'] ?? 0;
        
        // Check table structure
        $columns = $db->query("DESCRIBE email_notification_settings")->fetchAll();
    }
} catch (PDOException $e) {
    $tableError = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Email Settings Table - Andcorp Autos Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-<?php echo $tableExists ? 'success' : 'danger'; ?> text-white">
                        <h5 class="mb-0"><i class="bi bi-database"></i> Email Settings Table Check</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($tableExists): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> <strong>Table exists!</strong> The email_notification_settings table is found.
                            </div>
                            
                            <p><strong>Row count:</strong> <?php echo $rowCount; ?> settings</p>
                            
                            <?php if ($rowCount == 0): ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i> The table exists but has no settings. You need to run the setup script to insert default settings.
                                </div>
                            <?php endif; ?>
                            
                            <h6>Table Structure:</h6>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Column</th>
                                        <th>Type</th>
                                        <th>Null</th>
                                        <th>Key</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($columns as $col): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($col['Field']); ?></td>
                                            <td><?php echo htmlspecialchars($col['Type']); ?></td>
                                            <td><?php echo htmlspecialchars($col['Null']); ?></td>
                                            <td><?php echo htmlspecialchars($col['Key']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-x-circle"></i> <strong>Table NOT FOUND!</strong> The email_notification_settings table does not exist.
                            </div>
                            
                            <?php if ($tableError): ?>
                                <div class="alert alert-warning">
                                    <strong>Error:</strong> <?php echo htmlspecialchars($tableError); ?>
                                </div>
                            <?php endif; ?>
                            
                            <h6>How to Fix:</h6>
                            <ol>
                                <li><strong>Run the setup script:</strong>
                                    <br><a href="<?php echo url('admin/setup_email_settings.php'); ?>" class="btn btn-primary btn-sm mt-2">
                                        <i class="bi bi-gear"></i> Run Setup Script
                                    </a>
                                </li>
                                <li><strong>Or manually create the table:</strong>
                                    <ol>
                                        <li>Go to cPanel → phpMyAdmin</li>
                                        <li>Select your database</li>
                                        <li>Click "SQL" tab</li>
                                        <li>Copy and paste the contents of: <code>database/email_notification_settings.sql</code></li>
                                        <li>Click "Go"</li>
                                    </ol>
                                </li>
                            </ol>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="<?php echo url('admin/settings.php'); ?>" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Settings
                            </a>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-primary">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

