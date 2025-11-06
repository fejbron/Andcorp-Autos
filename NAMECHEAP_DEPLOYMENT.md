# 🚀 Namecheap cPanel Deployment Guide

## 📋 Pre-Deployment Checklist

- [ ] All code changes committed
- [ ] Database backup created
- [ ] Test files removed
- [ ] Environment variables set
- [ ] File permissions configured
- [ ] SSL certificate installed (recommended)

---

## 📦 Step 1: Prepare Files for Upload

### Files to Remove (Development Only)
```bash
# Remove these files before deployment:
- production_readiness_test.php
- PRODUCTION_READINESS_REPORT.md
- PRODUCTION_CHECKLIST.md
- NAMECHEAP_DEPLOYMENT.md (this file - optional)
- .git/ (if deploying via FTP)
- Any test scripts in public/admin/
```

### Files to Keep
- All application files in `app/`, `public/`, `database/`
- `.htaccess.production` (will become `.htaccess`)
- All assets in `public/assets/`
- Upload directories

---

## 🗄️ Step 2: Database Setup

### 2.1 Create Database in cPanel

1. **Login to cPanel**
   - Go to: `https://yourdomain.com/cpanel`
   - Login with your Namecheap credentials

2. **Create MySQL Database**
   - Click **"MySQL Databases"**
   - Under "Create New Database", enter: `andcorp_db`
   - Click **"Create Database"**

3. **Create Database User**
   - Scroll to "MySQL Users"
   - Username: `andcorp_user`
   - Password: Generate strong password (save it!)
   - Click **"Create User"**

4. **Assign User to Database**
   - Scroll to "Add User To Database"
   - Select user: `andcorp_user`
   - Select database: `andcorp_db`
   - Click **"Add"**
   - Check **"ALL PRIVILEGES"**
   - Click **"Make Changes"**

5. **Note Database Details**
   - Database Name: `username_andcorp_db`
   - Database User: `username_andcorp_user`
   - Database Host: `localhost` (usually)
   - Save these for Step 3!

---

### 2.2 Import Database

**Option A: Using phpMyAdmin (Recommended)**

1. **Access phpMyAdmin**
   - In cPanel, click **"phpMyAdmin"**
   - Select your database from left sidebar

2. **Import SQL Files**
   - Click **"Import"** tab
   - Click **"Choose File"**
   - Upload `database/schema.sql`
   - Click **"Go"**
   - Wait for success message

3. **Import Additional Tables**
   - Repeat for `database/deposits_tracking.sql`
   - Repeat for `database/quote_requests.sql`

**Option B: Using Command Line (if you have SSH access)**

```bash
mysql -u username_andcorp_user -p username_andcorp_db < database/schema.sql
mysql -u username_andcorp_user -p username_andcorp_db < database/deposits_tracking.sql
mysql -u username_andcorp_user -p username_andcorp_db < database/quote_requests.sql
```

---

## 📁 Step 3: Upload Files via FTP

### 3.1 Get FTP Credentials

1. In cPanel, go to **"FTP Accounts"**
2. Note your FTP credentials:
   - **FTP Server**: `ftp.yourdomain.com` or `yourdomain.com`
   - **FTP Username**: `username@yourdomain.com`
   - **FTP Password**: (your password)
   - **Port**: 21 (or 22 for SFTP)

### 3.2 Upload Files

**Using FileZilla (Recommended):**

1. **Connect to FTP**
   - Host: `ftp.yourdomain.com`
   - Username: `username@yourdomain.com`
   - Password: (your password)
   - Port: 21

2. **Navigate to Public HTML**
   - In **Remote site**, navigate to: `/public_html/`
   - Create folder: `andcorp` (optional, or upload directly to public_html)

3. **Upload Files**
   - Upload ALL files from your local project
   - **Important**: Upload `public/` folder contents to `public_html/`
   - Upload `app/` folder to `app/` (same level as public_html)
   - Upload `database/` folder (optional, for reference)

**Correct Structure on Server:**
```
/home/username/
├── public_html/          (or public_html/andcorp/)
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   ├── orders/
│   ├── admin/
│   ├── assets/
│   ├── includes/
│   └── .htaccess
├── app/
│   ├── Auth.php
│   ├── Database.php
│   ├── Models/
│   └── ...
└── database/ (optional)
```

---

## ⚙️ Step 4: Configure Application

### 4.1 Update Database Configuration

Edit `app/Database.php`:

```php
<?php
class Database {
    private static $host = 'localhost';
    private static $database = 'username_andcorp_db';  // Replace with your database name
    private static $username = 'username_andcorp_user'; // Replace with your database user
    private static $password = 'YOUR_DATABASE_PASSWORD'; // Replace with your database password
    // ... rest of code
}
```

### 4.2 Update Base Path (if needed)

If your app is in a subfolder (e.g., `public_html/andcorp/`), update `public/bootstrap.php`:

```php
// If app is at: yourdomain.com/andcorp/
// Update getBasePath() or set manually:
define('BASE_PATH', '/andcorp/public');
```

### 4.3 Activate Production .htaccess

1. **Rename file**:
   - `public/.htaccess.production` → `public/.htaccess`
   - Or upload as `.htaccess` directly

2. **Verify .htaccess contents**:
   - Should have rewrite rules
   - Should have security headers
   - Should disable directory listing

---

## 🔒 Step 5: Set File Permissions

### 5.1 Set Permissions via cPanel File Manager

