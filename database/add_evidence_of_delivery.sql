-- Add 'evidence_of_delivery' to order_documents document_type ENUM
-- This migration adds a new document type for delivery evidence

ALTER TABLE order_documents 
MODIFY COLUMN document_type ENUM(
    'car_image', 
    'title', 
    'bill_of_lading', 
    'bill_of_entry',
    'evidence_of_delivery'
) NOT NULL;

