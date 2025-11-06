# Email Notification Settings - Deployment Checklist

## Issue: HTTP ERROR 500 on Live Server

The 500 error is likely caused by one of these issues:

### ✅ Checklist to Fix

1. **Upload the Settings Model File**
   - Ensure `app/Models/Settings.php` exists on your live server
   - File path: `/path/to/your/app/Models/Settings.php`
   - If missing, upload it from your local project

2. **Create the Database Table**
   - The `email_notification_settings` table needs to exist
   - Run the setup script: `https://yourdomain.com/admin/setup_email_settings.php`
   - OR manually run the SQL: `database/email_notification_settings.sql`

3. **Check File Permissions**
   - Ensure PHP can read the Settings.php file
   - Recommended: 644 for files, 755 for directories

4. **Verify Autoloader**
   - Check that `app/Models/Settings.php` is in the correct location
   - The autoloader looks for: `app/Models/Settings.php`

5. **Check Error Logs**
   - On Namecheap cPanel, check error logs in:
     - cPanel → Metrics → Errors
     - Or check your server's PHP error log
   - Look for specific error messages about Settings class or table

## Quick Fix Steps

### Step 1: Upload Settings Model
If `app/Models/Settings.php` is missing:
1. Upload `app/Models/Settings.php` to your server
2. Ensure it's in the correct location: `app/Models/Settings.php`

### Step 2: Create Database Table
Option A - Use Setup Script (Easiest):
1. Upload `public/admin/setup_email_settings.php` to your server
2. Visit: `https://yourdomain.com/admin/setup_email_settings.php`
3. Follow the instructions

Option B - Manual SQL:
1. Go to cPanel → phpMyAdmin
2. Select your database
3. Click "SQL" tab
4. Copy and paste contents of `database/email_notification_settings.sql`
5. Click "Go"

### Step 3: Verify
1. Visit: `https://yourdomain.com/admin/settings.php`
2. Should see the Email Notification Settings form
3. If table is missing, you'll see a helpful warning with instructions

## Common Issues

### Issue: "Class Settings not found"
**Solution:** Upload `app/Models/Settings.php` to the server

### Issue: "Table doesn't exist"
**Solution:** Run the setup script or SQL file to create the table

### Issue: "Permission denied"
**Solution:** Check file permissions (644 for files, 755 for directories)

### Issue: Autoloader can't find file
**Solution:** Verify the file path matches exactly: `app/Models/Settings.php`

## Files to Upload

Make sure these files are on your live server:
- ✅ `app/Models/Settings.php` (NEW - Required)
- ✅ `public/admin/settings.php` (Updated)
- ✅ `public/admin/setup_email_settings.php` (NEW - For setup)
- ✅ `database/email_notification_settings.sql` (NEW - For manual setup)
- ✅ `app/Notification.php` (Updated - Already exists)

## Testing

After deployment:
1. Visit: `https://yourdomain.com/admin/settings.php`
2. Should see Email Notification Settings form
3. Configure your email preferences
4. Test by updating an order status

## Need Help?

If you still see errors:
1. Check cPanel error logs
2. Enable error display temporarily (in `.env` set `APP_DEBUG=true`)
3. Check that all required files are uploaded
4. Verify database connection is working