1. **Go to File Manager** in cPanel
2. **Navigate to** `public_html/` (or your app folder)
3. **Set Permissions**:

```
Directories (folders): 755
Files: 644
```

**Specific folders that need 755:**
- `public/uploads/`
- `public/uploads/documents/`
- `public/uploads/deposits/`
- `public/uploads/images/`

### 5.2 Set Permissions via SSH (if available)

```bash
cd /home/username/public_html

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Make uploads writable
chmod 755 public/uploads
chmod 755 public/uploads/documents
chmod 755 public/uploads/deposits
chmod 755 public/uploads/images
```

---

## 🌐 Step 6: Configure Domain & SSL

### 6.1 Point Domain to Application

**If app is in root (`public_html/`):**
- Already configured! Domain should work automatically

**If app is in subfolder (`public_html/andcorp/`):**
- Option 1: Move files to `public_html/`
- Option 2: Create subdomain: `app.yourdomain.com` → `public_html/andcorp/`

### 6.2 Install SSL Certificate (Recommended)

1. **In cPanel**, go to **"SSL/TLS"**
2. **Install Let's Encrypt** (free):
   - Click **"Manage SSL Sites"**
   - Select your domain
   - Check **"Run AutoSSL"** or install Let's Encrypt
   - Click **"Run AutoSSL"**

3. **Force HTTPS** (already in .htaccess):
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

---

## 🔧 Step 7: PHP Configuration

### 7.1 Check PHP Version

1. **In cPanel**, go to **"Select PHP Version"**
2. **Select PHP 8.1 or higher** (recommended: 8.1, 8.2, or 8.3)
3. **Enable Extensions**:
   - ✅ `pdo_mysql`
   - ✅ `mysqli`
   - ✅ `mbstring`
   - ✅ `openssl`
   - ✅ `session`

### 7.2 PHP Settings (if customizable)

Via `.htaccess` or `php.ini`:
```apache
php_value upload_max_filesize 10M
php_value post_max_size 12M
php_value memory_limit 256M
php_value max_execution_time 60
```

---

## ✅ Step 8: Test Deployment

### 8.1 Test Checklist

- [ ] **Homepage loads**: `https://yourdomain.com/`
- [ ] **Registration works**: `https://yourdomain.com/register.php`
- [ ] **Login works**: `https://yourdomain.com/login.php`
- [ ] **Dashboard loads**: `https://yourdomain.com/dashboard.php`
- [ ] **Database connection works**
- [ ] **File uploads work** (test document upload)
- [ ] **Admin panel accessible**: `https://yourdomain.com/admin/dashboard.php`
- [ ] **SSL certificate active** (HTTPS works)
- [ ] **No PHP errors** (check error logs)

### 8.2 Check Error Logs

**In cPanel:**
1. Go to **"Errors"** or **"Error Log"**
2. Review for any PHP errors
3. Fix any issues found

**Common Issues:**
- Database connection errors → Check credentials in `app/Database.php`
- File permission errors → Set uploads folder to 755
- 404 errors → Check `.htaccess` is active
- SSL errors → Wait for certificate propagation (up to 24 hours)

---

## 🎯 Post-Deployment

### Security Hardening

1. **Remove test files**:
   ```bash
   rm production_readiness_test.php
   rm -rf .git/
   ```

2. **Set up automated backups**:
   - In cPanel: **"Backup"** → Schedule daily backups
   - Or use **"Backup Wizard"** for manual backups

3. **Monitor error logs**:
   - Check cPanel Error Log weekly
   - Set up email notifications for critical errors

### Performance Optimization

1. **Enable caching** (if available):
   - In cPanel: **"Optimize Website"**
   - Enable Gzip compression

2. **Database optimization**:
   ```sql
   OPTIMIZE TABLE orders;
   OPTIMIZE TABLE deposits;
   OPTIMIZE TABLE quote_requests;
   ```

---

## 📞 Support & Troubleshooting

### Common Issues

**Issue: White screen / 500 error**
- Check `.htaccess` syntax
- Check PHP error logs
- Verify file permissions

**Issue: Database connection failed**
- Verify database credentials
- Check database user has privileges
- Ensure database exists

**Issue: Uploads not working**
- Check `uploads/` folder permissions (755)
- Check PHP `upload_max_filesize` setting
- Verify folder exists

**Issue: CSS/JS not loading**
- Check `assets/` folder uploaded
- Verify file paths in HTML
- Check `.htaccess` allows static files

### Getting Help

1. **Check cPanel Error Logs** first
2. **Review PHP error logs** in cPanel
3. **Test database connection** separately
4. **Contact Namecheap support** for hosting issues

---

## 📝 Quick Reference

### File Structure
```
public_html/
├── index.php
├── login.php
├── .htaccess
├── admin/
├── orders/
├── assets/
└── includes/

app/
├── Database.php (UPDATE THIS!)
├── Auth.php
└── Models/

database/
└── schema.sql (reference only)
```

### Database Connection
```php
// app/Database.php
private static $host = 'localhost';
private static $database = 'username_andcorp_db';
private static $username = 'username_andcorp_user';
private static $password = 'YOUR_PASSWORD';
```

### URLs
- Homepage: `https://yourdomain.com/`
- Admin: `https://yourdomain.com/admin/dashboard.php`
- Login: `https://yourdomain.com/login.php`

---

**Deployment Date**: _____________  
**Deployed By**: _____________  
**Domain**: _____________  
**Database**: _____________

