<?php
/**
 * Support Ticket Model
 * Handles all support ticket operations
 */
class SupportTicket {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new support ticket
     */
    public function create($data) {
        try {
            // Generate ticket number
            $ticketNumber = $this->generateTicketNumber();
            
            $sql = "INSERT INTO support_tickets (
                        ticket_number, customer_id, order_id, subject, 
                        category, priority, status
                    ) VALUES (?, ?, ?, ?, ?, ?, 'open')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $ticketNumber,
                $data['customer_id'],
                $data['order_id'] ?? null,
                $data['subject'],
                $data['category'],
                $data['priority'] ?? 'normal'
            ]);
            
            $ticketId = $this->db->lastInsertId();
            
            // Add initial message
            if (!empty($data['message'])) {
                $this->addReply($ticketId, $data['customer_user_id'], $data['message'], false);
            }
            
            // Log activity
            Auth::logActivity(
                $data['customer_user_id'],
                'ticket_created',
                "Created support ticket: {$ticketNumber}"
            );
            
            return $ticketId;
        } catch (PDOException $e) {
            error_log("SupportTicket::create() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add a reply to a ticket
     */
    public function addReply($ticketId, $userId, $message, $isStaffReply = false, $attachmentPath = null) {
        try {
            $sql = "INSERT INTO ticket_replies (
                        ticket_id, user_id, message, is_staff_reply, attachment_path
                    ) VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $ticketId,
                $userId,
                $message,
                $isStaffReply ? 1 : 0,
                $attachmentPath
            ]);
            
            if ($result) {
                // Update ticket status if staff replied
                if ($isStaffReply) {
                    $this->updateStatus($ticketId, 'pending');
                }
                
                // Update ticket's updated_at timestamp
                $updateSql = "UPDATE support_tickets SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([$ticketId]);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("SupportTicket::addReply() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get ticket by ID with customer and order info
     */
    public function findById($id) {
        try {
            $sql = "SELECT t.*,
                           c.user_id as customer_user_id,
                           u.first_name as customer_first_name,
                           u.last_name as customer_last_name,
                           u.email as customer_email,
                           o.order_number,
                           staff.first_name as assigned_first_name,
                           staff.last_name as assigned_last_name
                    FROM support_tickets t
                    LEFT JOIN customers c ON t.customer_id = c.id
                    LEFT JOIN users u ON c.user_id = u.id
                    LEFT JOIN orders o ON t.order_id = o.id
                    LEFT JOIN users staff ON t.assigned_to = staff.id
                    WHERE t.id = ?
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SupportTicket::findById() error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get ticket by ticket number
     */
    public function findByTicketNumber($ticketNumber) {
        try {
            $sql = "SELECT t.*,
                           c.user_id as customer_user_id,
                           u.first_name as customer_first_name,
                           u.last_name as customer_last_name,
                           u.email as customer_email,
                           o.order_number
                    FROM support_tickets t
                    LEFT JOIN customers c ON t.customer_id = c.id
                    LEFT JOIN users u ON c.user_id = u.id
                    LEFT JOIN orders o ON t.order_id = o.id
                    WHERE t.ticket_number = ?
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$ticketNumber]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SupportTicket::findByTicketNumber() error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all tickets for a customer
     */
    public function getByCustomer($customerId, $status = null, $limit = 50) {
        try {
            $sql = "SELECT t.*,
                           o.order_number,
                           (SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = t.id) as reply_count
                    FROM support_tickets t
                    LEFT JOIN orders o ON t.order_id = o.id
                    WHERE t.customer_id = ?";
            
            $params = [$customerId];
            
            if ($status) {
                $sql .= " AND t.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY t.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SupportTicket::getByCustomer() error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all tickets (admin)
     */
    public function getAll($status = null, $limit = 100, $offset = 0) {
        try {
            $sql = "SELECT t.*,
                           u.first_name as customer_first_name,
                           u.last_name as customer_last_name,
                           u.email as customer_email,
                           o.order_number,
                           staff.first_name as assigned_first_name,
                           staff.last_name as assigned_last_name,
                           (SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = t.id) as reply_count
                    FROM support_tickets t
                    LEFT JOIN customers c ON t.customer_id = c.id
                    LEFT JOIN users u ON c.user_id = u.id
                    LEFT JOIN orders o ON t.order_id = o.id
                    LEFT JOIN users staff ON t.assigned_to = staff.id";
            
            $params = [];
            
            if ($status) {
                $sql .= " WHERE t.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY 
                        CASE t.priority
                            WHEN 'urgent' THEN 1
                            WHEN 'high' THEN 2
                            WHEN 'normal' THEN 3
                            WHEN 'low' THEN 4
                        END,
                        t.created_at DESC
                      LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SupportTicket::getAll() error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get ticket replies
     */
    public function getReplies($ticketId) {
        try {
            $sql = "SELECT r.*,
                           u.first_name,
                           u.last_name,
                           u.email,
                           u.role
                    FROM ticket_replies r
                    LEFT JOIN users u ON r.user_id = u.id
                    WHERE r.ticket_id = ?
                    ORDER BY r.created_at ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$ticketId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SupportTicket::getReplies() error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update ticket status
     */
    public function updateStatus($ticketId, $status, $userId = null) {
        try {
            $sql = "UPDATE support_tickets SET status = ?";
            $params = [$status];
            
            if ($status === 'closed') {
                $sql .= ", closed_at = CURRENT_TIMESTAMP";
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $ticketId;
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result && $userId) {
                Auth::logActivity($userId, 'ticket_updated', "Updated ticket #{$ticketId} status to {$status}");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("SupportTicket::updateStatus() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Assign ticket to staff
     */
    public function assignTo($ticketId, $staffId, $assignedBy) {
        try {
            $sql = "UPDATE support_tickets SET assigned_to = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$staffId, $ticketId]);
            
            if ($result) {
                Auth::logActivity($assignedBy, 'ticket_assigned', "Assigned ticket #{$ticketId} to staff #{$staffId}");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("SupportTicket::assignTo() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Search tickets
     */
    public function search($query) {
        try {
            $sql = "SELECT t.*,
                           u.first_name as customer_first_name,
                           u.last_name as customer_last_name,
                           u.email as customer_email,
                           o.order_number
                    FROM support_tickets t
                    LEFT JOIN customers c ON t.customer_id = c.id
                    LEFT JOIN users u ON c.user_id = u.id
                    LEFT JOIN orders o ON t.order_id = o.id
                    WHERE t.ticket_number LIKE ?
                       OR t.subject LIKE ?
                       OR u.first_name LIKE ?
                       OR u.last_name LIKE ?
                       OR u.email LIKE ?
                       OR o.order_number LIKE ?
                    ORDER BY t.created_at DESC
                    LIMIT 100";
            
            $searchTerm = '%' . Security::sanitizeString($query, 255) . '%';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SupportTicket::search() error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get ticket statistics
     */
    public function getStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
                        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count,
                        SUM(CASE WHEN priority = 'urgent' AND status IN ('open', 'pending') THEN 1 ELSE 0 END) as urgent_count
                    FROM support_tickets";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SupportTicket::getStats() error: " . $e->getMessage());
            return [
                'total' => 0,
                'open_count' => 0,
                'pending_count' => 0,
                'resolved_count' => 0,
                'closed_count' => 0,
                'urgent_count' => 0
            ];
        }
    }
    
    /**
     * Delete a ticket (admin only)
     */
    public function delete($ticketId) {
        try {
            $sql = "DELETE FROM support_tickets WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$ticketId]);
        } catch (PDOException $e) {
            error_log("SupportTicket::delete() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate unique ticket number
     */
    private function generateTicketNumber() {
        $prefix = 'TKT';
        $date = date('Ymd');
        $random = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
        return "{$prefix}-{$date}-{$random}";
    }
}

