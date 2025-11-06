<?php
require_once '../../bootstrap.php';
Auth::requireStaff();

header('Content-Type: text/plain');

$db = Database::getInstance()->getConnection();

// Check actual ENUM values in the database
$sql = "SHOW COLUMNS FROM orders WHERE Field = 'status'";
$stmt = $db->query($sql);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "=== Database Status Column Info ===\n";
echo "Type: " . ($result['Type'] ?? 'NOT FOUND') . "\n";
echo "Null: " . ($result['Null'] ?? 'N/A') . "\n";
echo "Default: " . ($result['Default'] ?? 'N/A') . "\n";
echo "\n";

// Parse ENUM values
if (isset($result['Type']) && preg_match("/enum\('(.*)'\)/i", $result['Type'], $matches)) {
    $enumValues = explode("','", $matches[1]);
    echo "Actual ENUM values in database:\n";
    foreach ($enumValues as $value) {
        echo "  - '$value'\n";
    }
} else {
    echo "Could not parse ENUM values from: " . ($result['Type'] ?? 'NULL') . "\n";
}

echo "\n=== Code Expected Values ===\n";
$expectedValues = ['pending', 'purchased', 'shipping', 'customs', 'inspection', 'repair', 'ready', 'delivered', 'cancelled'];
foreach ($expectedValues as $value) {
    echo "  - '$value'\n";
}

echo "\n=== Test Status Values ===\n";
$testValues = ['pending', 'purchased', 'Pending', 'PURCHASED', ' pending ', 'purchased '];
foreach ($testValues as $testValue) {
    $trimmed = trim(strtolower($testValue));
    $isValid = in_array($trimmed, $expectedValues, true);
    echo "  '$testValue' -> '$trimmed' -> " . ($isValid ? 'VALID' : 'INVALID') . "\n";
}

