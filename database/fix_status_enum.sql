-- Fix orders.status ENUM to match the expected values
-- This updates the existing database table to include all required status values

ALTER TABLE orders 
MODIFY COLUMN status ENUM(
    'pending', 
    'purchased', 
    'shipping', 
    'customs', 
    'inspection', 
    'repair', 
    'ready', 
    'delivered', 
    'cancelled'
) DEFAULT 'pending';

-- Verify the change
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'orders' 
  AND COLUMN_NAME = 'status';

