-- Add 'evidence_of_delivery' to order_documents document_type ENUM
-- This is a robust migration that handles various edge cases

-- Step 1: Check if the column exists and get current ENUM values
-- (This is just for reference, you can skip this in phpMyAdmin)

-- Step 2: Add the new ENUM value
-- If you get an error, try the alternative method below

ALTER TABLE order_documents 
MODIFY COLUMN document_type ENUM(
    'car_image', 
    'title', 
    'bill_of_lading', 
    'bill_of_entry',
    'evidence_of_delivery'
) NOT NULL;

-- Alternative method if the above fails:
-- This method preserves any existing data and adds the new value

-- First, check current ENUM values:
-- SHOW COLUMNS FROM order_documents WHERE Field = 'document_type';

-- If the ALTER TABLE fails, try this approach:
-- 1. Create a temporary column
-- ALTER TABLE order_documents ADD COLUMN document_type_new ENUM(
--     'car_image', 
--     'title', 
--     'bill_of_lading', 
--     'bill_of_entry',
--     'evidence_of_delivery'
-- ) NOT NULL AFTER document_type;

-- 2. Copy data
-- UPDATE order_documents SET document_type_new = document_type;

-- 3. Drop old column
-- ALTER TABLE order_documents DROP COLUMN document_type;

-- 4. Rename new column
-- ALTER TABLE order_documents CHANGE document_type_new document_type ENUM(
--     'car_image', 
--     'title', 
--     'bill_of_lading', 
--     'bill_of_entry',
--     'evidence_of_delivery'
-- ) NOT NULL;

