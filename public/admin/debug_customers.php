<?php
/**
 * Diagnostic script to check customer database queries
 * Access this file directly to see database errors
 * Delete this file after debugging
 */

require_once '../bootstrap.php';

// Only allow access if logged in as admin (for security)
if (!Auth::check() || !Auth::isAdmin()) {
    die('Access denied. Admin login required.');
}

header('Content-Type: text/plain');

echo "=== CUSTOMER DATABASE DIAGNOSTICS ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connection successful\n\n";
    
    // Check if tables exist
    echo "Checking tables...\n";
    $tables = ['customers', 'users', 'orders', 'deposits'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo "  - $table: " . ($exists ? "✅ EXISTS" : "❌ NOT FOUND") . "\n";
    }
    echo "\n";
    
    // Check customers table structure
    echo "Checking customers table structure...\n";
    try {
        $stmt = $db->query("DESCRIBE customers");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "  Columns in customers table:\n";
        foreach ($columns as $col) {
            echo "    - {$col['Field']} ({$col['Type']})\n";
        }
    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // Check users table structure
    echo "Checking users table structure...\n";
    try {
        $stmt = $db->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "  Columns in users table:\n";
        foreach ($columns as $col) {
            echo "    - {$col['Field']} ({$col['Type']})\n";
        }
    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // Test the actual query
    echo "Testing customer query...\n";
    try {
        $sql = "SELECT c.*, u.email, u.first_name, u.last_name, u.phone, u.is_active, u.created_at as user_created_at
                FROM customers c
                JOIN users u ON c.user_id = u.id
                ORDER BY c.created_at DESC
                LIMIT 5";
        $stmt = $db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "  ✅ Query successful! Found " . count($results) . " customers\n";
        if (!empty($results)) {
            echo "  Sample customer data:\n";
            $sample = $results[0];
            foreach ($sample as $key => $value) {
                echo "    - $key: " . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "\n";
            }
        }
    } catch (PDOException $e) {
        echo "  ❌ Query failed!\n";
        echo "  Error Code: " . $e->getCode() . "\n";
        echo "  Error Message: " . $e->getMessage() . "\n";
        echo "  SQL State: " . $e->errorInfo[0] . "\n";
        if (isset($e->errorInfo[2])) {
            echo "  SQL Error Info: " . $e->errorInfo[2] . "\n";
        }
    }
    echo "\n";
    
    // Test Customer model
    echo "Testing Customer model...\n";
    try {
        $customerModel = new Customer();
        $customers = $customerModel->getAll(5);
        echo "  ✅ Customer::getAll() successful! Returned " . count($customers) . " customers\n";
        if (!empty($customers)) {
            echo "  Sample data type: " . gettype($customers[0]) . "\n";
            if (is_array($customers[0])) {
                echo "  Sample keys: " . implode(', ', array_keys($customers[0])) . "\n";
            }
        }
    } catch (Exception $e) {
        echo "  ❌ Customer::getAll() failed!\n";
        echo "  Error: " . $e->getMessage() . "\n";
        echo "  Error Class: " . get_class($e) . "\n";
    }
    echo "\n";
    
    // Check cache directory
    echo "Checking cache directory...\n";
    $cacheDir = __DIR__ . '/../storage/cache/';
    if (is_dir($cacheDir)) {
        echo "  ✅ Cache directory exists: $cacheDir\n";
        echo "  Writable: " . (is_writable($cacheDir) ? "✅ YES" : "❌ NO") . "\n";
    } else {
        echo "  ❌ Cache directory does not exist: $cacheDir\n";
        echo "  Attempting to create...\n";
        if (mkdir($cacheDir, 0755, true)) {
            echo "  ✅ Created successfully\n";
        } else {
            echo "  ❌ Failed to create\n";
        }
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "Error Class: " . get_class($e) . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== END DIAGNOSTICS ===\n";

