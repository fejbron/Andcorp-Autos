<?php

class Settings {
    private $db;
    private $lastError = null;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get a setting value by key
     */
    public function get($key, $default = null) {
        $key = Security::sanitizeString($key, 100);
        
        $sql = "SELECT setting_value FROM email_notification_settings WHERE setting_key = :key";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':key' => $key]);
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : $default;
    }
    
    /**
     * Get all settings
     */
    public function getAll() {
        try {
            $sql = "SELECT setting_key, setting_value, description FROM email_notification_settings ORDER BY setting_key";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = [
                    'value' => $row['setting_value'],
                    'description' => $row['description']
                ];
            }
            
            return $settings;
        } catch (PDOException $e) {
            error_log("Settings::getAll() error: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            throw $e;
        }
    }
    
    /**
     * Set a setting value
     */
    public function set($key, $value, $updatedBy = null) {
        $key = Security::sanitizeString($key, 100);
        $value = $value !== null ? Security::sanitizeString($value, 5000) : null;
        
        // Validate updated_by if provided
        $updatedBy = $updatedBy ? Security::sanitizeInt($updatedBy) : null;
        if ($updatedBy && $updatedBy <= 0) {
            $updatedBy = null; // Invalid user ID, set to null
        }
        
        try {
            // Use separate parameter names for UPDATE clause to avoid PDO parameter binding issues
            $sql = "INSERT INTO email_notification_settings (setting_key, setting_value, updated_by) 
                    VALUES (:key, :value, :updated_by)
                    ON DUPLICATE KEY UPDATE 
                        setting_value = VALUES(setting_value),
                        updated_by = VALUES(updated_by),
                        updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':key' => $key,
                ':value' => $value,
                ':updated_by' => $updatedBy
            ]);
            
            return $result;
        } catch (PDOException $e) {
            // Log the error for debugging
            error_log("Settings::set() error for key '$key': " . $e->getMessage());
            throw $e; // Re-throw to be caught by updateMultiple
        }
    }
    
    /**
     * Update multiple settings at once
     */
    public function updateMultiple($settings, $updatedBy = null) {
        if (empty($settings)) {
            return false;
        }
        
        $this->db->beginTransaction();
        
        try {
            foreach ($settings as $key => $value) {
                $result = $this->set($key, $value, $updatedBy);
                if (!$result) {
                    throw new Exception("Failed to update setting: $key");
                }
            }
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            $errorMsg = "Settings update PDO error: " . $e->getMessage();
            error_log($errorMsg);
            // Store error message for retrieval
            $this->lastError = $errorMsg;
            return false;
        } catch (Exception $e) {
            $this->db->rollBack();
            $errorMsg = "Settings update error: " . $e->getMessage();
            error_log($errorMsg);
            $this->lastError = $errorMsg;
            return false;
        }
    }
    
    /**
     * Get last error message
     */
    public function getLastError() {
        return $this->lastError ?? null;
    }
    
    /**
     * Check if email notifications are enabled
     */
    public function isEmailNotificationsEnabled() {
        return $this->get('email_notifications_enabled', '1') === '1';
    }
    
    /**
     * Check if email should be sent for specific event
     */
    public function shouldSendEmailForEvent($event) {
        if (!$this->isEmailNotificationsEnabled()) {
            return false;
        }
        
        $key = 'email_on_' . strtolower($event);
        return $this->get($key, '1') === '1';
    }
    
    /**
     * Check if email should be sent for specific order status
     */
    public function shouldSendEmailForStatus($status) {
        if (!$this->isEmailNotificationsEnabled()) {
            return false;
        }
        
        if (!$this->shouldSendEmailForEvent('order_status_change')) {
            return false;
        }
        
        $statusKey = 'email_status_' . strtolower($status);
        return $this->get($statusKey, '0') === '1';
    }
    
    /**
     * Get email from name
     */
    public function getEmailFromName() {
        return $this->get('email_from_name', 'Andcorp Autos');
    }
    
    /**
     * Get email from address
     */
    public function getEmailFromAddress() {
        return $this->get('email_from_address', getenv('MAIL_FROM_ADDRESS') ?: 'noreply@andcorpautos.com');
    }
    
    /**
     * Get email reply-to address
     */
    public function getEmailReplyTo() {
        return $this->get('email_reply_to', 'info@andcorpautos.com');
    }
}

