<?php

class Deposit {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new deposit record
     */
    public function create($data) {
        $sql = "INSERT INTO deposits (
                    order_id, customer_id, amount, currency, payment_method,
                    bank_name, account_number, reference_number,
                    transaction_date, transaction_time, deposit_slip,
                    status, notes, created_by
                ) VALUES (
                    :order_id, :customer_id, :amount, :currency, :payment_method,
                    :bank_name, :account_number, :reference_number,
                    :transaction_date, :transaction_time, :deposit_slip,
                    :status, :notes, :created_by
                )";
        
        $params = [
            ':order_id' => Security::sanitizeInt($data['order_id']),
            ':customer_id' => Security::sanitizeInt($data['customer_id']),
            ':amount' => Security::sanitizeFloat($data['amount'], 0),
            ':currency' => Security::sanitizeString($data['currency'] ?? 'GHS', 3),
            ':payment_method' => $data['payment_method'] ?? 'bank_transfer',
            ':bank_name' => !empty($data['bank_name']) ? Security::sanitizeString($data['bank_name'], 100) : null,
            ':account_number' => !empty($data['account_number']) ? Security::sanitizeString($data['account_number'], 50) : null,
            ':reference_number' => !empty($data['reference_number']) ? Security::sanitizeString($data['reference_number'], 100) : null,
            ':transaction_date' => $data['transaction_date'],
            ':transaction_time' => $data['transaction_time'],
            ':deposit_slip' => $data['deposit_slip'] ?? null,
            ':status' => $data['status'] ?? 'pending',
            ':notes' => !empty($data['notes']) ? Security::sanitizeString($data['notes'], 1000) : null,
            ':created_by' => Security::sanitizeInt($data['created_by'])
        ];
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $depositId = $this->db->lastInsertId();
        
        // Update order's total_deposits
        $this->updateOrderTotalDeposits($data['order_id']);
        
