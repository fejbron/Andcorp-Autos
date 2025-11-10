-- Add Texas Metro Auction to auction sources
-- Migration date: November 2025
-- Adds 'texas_metro' to the auction_source ENUM field

-- Update the vehicles table to include all auction sources including Texas Metro Auction
ALTER TABLE vehicles 
MODIFY COLUMN auction_source ENUM('copart', 'iaa', 'sca', 'tgna', 'manheim', 'texas_metro') NOT NULL;

-- Verify the changes
SHOW COLUMNS FROM vehicles WHERE Field = 'auction_source';

-- Show success message
SELECT 'Texas Metro Auction added successfully to auction sources!' AS message;

-- Notes:
-- This migration updates the auction_source ENUM to include:
-- - copart (Copart)
-- - iaa (IAA - Insurance Auto Auctions)
-- - sca (SCA Auction)
-- - tgna (The Great Northern Auction)
-- - manheim (Manheim Auctions)
-- - texas_metro (Texas Metro Auction) [NEW]
--
-- No data is lost - all existing auction_source values remain intact.
-- New orders can now use 'texas_metro' as an auction source option.

