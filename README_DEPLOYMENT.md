# AndCorp Car Dealership - Deployment Package

## 📦 Package Contents

This deployment package contains everything needed to run the AndCorp Car Dealership Management System on a cPanel VPS.

### What's Included

- **Application Files:** Complete PHP application
- **Database Schema:** SQL files for database setup
- **Documentation:** Comprehensive deployment guides
- **Configuration Templates:** Production-ready config files
- **Security Features:** CSRF protection, input validation, rate limiting
- **Modern UI:** Light blue & white flat design theme

## 🚀 Quick Start

1. **Read the Documentation First**
   - Start with `DEPLOYMENT_CPANEL.md` for step-by-step instructions
   - Use `DEPLOYMENT_CHECKLIST.md` to track your progress

2. **Upload Files to cPanel**
   - Use File Manager or FTP
   - Follow the directory structure in the guide

3. **Set Up Database**
   - Create MySQL database in cPanel
   - Import SQL files in correct order

4. **Configure Environment**
   - Copy `env.cpanel.template` to `.env`
   - Update with your database credentials

5. **Test & Launch**
   - Visit your domain
   - Login with default credentials
   - Change passwords immediately!

## 📁 File Structure

```
Andcorp-test/
├── public/                    # Web-accessible files (goes to public_html)
│   ├── admin/                # Admin panel pages
│   ├── orders/               # Order management pages
│   ├── assets/               # CSS, JS, images
│   ├── uploads/              # User uploaded files
│   ├── index.php             # Homepage
│   ├── login.php             # Login page
│   ├── register.php          # Registration page
│   ├── dashboard.php         # Customer dashboard
│   ├── gallery.php           # Car gallery
│   ├── profile.php           # User profile
│   ├── bootstrap.php         # Application bootstrap
│   └── .htaccess             # Apache configuration
│
├── app/                      # Application logic (keep outside public_html)
│   ├── Models/               # Database models
│   ├── Auth.php              # Authentication
│   ├── Database.php          # Database connection
│   ├── Security.php          # Security functions
│   ├── Validator.php         # Input validation
│   ├── Headers.php           # Security headers
│   └── Cache.php             # Caching system
│
├── config/                   # Configuration files
│   └── database.php          # Database config
│
├── database/                 # Database schema and migrations
│   ├── schema.sql            # Main database structure
│   ├── seed.sql              # Initial data
│   ├── updates_gallery_final.sql  # Gallery features
│   └── indexes.sql           # Database indexes
│
├── .env                      # Environment variables (create from template)
├── env.cpanel.template       # Environment template for cPanel
├── DEPLOYMENT_CPANEL.md      # Full deployment guide
├── DEPLOYMENT_CHECKLIST.md   # Deployment checklist
├── README.md                 # Original project README
└── README_DEPLOYMENT.md      # This file
```

## 🔐 Default Credentials

**⚠️ CHANGE THESE IMMEDIATELY AFTER DEPLOYMENT!**

**Admin Account:**
- Email: `admin@andcorp.com`
- Password: `admin123`

**Customer Account (for testing):**
- Email: `customer@example.com`
- Password: `customer123`

## ⚙️ Server Requirements

### Minimum Requirements
- **PHP:** 8.1 or higher
- **MySQL:** 5.7+ or MariaDB 10.3+
- **Disk Space:** 500 MB minimum
- **Memory:** 256 MB PHP memory limit

### Required PHP Extensions
- PDO
- pdo_mysql
- mbstring
- openssl
- json
- fileinfo
- gd (for image processing)

### Required Apache Modules
- mod_rewrite
- mod_headers

## 🎨 Features

### For Administrators
- Dashboard with analytics
- Order management (create, edit, view, track)
- Customer management
- Document upload (car images, title, bills)
- Cost breakdown tracking
- Reports generation
- Vehicle information management
- Status updates and notifications

### For Customers
- Personal dashboard
- Order placement
- Order tracking
- Document viewing
- Gallery of imported vehicles
- Profile management with Ghana Card
- Cost breakdown visibility
- Notification system