        return $depositId;
    }
    
    /**
     * Update a deposit record
     */
    public function update($id, $data) {
        $sql = "UPDATE deposits SET
                    amount = :amount,
                    currency = :currency,
                    payment_method = :payment_method,
                    bank_name = :bank_name,
                    account_number = :account_number,
                    reference_number = :reference_number,
                    transaction_date = :transaction_date,
                    transaction_time = :transaction_time,
                    status = :status,
                    notes = :notes
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':id' => Security::sanitizeInt($id),
            ':amount' => Security::sanitizeFloat($data['amount'], 0),
            ':currency' => Security::sanitizeString($data['currency'] ?? 'GHS', 3),
            ':payment_method' => $data['payment_method'] ?? 'bank_transfer',
            ':bank_name' => !empty($data['bank_name']) ? Security::sanitizeString($data['bank_name'], 100) : null,
            ':account_number' => !empty($data['account_number']) ? Security::sanitizeString($data['account_number'], 50) : null,
            ':reference_number' => !empty($data['reference_number']) ? Security::sanitizeString($data['reference_number'], 100) : null,
            ':transaction_date' => $data['transaction_date'],
            ':transaction_time' => $data['transaction_time'],
            ':status' => $data['status'] ?? 'pending',
            ':notes' => !empty($data['notes']) ? Security::sanitizeString($data['notes'], 1000) : null
        ]);
        
        // Update order's total_deposits
        $deposit = $this->findById($id);
        if ($deposit) {
            $this->updateOrderTotalDeposits($deposit['order_id']);
        }
        
        return $result;
    }
    
    /**
     * Verify a deposit
     */
    public function verify($id, $verifiedBy) {
        $sql = "UPDATE deposits SET
                    status = 'verified',
                    verified_by = :verified_by,
                    verified_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':id' => Security::sanitizeInt($id),
            ':verified_by' => Security::sanitizeInt($verifiedBy)
        ]);
        
        // Update order's total_deposits
        $deposit = $this->findById($id);
        if ($deposit) {
            $this->updateOrderTotalDeposits($deposit['order_id']);
        }
        
        return $result;
    }
    
    /**
     * Reject a deposit
     */
    public function reject($id, $notes = null) {
        $sql = "UPDATE deposits SET
                    status = 'rejected',
                    notes = :notes
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':id' => Security::sanitizeInt($id),
            ':notes' => $notes ? Security::sanitizeString($notes, 1000) : null
        ]);
        
        // Update order's total_deposits
        $deposit = $this->findById($id);
        if ($deposit) {
            $this->updateOrderTotalDeposits($deposit['order_id']);
        }
        
        return $result;
    }
    
    /**
     * Delete a deposit
     */
    public function delete($id) {
        $deposit = $this->findById($id);
        
        $sql = "DELETE FROM deposits WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([':id' => Security::sanitizeInt($id)]);
        
        // Update order's total_deposits
        if ($deposit) {
            $this->updateOrderTotalDeposits($deposit['order_id']);
        }
        
        return $result;
    }
    
    /**
     * Find deposit by ID
     */
    public function findById($id) {
        $sql = "SELECT d.*, 
                       o.order_number,
                       c.user_id as customer_user_id,
                       u.first_name as customer_first_name,
                       u.last_name as customer_last_name,
                       u.email as customer_email,
                       v.first_name as verifier_first_name,
                       v.last_name as verifier_last_name,
                       creator.first_name as creator_first_name,
                       creator.last_name as creator_last_name
                FROM deposits d
                LEFT JOIN orders o ON d.order_id = o.id
                LEFT JOIN customers c ON d.customer_id = c.id
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN users v ON d.verified_by = v.id
                LEFT JOIN users creator ON d.created_by = creator.id
                WHERE d.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => Security::sanitizeInt($id)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all deposits for an order
     */
    public function getByOrder($orderId) {
        $sql = "SELECT d.*,
                       u.first_name as customer_first_name,
                       u.last_name as customer_last_name,
                       v.first_name as verifier_first_name,
                       v.last_name as verifier_last_name
                FROM deposits d
                LEFT JOIN customers c ON d.customer_id = c.id
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN users v ON d.verified_by = v.id
                WHERE d.order_id = :order_id
                ORDER BY d.transaction_date DESC, d.transaction_time DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':order_id' => Security::sanitizeInt($orderId)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all deposits for a customer
     */
    public function getByCustomer($customerId) {
        $sql = "SELECT d.*, o.order_number
                FROM deposits d
                LEFT JOIN orders o ON d.order_id = o.id
                WHERE d.customer_id = :customer_id
                ORDER BY d.transaction_date DESC, d.transaction_time DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':customer_id' => Security::sanitizeInt($customerId)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all deposits with filters
     */
    public function getAll($status = null, $limit = 100, $offset = 0) {
        $sql = "SELECT d.*,
                       o.order_number,
                       u.first_name as customer_first_name,
                       u.last_name as customer_last_name,
                       u.email as customer_email
                FROM deposits d
                LEFT JOIN orders o ON d.order_id = o.id
                LEFT JOIN customers c ON d.customer_id = c.id
                LEFT JOIN users u ON c.user_id = u.id";
        
        if ($status) {
            $sql .= " WHERE d.status = :status";
        }
        
        $sql .= " ORDER BY d.transaction_date DESC, d.transaction_time DESC
                  LIMIT :limit OFFSET :offset";
        
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
     * Get pending deposits count
     */
    public function getPendingCount() {
        $sql = "SELECT COUNT(*) as count FROM deposits WHERE status = 'pending'";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
    /**
     * Get deposit statistics
     */
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total_deposits,
                    COALESCE(SUM(CASE WHEN status = 'verified' THEN amount ELSE 0 END), 0) as total_verified,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as total_pending,
                    COALESCE(SUM(CASE WHEN status = 'rejected' THEN amount ELSE 0 END), 0) as total_rejected,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN status = 'verified' THEN 1 END) as verified_count,
                    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count
                FROM deposits";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update order's total deposits (only verified deposits)
     */
    private function updateOrderTotalDeposits($orderId) {
        $orderId = Security::sanitizeInt($orderId);
        
        $sql = "UPDATE orders o SET
                    total_deposits = (
                        SELECT COALESCE(SUM(amount), 0)
                        FROM deposits
                        WHERE order_id = ? AND status = 'verified'
                    ),
                    balance_due = total_cost - (
                        SELECT COALESCE(SUM(amount), 0)
                        FROM deposits
                        WHERE order_id = ? AND status = 'verified'
                    )
                WHERE o.id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$orderId, $orderId, $orderId]);
    }
    
    /**
     * Search deposits
     */
    public function search($query) {
        $sql = "SELECT d.*,
                       o.order_number,
                       u.first_name as customer_first_name,
                       u.last_name as customer_last_name,
                       u.email as customer_email
                FROM deposits d
                LEFT JOIN orders o ON d.order_id = o.id
                LEFT JOIN customers c ON d.customer_id = c.id
                LEFT JOIN users u ON c.user_id = u.id
                WHERE o.order_number LIKE :query
                   OR d.reference_number LIKE :query
                   OR d.bank_name LIKE :query
                   OR u.first_name LIKE :query
                   OR u.last_name LIKE :query
                   OR u.email LIKE :query
                ORDER BY d.transaction_date DESC, d.transaction_time DESC
                LIMIT 100";
        
        $stmt = $this->db->prepare($sql);
        $searchTerm = '%' . Security::sanitizeString($query, 255) . '%';
        $stmt->execute([':query' => $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

