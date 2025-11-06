<?php
require_once 'public/bootstrap.php';

// Bypass auth for CLI
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "=== ENUM Diagnostic ===\n\n";
    
    // Get actual ENUM values from database
    $sql = "SHOW COLUMNS FROM orders WHERE Field = 'status'";
    $stmt = $db->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Database Column Type:\n";
    echo $result['Type'] . "\n\n";
    
    // Parse ENUM values
    if (preg_match("/enum\('(.*)'\)/i", $result['Type'], $matches)) {
        $dbEnumValues = explode("','", $matches[1]);
        
        echo "Database ENUM Values (" . count($dbEnumValues) . "):\n";
        foreach ($dbEnumValues as $index => $value) {
            $hex = bin2hex($value);
            $hasWhitespace = preg_match('/\s/', $value);
            $length = strlen($value);
            $whitespaceNote = $hasWhitespace ? " ⚠️ HAS WHITESPACE" : "";
            echo "  [$index] '$value' (Length: $length, Hex: $hex)$whitespaceNote\n";
        }
    }
    
    echo "\n";
    echo "Code Expected Values:\n";
    $codeExpectedValues = ['Pending', 'Purchased', 'Shipping', 'Customs', 'Inspection', 'Repair', 'Ready', 'Delivered', 'Cancelled'];
    foreach ($codeExpectedValues as $index => $value) {
        $hex = bin2hex($value);
        $length = strlen($value);
        echo "  [$index] '$value' (Length: $length, Hex: $hex)\n";
    }
    
    echo "\n=== Comparison ===\n";
    
    if (isset($dbEnumValues)) {
        $dbTrimmed = array_map('trim', $dbEnumValues);
        $codeTrimmed = array_map('trim', $codeExpectedValues);
        
        $missingInDb = array_diff($codeTrimmed, $dbTrimmed);
        $extraInDb = array_diff($dbTrimmed, $codeTrimmed);
        
        if (empty($missingInDb) && empty($extraInDb) && count($dbTrimmed) === count($codeTrimmed)) {
            // Check exact match
            $exactMatch = true;
            $caseIssues = [];
            foreach ($codeExpectedValues as $index => $expected) {
                if (!isset($dbEnumValues[$index]) || $dbEnumValues[$index] !== $expected) {
                    $exactMatch = false;
                    if (isset($dbEnumValues[$index])) {
                        $caseIssues[] = [
                            'expected' => $expected,
                            'actual' => $dbEnumValues[$index],
                            'index' => $index
                        ];
                    }
                }
            }
            
            if ($exactMatch) {
                echo "✅ Perfect Match! All values match exactly.\n";
            } else {
                echo "⚠️ Case/Whitespace Mismatch:\n";
                foreach ($caseIssues as $issue) {
                    echo "  Index $index: Expected '$issue[expected]' (Hex: " . bin2hex($issue['expected']) . ")\n";
                    echo "              Got      '$issue[actual]' (Hex: " . bin2hex($issue['actual']) . ")\n";
                }
            }
        } else {
            echo "❌ Value Mismatch:\n";
            if (!empty($missingInDb)) {
                echo "  Missing in Database: " . implode(', ', $missingInDb) . "\n";
            }
            if (!empty($extraInDb)) {
                echo "  Extra in Database: " . implode(', ', $extraInDb) . "\n";
            }
        }
        
        echo "\n=== Character-by-Character Comparison ===\n";
        $maxCount = max(count($codeExpectedValues), count($dbEnumValues));
        for ($i = 0; $i < $maxCount; $i++) {
            $expected = $codeExpectedValues[$i] ?? 'N/A';
            $actual = $dbEnumValues[$i] ?? 'N/A';
            $matches = ($expected === $actual);
            $status = $matches ? '✅' : '❌';
            echo sprintf("%-3s [%2d] Expected: '%-12s' | Database: '%-12s'\n", $status, $i, $expected, $actual);
            if (!$matches && $expected !== 'N/A' && $actual !== 'N/A') {
                echo "      Expected Hex: " . bin2hex($expected) . "\n";
                echo "      Database Hex: " . bin2hex($actual) . "\n";
            }
        }
    }
    
    // Check sample data
    echo "\n=== Sample Status Values in Orders ===\n";
    $sql = "SELECT DISTINCT status FROM orders LIMIT 10";
    $stmt = $db->query($sql);
    $sampleStatuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($sampleStatuses)) {
        foreach ($sampleStatuses as $status) {
            $hasWhitespace = preg_match('/\s/', $status);
            $trimmed = trim($status);
            $inExpected = in_array($trimmed, $codeExpectedValues, true);
            $wsNote = $hasWhitespace ? " ⚠️ HAS WHITESPACE" : "";
            $validNote = $inExpected ? " ✓ Valid" : " ? Unknown";
            echo "  '$status' (Length: " . strlen($status) . ")$wsNote$validNote\n";
        }
    } else {
        echo "  No orders found.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

