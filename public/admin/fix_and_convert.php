<?php
require_once '../../bootstrap.php';
Auth::requireStaff();

$db = Database::getInstance()->getConnection();

// First, check current ENUM values
$sql = "SHOW COLUMNS FROM orders WHERE Field = 'status'";
$stmt = $db->query($sql);
$current = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Current Status ENUM:</h2>";
echo "<pre>" . htmlspecialchars($current['Type'] ?? 'NOT FOUND') . "</pre>";

// Try to update the ENUM with all required values
try {
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
    
    echo "<h2 style='color: green;'>✅ ENUM Updated Successfully!</h2>";
    
    // Verify
    $sql = "SHOW COLUMNS FROM orders WHERE Field = 'status'";
    $stmt = $db->query($sql);
    $updated = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Updated Status ENUM:</h2>";
    echo "<pre>" . htmlspecialchars($updated['Type'] ?? 'NOT FOUND') . "</pre>";
    
    // Test insert
    echo "<h2>Testing with 'shipping' status...</h2>";
    try {
        $testSql = "INSERT INTO orders (customer_id, order_number, status, total_cost, deposit_amount, balance_due, currency) 
                    VALUES (999999, 'TEST-999', 'shipping', 0, 0, 0, 'GHS')";
        $db->exec($testSql);
        $testId = $db->lastInsertId();
        
        // Delete test record
        $db->exec("DELETE FROM orders WHERE id = $testId");
        
        echo "<p style='color: green;'>✅ Test INSERT with 'shipping' status succeeded!</p>";
        echo "<p>The database now accepts 'shipping' and other status values.</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Test INSERT failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error updating ENUM:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    
    // Alternative: Check if we can use existing values
    echo "<h2>Alternative: Checking existing values...</h2>";
    if (preg_match("/enum\('(.*)'\)/i", $current['Type'] ?? '', $matches)) {
        $existingValues = explode("','", $matches[1]);
        echo "<p>Existing ENUM values: " . implode(', ', $existingValues) . "</p>";
        
        // Update code to use only existing values
        echo "<p><strong>Note:</strong> If the ALTER failed, you may need to update the code to use only these existing values, or manually update the database via phpMyAdmin.</p>";
    }
}

echo "<hr>";
echo "<a href='quote-requests/convert.php?id=" . ($_GET['id'] ?? '') . "'>← Go Back to Convert</a>";

