<?php

class Vehicle {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        // Validate required fields
        $orderId = Security::sanitizeInt($data['order_id'] ?? 0);
        if ($orderId <= 0) {
            throw new InvalidArgumentException('Invalid order ID');
        }
        
        $auctionSource = Security::validateEnum($data['auction_source'] ?? '', ['copart', 'iaa']) 
            ? $data['auction_source'] 
            : 'copart';
        
        $make = Security::sanitizeString($data['make'] ?? '', 100);
        $model = Security::sanitizeString($data['model'] ?? '', 100);
        $year = Security::sanitizeInt($data['year'] ?? 0, 1990, date('Y') + 1);
        
        if (empty($make) || empty($model) || $year <= 0) {
            throw new InvalidArgumentException('Make, model, and year are required');
        }
        
        $sql = "INSERT INTO vehicles (order_id, auction_source, listing_url, lot_number, vin, make, model, year, color, mileage, engine_type, transmission, condition_description, purchase_price, purchase_date) 
                VALUES (:order_id, :auction_source, :listing_url, :lot_number, :vin, :make, :model, :year, :color, :mileage, :engine_type, :transmission, :condition_description, :purchase_price, :purchase_date)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':order_id' => $orderId,
            ':auction_source' => $auctionSource,
            ':listing_url' => !empty($data['listing_url']) ? Security::sanitizeUrl($data['listing_url']) : null,
            ':lot_number' => !empty($data['lot_number']) ? Security::sanitizeString($data['lot_number'], 100) : null,
            ':vin' => !empty($data['vin']) ? Security::sanitizeString(strtoupper($data['vin']), 17) : null,
            ':make' => $make,
            ':model' => $model,
            ':year' => $year,
            ':color' => !empty($data['color']) ? Security::sanitizeString($data['color'], 50) : null,
            ':mileage' => !empty($data['mileage']) ? Security::sanitizeInt($data['mileage'], 0) : null,
            ':engine_type' => !empty($data['engine_type']) ? Security::sanitizeString($data['engine_type'], 100) : null,
            ':transmission' => !empty($data['transmission']) && Security::validateEnum($data['transmission'], ['Automatic', 'Manual', 'CVT']) ? $data['transmission'] : null,
            ':condition_description' => !empty($data['condition_description']) ? Security::sanitizeString($data['condition_description'], 2000) : null,
            ':purchase_price' => !empty($data['purchase_price']) ? Security::sanitizeFloat($data['purchase_price'], 0) : null,
            ':purchase_date' => !empty($data['purchase_date']) ? $data['purchase_date'] : null
        ]);
    }
    
    public function findByOrderId($orderId) {
        $orderId = Security::sanitizeInt($orderId);
        if ($orderId <= 0) {
            return null;
        }
        
        $sql = "SELECT * FROM vehicles WHERE order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetch();
    }
    
    public function update($orderId, $data) {
        // Whitelist allowed fields
        $allowedFields = [
            'auction_source', 'listing_url', 'lot_number', 'vin', 'make', 'model', 
            'year', 'color', 'mileage', 'engine_type', 'transmission', 
            'condition_description', 'purchase_price', 'purchase_date'
        ];
        
        $fields = [];
        $params = [':order_id' => Security::sanitizeInt($orderId)];
        
        foreach ($data as $key => $value) {
            if (!in_array($key, $allowedFields, true)) {
                continue; // Skip unauthorized fields
            }
            
            if ($key === 'auction_source') {
                $fields[] = "auction_source = :auction_source";
                $params[':auction_source'] = Security::validateEnum($value, ['copart', 'iaa']) ? $value : 'copart';
            } elseif ($key === 'listing_url') {
                $fields[] = "listing_url = :listing_url";
                $params[':listing_url'] = !empty($value) ? Security::sanitizeUrl($value) : null;
            } elseif ($key === 'lot_number' || $key === 'vin' || $key === 'color') {
                $fields[] = "$key = :$key";
                $params[":$key"] = !empty($value) ? Security::sanitizeString($value, $key === 'vin' ? 17 : ($key === 'color' ? 50 : 100)) : null;
            } elseif ($key === 'make' || $key === 'model' || $key === 'engine_type') {
                $fields[] = "$key = :$key";
                $params[":$key"] = Security::sanitizeString($value, 100);
            } elseif ($key === 'year') {
                $fields[] = "year = :year";
                $params[':year'] = Security::sanitizeInt($value, 1990, date('Y') + 1);
            } elseif ($key === 'mileage') {
                $fields[] = "mileage = :mileage";
                $params[':mileage'] = !empty($value) ? Security::sanitizeInt($value, 0) : null;
            } elseif ($key === 'transmission') {
                if (Security::validateEnum($value, ['Automatic', 'Manual', 'CVT'])) {
                    $fields[] = "transmission = :transmission";
                    $params[':transmission'] = $value;
                }
            } elseif ($key === 'condition_description') {
                $fields[] = "condition_description = :condition_description";
                $params[':condition_description'] = !empty($value) ? Security::sanitizeString($value, 2000) : null;
            } elseif ($key === 'purchase_price') {
                $fields[] = "purchase_price = :purchase_price";
                $params[':purchase_price'] = !empty($value) ? Security::sanitizeFloat($value, 0) : null;
            } elseif ($key === 'purchase_date') {
                $fields[] = "purchase_date = :purchase_date";
                $params[':purchase_date'] = !empty($value) ? $value : null;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE vehicles SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
