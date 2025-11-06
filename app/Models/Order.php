<?php

class Order {
    private $db;
    private static $validStatuses = null;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get valid status values from database ENUM
     */
    private function getValidStatuses() {
        if (self::$validStatuses !== null) {
            return self::$validStatuses;
        }
        
        try {
            $sql = "SHOW COLUMNS FROM orders WHERE Field = 'status'";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && preg_match("/enum\('(.*)'\)/i", $result['Type'], $matches)) {
                self::$validStatuses = explode("','", $matches[1]);
                return self::$validStatuses;
            }
        } catch (Exception $e) {
            error_log("Error getting valid statuses from database: " . $e->getMessage());
        }
        
        // Fallback to expected values (capitalized to match database)
        self::$validStatuses = ['Pending', 'Purchased', 'Shipping', 'Customs', 'Inspection', 'Repair', 'Ready', 'Delivered', 'Cancelled'];
        return self::$validStatuses;
    }
    
    public function create($data) {
        // Validate and sanitize inputs
        $customerId = Security::sanitizeInt($data['customer_id'] ?? 0);
        if ($customerId <= 0) {
            throw new InvalidArgumentException('Invalid customer ID');
        }
        
        $sql = "INSERT INTO orders (customer_id, order_number, status, total_cost, deposit_amount, balance_due, currency, notes) 
                VALUES (:customer_id, :order_number, :status, :total_cost, :deposit_amount, :balance_due, :currency, :notes)";
        
        // Sanitize status - ensure it's a valid ENUM value from database
        $status = $data['status'] ?? 'Pending';
        $status = trim($status);
        // Capitalize first letter to match database ENUM format
        $status = ucfirst(strtolower($status));
        
        // Get valid statuses from database ENUM (dynamically)
        $allowedStatuses = $this->getValidStatuses();
        
        if (!in_array($status, $allowedStatuses, true)) {
            error_log("Invalid status value received: '" . $status . "'. Allowed: " . implode(', ', $allowedStatuses) . ". Defaulting to 'Pending'");
            $status = 'Pending';
        }
        
        // Log the status value being inserted for debugging
        error_log("Order::create - Inserting status: '$status'");
        
        try {
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':customer_id' => $customerId,
                ':order_number' => Security::sanitizeString($data['order_number'] ?? '', 50),
                ':status' => $status, // Use validated status directly
                ':total_cost' => Security::sanitizeFloat($data['total_cost'] ?? 0, 0),
                ':deposit_amount' => Security::sanitizeFloat($data['deposit_amount'] ?? 0, 0),
                ':balance_due' => Security::sanitizeFloat($data['balance_due'] ?? 0, 0),
                ':currency' => 'GHS', // Ghana Cedis only
                ':notes' => !empty($data['notes']) ? Security::sanitizeString($data['notes'], 5000) : null
            ]);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Order::create - SQL Error: " . json_encode($errorInfo));
                throw new Exception("Database error: " . ($errorInfo[2] ?? 'Unknown error'));
            }
        } catch (PDOException $e) {
            error_log("Order::create - PDO Exception: " . $e->getMessage());
            error_log("Order::create - Status value: '$status'");
            error_log("Order::create - Full data: " . json_encode($data));
            throw $e;
        }
        
        $orderId = $this->db->lastInsertId();
        
        // Clear cache after creating order
        Cache::delete('order_status_counts');
        
        return $orderId;
    }
    
    public function findById($id) {
        $id = Security::sanitizeInt($id);
        if ($id <= 0) {
            return null;
        }
        
        $sql = "SELECT o.*, c.user_id, u.first_name, u.last_name, u.email, u.phone 
                FROM orders o
                JOIN customers c ON o.customer_id = c.id
                JOIN users u ON c.user_id = u.id
                WHERE o.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public function findByOrderNumber($orderNumber) {
        $orderNumber = Security::sanitizeString($orderNumber, 50);
        
        $sql = "SELECT o.*, c.user_id, u.first_name, u.last_name, u.email 
                FROM orders o
                JOIN customers c ON o.customer_id = c.id
                JOIN users u ON c.user_id = u.id
                WHERE o.order_number = :order_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':order_number' => $orderNumber]);
        return $stmt->fetch();
    }
    
    public function getByCustomer($customerId) {
        $customerId = Security::sanitizeInt($customerId);
        if ($customerId <= 0) {
            return [];
        }
        
        $sql = "SELECT * FROM orders WHERE customer_id = :customer_id ORDER BY created_at DESC LIMIT 500";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':customer_id' => $customerId]);
        return $stmt->fetchAll();
    }
    
    public function getAll($status = null, $limit = 100, $offset = 0) {
        // Enforce reasonable limits for performance
        $limit = min($limit, 500); // Max 500 records
        $offset = max($offset, 0);
        
        if ($status) {
            $sql = "SELECT o.*, u.first_name, u.last_name, u.email 
                    FROM orders o
                    JOIN customers c ON o.customer_id = c.id
                    JOIN users u ON c.user_id = u.id
                    WHERE o.status = :status 
                    ORDER BY o.created_at DESC 
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        } else {
            $sql = "SELECT o.*, u.first_name, u.last_name, u.email 
                    FROM orders o
                    JOIN customers c ON o.customer_id = c.id
                    JOIN users u ON c.user_id = u.id
                    ORDER BY o.created_at DESC 
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
        }
        
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function updateStatus($id, $status) {
        $sql = "UPDATE orders SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':id' => Security::sanitizeInt($id), 
            ':status' => Security::sanitizeStatus($status)
        ]);
        
        // Clear cache after status update
        if ($result) {
            Cache::delete('order_status_counts');
        }
        
        return $result;
    }
    
    public function update($id, $data) {
        // Whitelist allowed fields for security
        $allowedFields = [
            'status', 'total_cost', 'deposit_amount', 'balance_due', 
            'currency', 'notes', 'order_number'
        ];
        
        $fields = [];
        $params = [':id' => Security::sanitizeInt($id)];
        
        foreach ($data as $key => $value) {
            if (!in_array($key, $allowedFields, true)) {
                continue; // Skip unauthorized fields
            }
            
            if ($key === 'status') {
                $fields[] = "status = :status";
                $params[':status'] = Security::sanitizeStatus($value);
            } elseif ($key === 'total_cost' || $key === 'deposit_amount' || $key === 'balance_due') {
                $fields[] = "$key = :$key";
                $params[":$key"] = Security::sanitizeFloat($value, 0);
            } elseif ($key === 'currency') {
                // Currency is fixed to GHS only
                $fields[] = "currency = :currency";
                $params[':currency'] = 'GHS';
            } elseif ($key === 'notes') {
                $fields[] = "notes = :notes";
                $params[':notes'] = Security::sanitizeString($value ?? '', 5000);
            } elseif ($key === 'order_number') {
                $fields[] = "order_number = :order_number";
                $params[':order_number'] = Security::sanitizeString($value, 50);
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE orders SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        // Clear relevant caches after update
        if ($result) {
            Cache::delete('order_status_counts');
            Cache::delete('orders_all_all_100_0');
        }
        
        return $result;
    }
    
    public function generateOrderNumber() {
        $year = date('Y');
        
        // Use cache to avoid database hit on every order creation
        $cacheKey = "order_count_{$year}";
        $count = Cache::remember($cacheKey, function() use ($year) {
            $sql = "SELECT COUNT(*) as count FROM orders WHERE YEAR(created_at) = :year";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':year' => $year]);
            $result = $stmt->fetch();
            return (int)$result['count'];
        }, 300); // Cache for 5 minutes
        
        // Increment and update cache
        $count++;
        Cache::set($cacheKey, $count, 300);
        
        return sprintf('ORD-%s-%04d', $year, $count);
    }
    
    public function getStatusCounts() {
        // Cache status counts for 5 minutes
        return Cache::remember('order_status_counts', function() {
            $sql = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
            $stmt = $this->db->query($sql);
            $results = $stmt->fetchAll();
            
            $counts = [];
            foreach ($results as $row) {
                $counts[$row['status']] = (int)$row['count'];
            }
            return $counts;
        }, 300);
    }
}
