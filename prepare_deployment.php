<?php
/**
 * Deployment Preparation Script for Namecheap/cPanel
 * 
 * This script prepares the application for deployment by:
 * - Removing development files
 * - Creating deployment package
 * - Generating configuration template
 */

echo "═══════════════════════════════════════════════\n";
echo " 🚀 DEPLOYMENT PREPARATION FOR NAMECHEAP\n";
echo "═══════════════════════════════════════════════\n\n";

$baseDir = __DIR__;
$publicDir = $baseDir . '/public';
$deployDir = $baseDir . '/deployment_package';

// Files to remove (development only)
$filesToRemove = [
    'production_readiness_test.php',
    'PRODUCTION_READINESS_REPORT.md',
    'PRODUCTION_CHECKLIST.md',
    'NAMECHEAP_DEPLOYMENT.md',
    'prepare_deployment.php',
    'public/admin/update_status_enum.php',
    'public/admin/fix_status_enum.php',
    'public/admin/check_enum_match.php',
];

// Files to copy
$dirsToCopy = [
    'app',
    'public',
    'database',
];

echo "📋 Step 1: Creating deployment directory...\n";
if (is_dir($deployDir)) {
    echo "   ⚠️  Deployment directory exists, removing...\n";
    removeDirectory($deployDir);
}
mkdir($deployDir, 0755, true);
echo "   ✅ Created: $deployDir\n\n";

echo "📁 Step 2: Copying application files...\n";
foreach ($dirsToCopy as $dir) {
    $source = $baseDir . '/' . $dir;
    $dest = $deployDir . '/' . $dir;
    
    if (is_dir($source)) {
        copyDirectory($source, $dest);
        echo "   ✅ Copied: $dir/\n";
    } else {
        echo "   ⚠️  Directory not found: $dir/\n";
    }
}
echo "\n";

echo "🧹 Step 3: Removing development files...\n";
$removed = 0;
foreach ($filesToRemove as $file) {
    $fullPath = $deployDir . '/' . $file;
    if (file_exists($fullPath)) {
        unlink($fullPath);
        echo "   ✅ Removed: $file\n";
        $removed++;
    }
}
if ($removed === 0) {
    echo "   ℹ️  No development files found (already clean)\n";
}
echo "\n";

echo "📝 Step 4: Renaming .htaccess.production to .htaccess...\n";
$htaccessProd = $deployDir . '/public/.htaccess.production';
$htaccess = $deployDir . '/public/.htaccess';
if (file_exists($htaccessProd)) {
    copy($htaccessProd, $htaccess);
    echo "   ✅ Created: public/.htaccess\n";
} else {
    echo "   ⚠️  .htaccess.production not found\n";
}
echo "\n";

echo "⚙️  Step 5: Creating configuration template...\n";
$configTemplate = <<<'PHP'
<?php
/**
 * Database Configuration Template
 * 
 * COPY THIS FILE TO: app/Database.php
 * Then update the database credentials below
 */

class Database {
    // UPDATE THESE VALUES FOR YOUR NAMECHEAP DATABASE
    private static $host = 'localhost';
    private static $database = 'YOUR_DATABASE_NAME';  // e.g., username_andcorp_db
    private static $username = 'YOUR_DATABASE_USER';  // e.g., username_andcorp_user
    private static $password = 'YOUR_DATABASE_PASSWORD';
    
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$database . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, self::$username, self::$password, $options);
            
            // Set a more lenient SQL mode to prevent ENUM warnings from becoming fatal errors
            $this->connection->exec("SET SESSION sql_mode = 'ONLY_FULL_GROUP_BY,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}
PHP;

file_put_contents($deployDir . '/DATABASE_CONFIG_TEMPLATE.php', $configTemplate);
echo "   ✅ Created: DATABASE_CONFIG_TEMPLATE.php\n";
echo "\n";

echo "📦 Step 6: Creating deployment package...\n";
$zipFile = $baseDir . '/andcorp_deployment_' . date('Y-m-d') . '.zip';
if (file_exists($zipFile)) {
    unlink($zipFile);
}

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($deployDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($deployDir) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
    
    $zip->close();
    $zipSize = filesize($zipFile);
    $zipSizeMB = round($zipSize / 1024 / 1024, 2);
    echo "   ✅ Created: " . basename($zipFile) . " ({$zipSizeMB} MB)\n";
} else {
    echo "   ❌ Failed to create ZIP file\n";
}
echo "\n";

echo "📋 Step 7: Creating README...\n";
$readme = <<<'MD'
# AndCorp Autos - Deployment Package

## 📦 What's Included

- `app/` - Application core files
- `public/` - Public web files (upload to public_html/)
- `database/` - SQL schema files (for reference)
- `.htaccess` - Production web server configuration
- `DATABASE_CONFIG_TEMPLATE.php` - Database configuration template

## 🚀 Quick Start

1. **Upload files** to your Namecheap hosting
2. **Create database** in cPanel
3. **Import SQL files** from `database/` folder
4. **Configure database** in `app/Database.php` (see template)
5. **Set file permissions** (755 for folders, 644 for files)
6. **Test your site!**

## 📖 Full Instructions

See `NAMECHEAP_DEPLOYMENT.md` for complete deployment guide.

## ⚙️ Configuration Required

1. Update `app/Database.php` with your database credentials
2. Verify `.htaccess` is active (should be in public_html/)
3. Set uploads folder permissions: `chmod 755 public/uploads/`

## 🔒 Security Notes

- All sensitive files are protected by .htaccess
- SSL/HTTPS is recommended (force HTTPS enabled)
- Error display is disabled in production
- Session security is enabled

## 📞 Support

For deployment issues:
1. Check cPanel Error Logs
2. Verify database connection
3. Check file permissions
4. Review NAMECHEAP_DEPLOYMENT.md

---

**Generated**: <?php echo date('Y-m-d H:i:s'); ?>
**Version**: 1.0
MD;

file_put_contents($deployDir . '/README.md', $readme);
echo "   ✅ Created: README.md\n";
echo "\n";

echo "═══════════════════════════════════════════════\n";
echo " ✅ DEPLOYMENT PACKAGE READY!\n";
echo "═══════════════════════════════════════════════\n\n";
echo "📦 Package Location: $deployDir\n";
echo "📦 ZIP File: $zipFile\n\n";
echo "📋 Next Steps:\n";
echo "   1. Review files in: $deployDir\n";
echo "   2. Upload ZIP file to Namecheap\n";
echo "   3. Extract in public_html/\n";
echo "   4. Follow NAMECHEAP_DEPLOYMENT.md\n\n";

// Helper functions
function copyDirectory($source, $destination) {
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $item) {
        $destPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
        
        if ($item->isDir()) {
            if (!is_dir($destPath)) {
                mkdir($destPath, 0755, true);
            }
        } else {
            copy($item, $destPath);
        }
    }
}

function removeDirectory($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? removeDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

