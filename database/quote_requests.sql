-- Quote Requests Table
CREATE TABLE IF NOT EXISTS quote_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    request_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('pending', 'reviewing', 'quoted', 'approved', 'rejected', 'converted') DEFAULT 'pending',
    
    -- Vehicle Details
    vehicle_type VARCHAR(100) DEFAULT NULL,
    make VARCHAR(100) DEFAULT NULL,
    model VARCHAR(100) DEFAULT NULL,
    year INT DEFAULT NULL,
    trim VARCHAR(100) DEFAULT NULL,
    vin VARCHAR(50) DEFAULT NULL,
    lot_number VARCHAR(50) DEFAULT NULL,
    auction_link TEXT DEFAULT NULL,
    
    -- Customer Preferences
    budget_min DECIMAL(10, 2) DEFAULT NULL,
    budget_max DECIMAL(10, 2) DEFAULT NULL,
    preferred_color VARCHAR(50) DEFAULT NULL,
    additional_requirements TEXT DEFAULT NULL,
    
    -- Quote Information (filled by admin)
    quoted_price DECIMAL(10, 2) DEFAULT NULL,
    shipping_cost DECIMAL(10, 2) DEFAULT NULL,
    duty_estimate DECIMAL(10, 2) DEFAULT NULL,
    total_estimate DECIMAL(10, 2) DEFAULT NULL,
    admin_notes TEXT DEFAULT NULL,
    quoted_by INT DEFAULT NULL,
    quoted_at DATETIME DEFAULT NULL,
    
    -- Conversion to Order
    order_id INT DEFAULT NULL,
    converted_by INT DEFAULT NULL,
    converted_at DATETIME DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (quoted_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (converted_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    
    INDEX idx_customer_id (customer_id),
    INDEX idx_request_number (request_number),
    INDEX idx_status (status),
    INDEX idx_order_id (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

