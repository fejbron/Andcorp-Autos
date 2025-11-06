-- Step-by-step migration to add 'evidence_of_delivery' to order_documents
-- Run each step separately if the combined ALTER TABLE fails

-- STEP 1: Check current ENUM values (run this first to see what you have)
-- SHOW COLUMNS FROM order_documents WHERE Field = 'document_type';

-- STEP 2: Try the simple ALTER TABLE (run this)
ALTER TABLE order_documents 
MODIFY COLUMN document_type ENUM(
    'car_image', 
    'title', 
    'bill_of_lading', 
    'bill_of_entry',
    'evidence_of_delivery'
) NOT NULL;

-- If STEP 2 fails with an error about existing data or constraints, 
-- use the alternative method below (STEPS 3-7):

-- STEP 3: Create temporary column with new ENUM
-- ALTER TABLE order_documents 
-- ADD COLUMN document_type_temp ENUM(
--     'car_image', 
--     'title', 
--     'bill_of_lading', 
--     'bill_of_entry',
--     'evidence_of_delivery'
-- ) NOT NULL DEFAULT 'car_image' AFTER document_type;

-- STEP 4: Copy existing data
-- UPDATE order_documents SET document_type_temp = document_type;

-- STEP 5: Drop the old column
-- ALTER TABLE order_documents DROP COLUMN document_type;

-- STEP 6: Rename the temporary column
-- ALTER TABLE order_documents 
-- CHANGE document_type_temp document_type ENUM(
--     'car_image', 
--     'title', 
--     'bill_of_lading', 
--     'bill_of_entry',
--     'evidence_of_delivery'
-- ) NOT NULL;

-- STEP 7: Verify the change
-- SHOW COLUMNS FROM order_documents WHERE Field = 'document_type';

