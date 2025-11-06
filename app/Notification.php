<?php

class Notification {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($userId, $orderId, $type, $subject, $message) {
        // Validate and sanitize inputs
        $userId = Security::sanitizeInt($userId);
        $orderId = $orderId ? Security::sanitizeInt($orderId) : null;
        $type = Security::validateEnum($type, ['email', 'sms', 'push']) ? $type : 'email';
        $subject = Security::sanitizeString($subject, 255);
        $message = Security::sanitizeString($message, 5000);
        
        if ($userId <= 0) {
            return false;
        }
        
        // Check if email notifications are enabled (for email type only)
        if ($type === 'email') {
            $settings = new Settings();
            if (!$settings->isEmailNotificationsEnabled()) {
                error_log("Email notifications are disabled in settings. Skipping notification.");
                return false;
            }
        }
        
        $sql = "INSERT INTO notifications (user_id, order_id, type, subject, message, status) 
                VALUES (:user_id, :order_id, :type, :subject, :message, 'pending')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':order_id' => $orderId,
            ':type' => $type,
            ':subject' => $subject,
            ':message' => $message
        ]);
        
        $notificationId = $this->db->lastInsertId();
        
        // Send notification based on type
        if ($type === 'email') {
            $this->sendEmail($notificationId);
        } elseif ($type === 'sms') {
            $this->sendSMS($notificationId);
        }
        
        return $notificationId;
    }
    
    public function sendOrderUpdate($orderId, $status, $additionalMessage = '') {
        // Check if email notifications are enabled for this status
        $settings = new Settings();
        if (!$settings->shouldSendEmailForStatus($status)) {
            error_log("Email notification skipped for status: {$status} (disabled in settings)");
            return false;
        }
        
        $orderModel = new Order();
        $order = $orderModel->findById($orderId);
        
        if (!$order) {
            return false;
        }
        
        // Normalize status to lowercase for message lookup
        $statusLower = strtolower($status);
        
        $statusMessages = [
            'pending' => 'Your order has been created and is pending processing.',
            'purchased' => 'Your vehicle has been successfully purchased from the auction!',
            'shipping' => 'Your vehicle is now being shipped to Ghana.',
            'customs' => 'Your vehicle has arrived and is going through customs clearance.',
            'inspection' => 'Your vehicle is currently being inspected.',
            'repair' => 'Your vehicle is in the shop for repairs and improvements.',
            'ready' => 'Great news! Your vehicle is ready for delivery.',
            'delivered' => 'Your vehicle has been delivered. Thank you for your business!',
            'cancelled' => 'Your order has been cancelled. If you have any questions, please contact us.'
        ];
        
        $subject = "Order Update: {$order['order_number']} - {$status}";
        $message = ($statusMessages[$statusLower] ?? 'Your order status has been updated.') . "\n\n" . $additionalMessage;
        
        // Get user from customer
        $customerModel = new Customer();
        $customer = $customerModel->findById($order['customer_id']);
        
        if (!$customer || !$customer['user_id']) {
            error_log("Cannot send email: Customer or user_id not found for order #{$orderId}");
            return false;
        }
        
        return $this->create($customer['user_id'], $orderId, 'email', $subject, $message);
    }
    
    private function sendEmail($notificationId) {
        $sql = "SELECT n.*, u.email, u.first_name 
                FROM notifications n
                JOIN users u ON n.user_id = u.id
                WHERE n.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $notificationId]);
        $notification = $stmt->fetch();
        
        if (!$notification) {
            return false;
        }
        
        // Get email settings
        $settings = new Settings();
        $fromName = $settings->getEmailFromName();
        $fromAddress = $settings->getEmailFromAddress();
        $replyTo = $settings->getEmailReplyTo();
        
        // Email headers
        $headers = "From: {$fromName} <{$fromAddress}>\r\n";
        $headers .= "Reply-To: {$replyTo}\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        // Email body (sanitize for email)
        $firstName = Security::escape($notification['first_name']);
        $subject = Security::escape($notification['subject']);
        $message = Security::escape($notification['message']);
        $body = $this->getEmailTemplate($firstName, $subject, $message);
        
        // Send email
        $sent = mail($notification['email'], $subject, $body, $headers);
        
        // Update notification status
        $updateSql = "UPDATE notifications SET status = :status, sent_at = CURRENT_TIMESTAMP WHERE id = :id";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->execute([
            ':id' => $notificationId,
            ':status' => $sent ? 'sent' : 'failed'
        ]);
        
        return $sent;
    }
    
    private function sendSMS($notificationId) {
        // Implement SMS sending logic here
        // This would integrate with your SMS provider (Twilio, Africa's Talking, etc.)
        
        if (getenv('SMS_ENABLED') !== 'true') {
            return false;
        }
        
        $sql = "SELECT n.*, u.phone, u.first_name 
                FROM notifications n
                JOIN users u ON n.user_id = u.id
                WHERE n.id = :id AND u.phone IS NOT NULL";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $notificationId]);
        $notification = $stmt->fetch();
        
        if (!$notification) {
            return false;
        }
        
        // TODO: Implement actual SMS sending
        // For now, mark as sent
        $updateSql = "UPDATE notifications SET status = 'sent', sent_at = CURRENT_TIMESTAMP WHERE id = :id";
        $updateStmt = $this->db->prepare($updateSql);
        return $updateStmt->execute([':id' => $notificationId]);
    }
    
    private function getEmailTemplate($name, $subject, $message) {
        // Inputs are already sanitized, but ensure HTML safety
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $message = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset=\"UTF-8\">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4a90e2; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 20px; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>AndCorp Car Dealership</h1>
                </div>
                <div class='content'>
                    <p>Dear {$name},</p>
                    <h2>{$subject}</h2>
                    <p>{$message}</p>
                    <p>If you have any questions, please don't hesitate to contact us.</p>
                    <p>Best regards,<br>The AndCorp Team</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " AndCorp Car Dealership. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    public function getUserNotifications($userId, $unreadOnly = false) {
        $userId = Security::sanitizeInt($userId);
        if ($userId <= 0) {
            return [];
        }
        
        if ($unreadOnly) {
            $sql = "SELECT * FROM notifications WHERE user_id = :user_id AND read_at IS NULL ORDER BY created_at DESC LIMIT 50";
        } else {
            $sql = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 50";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    public function getById($notificationId) {
        $sql = "SELECT * FROM notifications WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => Security::sanitizeInt($notificationId)]);
        return $stmt->fetch();
    }
    
    public function markAsRead($notificationId) {
        $sql = "UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => Security::sanitizeInt($notificationId)]);
    }
}
