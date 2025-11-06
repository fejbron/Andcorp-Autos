<?php

class InspectionReport {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        $sql = "INSERT INTO inspection_reports (order_id, inspector_name, inspection_date, overall_condition, exterior_condition, interior_condition, engine_condition, transmission_condition, electrical_system, mechanical_issues, cosmetic_issues, recommendations, estimated_repair_cost) 
                VALUES (:order_id, :inspector_name, :inspection_date, :overall_condition, :exterior_condition, :interior_condition, :engine_condition, :transmission_condition, :electrical_system, :mechanical_issues, :cosmetic_issues, :recommendations, :estimated_repair_cost)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':order_id' => $data['order_id'],
            ':inspector_name' => $data['inspector_name'] ?? null,
            ':inspection_date' => $data['inspection_date'],
            ':overall_condition' => $data['overall_condition'],
            ':exterior_condition' => $data['exterior_condition'] ?? null,
            ':interior_condition' => $data['interior_condition'] ?? null,
            ':engine_condition' => $data['engine_condition'] ?? null,
            ':transmission_condition' => $data['transmission_condition'] ?? null,
            ':electrical_system' => $data['electrical_system'] ?? null,
            ':mechanical_issues' => $data['mechanical_issues'] ?? null,
            ':cosmetic_issues' => $data['cosmetic_issues'] ?? null,
            ':recommendations' => $data['recommendations'] ?? null,
            ':estimated_repair_cost' => $data['estimated_repair_cost'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function findByOrderId($orderId) {
        $orderId = Security::sanitizeInt($orderId);
        if ($orderId <= 0) {
            return [];
        }
        
        $sql = "SELECT * FROM inspection_reports WHERE order_id = :order_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll();
    }
    
    public function findById($id) {
        $id = Security::sanitizeInt($id);
        if ($id <= 0) {
            return null;
        }
        
        $sql = "SELECT * FROM inspection_reports WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public function addPhoto($reportId, $photoPath, $category, $caption = null) {
        $sql = "INSERT INTO inspection_photos (inspection_report_id, photo_path, photo_category, caption) 
                VALUES (:report_id, :photo_path, :category, :caption)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':report_id' => $reportId,
            ':photo_path' => $photoPath,
            ':category' => $category,
            ':caption' => $caption
        ]);
    }
    
    public function getPhotos($reportId) {
        $sql = "SELECT * FROM inspection_photos WHERE inspection_report_id = :report_id ORDER BY created_at";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':report_id' => $reportId]);
        return $stmt->fetchAll();
    }
    
    public function approve($id, $userId) {
        $sql = "UPDATE inspection_reports SET approved = 1, approved_by = :user_id, approved_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id, ':user_id' => $userId]);
    }
}