### Security Features
- CSRF protection on all forms
- Rate limiting on login/registration
- Input validation and sanitization
- Secure password hashing (bcrypt cost 12)
- Session security (httpOnly, secure, SameSite)
- SQL injection prevention (prepared statements)
- XSS protection
- Security headers
- File upload validation

### Performance Features
- Database query optimization
- Caching system
- GZIP compression
- Database indexes
- N+1 query prevention

## 📋 Deployment Steps Overview

1. **Pre-Deployment**
   - Review server requirements
   - Prepare cPanel access
   - Review documentation

2. **File Upload**
   - Upload via FTP or File Manager
   - Set correct directory structure
   - Verify all files uploaded

3. **Database Setup**
   - Create database and user
   - Import SQL files
   - Verify tables created

4. **Configuration**
   - Create .env file
   - Update database credentials
   - Set production settings

5. **Permissions**
   - Set folder permissions (755/644)
   - Secure .env file (600)
   - Create upload directories

6. **Testing**
   - Test homepage loads
   - Test login functionality
   - Test admin features
   - Test customer features

7. **Security**
   - Change default passwords
   - Enable HTTPS
   - Verify security headers
   - Test CSRF protection

8. **Launch**
   - Monitor for errors
   - Set up backups
   - Configure email (optional)
   - Document deployment

## 🆘 Support & Troubleshooting

### Common Issues

**500 Internal Server Error**
- Check error logs in cPanel
- Verify PHP version (8.1+)
- Check file permissions
- Verify paths in bootstrap.php

**Database Connection Failed**
- Check credentials in .env
- Verify database exists
- Check user privileges
- Try localhost vs 127.0.0.1

**Page Not Found (404)**
- Verify .htaccess uploaded
- Check mod_rewrite enabled
- Clear browser cache

**CSS/JS Not Loading**
- Check file paths
- Verify assets folder uploaded
- Check console for 404s

### Getting Help

1. Check `DEPLOYMENT_CPANEL.md` troubleshooting section
2. Review cPanel error logs
3. Verify all checklist items completed
4. Contact hosting provider for server-specific issues

## 📊 Post-Deployment Tasks

### Immediate (Within 24 hours)
- [ ] Change all default passwords
- [ ] Update admin email address
- [ ] Test all major features
- [ ] Monitor error logs
- [ ] Set up SSL certificate
- [ ] Enable HTTPS redirect

### Within First Week
- [ ] Set up automated backups
- [ ] Configure email notifications
- [ ] Test email functionality
- [ ] Create backup restoration plan
- [ ] Document custom configurations
- [ ] Train staff on system usage

### Ongoing Maintenance
- [ ] Monitor error logs weekly
- [ ] Backup database weekly
- [ ] Update passwords quarterly
- [ ] Review and clean old files monthly
- [ ] Monitor disk space usage
- [ ] Review security logs

## 🔒 Security Best Practices

1. **Always use HTTPS** - Enable SSL certificate
2. **Change default passwords** - Use strong, unique passwords
3. **Keep .env secure** - Never commit to version control
4. **Regular backups** - Automate database and file backups
5. **Monitor logs** - Check for suspicious activity
6. **Update regularly** - Keep PHP and dependencies updated
7. **Limit access** - Use strong passwords and 2FA if available
8. **Test security** - Regularly test for vulnerabilities

## 📝 Version Information

- **Version:** 1.0.0
- **Release Date:** November 2025
- **PHP Version:** 8.1+
- **Framework:** Vanilla PHP (no framework)
- **Database:** MySQL 5.7+ / MariaDB 10.3+

## 📄 License

This is a proprietary application for AndCorp Car Dealership.

## 🎉 Ready to Deploy?

Follow these steps:

1. ✅ Read `DEPLOYMENT_CPANEL.md`
2. ✅ Use `DEPLOYMENT_CHECKLIST.md` to track progress
3. ✅ Upload files to cPanel
4. ✅ Set up database
5. ✅ Configure .env
6. ✅ Test the system
7. ✅ Launch!

**Good luck with your deployment! 🚀**

---

**Need help?** Review the troubleshooting sections in `DEPLOYMENT_CPANEL.md` or check cPanel error logs.

