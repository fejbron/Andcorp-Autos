-- Add Ghana Card field to customers table (if not exists)
ALTER TABLE customers 
ADD COLUMN IF NOT EXISTS ghana_card_number VARCHAR(100) DEFAULT NULL AFTER identification_number;

-- Add cost breakdown fields to orders table (if not exists)
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS car_cost DECIMAL(10, 2) DEFAULT 0.00 AFTER total_cost,
ADD COLUMN IF NOT EXISTS transportation_cost DECIMAL(10, 2) DEFAULT 0.00 AFTER car_cost,
ADD COLUMN IF NOT EXISTS duty_cost DECIMAL(10, 2) DEFAULT 0.00 AFTER transportation_cost,
ADD COLUMN IF NOT EXISTS clearing_cost DECIMAL(10, 2) DEFAULT 0.00 AFTER duty_cost,
ADD COLUMN IF NOT EXISTS fixing_cost DECIMAL(10, 2) DEFAULT 0.00 AFTER clearing_cost,
ADD COLUMN IF NOT EXISTS total_usd DECIMAL(10, 2) DEFAULT 0.00 AFTER fixing_cost;

-- Create order_documents table for storing uploaded documents
CREATE TABLE IF NOT EXISTS order_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    document_type ENUM('car_image', 'title', 'bill_of_lading', 'bill_of_entry') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_order_documents_order (order_id),
    INDEX idx_order_documents_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create uploads directory structure marker
-- Run: mkdir -p public/uploads/documents public/uploads/cars

