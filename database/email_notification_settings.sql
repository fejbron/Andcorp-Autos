-- Email Notification Settings Table
CREATE TABLE IF NOT EXISTS email_notification_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO email_notification_settings (setting_key, setting_value, description) VALUES
('email_notifications_enabled', '1', 'Enable/disable email notifications globally'),
('email_on_order_status_change', '1', 'Send email when order status changes'),
('email_on_order_created', '1', 'Send email when new order is created'),
('email_on_deposit_received', '1', 'Send email when deposit is received'),
('email_on_deposit_verified', '1', 'Send email when deposit is verified'),
('email_on_quote_submitted', '1', 'Send email when quote is submitted to customer'),
('email_on_quote_approved', '1', 'Send email when quote is approved'),
('email_status_pending', '0', 'Send email for Pending status'),
('email_status_purchased', '1', 'Send email for Purchased status'),
('email_status_shipping', '1', 'Send email for Shipping status'),
('email_status_customs', '1', 'Send email for Customs status'),
('email_status_inspection', '0', 'Send email for Inspection status'),
('email_status_repair', '0', 'Send email for Repair status'),
('email_status_ready', '1', 'Send email for Ready status'),
('email_status_delivered', '1', 'Send email for Delivered status'),
('email_status_cancelled', '1', 'Send email for Cancelled status'),
('email_from_name', 'Andcorp Autos', 'Email sender name'),
('email_from_address', 'noreply@andcorpautos.com', 'Email sender address'),
('email_reply_to', 'info@andcorpautos.com', 'Email reply-to address')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

