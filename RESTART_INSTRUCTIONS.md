# How to Restart MAMP Apache Server

## Quick Steps

1. **Open MAMP** application
2. Click **"Stop Servers"** button (top right)
3. Wait 5 seconds
4. Click **"Start Servers"** button
5. Wait for both Apache and MySQL to show green

## Why This Is Needed

PHP caches compiled files (OpCache). After editing PHP files, you need to restart Apache to:
- Clear the OpCache
- Load the new Database.php with fixed SQL mode
- Apply all code changes

## After Restarting

Try the quote conversion again:
1. Go to: `http://localhost:8888/Andcorp-test/public/admin/quote-requests.php`
2. Click "View" on a quote request
3. Fill in quote details (if not already filled)
4. Click "Create Order from Quote"
5. Select status (Pending, Purchased, etc.)
6. Click "Create Order"

## Expected Result

✅ "Order created successfully! Order #ORD-XXXXXXXX has been created."

## If Still Failing

Run this command to check the latest error:
```bash
tail -20 /Applications/MAMP/logs/php_error.log
```

Look for timestamps AFTER you restarted Apache (current time).

