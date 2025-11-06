# ⚡ Quick Deployment Guide for Namecheap

## 🚀 5-Minute Setup

### Step 1: Prepare Files (Run Once)
```bash
php prepare_deployment.php
```

This creates:
- `deployment_package/` - Clean files ready for upload
- `andcorp_deployment_YYYY-MM-DD.zip` - ZIP file for easy upload

---

### Step 2: Upload to Namecheap

**Option A: Upload ZIP (Easiest)**
1. Login to cPanel → File Manager
2. Upload `andcorp_deployment_YYYY-MM-DD.zip`
3. Extract in `public_html/`
4. Move contents to root if needed

**Option B: Upload via FTP**
1. Use FileZilla
2. Connect to `ftp.yourdomain.com`
3. Upload all files from `deployment_package/`
4. Upload `public/` contents to `public_html/`

---

### Step 3: Database Setup

1. **cPanel → MySQL Databases**
   - Create database: `andcorp_db`
   - Create user: `andcorp_user`
   - Add user to database with ALL PRIVILEGES

2. **cPanel → phpMyAdmin**
   - Select your database
   - Import `database/schema.sql`
   - Import `database/deposits_tracking.sql`
   - Import `database/quote_requests.sql`

---

### Step 4: Configure Database

Edit `app/Database.php`:
```php
private static $database = 'username_andcorp_db';
private static $username = 'username_andcorp_user';
private static $password = 'YOUR_PASSWORD';
```

---

### Step 5: Set Permissions

In cPanel File Manager:
- Folders: `755`
- Files: `644`
- `public/uploads/`: `755` (important!)

---

### Step 6: Test

1. Visit: `https://yourdomain.com/`
2. Register account
3. Login
4. Test admin panel

**Done!** 🎉

---

## 🔧 Common Issues

**White screen?**
- Check `.htaccess` is uploaded
- Check PHP error logs in cPanel

**Database error?**
- Verify credentials in `app/Database.php`
- Check database user has privileges

**Uploads not working?**
- Set `public/uploads/` to 755
- Check PHP upload limits

---

## 📞 Need Help?

See `NAMECHEAP_DEPLOYMENT.md` for detailed instructions.

