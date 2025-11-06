# 🚀 Production Deployment Checklist

## ✅ Pre-Deployment Checks

### 1. Security Configuration
- [ ] Disable `display_errors` in `php.ini` or `.htaccess`
- [ ] Enable error logging to file instead
- [ ] Set strong session cookie parameters
- [ ] Verify HTTPS is enabled
- [ ] Change database credentials from defaults
- [ ] Review and update CORS/CSP headers
- [ ] Remove or restrict access to test scripts

### 2. Database
- [ ] Run all database migrations (`database/schema.sql`, `database/deposits_tracking.sql`, `database/quote_requests.sql`)
- [ ] Verify all ENUM values are capitalized correctly
- [ ] Create database backup strategy
- [ ] Set up automated backups
- [ ] Verify database connection pooling settings
- [ ] Check database character set (should be utf8mb4)

### 3. File System
- [ ] Set proper file permissions:
  - Directories: 755
  - PHP files: 644
  - `uploads/` directory: 755 (writable)
- [ ] Rename `.htaccess.production` to `.htaccess`
- [ ] Remove development files:
  - `production_readiness_test.php`
  - `check_enum_match.php`
  - `admin/update_status_enum.php`
  - `admin/fix_status_enum.php`
  - Any other test files
- [ ] Ensure `uploads/` subdirectories exist:
  - `uploads/documents/`
  - `uploads/deposits/`
  - `uploads/images/`

### 4. Application Configuration
- [ ] Update database credentials in `app/Database.php`
- [ ] Set correct `BASE_PATH` in `public/bootstrap.php` if not in root
- [ ] Configure email settings (if using SMTP)
- [ ] Set correct timezone in `php.ini` or application
- [ ] Review and update session configuration
- [ ] Configure rate limiting for authentication

### 5. Testing
- [ ] Test user registration workflow
- [ ] Test login/logout functionality
- [ ] Test order creation and management
- [ ] Test quote request workflow
- [ ] Test deposit tracking
- [ ] Test file uploads (documents, images)
- [ ] Test email notifications
- [ ] Test all redirects work correctly
- [ ] Test CSRF protection on all forms
- [ ] Verify all status updates work

### 6. Performance
- [ ] Enable PHP OPcache
- [ ] Configure OPcache settings:
  ```ini
  opcache.enable=1
  opcache.memory_consumption=256
  opcache.interned_strings_buffer=16
  opcache.max_accelerated_files=10000
  opcache.revalidate_freq=60
  ```
- [ ] Minimize CSS/JS files
- [ ] Enable gzip compression
- [ ] Configure browser caching headers

### 7. Monitoring
- [ ] Set up error logging
- [ ] Configure log rotation
- [ ] Set up monitoring for:
  - Server resources (CPU, Memory, Disk)
  - Database performance
  - Application errors
- [ ] Set up alerts for critical errors

## 📝 Deployment Steps

### Step 1: Prepare Server
```bash
# Update server packages
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install apache2 php8.1 php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl

# Enable Apache modules
sudo a2enmod rewrite
sudo a2enmod headers
```

### Step 2: Upload Files
```bash
# Using SCP or SFTP, upload all files except:
# - .git/
# - *.md files
# - test scripts
# - development configuration
```

### Step 3: Configure Database
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE andcorp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Create database user
mysql -u root -p -e "CREATE USER 'andcorp_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON andcorp_db.* TO 'andcorp_user'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"

# Import schema
mysql -u andcorp_user -p andcorp_db < database/schema.sql
mysql -u andcorp_user -p andcorp_db < database/deposits_tracking.sql
mysql -u andcorp_user -p andcorp_db < database/quote_requests.sql
```

### Step 4: Configure Application
```php
// Update app/Database.php
private static $host = 'localhost';
private static $database = 'andcorp_db';
private static $username = 'andcorp_user';
private static $password = 'YOUR_DATABASE_PASSWORD';
```

### Step 5: Set Permissions
```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/andcorp

