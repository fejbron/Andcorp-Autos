<?php
/**
 * Diagnostic script to check if Settings model file exists
 * This helps diagnose why Settings class is not found on live server
 */

require_once '../bootstrap.php';
Auth::requireAdmin();

$checks = [];
$errors = [];

// Check 1: Check if Settings.php file exists in expected location
$expectedPaths = [
    __DIR__ . '/../../app/Models/Settings.php',
    __DIR__ . '/../app/Models/Settings.php',
    dirname(dirname(__DIR__)) . '/app/Models/Settings.php',
];

$foundPath = null;
foreach ($expectedPaths as $path) {
    if (file_exists($path)) {
        $foundPath = $path;
        $checks[] = ['status' => 'success', 'message' => "Settings.php found at: $path"];
        break;
    } else {
        $checks[] = ['status' => 'info', 'message' => "Checked: $path (not found)"];
    }
}

if (!$foundPath) {
    $checks[] = ['status' => 'error', 'message' => 'Settings.php NOT FOUND in any expected location'];
    $errors[] = 'Settings.php file is missing';
}

// Check 2: Check if class can be autoloaded
if (class_exists('Settings')) {
    $checks[] = ['status' => 'success', 'message' => 'Settings class can be loaded via autoloader'];
} else {
    $checks[] = ['status' => 'error', 'message' => 'Settings class CANNOT be loaded via autoloader'];
    $errors[] = 'Autoloader cannot find Settings class';
}

// Check 3: Check autoloader paths
$autoloaderPaths = [
    __DIR__ . '/../../app/',
    __DIR__ . '/../../app/Models/',
];
$checks[] = ['status' => 'info', 'message' => 'Autoloader looks in: ' . implode(', ', $autoloaderPaths)];

// Check 4: Check if app/Models directory exists
$modelsDir = dirname(dirname(__DIR__)) . '/app/Models';
if (is_dir($modelsDir)) {
    $checks[] = ['status' => 'success', 'message' => "app/Models directory exists: $modelsDir"];
    
    // List files in Models directory
    $files = scandir($modelsDir);
    $files = array_filter($files, fn($f) => $f !== '.' && $f !== '..');
    $checks[] = ['status' => 'info', 'message' => 'Files in app/Models: ' . implode(', ', $files)];
} else {
    $checks[] = ['status' => 'error', 'message' => "app/Models directory NOT FOUND: $modelsDir"];
    $errors[] = 'app/Models directory is missing';
}

// Check 5: Try to manually require the file if it exists
if ($foundPath) {
    try {
        require_once $foundPath;
        if (class_exists('Settings')) {
            $checks[] = ['status' => 'success', 'message' => 'Settings class loaded successfully after manual require'];
        } else {
            $checks[] = ['status' => 'error', 'message' => 'File exists but class still not found after manual require'];
            $errors[] = 'Settings.php file exists but class definition is missing or incorrect';
        }
    } catch (Exception $e) {
        $checks[] = ['status' => 'error', 'message' => 'Error requiring file: ' . $e->getMessage()];
        $errors[] = $e->getMessage();
    }
}

// Check 6: Check __DIR__ values for debugging
$checks[] = ['status' => 'info', 'message' => 'Current script __DIR__: ' . __DIR__];
$checks[] = ['status' => 'info', 'message' => 'Project root (estimated): ' . dirname(dirname(__DIR__))];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings File Diagnostic - Andcorp Autos Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-bug"></i> Settings Model Diagnostic Tool</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($errors)): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> <strong>All checks passed!</strong> Settings model should be working.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> <strong>Issues found:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <h6 class="mt-4">Diagnostic Results:</h6>
                        <div class="list-group">
                            <?php foreach ($checks as $check): ?>
                                <div class="list-group-item">
                                    <?php if ($check['status'] === 'success'): ?>
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    <?php elseif ($check['status'] === 'error'): ?>
                                        <i class="bi bi-x-circle-fill text-danger"></i>
                                    <?php else: ?>
                                        <i class="bi bi-info-circle-fill text-info"></i>
                                    <?php endif; ?>
                                    <span class="ms-2"><?php echo htmlspecialchars($check['message']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="mt-4">
                                <h6>How to Fix:</h6>
                                <ol>
                                    <li><strong>Upload the Settings.php file:</strong>
                                        <ul>
                                            <li>File location: <code>app/Models/Settings.php</code></li>
                                            <li>Upload it to your server in the same location</li>
                                            <li>Make sure the file permissions are correct (644)</li>
                                        </ul>
                                    </li>
                                    <li><strong>Verify the file path:</strong>
                                        <ul>
                                            <li>The file should be at: <code><?php echo htmlspecialchars(dirname(dirname(__DIR__)) . '/app/Models/Settings.php'); ?></code></li>
                                            <li>Or relative to your web root</li>
                                        </ul>
                                    </li>
                                    <li><strong>Check file permissions:</strong>
                                        <ul>
                                            <li>Files should be 644</li>
                                            <li>Directories should be 755</li>
                                        </ul>
                                    </li>
                                </ol>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="<?php echo url('admin/settings.php'); ?>" class="btn btn-primary">
                                <i class="bi bi-arrow-left"></i> Back to Settings
                            </a>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">
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

