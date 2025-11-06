# Quick Start Guide

## Getting Your Car Dealership Web App Running

### Prerequisites Check
Make sure you have:
- ✅ PHP 8.1 or higher
- ✅ MySQL or MariaDB
- ✅ Web browser

### Quick Setup (5 minutes)

#### Step 1: Create the Database
```bash
# Open MySQL
mysql -u root -p

# Create database
CREATE DATABASE car_dealership;
exit;
```

#### Step 2: Import Database Schema
```bash
# Navigate to your project directory
cd /Users/frederickbronijnr/Desktop/Andcorp-test

# Import the schema
mysql -u root -p car_dealership < database/schema.sql

# Import sample data (includes demo accounts)
mysql -u root -p car_dealership < database/seed.sql
```

#### Step 3: Configure Environment
```bash
# Copy the environment template
cp .env.example .env

# Edit .env file with your database password
# Change DB_PASSWORD to match your MySQL root password
nano .env
```

#### Step 4: Start the Application
```bash
# Navigate to public directory
cd public

# Start PHP development server
php -S localhost:8000
```

#### Step 5: Access the Application
Open your browser and go to:
- **Homepage:** http://localhost:8000
- **Login:** http://localhost:8000/login.php

### Login Credentials

**Admin Dashboard:**
- Email: `admin@andcorp.com`
- Password: `admin123`

**Customer Portal:**
- Email: `customer@example.com`
- Password: `customer123`

### What You Can Do

#### As Admin:
1. View all orders and customers
2. Create new orders
3. Update order status
4. Add shipping information
5. Upload inspection reports
6. Track repairs
7. Manage deliveries
8. Send notifications

#### As Customer:
1. View their orders
2. Track order progress
3. See vehicle details
4. View inspection reports
5. Check customs fees
6. Monitor repair updates
7. Receive notifications

### Your Business Workflow

```
Customer Order → Purchase from Copart/IAA → Shipping to Ghana → 
Customs Clearance → Vehicle Inspection → Repairs → Delivery
```

Each stage is tracked and customers receive updates!

### Troubleshooting

**Can't connect to database?**
- Make sure MySQL is running: `mysql.server start` (macOS)
- Check credentials in `.env` file

**Page not found?**
- Make sure you're in the `public` directory
- Check the URL includes `localhost:8000`

**Permission errors?**
```bash
chmod -R 755 storage/
```

### Next Steps

1. **Change Default Passwords** - Update admin password in database
2. **Configure Email** - Set up SMTP in `.env` for notifications
3. **Add Your Logo** - Replace branding in navigation
4. **Customize Colors** - Edit Bootstrap theme colors
5. **Add Content** - Update homepage with your business details

### Project Structure

- `public/` - All web pages (index, login, dashboard, etc.)
- `app/` - PHP classes (Database, Auth, Models)
- `database/` - SQL schema and sample data
- `storage/uploads/` - Uploaded files storage

### Need Help?

Check these files:
- `README.md` - Full project documentation
- `INSTALL.md` - Detailed installation guide
- `PROJECT_SUMMARY.md` - Complete feature list

### Important Security Notes

For production deployment:
1. Change all default passwords
2. Set `APP_DEBUG=false` in `.env`
3. Use HTTPS
4. Secure file upload directory
5. Enable MySQL security features

---

**You're all set!** 🎉

Your car dealership management system is ready to use. Start by logging in as admin to explore the features.
