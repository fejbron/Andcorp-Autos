<?php

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        // Validate and sanitize input
        $email = Security::validateEmail($data['email'] ?? '');
        if (!$email) {
            throw new InvalidArgumentException('Invalid email address');
        }
        
        $password = $data['password'] ?? '';
        $passwordValidation = Security::validatePassword($password);
        if ($passwordValidation !== true) {
            throw new InvalidArgumentException($passwordValidation);
        }
        
        $sql = "INSERT INTO users (email, password, role, first_name, last_name, phone) 
                VALUES (:email, :password, :role, :first_name, :last_name, :phone)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            ':role' => Security::sanitizeRole($data['role'] ?? 'customer'),
            ':first_name' => Security::sanitizeString($data['first_name'] ?? '', 100),
            ':last_name' => Security::sanitizeString($data['last_name'] ?? '', 100),
            ':phone' => !empty($data['phone']) ? Security::sanitizeString($data['phone'], 20) : null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public function update($id, $data) {
        // Whitelist allowed fields
        $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'password', 'role', 'is_active'];
        $fields = [];
        $params = [':id' => Security::sanitizeInt($id)];
        
        foreach ($data as $key => $value) {
            if (!in_array($key, $allowedFields, true)) {
                continue; // Skip unauthorized fields
            }
            
            if ($key === 'password') {
                $passwordValidation = Security::validatePassword($value);
                if ($passwordValidation !== true) {
                    throw new InvalidArgumentException($passwordValidation);
                }
                $fields[] = "password = :password";
                $params[':password'] = password_hash($value, PASSWORD_BCRYPT, ['cost' => 12]);
            } elseif ($key === 'email') {
                $email = Security::validateEmail($value);
                if (!$email) {
                    throw new InvalidArgumentException('Invalid email address');
                }
                $fields[] = "email = :email";
                $params[':email'] = $email;
            } elseif ($key === 'role') {
                $fields[] = "role = :role";
                $params[':role'] = Security::sanitizeRole($value);
            } elseif ($key === 'is_active') {
                $fields[] = "is_active = :is_active";
                $params[':is_active'] = (bool)$value ? 1 : 0;
            } elseif ($key === 'first_name' || $key === 'last_name') {
                $fields[] = "$key = :$key";
                $params[":$key"] = Security::sanitizeString($value, 100);
            } elseif ($key === 'phone') {
                $fields[] = "phone = :phone";
                $params[':phone'] = !empty($value) ? Security::sanitizeString($value, 20) : null;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function getAll($role = null) {
        if ($role) {
            $sql = "SELECT * FROM users WHERE role = :role ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':role' => $role]);
        } else {
            $sql = "SELECT * FROM users ORDER BY created_at DESC";
            $stmt = $this->db->query($sql);
        }
        return $stmt->fetchAll();
    }
}
