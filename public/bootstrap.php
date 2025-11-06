<?php
// Load Security and Headers classes first (before anything else)
require_once __DIR__ . '/../app/Security.php';
require_once __DIR__ . '/../app/Headers.php';
require_once __DIR__ . '/../app/Validator.php';
require_once __DIR__ . '/../app/Cache.php';

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
    // Use 'Lax' for local development to allow POST requests with CSRF tokens
    // In production, consider using 'Strict' for better security
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    ini_set('session.cookie_lifetime', 0); // Until browser closes
    
    session_start();
    
    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        // Regenerate every 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Set security headers
Headers::setSecurityHeaders();

// Enable output compression
Headers::enableCompression();

// Autoloader
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../app/' . $class . '.php',
        __DIR__ . '/../app/Models/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Helper functions
function getBasePath() {
    static $basePath = null;
    if ($basePath === null) {
        // Get the public directory path from SCRIPT_NAME
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
        
        if (!empty($scriptName)) {
            // Find the 'public' directory in the path
            if (strpos($scriptName, '/public/') !== false) {
                // Extract everything up to and including 'public'
                $parts = explode('/public/', $scriptName);
                $basePath = $parts[0] . '/public';
            } else {
                // Fallback to script directory
                $scriptDir = dirname($scriptName);
                if ($scriptDir !== '/' && $scriptDir !== '\\' && $scriptDir !== '.') {
                    $basePath = rtrim($scriptDir, '/');
                } else {
                    $basePath = '';
                }
            }
        } else {
            $basePath = '';
        }
    }
    return $basePath;
}

function redirect($url) {
    // If URL is absolute (starts with /), prepend base path if needed
    if (strpos($url, '/') === 0) {
        $basePath = getBasePath();
        // Only add base path if it's not already in the URL
        if ($basePath !== '/' && strpos($url, $basePath) !== 0) {
            $url = $basePath . $url;
        }
    }
    header("Location: $url");
    exit;
}

function old($key, $default = '') {
    return $_SESSION['old'][$key] ?? $default;
}

function clearOld() {
    unset($_SESSION['old']);
}

function setOld($data) {
    $_SESSION['old'] = $data;
}

function error($key) {
    return $_SESSION['errors'][$key] ?? null;
}

function hasError($key) {
    return isset($_SESSION['errors'][$key]);
}

function setErrors($errors) {
    $_SESSION['errors'] = $errors;
}

function clearErrors() {
    unset($_SESSION['errors']);
}

function success() {
    $message = $_SESSION['success'] ?? null;
    unset($_SESSION['success']);
    return $message;
}

function setSuccess($message) {
    $_SESSION['success'] = $message;
}

function formatCurrency($amount, $currency = 'GHS') {
    // Handle null values for PHP 8+ compatibility
    $amount = $amount ?? 0;
    return $currency . ' ' . number_format((float)$amount, 2);
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'warning',
        'purchased' => 'info',
        'shipping' => 'primary',
        'customs' => 'secondary',
        'inspection' => 'info',
        'repair' => 'warning',
        'ready' => 'success',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    return $classes[$status] ?? 'secondary';
}

function getStatusLabel($status) {
    return ucwords(str_replace('_', ' ', $status));
}

function uploadFile($file, $directory = 'uploads/') {
    // Validate file upload
    $validation = Security::validateFileUpload($file);
    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }
    
    $uploadDir = __DIR__ . '/../storage/' . $directory;
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate secure filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Set proper file permissions
        chmod($filepath, 0644);
        return ['success' => true, 'path' => $directory . $filename];
    }
    
    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

function asset($path) {
    return '/' . ltrim($path, '/');
}

function url($path = '') {
    // Always use absolute paths starting from document root
    $basePath = getBasePath();
    
    // If basePath is empty or root, just use the path directly
    if ($basePath === '' || $basePath === '/') {
        if ($path === '' || $path === '/') {
            return '/';
        }
        return '/' . ltrim($path, '/');
    }
    
    // Handle empty path or root
    if ($path === '' || $path === '/') {
        return $basePath . '/';
    }
    
    // Remove leading slash if present
    $path = ltrim($path, '/');
    
    // Ensure basePath doesn't have trailing slash (we'll add it)
    $basePath = rtrim($basePath, '/');
    
    return $basePath . '/' . $path;
}
