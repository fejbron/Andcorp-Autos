# Order Status Migration Instructions

## Overview
This migration updates the order status workflow to include more detailed tracking stages for vehicles being imported to Ghana.

## New Status Flow
The new workflow adds 3 additional statuses between "Purchased" and "Shipping", and 2 more between "Shipping" and "Inspection":

1. **Pending** - Order created
2. **Purchased** - Vehicle acquired
3. **Delivered to Port of Load** *(NEW)* - Vehicle at origin port
4. **Origin customs clearance** *(NEW)* - Export customs clearance
5. **Shipping** - En route to Ghana
6. **Arrived in Ghana** *(NEW)* - Arrived at Ghana port
7. **Ghana Customs Clearance** *(NEW)* - Import customs clearance
8. **Inspection** - Vehicle inspection
9. **Repair** - Repairs if needed
10. **Ready** - Ready for delivery
11. **Delivered** - Delivered to customer
12. **Cancelled** - Order cancelled

## Migration Steps

### Step 1: Backup Database
**IMPORTANT**: Always backup your database before running migrations!

```bash
mysqldump -u your_username -p andcorp_autos > backup_before_status_migration_$(date +%Y%m%d).sql
```

### Step 2: Run Database Migration
Run the main status update migration:

```bash
mysql -u your_username -p andcorp_autos < database/update_order_statuses_2025.sql
```

This will:
- Update the `orders` table `status` ENUM field with the new statuses
- Maintain all existing order data (no data loss)

### Step 3: Update Email Notification Settings (Optional)
If you're using email notifications, run this to add settings for the new statuses:

```bash
mysql -u your_username -p andcorp_autos < database/update_email_notification_statuses_2025.sql
```

This will:
- Add email notification settings for the 4 new statuses
- Set them to enabled by default (value='1')
- Keep all existing settings intact

### Step 4: Verify Migration
Check that the migration was successful:

```sql
-- Verify the status ENUM was updated
SHOW COLUMNS FROM orders WHERE Field = 'status';

-- Check existing orders still have valid statuses
SELECT DISTINCT status FROM orders;

-- Verify email notification settings (if applicable)
SELECT setting_key, setting_value 
FROM email_notification_settings 
WHERE setting_key LIKE 'email_status_%' 
ORDER BY setting_key;
```

### Step 5: Clear Application Cache
If your application has caching enabled, clear it:

```bash
# If using file-based cache
rm -rf storage/cache/*

# Or restart PHP-FPM/Apache to clear opcache
sudo service php-fpm restart
# OR
sudo service apache2 restart
```

## Rollback Instructions

If you need to rollback to the previous status structure:

### 1. Restore from Backup
```bash
mysql -u your_username -p andcorp_autos < backup_before_status_migration_YYYYMMDD.sql
```

### 2. Or run manual rollback SQL
**WARNING**: This will reset any orders with new statuses to "Pending"!

```sql
-- Update any orders with new statuses to closest equivalent
UPDATE orders 
SET status = 'Purchased' 
WHERE status IN ('Delivered to Port of Load', 'Origin customs clearance');

UPDATE orders 
SET status = 'Shipping' 
WHERE status = 'Arrived in Ghana';

UPDATE orders 
SET status = 'Customs' 
WHERE status = 'Ghana Customs Clearance';

-- Revert the ENUM field
ALTER TABLE orders 
MODIFY COLUMN status ENUM('Pending', 'Purchased', 'Shipping', 'Customs', 'Inspection', 'Repair', 'Ready', 'Delivered', 'Cancelled') 
DEFAULT 'Pending';
```

## Files Updated

### Database Files
- `database/schema.sql` - Updated for fresh installations
- `database/fix_status_enum.sql` - Updated for existing installations
- `database/update_order_statuses_2025.sql` - New migration file
- `database/update_email_notification_statuses_2025.sql` - Email settings migration

### Application Files
- `app/Models/Order.php` - Updated status validation
- `app/Security.php` - Updated sanitizeStatus method
- `app/Notification.php` - Added email messages for new statuses
- `public/bootstrap.php` - Updated status badge helper functions
- `public/admin/orders/edit.php` - Updated status dropdown
- `public/admin/orders/create.php` - Updated status dropdown and validation
- `public/admin/settings.php` - Updated email notification settings display
- `public/includes/order-card.php` - Updated progress bar calculation
- `public/orders/view.php` - Updated order timeline display
- `README.md` - Updated documentation

## Testing Checklist

After migration, test the following:

- [ ] Create a new order - verify status defaults to "Pending"
- [ ] Edit an existing order - verify all status options appear in dropdown
- [ ] Update order status - verify status change saves correctly
- [ ] Check order list page - verify status badges display correctly
- [ ] View order details - verify progress bar shows correctly
- [ ] Check customer order view - verify timeline displays all statuses
- [ ] Test email notifications (if enabled) - verify emails send for new statuses
- [ ] Check admin settings page - verify new status email toggles appear
- [ ] Verify existing orders still display correctly

## Support

If you encounter any issues during migration:
1. Check the error logs (`storage/logs/` or server error logs)
2. Verify database connection settings in `config/database.php`
3. Ensure all files were updated (check file modification dates)
4. Contact technical support with specific error messages

## Additional Migration: Texas Metro Auction Source

### Overview
This migration adds "Texas Metro Auction" as a new auction source option alongside existing sources (Copart, IAA, SCA, TGNA, Manheim).

### Migration Steps

**Step 1: Backup Database** (if not already done above)
```bash
mysqldump -u your_username -p andcorp_autos > backup_before_auction_update_$(date +%Y%m%d).sql
```

**Step 2: Run Auction Source Migration**
```bash
mysql -u your_username -p andcorp_autos < database/add_texas_metro_auction_2025.sql
```

This will:
- Update the `vehicles` table `auction_source` ENUM field
- Add 'texas_metro' as a valid auction source
- Maintain all existing vehicle data (no data loss)

**Step 3: Verify Update**
After running the migration, verify it worked:

1. Log in to admin dashboard
2. Create or edit an order
3. Check the "Auction Source" dropdown includes "Texas Metro Auction"
4. Save an order with Texas Metro Auction and verify it saves correctly

### Available Auction Sources (After Migration)
1. Copart
2. IAA (Insurance Auto Auctions)
3. SCA Auction
4. The Great Northern Auction (TGNA)
5. Manheim Auctions
6. Texas Metro Auction *(NEW)*

## Notes

- All existing orders will retain their current status
- The new statuses are optional - you can skip stages if not applicable
- Email notifications for new statuses default to enabled
- The order Model dynamically reads statuses from the database, so changes take effect immediately
- Customer-facing displays automatically adapt to the new status flow
- All existing vehicle records retain their auction source values
- New auction source option is available immediately after migration

