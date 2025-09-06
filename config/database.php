<?php
/**
 * Database Configuration
 * Galit Digital Printing System
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'galit_digital_printing';
    private $username = 'root';
    private $password = '';
    private $conn;

    /**
     * Get database connection
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }

    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                return array(
                    'status' => 'success',
                    'message' => 'Database connection successful'
                );
            }
        } catch(Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            );
        }
    }
}

/**
 * Database utility functions
 */
class DatabaseUtils {
    
    /**
     * Get orders with optional filters
     */
    public static function getOrders($filters = array()) {
        $db = new Database();
        $conn = $db->getConnection();
        
        $sql = "SELECT * FROM orders WHERE 1=1";
        $params = array();
        
        if (isset($filters['service_category']) && !empty($filters['service_category'])) {
            $sql .= " AND service_category = :service_category";
            $params['service_category'] = $filters['service_category'];
        }
        
        if (isset($filters['order_status']) && !empty($filters['order_status'])) {
            $sql .= " AND order_status = :order_status";
            $params['order_status'] = $filters['order_status'];
        }
        
        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $sql .= " AND order_date >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $sql .= " AND order_date <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY order_date DESC";
        
        if (isset($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }
        
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            return array('error' => $e->getMessage());
        }
    }
    
    /**
     * Get sales analytics data
     */
    public static function getSalesAnalytics($period = 'month') {
        $db = new Database();
        $conn = $db->getConnection();
        
        $dateCondition = "";
        switch($period) {
            case 'day':
                $dateCondition = "DATE(transaction_date) = CURDATE()";
                break;
            case 'week':
                $dateCondition = "transaction_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateCondition = "MONTH(transaction_date) = MONTH(NOW()) AND YEAR(transaction_date) = YEAR(NOW())";
                break;
            case 'year':
                $dateCondition = "YEAR(transaction_date) = YEAR(NOW())";
                break;
            default:
                $dateCondition = "1=1";
        }
        
        $sql = "SELECT 
                    category,
                    SUM(amount) as total_amount,
                    COUNT(*) as transaction_count
                FROM sales_transactions 
                WHERE $dateCondition
                GROUP BY category
                ORDER BY total_amount DESC";
        
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            return array('error' => $e->getMessage());
        }
    }
    
    /**
     * Get others category breakdown
     */
    public static function getOthersBreakdown() {
        $db = new Database();
        $conn = $db->getConnection();
        
        $sql = "SELECT 
                    service,
                    SUM(amount) as total_amount,
                    COUNT(*) as order_count
                FROM sales_transactions 
                WHERE category = 'others'
                GROUP BY service
                ORDER BY total_amount DESC";
        
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            return array('error' => $e->getMessage());
        }
    }
}
?>
