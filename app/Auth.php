<?php

class Auth {
    
    public static function login($email, $password) {
        $userModel = new User();
        $user = $userModel->findByEmail($email);
        
        if ($user && $userModel->verifyPassword($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            
            // Log activity
            self::logActivity($user['id'], 'login', 'User logged in');
            
            return true;
        }
        
        return false;
    }
    
    public static function logout() {
        if (isset($_SESSION['user_id'])) {
            self::logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        
        // Clear all session variables
        $_SESSION = [];
        
        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy the session
        session_destroy();
        return true;
    }
    
    public static function check() {
        return isset($_SESSION['user_id']);
    }
    
    public static function user() {
        if (!self::check()) {
            return null;
        }
        
        // Validate session user_id
        $userId = Security::sanitizeInt($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }
        
        $userModel = new User();
        return $userModel->findById($userId);
    }
    
    public static function userId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function userRole() {
        return $_SESSION['user_role'] ?? null;
    }
    
    public static function isAdmin() {
        return self::userRole() === 'admin';
    }
    
    public static function isStaff() {
        return in_array(self::userRole(), ['admin', 'staff']);
    }
    
    public static function isCustomer() {
        return self::userRole() === 'customer';
    }
    
    public static function requireAuth() {
        if (!self::check()) {
            redirect('login.php');
        }
    }
    
    public static function requireAdmin() {
        self::requireAuth();
        if (!self::isAdmin()) {
            redirect('dashboard.php');
        }
    }
    
    public static function requireStaff() {
        self::requireAuth();
        if (!self::isStaff()) {
            redirect('dashboard.php');
        }
    }
    
    private static function logActivity($userId, $action, $description = null) {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                    VALUES (:user_id, :action, :description, :ip_address)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':action' => $action,
                ':description' => $description,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the operation
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
    
    public static function logOrderActivity($userId, $orderId, $action, $description = null) {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "INSERT INTO activity_logs (user_id, order_id, action, description, ip_address) 
                    VALUES (:user_id, :order_id, :action, :description, :ip_address)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':order_id' => $orderId,
                ':action' => $action,
                ':description' => $description,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the operation
            error_log("Failed to log order activity: " . $e->getMessage());
        }
    }
}
