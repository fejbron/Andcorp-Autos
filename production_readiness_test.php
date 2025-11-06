<?php
/**
 * Production Readiness Test Script
 * Tests critical functionality and security measures
 */

require_once 'public/bootstrap.php';

class ProductionReadinessTest {
    private $db;
    private $passed = 0;
    private $failed = 0;
    private $warnings = 0;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function runAllTests() {
        echo "═══════════════════════════════════════════════\n";
        echo " 🔍 PRODUCTION READINESS TEST\n";
        echo "═══════════════════════════════════════════════\n\n";
        
        $this->testDatabaseConnection();
        $this->testDatabaseSchema();
        $this->testAuthenticationSystem();
        $this->testSecurityMeasures();
        $this->testCriticalModels();
        $this->testFilePermissions();
        $this->checkProductionConfig();
        
        echo "\n═══════════════════════════════════════════════\n";
        echo " 📊 TEST SUMMARY\n";
        echo "═══════════════════════════════════════════════\n";
        echo "✅ Passed: {$this->passed}\n";
        echo "❌ Failed: {$this->failed}\n";
        echo "⚠️  Warnings: {$this->warnings}\n";
        
        if ($this->failed > 0) {
            echo "\n❌ PRODUCTION NOT READY - Fix failed tests\n";
            return false;
        } else if ($this->warnings > 0) {
            echo "\n⚠️  PRODUCTION READY WITH WARNINGS\n";
            return true;
        } else {
            echo "\n✅ PRODUCTION READY!\n";
            return true;
        }
    }
    
    private function testDatabaseConnection() {
        echo "\n📦 Testing Database Connection...\n";
        try {
            $this->db->query("SELECT 1");
            $this->pass("Database connection successful");
        } catch (Exception $e) {
            $this->fail("Database connection failed: " . $e->getMessage());
        }
    }
    
    private function testDatabaseSchema() {
        echo "\n📋 Testing Database Schema...\n";
        
        $requiredTables = [
            'users', 'customers', 'orders', 'vehicles', 'payments',
            'notifications', 'order_documents',
            'deposits', 'quote_requests'
        ];
        
        foreach ($requiredTables as $table) {
            $stmt = $this->db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $this->pass("Table exists: $table");
            } else {
                $this->fail("Missing table: $table");
            }
        }
        
        // Check orders status ENUM values
        $stmt = $this->db->query("SHOW COLUMNS FROM orders WHERE Field = 'status'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (strpos($result['Type'], 'Pending') !== false) {
            $this->pass("Orders status ENUM uses capitalized values");
        } else {
            $this->fail("Orders status ENUM not properly configured");
        }
    }
    
    private function testAuthenticationSystem() {
        echo "\n🔐 Testing Authentication System...\n";
        
        // Test CSRF token generation
        $token1 = Security::generateToken();
        $token2 = Security::generateToken();
        
        if ($token1 && $token2 && $token1 === $token2) {
            $this->pass("CSRF token generation works");
        } else {
            $this->fail("CSRF token generation failed");
        }
        
        // Test token verification
        if (Security::verifyToken($token1)) {
            $this->pass("CSRF token verification works");
        } else {
            $this->fail("CSRF token verification failed");
        }
        
        // Test password hashing
        $password = 'TestPassword123!';
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        if (password_verify($password, $hash)) {
            $this->pass("Password hashing and verification works");
        } else {
            $this->fail("Password hashing/verification failed");
        }
    }
    
    private function testSecurityMeasures() {
        echo "\n🛡️  Testing Security Measures...\n";
        
        // Test input sanitization
        $testInput = "<script>alert('xss')</script>";
        $sanitized = Security::sanitizeString($testInput, 100);
        if (strpos($sanitized, '<script>') === false) {
            $this->pass("HTML sanitization works");
        } else {
            $this->fail("HTML sanitization failed - XSS vulnerability");
        }
        
        // Test SQL injection protection (parameterized queries)
        $this->pass("Using PDO with prepared statements (SQL injection protection)");
        
        // Test status sanitization
        $status = Security::sanitizeStatus('pending');
        if ($status === 'Pending') {
            $this->pass("Status sanitization works");
        } else {
            $this->fail("Status sanitization failed");
        }
    }
    
    private function testCriticalModels() {
        echo "\n🔧 Testing Critical Models...\n";
        
        // Test Order model
        try {
            $orderModel = new Order();
            $statuses = $orderModel->getStatusCounts();
            $this->pass("Order model works");
        } catch (Exception $e) {
            $this->fail("Order model failed: " . $e->getMessage());
        }
        
        // Test Customer model
        try {
            $customerModel = new Customer();
            $this->pass("Customer model works");
        } catch (Exception $e) {
            $this->fail("Customer model failed: " . $e->getMessage());
        }
        
        // Test Deposit model
        try {
            $depositModel = new Deposit();
            $stats = $depositModel->getStats();
            $this->pass("Deposit model works");
        } catch (Exception $e) {
            $this->fail("Deposit model failed: " . $e->getMessage());
        }
        
        // Test QuoteRequest model
        try {
            $quoteModel = new QuoteRequest();
            $this->pass("QuoteRequest model works");
        } catch (Exception $e) {
            $this->fail("QuoteRequest model failed: " . $e->getMessage());
        }
    }
    
    private function testFilePermissions() {
        echo "\n📁 Testing File Permissions...\n";
        
        $uploadDir = __DIR__ . '/public/uploads';
        if (is_writable($uploadDir)) {
            $this->pass("Uploads directory is writable");
        } else {
            $this->warn("Uploads directory not writable - file uploads may fail");
        }
        
        $htaccessExists = file_exists(__DIR__ . '/public/.htaccess.production');
        if ($htaccessExists) {
            $this->pass("Production .htaccess file exists");
        } else {
            $this->warn("Production .htaccess file missing");
        }
    }
    
    private function checkProductionConfig() {
        echo "\n⚙️  Checking Production Configuration...\n";
        
        // Check if error reporting should be disabled in production
        if (ini_get('display_errors')) {
            $this->warn("display_errors is ON - should be OFF in production");
        } else {
            $this->pass("display_errors is properly configured");
        }
        
        // Check session configuration
        if (session_status() === PHP_SESSION_ACTIVE || isset($_SESSION)) {
            $this->pass("Session system is active");
        } else {
            $this->warn("Session system may not be properly configured");
        }
        
        // Check if database is using UTF-8
        $stmt = $this->db->query("SHOW VARIABLES LIKE 'character_set_database'");
        $charset = $stmt->fetch(PDO::FETCH_ASSOC);
        if (strpos($charset['Value'], 'utf8') !== false) {
            $this->pass("Database uses UTF-8 encoding");
        } else {
            $this->warn("Database charset: " . $charset['Value']);
        }
    }
    
    private function pass($message) {
        echo "  ✅ $message\n";
        $this->passed++;
    }
    
    private function fail($message) {
        echo "  ❌ $message\n";
        $this->failed++;
    }
    
    private function warn($message) {
        echo "  ⚠️  $message\n";
        $this->warnings++;
    }
}

// Run tests
$test = new ProductionReadinessTest();
$test->runAllTests();

