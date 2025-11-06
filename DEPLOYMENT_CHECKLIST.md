# cPanel Deployment Checklist

Use this checklist to ensure successful deployment.

## Pre-Deployment

- [ ] Review server requirements (PHP 8.1+, MySQL 5.7+)
- [ ] Backup existing site (if updating)
- [ ] Have cPanel login credentials ready
- [ ] Have FTP/SFTP credentials (if using)
- [ ] Review DEPLOYMENT_CPANEL.md guide

## File Upload

- [ ] Upload all files via FTP or File Manager
- [ ] Verify file structure is correct
- [ ] Check that public files are in public_html
- [ ] Check that app, config, database folders are outside public_html
- [ ] Verify .htaccess is uploaded to public_html

## Database Setup

- [ ] Create MySQL database in cPanel
- [ ] Create MySQL user with strong password
- [ ] Grant ALL PRIVILEGES to user on database
- [ ] Note down database credentials
- [ ] Import schema.sql via phpMyAdmin
- [ ] Import seed.sql via phpMyAdmin
- [ ] Import updates_gallery_final.sql via phpMyAdmin
- [ ] Import indexes.sql via phpMyAdmin
- [ ] Verify all tables exist (check in phpMyAdmin)

## Configuration

- [ ] Copy .env.example to .env (if not already done)
- [ ] Update DB_HOST in .env
- [ ] Update DB_DATABASE in .env
- [ ] Update DB_USERNAME in .env
- [ ] Update DB_PASSWORD in .env
- [ ] Update APP_URL in .env
- [ ] Set APP_ENV=production
- [ ] Set APP_DEBUG=false
- [ ] Configure email settings (optional)

## File Permissions

- [ ] Set chmod 755 on public_html/uploads
- [ ] Set chmod 755 on public_html/uploads/documents
- [ ] Set chmod 755 on public_html/uploads/cars
- [ ] Set chmod 755 on storage
- [ ] Set chmod 755 on storage/cache
- [ ] Set chmod 600 on .env
- [ ] Set chmod 644 on all .php files

## Path Configuration

- [ ] Update bootstrap.php paths to absolute paths
- [ ] Verify require_once paths point to correct locations
- [ ] Test that Security, Headers, Validator classes load
- [ ] Check autoloader paths are correct

## Testing

- [ ] Visit homepage - should load without errors
- [ ] Test login with admin credentials
- [ ] Test login with customer credentials
- [ ] Check admin dashboard loads
- [ ] Test creating a new order
- [ ] Test document upload functionality
- [ ] Test gallery page
- [ ] Check all navigation links work
- [ ] Test registration page
- [ ] Test logout functionality

## Security

- [ ] Change admin default password
- [ ] Change customer default password
- [ ] Update admin email address
- [ ] Verify APP_DEBUG=false in .env
- [ ] Check .env is not accessible via browser
- [ ] Enable SSL certificate (AutoSSL or Let's Encrypt)
- [ ] Force HTTPS in .htaccess
- [ ] Verify security headers are set
- [ ] Test CSRF protection is working

## Post-Deployment

- [ ] Set up automated backups in cPanel
- [ ] Configure backup schedule (daily/weekly)
- [ ] Set up email notifications (if needed)
- [ ] Test email functionality
- [ ] Monitor error logs for 24 hours
- [ ] Create staging environment (recommended)
- [ ] Document any custom configurations
- [ ] Share access credentials with team (securely)

## Optional Enhancements

- [ ] Set up CDN for static assets
- [ ] Configure Redis/Memcached (if available)
- [ ] Enable OPcache in PHP settings
- [ ] Set up monitoring/uptime alerts
- [ ] Configure cron jobs for maintenance tasks
- [ ] Add Google Analytics or tracking
- [ ] Set up error monitoring (Sentry, etc.)

## Troubleshooting Done

- [ ] Resolved any 500 errors
- [ ] Fixed database connection issues
- [ ] Corrected file permission problems
- [ ] Fixed path-related errors
- [ ] Resolved CSS/JS loading issues

## Documentation

- [ ] Document deployment date
- [ ] Note server details (hosting provider, plan)
- [ ] Record database credentials (store securely)
- [ ] Document any customizations made
- [ ] Create backup restoration procedure
- [ ] Share deployment guide with team

---

## Deployment Date: ________________

## Deployed By: ________________

## Server Details:
- Hosting: ________________
- Domain: ________________
- cPanel URL: ________________

## Notes:
________________________________________________________________
________________________________________________________________
________________________________________________________________
________________________________________________________________

---

✅ **Deployment Complete!**

After checking all items, your AndCorp Car Dealership system should be live and operational on your cPanel VPS.

