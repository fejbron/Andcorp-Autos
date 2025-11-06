# cPanel VPS Deployment Guide

## Prerequisites

- cPanel account with access to:
  - File Manager or FTP/SFTP
  - MySQL Databases
  - PHP 8.1 or higher
- SSH access (optional but recommended)

## Quick Deployment Steps

### 1. Upload Files

**Option A: Using File Manager**
1. Log into cPanel
2. Go to **File Manager**
3. Navigate to `public_html` (or your domain's root)
4. Upload the `andcorp-deployment.zip` file
5. Extract the zip file
6. Move contents from `Andcorp-test/public/*` to `public_html/`
7. Move `Andcorp-test/app`, `Andcorp-test/config`, `Andcorp-test/database` folders one level up (outside public_html)

**Option B: Using FTP/SFTP**
1. Connect via FileZilla or similar FTP client
2. Upload all files to your server
3. Structure should be:
   ```
   /home/yourusername/
   ├── public_html/          (All files from /public folder)
   │   ├── index.php
   │   ├── login.php
   │   ├── register.php
   │   ├── bootstrap.php
   │   ├── admin/
   │   ├── orders/
   │   ├── assets/
   │   └── .htaccess
   ├── app/
   ├── config/
   ├── database/
   └── .env
   ```

### 2. Create MySQL Database

1. In cPanel, go to **MySQL Databases**
2. Create a new database (e.g., `username_cardeal`)
3. Create a new MySQL user with a strong password
4. Add the user to the database with **ALL PRIVILEGES**
5. Note down:
   - Database name
   - Database user
   - Database password
   - Database host (usually `localhost`)

### 3. Import Database Schema

1. Go to **phpMyAdmin** in cPanel
2. Select your newly created database
3. Click **Import** tab
4. Upload and import these files in order:
   - `database/schema.sql`
   - `database/seed.sql`
   - `database/updates_gallery_final.sql`
   - `database/indexes.sql`

### 4. Configure Environment

1. In File Manager, navigate to your home directory (one level above `public_html`)
2. Edit the `.env` file with your database credentials:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Application
APP_NAME="AndCorp Car Dealership"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Email Configuration (Optional - for notifications)
MAIL_HOST=smtp.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="AndCorp Car Dealership"

# SMS Configuration (Optional - Twilio)
SMS_ENABLED=false
TWILIO_SID=
TWILIO_TOKEN=
TWILIO_FROM=
```

### 5. Set File Permissions

Using File Manager or SSH, set these permissions:

```bash
# Directories that need write access
chmod 755 public_html/uploads
chmod 755 public_html/uploads/documents
chmod 755 public_html/uploads/cars
chmod 755 storage
chmod 755 storage/cache

# Make sure .env is readable only by owner
chmod 600 .env

# Ensure PHP files are readable
chmod 644 public_html/*.php
chmod 644 app/*.php
chmod 644 config/*.php
```

### 6. Update Bootstrap Path

1. Edit `public_html/bootstrap.php`
2. Update the paths to point to the correct locations:

```php
// Instead of:
require_once __DIR__ . '/../app/Security.php';

// Use:
require_once $_SERVER['DOCUMENT_ROOT'] . '/../app/Security.php';
```

Or use absolute paths like:
```php
require_once '/home/yourusername/app/Security.php';
```

### 7. Verify .htaccess

Ensure `public_html/.htaccess` exists with:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Force HTTPS (recommended for production)
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
    
    # Prevent directory browsing
    Options -Indexes
    
    # Protect sensitive files
    <FilesMatch "^\.">
        Order allow,deny
        Deny from all
    </FilesMatch>
</IfModule>

# PHP settings
<IfModule mod_php7.c>
    php_value upload_max_filesize 20M
    php_value post_max_size 25M
    php_value max_execution_time 300
    php_value max_input_time 300
    php_value memory_limit 256M
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

### 8. Create Required Directories

Make sure these directories exist:
```
public_html/uploads/
public_html/uploads/documents/
public_html/uploads/cars/
storage/
storage/cache/
```

### 9. Test the Installation

1. Visit your domain: `https://yourdomain.com`
2. You should see the AndCorp homepage
3. Try logging in with default credentials:
   - **Admin:** admin@andcorp.com / admin123
   - **Customer:** customer@example.com / customer123

### 10. Post-Deployment Security

**IMPORTANT:** After successful deployment:

1. **Change Default Passwords**
   - Log in as admin
   - Go to Profile → Change password
   - Update both admin and customer default passwords

2. **Update Admin Email**
   - Change admin email from default to your actual email

3. **Disable Debug Mode**
   - Ensure `.env` has `APP_DEBUG=false`

4. **Secure .env File**
   - Move `.env` outside public_html
   - Update bootstrap.php to reference correct path

5. **Set Up SSL Certificate**
   - Use cPanel's AutoSSL or Let's Encrypt
   - Ensure HTTPS is enforced

6. **Configure Backups**
   - Set up automatic database backups in cPanel
   - Schedule regular file backups

7. **Set Up Cron Jobs (Optional)**
   - For automated tasks like cleanup, notifications, etc.
   - In cPanel → Cron Jobs

## Troubleshooting

### White Screen / 500 Error
- Check error logs in cPanel → Error Log
- Verify file permissions
- Ensure PHP version is 8.1+
- Check that all paths in bootstrap.php are correct

### Database Connection Error
- Verify database credentials in .env
- Ensure database user has proper privileges
- Check if database host is correct (try `localhost` or `127.0.0.1`)

### File Upload Issues
- Check folder permissions (755 or 777)
- Verify PHP upload limits in .htaccess
- Ensure upload directories exist

### Page Not Found Errors
- Check .htaccess is uploaded and readable
- Verify mod_rewrite is enabled
- Clear browser cache

### CSS/JS Not Loading
- Check file paths in templates
- Verify assets folder is uploaded
- Check browser console for 404 errors

## Server Requirements

- **PHP:** 8.1 or higher
- **MySQL:** 5.7+ or MariaDB 10.3+
- **Apache Modules:**
  - mod_rewrite
  - mod_headers
- **PHP Extensions:**
  - PDO
  - pdo_mysql
  - mbstring
  - openssl
  - json
  - fileinfo
  - gd (for image processing)

## Support & Maintenance

### Regular Maintenance Tasks
1. Monitor error logs weekly
2. Update customer passwords periodically
3. Backup database weekly
4. Review and clean up old uploads monthly
5. Monitor disk space usage

### Performance Optimization
1. Enable OPcache in PHP settings (cPanel → MultiPHP INI Editor)
2. Enable GZIP compression (already in code)
3. Use CDN for static assets (optional)
4. Monitor and optimize slow database queries

## Additional Configuration

### Email Setup
To enable email notifications:
1. Set up email account in cPanel
2. Update MAIL_* settings in .env
3. Test email functionality

### Custom Domain
If using a subdomain or custom path:
1. Update APP_URL in .env
2. Adjust RewriteBase in .htaccess if needed

### Staging Environment
Consider setting up a staging subdomain:
1. Create subdomain in cPanel (e.g., staging.yourdomain.com)
2. Deploy to separate directory
3. Use separate database
4. Test updates before production deployment

---

**Deployed Successfully?** 🎉
Remember to change default passwords and review security settings!

For issues, check error logs in cPanel or contact your hosting provider.

