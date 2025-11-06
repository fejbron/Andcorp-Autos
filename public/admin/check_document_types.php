<?php
/**
 * Diagnostic script to check current document_type ENUM values
 * Access: https://app.andcorpautos.com/public/admin/check_document_types.php
 */
require_once '../bootstrap.php';
Auth::requireStaff();

$db = Database::getInstance()->getConnection();

// Get current ENUM values
$stmt = $db->query("SHOW COLUMNS FROM order_documents WHERE Field = 'document_type'");
$column = $stmt->fetch(PDO::FETCH_ASSOC);

// Extract ENUM values
$enumValues = [];
if ($column && isset($column['Type'])) {
    preg_match("/^enum\((.*)\)$/", $column['Type'], $matches);
    if (isset($matches[1])) {
        $enumValues = array_map(function($value) {
            return trim($value, "'");
        }, explode(',', $matches[1]));
    }
}

// Check if evidence_of_delivery exists
$hasEvidenceOfDelivery = in_array('evidence_of_delivery', $enumValues);

// Get count of documents by type
$countStmt = $db->query("SELECT document_type, COUNT(*) as count FROM order_documents GROUP BY document_type");
$typeCounts = $countStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Types Check - Andcorp Autos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <h1>Document Types Diagnostic</h1>
        <p class="text-muted">This page shows the current document_type ENUM values in the database.</p>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Current ENUM Values</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($enumValues)): ?>
                            <div class="alert alert-danger">
                                <strong>Error:</strong> Could not retrieve ENUM values. The table might not exist.
                            </div>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($enumValues as $value): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php echo htmlspecialchars($value); ?>
                                        <?php if ($value === 'evidence_of_delivery'): ?>
                                            <span class="badge bg-success">✓ Enabled</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <?php if (!$hasEvidenceOfDelivery): ?>
                                <div class="alert alert-warning mt-3">
                                    <strong>⚠️ Missing:</strong> 'evidence_of_delivery' is not in the ENUM values.
                                    <br><br>
                                    <strong>To fix this, run this SQL:</strong>
                                    <pre class="mt-2 p-2 bg-light border">ALTER TABLE order_documents 
MODIFY COLUMN document_type ENUM(
    'car_image', 
    'title', 
    'bill_of_lading', 
    'bill_of_entry',
    'evidence_of_delivery'
) NOT NULL;</pre>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success mt-3">
                                    <strong>✓ Success:</strong> 'evidence_of_delivery' is enabled in the database!
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Document Counts by Type</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($typeCounts)): ?>
                            <p class="text-muted">No documents found in the database.</p>
                        <?php else: ?>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Document Type</th>
                                        <th class="text-end">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($typeCounts as $count): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($count['document_type']); ?></td>
                                            <td class="text-end"><?php echo $count['count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Column Information</h5>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3 border"><?php print_r($column); ?></pre>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="<?php echo url('admin/dashboard.php'); ?>" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>

