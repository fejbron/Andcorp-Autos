<?php

/**
 * Security utility class for input validation, sanitization, and CSRF protection
 */
class Security {
    
    /**
     * Generate CSRF token
     */
    public static function generateToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get CSRF token field for forms
     */
    public static function csrfField() {
        return '<input type="hidden" name="csrf_token" value="' . self::generateToken() . '">';
    }
    
    /**
     * Validate and sanitize email
     */
    public static function validateEmail($email) {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
    }
    
    /**
     * Sanitize string input
     */
    public static function sanitizeString($input, $maxLength = null) {
        // Handle null values for PHP 8+ compatibility
        $input = $input ?? '';
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        if ($maxLength && mb_strlen($input) > $maxLength) {
            $input = mb_substr($input, 0, $maxLength);
        }
        return $input;
    }
    
    /**
     * Sanitize integer
     */
    public static function sanitizeInt($input, $min = null, $max = null) {
        $input = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        $input = (int) $input;
        if ($min !== null && $input < $min) return $min;
        if ($max !== null && $input > $max) return $max;
        return $input;
    }
    
    /**
     * Sanitize float/decimal
     */
    public static function sanitizeFloat($input, $min = null, $max = null) {
        $input = filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $input = (float) $input;
        if ($min !== null && $input < $min) return $min;
        if ($max !== null && $input > $max) return $max;
        return $input;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        if (mb_strlen($password) < 8) {
            return 'Password must be at least 8 characters long';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            return 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'Password must contain at least one number';
        }
        return true;
    }
    
    /**
     * Validate phone number (basic)
     */
    public static function validatePhone($phone) {
        $phone = preg_replace('/[^0-9+\-() ]/', '', $phone);
        return mb_strlen($phone) >= 10 && mb_strlen($phone) <= 20;
    }
    
    /**
     * Sanitize URL
     */
    public static function sanitizeUrl($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'], $maxSize = 5242880) {
        $errors = [];
        
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'No file uploaded'];
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Upload error: ' . $file['error']];
        }
        
        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'error' => 'File size exceeds maximum allowed size'];
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['valid' => false, 'error' => 'File type not allowed'];
        }
        
        // Additional security: check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        if (!in_array($extension, $allowedExtensions)) {
            return ['valid' => false, 'error' => 'File extension not allowed'];
        }
        
        return ['valid' => true, 'mime_type' => $mimeType];
    }
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit($key, $maxAttempts = 5, $window = 300) {
        $cacheKey = "rate_limit_{$key}";
        
        if (!isset($_SESSION[$cacheKey])) {
            $_SESSION[$cacheKey] = [
                'attempts' => 0,
                'reset_time' => time() + $window
            ];
        }
        
        $rateLimit = $_SESSION[$cacheKey];
        
        // Reset if window expired
        if (time() > $rateLimit['reset_time']) {
            $_SESSION[$cacheKey] = [
                'attempts' => 0,
                'reset_time' => time() + $window
            ];
            return true;
        }
        
        // Check if limit exceeded
        if ($rateLimit['attempts'] >= $maxAttempts) {
            return false;
        }
        
        // Increment attempts
        $_SESSION[$cacheKey]['attempts']++;
        return true;
    }
    
    /**
     * Get rate limit remaining
     */
    public static function getRateLimitRemaining($key) {
        $cacheKey = "rate_limit_{$key}";
        if (!isset($_SESSION[$cacheKey])) {
            return 5; // Default max attempts
        }
        return max(0, 5 - $_SESSION[$cacheKey]['attempts']);
    }
    
    /**
     * Escape output for XSS protection
     */
    public static function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate enum value
     */
    public static function validateEnum($value, array $allowed) {
        return in_array($value, $allowed, true);
    }
    
    /**
     * Sanitize order status
     */
    public static function sanitizeStatus($status) {
        // Normalize to capitalized format to match database ENUM
        $status = ucfirst(strtolower(trim($status ?? '')));
        $allowed = ['Pending', 'Purchased', 'Shipping', 'Customs', 'Inspection', 'Repair', 'Ready', 'Delivered', 'Cancelled'];
        return in_array($status, $allowed, true) ? $status : 'Pending';
    }
    
    /**
     * Sanitize role
     */
    public static function sanitizeRole($role) {
        $allowed = ['customer', 'staff', 'admin'];
        return in_array($role, $allowed, true) ? $role : 'customer';
    }
}

