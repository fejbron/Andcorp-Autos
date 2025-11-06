<?php

class QuoteRequest {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new quote request
     */
    public function create($data) {
        $customerId = Security::sanitizeInt($data['customer_id'] ?? 0);
        if ($customerId <= 0) {
            throw new InvalidArgumentException('Invalid customer ID');
        }
        
        // Generate unique request number
        $requestNumber = 'QR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO quote_requests (
                    customer_id, request_number, status,
                    vehicle_type, make, model, year, trim, vin, lot_number, auction_link,
                    budget_min, budget_max, preferred_color, additional_requirements
                ) VALUES (
                    :customer_id, :request_number, :status,
                    :vehicle_type, :make, :model, :year, :trim, :vin, :lot_number, :auction_link,
                    :budget_min, :budget_max, :preferred_color, :additional_requirements
                )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':customer_id' => $customerId,
            ':request_number' => $requestNumber,
            ':status' => 'pending',
            ':vehicle_type' => !empty($data['vehicle_type']) ? Security::sanitizeString($data['vehicle_type'], 100) : null,
            ':make' => !empty($data['make']) ? Security::sanitizeString($data['make'], 100) : null,
            ':model' => !empty($data['model']) ? Security::sanitizeString($data['model'], 100) : null,
            ':year' => !empty($data['year']) ? Security::sanitizeInt($data['year']) : null,
            ':trim' => !empty($data['trim']) ? Security::sanitizeString($data['trim'], 100) : null,
            ':vin' => !empty($data['vin']) ? Security::sanitizeString($data['vin'], 50) : null,
            ':lot_number' => !empty($data['lot_number']) ? Security::sanitizeString($data['lot_number'], 50) : null,
            ':auction_link' => !empty($data['auction_link']) ? Security::sanitizeUrl($data['auction_link']) : null,
            ':budget_min' => !empty($data['budget_min']) ? Security::sanitizeFloat($data['budget_min'], 0) : null,
            ':budget_max' => !empty($data['budget_max']) ? Security::sanitizeFloat($data['budget_max'], 0) : null,
            ':preferred_color' => !empty($data['preferred_color']) ? Security::sanitizeString($data['preferred_color'], 50) : null,
            ':additional_requirements' => !empty($data['additional_requirements']) ? Security::sanitizeString($data['additional_requirements'], 2000) : null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update quote request
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => Security::sanitizeInt($id)];
        
        $allowedFields = ['status', 'vehicle_type', 'make', 'model', 'year', 'trim', 'vin', 'lot_number', 
                         'auction_link', 'budget_min', 'budget_max', 'preferred_color', 'additional_requirements',
                         'quoted_price', 'shipping_cost', 'duty_estimate', 'total_estimate', 'admin_notes',
                         'order_id', 'converted_by', 'converted_at'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                
                if (in_array($key, ['budget_min', 'budget_max', 'quoted_price', 'shipping_cost', 'duty_estimate', 'total_estimate'])) {
                    $params[":$key"] = Security::sanitizeFloat($value, 0);
                } elseif (in_array($key, ['year', 'order_id', 'converted_by'])) {
                    $params[":$key"] = $value ? Security::sanitizeInt($value) : null;
                } elseif ($key === 'status') {
                    $params[":$key"] = Security::sanitizeString($value, 20);
                } elseif ($key === 'converted_at') {
                    $params[":$key"] = $value; // Already a datetime string
                } else {
                    $params[":$key"] = Security::sanitizeString($value, 2000);
                }
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE quote_requests SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Add quote to request
     */
    public function addQuote($id, $quoteData, $adminId) {
        $sql = "UPDATE quote_requests SET
                    quoted_price = :quoted_price,
                    shipping_cost = :shipping_cost,
                    duty_estimate = :duty_estimate,
                    total_estimate = :total_estimate,
                    admin_notes = :admin_notes,
                    quoted_by = :quoted_by,
                    quoted_at = NOW(),
                    status = 'quoted'
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => Security::sanitizeInt($id),
            ':quoted_price' => Security::sanitizeFloat($quoteData['quoted_price'], 0),
            ':shipping_cost' => Security::sanitizeFloat($quoteData['shipping_cost'], 0),
            ':duty_estimate' => Security::sanitizeFloat($quoteData['duty_estimate'], 0),
            ':total_estimate' => Security::sanitizeFloat($quoteData['total_estimate'], 0),
            ':admin_notes' => !empty($quoteData['admin_notes']) ? Security::sanitizeString($quoteData['admin_notes'], 2000) : null,
            ':quoted_by' => Security::sanitizeInt($adminId)
        ]);
    }
    
    /**
     * Convert quote request to order
     */
    public function convertToOrder($id, $orderId, $adminId) {
        $sql = "UPDATE quote_requests SET
                    order_id = :order_id,
                    converted_by = :converted_by,
                    converted_at = NOW(),
                    status = 'converted'
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => Security::sanitizeInt($id),
            ':order_id' => Security::sanitizeInt($orderId),
            ':converted_by' => Security::sanitizeInt($adminId)
        ]);
    }
    
    /**
     * Find quote request by ID
     */
    public function findById($id) {
        $sql = "SELECT qr.*,
                       c.user_id as customer_user_id,
                       u.first_name as customer_first_name,
                       u.last_name as customer_last_name,
                       u.email as customer_email,
                       u.phone as customer_phone,
                       c.ghana_card_number,
                       admin.first_name as quoted_by_first_name,
                       admin.last_name as quoted_by_last_name
                FROM quote_requests qr
                LEFT JOIN customers c ON qr.customer_id = c.id
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN users admin ON qr.quoted_by = admin.id
                WHERE qr.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => Security::sanitizeInt($id)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all quote requests by customer
     */
    public function getByCustomer($customerId) {
        $sql = "SELECT * FROM quote_requests
                WHERE customer_id = :customer_id
                ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':customer_id' => Security::sanitizeInt($customerId)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all quote requests with filters
     */
    public function getAll($status = null, $limit = 100, $offset = 0) {
        $sql = "SELECT qr.*,
                       c.user_id as customer_user_id,
                       u.first_name as customer_first_name,
                       u.last_name as customer_last_name,
                       u.email as customer_email
                FROM quote_requests qr
                LEFT JOIN customers c ON qr.customer_id = c.id
                LEFT JOIN users u ON c.user_id = u.id";
        
        if ($status) {
            $sql .= " WHERE qr.status = :status";
        }
        
        $sql .= " ORDER BY qr.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        if ($status) {
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get pending quote requests count
     */
    public function getPendingCount() {
        $sql = "SELECT COUNT(*) as count FROM quote_requests WHERE status = 'pending'";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
    /**
     * Get status counts
     */
    public function getStatusCounts() {
        $sql = "SELECT status, COUNT(*) as count
                FROM quote_requests
                GROUP BY status";
        
        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $counts = [];
        foreach ($results as $result) {
            $counts[$result['status']] = $result['count'];
        }
        
        return $counts;
    }
    
    /**
     * Search quote requests
     */
    public function search($query) {
        $sql = "SELECT qr.*,
                       u.first_name as customer_first_name,
                       u.last_name as customer_last_name,
                       u.email as customer_email
                FROM quote_requests qr
                LEFT JOIN customers c ON qr.customer_id = c.id
                LEFT JOIN users u ON c.user_id = u.id
                WHERE qr.request_number LIKE :query
                   OR qr.vin LIKE :query
                   OR qr.lot_number LIKE :query
                   OR qr.make LIKE :query
                   OR qr.model LIKE :query
                   OR u.first_name LIKE :query
                   OR u.last_name LIKE :query
                   OR u.email LIKE :query
                ORDER BY qr.created_at DESC
                LIMIT 100";
        
        $stmt = $this->db->prepare($sql);
        $searchTerm = '%' . Security::sanitizeString($query, 255) . '%';
        $stmt->execute([':query' => $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Delete quote request
     */
    public function delete($id) {
        $sql = "DELETE FROM quote_requests WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => Security::sanitizeInt($id)]);
    }
}

