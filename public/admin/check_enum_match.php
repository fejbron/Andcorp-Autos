<?php
require_once '../../bootstrap.php';
Auth::requireStaff();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENUM Diagnostic - AndCorp Autos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .match { color: green; font-weight: bold; }
        .mismatch { color: red; font-weight: bold; }
        .hex { font-family: monospace; font-size: 0.9em; color: #666; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container py-4">
        <h2 class="mb-4"><i class="bi bi-search"></i> ENUM Value Diagnostic</h2>
        
        <?php
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get actual ENUM values from database
            $sql = "SHOW COLUMNS FROM orders WHERE Field = 'status'";
            $stmt = $db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo '<div class="card mb-4">';
            echo '<div class="card-header bg-primary text-white"><h5>Database ENUM Values</h5></div>';
            echo '<div class="card-body">';
            
            if ($result && isset($result['Type'])) {
                echo '<p><strong>Column Type:</strong> <code>' . htmlspecialchars($result['Type']) . '</code></p>';
                
                // Parse ENUM values
                if (preg_match("/enum\('(.*)'\)/i", $result['Type'], $matches)) {
                    $dbEnumValues = explode("','", $matches[1]);
                    
                    echo '<h6>Database ENUM Values (' . count($dbEnumValues) . '):</h6>';
                    echo '<ul>';
                    foreach ($dbEnumValues as $index => $value) {
                        $hex = bin2hex($value);
                        $hasWhitespace = preg_match('/\s/', $value);
                        $whitespaceIndicator = $hasWhitespace ? ' <span class="badge bg-danger">⚠️ HAS WHITESPACE</span>' : '';
                        echo '<li>';
                        echo '<strong>' . htmlspecialchars($value) . '</strong>';
                        echo $whitespaceIndicator;
                        echo '<br><span class="hex">Length: ' . strlen($value) . ' bytes | Hex: ' . $hex . '</span>';
                        echo '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p class="text-danger">Could not parse ENUM values!</p>';
                }
            } else {
                echo '<p class="text-danger">Could not retrieve column information!</p>';
            }
            
            echo '</div></div>';
            
            // Code expected values
            $codeExpectedValues = ['Pending', 'Purchased', 'Shipping', 'Customs', 'Inspection', 'Repair', 'Ready', 'Delivered', 'Cancelled'];
            
            echo '<div class="card mb-4">';
            echo '<div class="card-header bg-info text-white"><h5>Code Expected Values</h5></div>';
            echo '<div class="card-body">';
            echo '<h6>Expected ENUM Values (' . count($codeExpectedValues) . '):</h6>';
            echo '<ul>';
            foreach ($codeExpectedValues as $value) {
                $hex = bin2hex($value);
                $hasWhitespace = preg_match('/\s/', $value);
                $whitespaceIndicator = $hasWhitespace ? ' <span class="badge bg-danger">⚠️ HAS WHITESPACE</span>' : '';
                echo '<li>';
                echo '<strong>' . htmlspecialchars($value) . '</strong>';
                echo $whitespaceIndicator;
                echo '<br><span class="hex">Length: ' . strlen($value) . ' bytes | Hex: ' . $hex . '</span>';
                echo '</li>';
            }
            echo '</ul>';
            echo '</div></div>';
            
            // Compare values
            if (isset($dbEnumValues) && is_array($dbEnumValues)) {
                echo '<div class="card mb-4">';
                echo '<div class="card-header bg-warning text-dark"><h5>Comparison Results</h5></div>';
                echo '<div class="card-body">';
                
                // Check if arrays match
                $dbTrimmed = array_map('trim', $dbEnumValues);
                $codeTrimmed = array_map('trim', $codeExpectedValues);
                
                $missingInDb = array_diff($codeTrimmed, $dbTrimmed);
                $extraInDb = array_diff($dbTrimmed, $codeTrimmed);
                
                if (empty($missingInDb) && empty($extraInDb) && count($dbTrimmed) === count($codeTrimmed)) {
                    // Check exact match (case-sensitive)
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
                        echo '<div class="alert alert-success">';
                        echo '<h6><i class="bi bi-check-circle"></i> ✅ Perfect Match!</h6>';
                        echo '<p>All ENUM values match exactly (including case and whitespace).</p>';
                        echo '</div>';
                    } else {
                        echo '<div class="alert alert-warning">';
                        echo '<h6><i class="bi bi-exclamation-triangle"></i> ⚠️ Case/Whitespace Mismatch</h6>';
                        echo '<p>Values exist but have case or whitespace differences:</p>';
                        echo '<ul>';
                        foreach ($caseIssues as $issue) {
                            echo '<li>';
                            echo '<strong>Expected:</strong> <code>' . htmlspecialchars($issue['expected']) . '</code> ';
                            echo '(Length: ' . strlen($issue['expected']) . ', Hex: ' . bin2hex($issue['expected']) . ')';
                            echo '<br>';
                            echo '<strong>Actual:</strong> <code>' . htmlspecialchars($issue['actual']) . '</code> ';
                            echo '(Length: ' . strlen($issue['actual']) . ', Hex: ' . bin2hex($issue['actual']) . ')';
                            echo '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="alert alert-danger">';
                    echo '<h6><i class="bi bi-x-circle"></i> ❌ Mismatch Found!</h6>';
                    
                    if (!empty($missingInDb)) {
                        echo '<p><strong>Missing in Database:</strong></p>';
                        echo '<ul>';
                        foreach ($missingInDb as $missing) {
                            echo '<li><code>' . htmlspecialchars($missing) . '</code></li>';
                        }
                        echo '</ul>';
                    }
                    
                    if (!empty($extraInDb)) {
                        echo '<p><strong>Extra in Database:</strong></p>';
                        echo '<ul>';
                        foreach ($extraInDb as $extra) {
                            echo '<li><code>' . htmlspecialchars($extra) . '</code></li>';
                        }
                        echo '</ul>';
                    }
                    
                    echo '</div>';
                }
                
                // Detailed character-by-character comparison
                echo '<h6>Detailed Character Analysis:</h6>';
                echo '<table class="table table-sm table-bordered">';
                echo '<thead><tr><th>Index</th><th>Expected</th><th>Database</th><th>Match</th><th>Expected Hex</th><th>Database Hex</th></tr></thead>';
                echo '<tbody>';
                $maxCount = max(count($codeExpectedValues), count($dbEnumValues));
                for ($i = 0; $i < $maxCount; $i++) {
                    $expected = $codeExpectedValues[$i] ?? 'N/A';
                    $actual = $dbEnumValues[$i] ?? 'N/A';
                    $matches = ($expected === $actual);
                    
                    echo '<tr>';
                    echo '<td>' . $i . '</td>';
                    echo '<td><code>' . htmlspecialchars($expected) . '</code></td>';
                    echo '<td><code>' . htmlspecialchars($actual) . '</code></td>';
                    echo '<td class="' . ($matches ? 'match' : 'mismatch') . '">' . ($matches ? '✅' : '❌') . '</td>';
                    echo '<td class="hex">' . ($expected !== 'N/A' ? bin2hex($expected) : 'N/A') . '</td>';
                    echo '<td class="hex">' . ($actual !== 'N/A' ? bin2hex($actual) : 'N/A') . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                
                echo '</div></div>';
            }
            
            // Check for whitespace in recent orders
            echo '<div class="card mb-4">';
            echo '<div class="card-header bg-secondary text-white"><h5>Sample Data Check</h5></div>';
            echo '<div class="card-body">';
            
            $sql = "SELECT DISTINCT status FROM orders ORDER BY id DESC LIMIT 20";
            $stmt = $db->query($sql);
            $sampleStatuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($sampleStatuses)) {
                echo '<h6>Status Values in Existing Orders:</h6>';
                echo '<ul>';
                foreach ($sampleStatuses as $status) {
                    $hasWhitespace = preg_match('/\s/', $status);
                    $trimmed = trim($status);
                    $whitespaceIndicator = $hasWhitespace ? ' <span class="badge bg-danger">⚠️ HAS WHITESPACE</span>' : '';
                    $inExpected = in_array($trimmed, $codeExpectedValues, true) ? ' <span class="badge bg-success">✓ Valid</span>' : ' <span class="badge bg-warning">? Unknown</span>';
                    
                    echo '<li>';
                    echo '<code>' . htmlspecialchars($status) . '</code>';
                    echo $whitespaceIndicator;
                    echo $inExpected;
                    echo '<br><span class="hex">Length: ' . strlen($status) . ' | Trimmed: "' . htmlspecialchars($trimmed) . '" | Hex: ' . bin2hex($status) . '</span>';
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p class="text-muted">No orders found in database.</p>';
            }
            
            echo '</div></div>';
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">';
            echo '<h6>Error:</h6>';
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
            echo '</div>';
        }
        ?>
        
        <div class="card">
            <div class="card-header bg-dark text-white"><h5>Quick Actions</h5></div>
            <div class="card-body">
                <a href="<?php echo url('admin/update_status_enum.php'); ?>" class="btn btn-primary">
                    <i class="bi bi-database-check"></i> Fix Database ENUM
                </a>
                <a href="<?php echo url('admin/orders/create.php'); ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Create Order
                </a>
            </div>
        </div>
    </div>
</body>
</html>

