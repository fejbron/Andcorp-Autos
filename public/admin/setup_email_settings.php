<?php
/**
 * Setup Email Notification Settings Table
 * Run this once to create the email_notification_settings table
 */

require_once '../../bootstrap.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// Read and execute SQL file
$sqlFile = __DIR__ . '/../../database/email_notification_settings.sql';
$sql = file_get_contents($sqlFile);

if (!$sql) {
    die("Error: Could not read SQL file: $sqlFile");
}

// Split SQL into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success = true;
$errors = [];

try {
    $db->beginTransaction();
    
    foreach ($statements as $statement) {
        if (empty($statement)) {
            continue;
        }
        
        try {
            $db->exec($statement);
        } catch (PDOException $e) {
            // Ignore "table already exists" errors
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate entry') === false) {
                $errors[] = $e->getMessage();
                $success = false;
            }
        }
    }
    
    if ($success) {
        $db->commit();
        $message = "Email notification settings table created successfully!";
        $messageType = "success";
    } else {
        $db->rollBack();
        $message = "Some errors occurred while creating the table.";
        $messageType = "warning";
    }
} catch (Exception $e) {
    $db->rollBack();
    $message = "Error: " . $e->getMessage();
    $messageType = "danger";
    $errors[] = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Email Settings - Andcorp Autos Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-gear"></i> Setup Email Notification Settings</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($messageType === 'success'): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> <?php echo $message; ?>
                            </div>
                            <p>The email notification settings table has been created and populated with default settings.</p>
                            <a href="<?php echo url('admin/settings.php'); ?>" class="btn btn-primary">
                                <i class="bi bi-arrow-right"></i> Go to Settings
                            </a>
                        <?php elseif ($messageType === 'warning'): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo $message; ?>
                            </div>
                            <?php if (!empty($errors)): ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($errors as $error): ?>
                                        <li class="text-danger"><i class="bi bi-x-circle"></i> <?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-x-circle"></i> <?php echo $message; ?>
                            </div>
                            <?php if (!empty($errors)): ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($errors as $error): ?>
                                        <li class="text-danger"><i class="bi bi-x-circle"></i> <?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <h6>Next Steps:</h6>
                            <ol>
                                <li>Go to <strong>Settings</strong> page</li>
                                <li>Configure your email notification preferences</li>
                                <li>Set your email addresses (From Name, From Address, Reply-To)</li>
                                <li>Enable/disable specific status notifications as needed</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

