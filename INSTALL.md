# Installation Guide

## Step 1: Set Up Environment

1. **Copy environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Edit `.env` file with your database credentials:**
   ```
   DB_HOST=localhost
   DB_DATABASE=car_dealership
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

## Step 2: Create Database

1. **Create MySQL database:**
   ```bash
   mysql -u root -p
   CREATE DATABASE car_dealership;
   exit;
   ```

2. **Import database schema:**
   ```bash
   mysql -u root -p car_dealership < database/schema.sql
   ```

3. **Import sample data (optional):**
   ```bash
   mysql -u root -p car_dealership < database/seed.sql
   ```

## Step 3: Set Up Directories

Create required directories for file uploads:
```bash
mkdir -p storage/uploads
chmod 755 storage/uploads
```

## Step 4: Start Development Server

You have several options:

### Option 1: PHP Built-in Server
```bash
cd public
php -S localhost:8000
```

### Option 2: Apache/Nginx
Point your web server's document root to the `public` directory.

Apache VirtualHost example:
```apache
<VirtualHost *:80>
    ServerName andcorp.local
    DocumentRoot /path/to/Andcorp-test/public
    
    <Directory /path/to/Andcorp-test/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Step 5: Access the Application

- **Homepage:** http://localhost:8000
- **Admin Login:** http://localhost:8000/login.php
  - Email: admin@andcorp.com
  - Password: admin123

- **Customer Login:**
  - Email: customer@example.com
  - Password: customer123

## Troubleshooting

### Database Connection Issues
- Verify MySQL is running
- Check credentials in `.env` file
- Ensure database exists

### Permission Issues
```bash
chmod -R 755 storage/
chmod -R 755 public/
```

### PHP Extensions Required
- PDO
- pdo_mysql
- mbstring
- openssl

Check installed extensions:
```bash
php -m
```

## Production Deployment

1. **Security:**
   - Change all default passwords
   - Set `APP_DEBUG=false` in `.env`
   - Use HTTPS
   - Secure database credentials

2. **Performance:**
   - Enable OPcache
   - Use production database
   - Configure proper caching

3. **Backup:**
   - Regular database backups
   - Backup uploaded files in `storage/`

## Support

For issues or questions, contact: support@andcorp.com
