# Quick Installation Steps for cPanel VPS

Follow these steps in order for successful deployment.

## Step 1: Download the Package
- Download `andcorp-deployment.zip` to your computer

## Step 2: Access cPanel
- Log into your cPanel account
- URL usually: `https://yourdomain.com:2083` or provided by your host

## Step 3: Upload Files

### Method A: File Manager (Recommended for beginners)
1. In cPanel, click **"File Manager"**
2. Navigate to your home directory (usually `/home/username/`)
3. Click **"Upload"** button at the top
4. Select `andcorp-deployment.zip` and upload
5. After upload completes, go back to File Manager
6. Find the zip file, right-click and select **"Extract"**
7. Extract to current directory

### Method B: FTP (For advanced users)
1. Use FileZilla or any FTP client
2. Connect using your FTP credentials
3. Upload `andcorp-deployment.zip` to home directory
4. Extract using cPanel File Manager

## Step 4: Organize Files

After extraction, you should have an `Andcorp-test` folder.

### Move Files to Correct Locations:

1. **Move everything from `Andcorp-test/public/` to `public_html/`:**
   - Select all files inside `Andcorp-test/public/`
   - Move them to your `public_html/` directory
   
2. **Move application folders up one level:**
   - Move `Andcorp-test/app/` to `/home/username/app/`
   - Move `Andcorp-test/config/` to `/home/username/config/`
   - Move `Andcorp-test/database/` to `/home/username/database/`
   
3. **Final structure should look like:**
   ```
   /home/username/
   ├── public_html/           ← Web root
   │   ├── index.php
   │   ├── login.php
   │   ├── bootstrap.php
   │   ├── admin/
   │   ├── orders/
   │   ├── assets/
   │   └── .htaccess
   ├── app/                   ← Application code
   ├── config/                ← Configuration
   ├── database/              ← SQL files
   └── Andcorp-test/          ← Can delete after setup
   ```

## Step 5: Create Database

1. In cPanel, go to **"MySQL Databases"**
2. Under **"Create New Database"**:
   - Database name: `cardealership` (or your choice)
   - Click "Create Database"
3. Under **"MySQL Users"** > **"Add New User"**:
   - Username: `cardealer` (or your choice)
   - Password: Generate a strong password or create one
   - Click "Create User"
4. Under **"Add User To Database"**:
   - User: Select the user you created
   - Database: Select the database you created
   - Click "Add"
   - Select **"ALL PRIVILEGES"**
   - Click "Make Changes"

**IMPORTANT:** Write down these details:
- Database name: _______________________
- Database user: _______________________
- Database password: ___________________

## Step 6: Import Database

1. In cPanel, go to **"phpMyAdmin"**
2. Click on your database name in the left sidebar
3. Click **"Import"** tab
4. Click **"Choose File"** and select:
   - First: `database/schema.sql` → Click "Go"
   - Wait for success, then:
   - Second: `database/seed.sql` → Click "Go"
   - Wait for success, then:
   - Third: `database/updates_gallery_final.sql` → Click "Go"
   - Wait for success, then:
   - Fourth: `database/indexes.sql` → Click "Go"
5. Click on your database name again
6. Verify you see these tables:
   - activity_logs
   - customers
   - inspection_reports
   - notifications
   - order_documents
   - order_updates
   - orders
   - tracking_history
   - users
   - vehicles

## Step 7: Configure Environment

1. In File Manager, go to `/home/username/`
2. Find `env.cpanel.template` file
3. Right-click → Copy → name it `.env`
4. Right-click `.env` → Edit
5. Update these lines with your database details:
   ```
   DB_DATABASE=your_actual_database_name
   DB_USERNAME=your_actual_database_user
   DB_PASSWORD=your_actual_database_password
   APP_URL=https://yourdomain.com
   APP_ENV=production
   APP_DEBUG=false
   ```
6. Save the file
7. Right-click `.env` → Change Permissions → Set to **600**

## Step 8: Update Bootstrap Paths