# Set directory permissions
find /var/www/andcorp -type d -exec chmod 755 {} \;

# Set file permissions
find /var/www/andcorp -type f -exec chmod 644 {} \;

# Make uploads writable
chmod 755 /var/www/andcorp/public/uploads
chmod 755 /var/www/andcorp/public/uploads/documents
chmod 755 /var/www/andcorp/public/uploads/deposits
chmod 755 /var/www/andcorp/public/uploads/images
```

### Step 6: Configure Apache
```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/andcorp/public
    
    <Directory /var/www/andcorp/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/andcorp_error.log
    CustomLog ${APACHE_LOG_DIR}/andcorp_access.log combined
    
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/privkey.pem
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName yourdomain.com
    Redirect permanent / https://yourdomain.com/
</VirtualHost>
```

### Step 7: Rename Production Files
```bash
# Activate production .htaccess
mv public/.htaccess.production public/.htaccess

# Remove test files
rm production_readiness_test.php
rm public/admin/update_status_enum.php
rm public/admin/fix_status_enum.php
rm public/admin/check_enum_match.php
```

### Step 8: Test Deployment
- [ ] Visit https://yourdomain.com
- [ ] Test registration
- [ ] Test login
- [ ] Test order creation
- [ ] Test file uploads
- [ ] Check error logs for any issues

## 🔒 Post-Deployment Security

### Immediate Actions
1. **Change all default passwords**
2. **Set up SSL/TLS certificate** (Let's Encrypt recommended)
3. **Configure firewall rules**
4. **Disable directory listing**
5. **Set up fail2ban** for brute force protection

### Regular Maintenance
- **Weekly**: Review error logs
- **Monthly**: Update dependencies and security patches
- **Quarterly**: Security audit and penetration testing
- **Backup**: Daily automated backups with 30-day retention

## 📊 Performance Optimization

### Database Optimization
```sql
-- Add indexes for frequently queried fields
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_customer ON orders(customer_id);
CREATE INDEX idx_deposits_order ON deposits(order_id);
CREATE INDEX idx_deposits_status ON deposits(status);
CREATE INDEX idx_quotes_customer ON quote_requests(customer_id);
CREATE INDEX idx_quotes_status ON quote_requests(status);

-- Optimize tables
OPTIMIZE TABLE orders;
OPTIMIZE TABLE deposits;
OPTIMIZE TABLE quote_requests;
```

### PHP Performance
```ini
# php.ini optimizations
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 10M
post_max_size = 12M

# Session optimization
session.gc_maxlifetime = 3600
session.cookie_httponly = 1
session.cookie_secure = 1
```

## 🚨 Troubleshooting

### Common Issues

**Issue**: 500 Internal Server Error
- Check Apache error logs
- Verify `.htaccess` syntax
- Check file permissions

**Issue**: Database connection failed
- Verify credentials in `app/Database.php`
- Check MySQL service is running
- Verify database exists and user has permissions

**Issue**: Uploads not working
- Check `uploads/` directory permissions (755)
- Verify PHP `upload_max_filesize` setting
- Check disk space

**Issue**: CSRF token errors
- Clear browser cookies/cache
- Verify session is working
- Check session cookie settings

## ✅ Final Verification

Run these commands to verify deployment:

```bash
# Check PHP version
php -v

# Verify Apache configuration
sudo apache2ctl configtest

# Check database connection
mysql -u andcorp_user -p andcorp_db -e "SELECT COUNT(*) FROM users;"

# Verify file permissions
ls -la /var/www/andcorp/public/uploads

# Check error logs
tail -100 /var/log/apache2/andcorp_error.log
```

---

## 📞 Support

For issues or questions:
- Check error logs first
- Review this checklist
- Consult application documentation

**Deployment Date**: _____________  
**Deployed By**: _____________  
**Version**: _____________  
**Server**: _____________

