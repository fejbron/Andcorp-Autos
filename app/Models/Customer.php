<?php

class Customer {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        // Validate and sanitize inputs
        $userId = Security::sanitizeInt($data['user_id'] ?? 0);
        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid user ID');
        }
        
        $sql = "INSERT INTO customers (user_id, address, city, country, identification_number, preferred_contact) 
                VALUES (:user_id, :address, :city, :country, :identification_number, :preferred_contact)";
        
        $preferredContactValue = $data['preferred_contact'] ?? 'email';
        $preferredContact = Security::validateEnum($preferredContactValue, ['email', 'phone', 'sms']) 
            ? $preferredContactValue 
            : 'email';
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':user_id' => $userId,
            ':address' => !empty($data['address']) ? Security::sanitizeString($data['address'], 500) : null,
            ':city' => !empty($data['city']) ? Security::sanitizeString($data['city'], 100) : null,
            ':country' => !empty($data['country']) ? Security::sanitizeString($data['country'], 100) : 'Ghana',
            ':identification_number' => !empty($data['identification_number']) ? Security::sanitizeString($data['identification_number'], 50) : null,
            ':preferred_contact' => $preferredContact
        ]);
        
        // Clear customers cache after creating
        if ($result) {
            Cache::delete('customers_all');
        }
        
        return $result;
    }
    
    public function findByUserId($userId) {
        $userId = Security::sanitizeInt($userId);
        if ($userId <= 0) {
            return null;
        }
        
        $sql = "SELECT c.*, u.email, u.first_name, u.last_name, u.phone, u.is_active 
                FROM customers c
                JOIN users u ON c.user_id = u.id
                WHERE c.user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch();
    }
    
    public function findById($id) {
        $id = Security::sanitizeInt($id);
        if ($id <= 0) {
            return null;
        }
        
        $sql = "SELECT c.*, u.email, u.first_name, u.last_name, u.phone, u.is_active 
                FROM customers c
                JOIN users u ON c.user_id = u.id
                WHERE c.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public function update($id, $data) {
        // Whitelist allowed fields
        $allowedFields = ['address', 'city', 'country', 'identification_number', 'preferred_contact'];
        $fields = [];
        $params = [':id' => Security::sanitizeInt($id)];
        
        foreach ($data as $key => $value) {
            if (!in_array($key, $allowedFields, true)) {
                continue; // Skip unauthorized fields
            }
            
            if ($key === 'preferred_contact') {
                $allowed = ['email', 'phone', 'sms'];
                if (in_array($value, $allowed, true)) {
                    $fields[] = "preferred_contact = :preferred_contact";
                    $params[':preferred_contact'] = $value;
                }
            } elseif ($key === 'address') {
                $fields[] = "address = :address";
                $params[':address'] = !empty($value) ? Security::sanitizeString($value, 500) : null;
            } elseif ($key === 'city' || $key === 'country') {
                $fields[] = "$key = :$key";
                $params[":$key"] = !empty($value) ? Security::sanitizeString($value, 100) : null;
            } elseif ($key === 'identification_number') {
                $fields[] = "identification_number = :identification_number";
                $params[':identification_number'] = !empty($value) ? Security::sanitizeString($value, 50) : null;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE customers SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function getAll($limit = 1000) {
        // Cache customers list for 10 minutes (only if no limit specified, meaning full list)
        if ($limit >= 1000) {
            return Cache::remember('customers_all', function() {
                $sql = "SELECT c.*, u.email, u.first_name, u.last_name, u.phone, u.is_active, u.created_at as user_created_at
                        FROM customers c
                        JOIN users u ON c.user_id = u.id
                        ORDER BY c.created_at DESC";
                
                $stmt = $this->db->query($sql);
                return $stmt->fetchAll();
            }, 600);
        }
        
        $sql = "SELECT c.*, u.email, u.first_name, u.last_name, u.phone, u.is_active, u.created_at as user_created_at
                FROM customers c
                JOIN users u ON c.user_id = u.id
                ORDER BY c.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', min($limit, 1000), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
