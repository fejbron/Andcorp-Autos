# Running AndCorp with MAMP 🚀

Yes! You can absolutely use MAMP to run this application. Here's how to set it up:

## Prerequisites

- MAMP or MAMP PRO installed
- PHP 8.1 or higher (MAMP includes PHP)

## Step-by-Step Setup

### 1. Start MAMP

1. Launch MAMP (or MAMP PRO)
2. Click "Start Servers" (or ensure servers are running)
3. Note the Apache and MySQL ports (default: Apache 8888, MySQL 8889)

### 2. Configure Document Root

#### Option A: MAMP (Free Version)
1. Click "Preferences" → "Web Server"
2. Set Document Root to your project's `public` folder:
   ```
   /Users/frederickbronijnr/Desktop/Andcorp-test/public
   ```
3. Click "OK"

#### Option B: MAMP PRO (Recommended)
1. Open MAMP PRO
2. Go to "Hosts" → Click "+" to add a new host
3. Set:
   - **Host Name**: `andcorp.local` (or any name you prefer)
   - **Document Root**: `/Users/frederickbronijnr/Desktop/Andcorp-test/public`
   - **Port**: 8888 (or your preferred port)
4. Click "Save" and the host will be added

### 3. Configure Database Connection

MAMP's MySQL default settings:
- **Host**: `localhost` (or `127.0.0.1`)
- **Port**: `8889` (default MAMP MySQL port)
- **Username**: `root`
- **Password**: `root` (default MAMP password)

Update your `.env` file:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=8889
DB_DATABASE=car_dealership
DB_USERNAME=root
DB_PASSWORD=root

# Mail Configuration
MAIL_FROM_ADDRESS=noreply@andcorp.com
MAIL_FROM_NAME=AndCorp Car Dealership

# SMS Configuration
SMS_ENABLED=false

# Application Settings
APP_ENV=development
APP_DEBUG=true
```

**Important**: The code has been updated to support MAMP's MySQL port (8889) automatically!

### 4. Create Database

#### Using MAMP's phpMyAdmin:
1. Go to: http://localhost:8888/phpMyAdmin (or http://andcorp.local/phpMyAdmin)
2. Login with:
   - Username: `root`
   - Password: `root`
3. Click "New" in the left sidebar
4. Create database:
   - Database name: `car_dealership`
   - Collation: `utf8mb4_unicode_ci`
5. Click "Create"
6. Select the `car_dealership` database
7. Click "Import" tab
8. Choose file: `database/schema.sql`
9. Click "Go" at the bottom
10. (Optional) Import `database/seed.sql` for demo data

#### Using Terminal:
```bash
# Navigate to your project
cd /Users/frederickbronijnr/Desktop/Andcorp-test

# Connect to MAMP MySQL (default port is 8889)
/Applications/MAMP/Library/bin/mysql -u root -proot -P 8889

# In MySQL prompt:
CREATE DATABASE IF NOT EXISTS car_dealership CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Import schema
/Applications/MAMP/Library/bin/mysql -u root -proot -P 8889 car_dealership < database/schema.sql

# (Optional) Import seed data
/Applications/MAMP/Library/bin/mysql -u root -proot -P 8889 car_dealership < database/seed.sql
```

### 5. Access Your Application

#### If using default MAMP Document Root:
- **Homepage**: http://localhost:8888
- **Admin**: http://localhost:8888/admin/dashboard.php
- **Login**: http://localhost:8888/login.php

#### If using MAMP PRO with custom host:
- **Homepage**: http://andcorp.local:8888
- **Admin**: http://andcorp.local:8888/admin/dashboard.php
- **Login**: http://andcorp.local:8888/login.php

### 6. Test the Setup

1. Visit the homepage
2. Try registering a new account
3. Login with demo credentials:
   - **Admin**: admin@andcorp.com / admin123
   - **Customer**: customer@example.com / customer123

## Troubleshooting MAMP

### Port Conflicts
If Apache port 8888 is in use:
- MAMP → Preferences → Ports → Set Apache Port to something else (e.g., 8080)
- Update your URLs accordingly

### MySQL Connection Issues
1. Check MySQL is running in MAMP
2. Verify port in MAMP preferences (usually 8889)
3. Test connection:
   ```bash
   /Applications/MAMP/Library/bin/mysql -u root -proot -P 8889 -e "SELECT 1"
   ```
4. Make sure your `.env` file has `DB_PORT=8889`

### File Permissions
Make sure storage folder is writable:
```bash
chmod -R 755 /Users/frederickbronijnr/Desktop/Andcorp-test/storage
```

### .htaccess Not Working
MAMP should support .htaccess by default. If not:
1. MAMP → Preferences → Apache
2. Ensure "Allow Override" is set to "All"

### Can't Find MySQL Binary
If the path `/Applications/MAMP/Library/bin/mysql` doesn't exist:
1. Check where MAMP is installed
2. Common locations:
   - `/Applications/MAMP/Library/bin/mysql`
   - `/Applications/MAMP/bin/mysql`
3. Use `which mysql` to find system MySQL (won't work with MAMP's port)

## MAMP vs Built-in PHP Server

**MAMP Advantages:**
- ✅ More production-like environment
- ✅ Access to phpMyAdmin
- ✅ Better for testing Apache-specific features
- ✅ Can set up multiple virtual hosts (MAMP PRO)

**Built-in PHP Server Advantages:**
- ✅ Simpler setup
- ✅ No configuration needed
- ✅ Good for quick development

You can use either! MAMP is recommended if you plan to deploy to Apache in production.

## Quick Checklist

- [ ] MAMP servers started
- [ ] Document root set to `/public` folder
- [ ] `.env` file created with MAMP MySQL credentials (port 8889)
- [ ] Database `car_dealership` created
- [ ] Schema imported (`database/schema.sql`)
- [ ] (Optional) Seed data imported (`database/seed.sql`)
- [ ] Application accessible at http://localhost:8888

## Next Steps

1. ✅ Start MAMP servers
2. ✅ Configure document root to `public` folder
3. ✅ Create database via phpMyAdmin or terminal
4. ✅ Import schema
5. ✅ Update `.env` with MAMP MySQL credentials (including `DB_PORT=8889`)
6. ✅ Visit http://localhost:8888

Enjoy your AndCorp Car Dealership system! 🚗💨
