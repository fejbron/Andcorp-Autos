<?php
/**
 * Setup Support Tickets Tables
 * This script creates the necessary database tables for the support ticket system
 * Run this once to set up the tables
 */

require_once '../bootstrap.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();
$errors = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_tables'])) {
    try {
        // Create support_tickets table
        $sql1 = "CREATE TABLE IF NOT EXISTS support_tickets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            ticket_number VARCHAR(50) UNIQUE NOT NULL,
            customer_id INT NOT NULL,
            order_id INT DEFAULT NULL,
            subject VARCHAR(255) NOT NULL,
            category ENUM('general', 'order', 'payment', 'shipping', 'technical', 'other') DEFAULT 'general',
            priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
            status ENUM('open', 'pending', 'resolved', 'closed') DEFAULT 'open',
            assigned_to INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            closed_at TIMESTAMP NULL DEFAULT NULL,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_customer_id (customer_id),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql1);
        $success[] = "✅ support_tickets table created successfully";
        
        // Create ticket_replies table
        $sql2 = "CREATE TABLE IF NOT EXISTS ticket_replies (
            id INT PRIMARY KEY AUTO_INCREMENT,
            ticket_id INT NOT NULL,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            is_staff_reply TINYINT(1) DEFAULT 0,
            attachment_path VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_ticket_id (ticket_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql2);
        $success[] = "✅ ticket_replies table created successfully";
        
        $success[] = "🎉 All support ticket tables created successfully!";
        
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
        error_log("Setup tickets error: " . $e->getMessage());
    }
}

// Check if tables exist
$tablesExist = false;
try {
    $stmt = $db->query("SHOW TABLES LIKE 'support_tickets'");
    $tablesExist = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    $errors[] = "Error checking tables: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Support Tickets - Andcorp Autos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-gear"></i> Support Tickets Setup</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <?php foreach ($success as $msg): ?>
                                    <div><?php echo $msg; ?></div>
                                <?php endforeach; ?>
                            </div>
                            <a href="<?php echo url('admin/tickets.php'); ?>" class="btn btn-primary">
                                <i class="bi bi-arrow-right"></i> Go to Support Tickets
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <div><?php echo $error; ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($tablesExist): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Support ticket tables already exist in the database.
                            </div>
                            <a href="<?php echo url('admin/tickets.php'); ?>" class="btn btn-primary">
                                <i class="bi bi-arrow-right"></i> Go to Support Tickets
                            </a>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> Support ticket tables are not yet created.
                            </div>
                            
                            <h5>This will create the following tables:</h5>
                            <ul>
                                <li><code>support_tickets</code> - Main tickets table</li>
                                <li><code>ticket_replies</code> - Ticket messages/replies table</li>
                            </ul>

                            <form method="POST" action="">
                                <button type="submit" name="create_tables" class="btn btn-success btn-lg">
                                    <i class="bi bi-plus-circle"></i> Create Support Ticket Tables
                                </button>
                            </form>
                        <?php endif; ?>

                        <hr>
                        <a href="<?php echo url('admin/dashboard.php'); ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