1. Go to `public_html/bootstrap.php`
2. Right-click → Edit
3. Find these lines near the top (around line 4-7):
   ```php
   require_once __DIR__ . '/../app/Security.php';
   require_once __DIR__ . '/../app/Headers.php';
   require_once __DIR__ . '/../app/Validator.php';
   require_once __DIR__ . '/../app/Cache.php';
   ```
4. Replace `__DIR__ . '/../app/` with your actual path.
   
   **Find your path:** In File Manager, click on `app` folder, look at the path at top.
   It will be like: `/home/username/app/`
   
   Update to:
   ```php
   require_once '/home/username/app/Security.php';
   require_once '/home/username/app/Headers.php';
   require_once '/home/username/app/Validator.php';
   require_once '/home/username/app/Cache.php';
   ```
5. Also find the autoloader section (around line 33):
   ```php
   $paths = [
       __DIR__ . '/../app/' . $class . '.php',
       __DIR__ . '/../app/Models/' . $class . '.php',
   ];
   ```
   
   Update to:
   ```php
   $paths = [
       '/home/username/app/' . $class . '.php',
       '/home/username/app/Models/' . $class . '.php',
   ];
   ```
6. Find the .env loading section (around line 46):
   ```php
   if (file_exists(__DIR__ . '/../.env')) {
       $lines = file(__DIR__ . '/../.env', ...);
   ```
   
   Update to:
   ```php
   if (file_exists('/home/username/.env')) {
       $lines = file('/home/username/.env', ...);
   ```
7. Save the file

## Step 9: Set File Permissions

In File Manager, set these permissions (right-click → Change Permissions):

```
public_html/                755
public_html/uploads/        755
public_html/uploads/documents/  755 (create if doesn't exist)
public_html/uploads/cars/   755 (create if doesn't exist)
app/                        755
config/                     755
database/                   755
.env                        600
```

Create missing directories:
- `public_html/uploads/documents/`
- `public_html/uploads/cars/`

## Step 10: Setup Production .htaccess

1. In `public_html/`, find `.htaccess.production`
2. Copy its contents
3. Open `public_html/.htaccess` and replace with the production version
4. Save the file

## Step 11: Enable SSL (Recommended)

1. In cPanel, go to **"SSL/TLS Status"**
2. Select your domain
3. Click **"Run AutoSSL"**
4. Wait for SSL to be installed
5. After SSL is active, uncomment HTTPS redirect in `.htaccess`:
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
   ```

## Step 12: Test Your Installation

1. Open your browser
2. Go to: `https://yourdomain.com`
3. You should see the AndCorp homepage

**Test Login:**
- Click "Login"
- Use: `admin@andcorp.com` / `admin123`
- You should see the admin dashboard

**Test Customer Login:**
- Logout
- Login with: `customer@example.com` / `customer123`
- You should see the customer dashboard

## Step 13: Secure Your Installation

**IMMEDIATELY after successful login:**

1. **Change Admin Password:**
   - Login as admin
   - Click Profile
   - Change password to something strong
   
2. **Change Admin Email:**
   - Update email to your actual email
   
3. **Delete Test Customer** (optional):
   - Or change their password too

4. **Verify .env is secure:**
   - Try accessing: `https://yourdomain.com/../.env`
   - Should show 403 Forbidden or 404

## Step 14: Setup Backups

1. In cPanel, go to **"Backup"**
2. Set up automatic backups:
   - Full backup: Weekly
   - Database backup: Daily
3. Download initial backup to your computer

## Done! 🎉

Your AndCorp Car Dealership system is now live!

### Next Steps:
- [ ] Create your first real customer account
- [ ] Create your first real order
- [ ] Upload car images and documents
- [ ] Configure email settings in .env (optional)
- [ ] Train staff on how to use the system
- [ ] Monitor error logs for first few days

### Support:
- Check `DEPLOYMENT_CPANEL.md` for detailed troubleshooting
- Check `DEPLOYMENT_CHECKLIST.md` for complete task list
- Review cPanel error logs if issues occur

---

**Default Credentials (CHANGE THESE!):**
- Admin: `admin@andcorp.com` / `admin123`
- Customer: `customer@example.com` / `customer123`

**Congratulations on your deployment! 🚀**

