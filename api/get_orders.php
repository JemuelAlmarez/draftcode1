<?php
/**
 * Orders API
 * Fetches orders and analytics data for admin dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

class OrdersAPI {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Get all orders with optional filters
     */
    public function getOrders($filters = array()) {
        try {
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
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll();
            
            return array(
                'success' => true,
                'data' => $orders,
                'count' => count($orders)
            );
            
        } catch(PDOException $e) {
            return array(
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get sales analytics by category
     */
    public function getSalesAnalytics($period = 'month') {
        try {
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
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $analytics = $stmt->fetchAll();
            
            // Format data for frontend
            $formattedData = array(
                'tarpaulin' => 0,
                'stickers' => 0,
                'shirts' => 0,
                'printxerox' => 0,
                'others' => 0
            );
            
            foreach ($analytics as $item) {
                $category = $item['category'];
                if (isset($formattedData[$category])) {
                    $formattedData[$category] = floatval($item['total_amount']);
                }
            }
            
            return array(
                'success' => true,
                'data' => $formattedData,
                'raw_data' => $analytics
            );
            
        } catch(PDOException $e) {
            return array(
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get others category breakdown
     */
    public function getOthersBreakdown() {
        try {
            $sql = "SELECT 
                        service,
                        SUM(amount) as total_amount,
                        COUNT(*) as order_count
                    FROM sales_transactions 
                    WHERE category = 'others'
                    GROUP BY service
                    ORDER BY total_amount DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $breakdown = $stmt->fetchAll();
            
            return array(
                'success' => true,
                'data' => $breakdown
            );
            
        } catch(PDOException $e) {
            return array(
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get order statistics
     */
    public function getOrderStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(amount) as total_revenue,
                        AVG(amount) as average_order_value,
                        COUNT(CASE WHEN order_status = 'pending' THEN 1 END) as pending_orders,
                        COUNT(CASE WHEN order_status = 'confirmed' THEN 1 END) as confirmed_orders,
                        COUNT(CASE WHEN order_status = 'in_production' THEN 1 END) as production_orders,
                        COUNT(CASE WHEN order_status = 'ready' THEN 1 END) as ready_orders,
                        COUNT(CASE WHEN order_status = 'delivered' THEN 1 END) as delivered_orders
                    FROM orders";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stats = $stmt->fetch();
            
            return array(
                'success' => true,
                'data' => $stats
            );
            
        } catch(PDOException $e) {
            return array(
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            );
        }
    }
}

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'orders';
    $filters = array();
    
    // Parse filters from query parameters
    if (isset($_GET['service_category'])) {
        $filters['service_category'] = $_GET['service_category'];
    }
    if (isset($_GET['order_status'])) {
        $filters['order_status'] = $_GET['order_status'];
    }
    if (isset($_GET['date_from'])) {
        $filters['date_from'] = $_GET['date_from'];
    }
    if (isset($_GET['date_to'])) {
        $filters['date_to'] = $_GET['date_to'];
    }
    if (isset($_GET['limit'])) {
        $filters['limit'] = $_GET['limit'];
    }
    
    $ordersAPI = new OrdersAPI();
    
    switch($action) {
        case 'orders':
            $result = $ordersAPI->getOrders($filters);
            break;
        case 'analytics':
            $period = $_GET['period'] ?? 'month';
            $result = $ordersAPI->getSalesAnalytics($period);
            break;
        case 'others_breakdown':
            $result = $ordersAPI->getOthersBreakdown();
            break;
        case 'stats':
            $result = $ordersAPI->getOrderStats();
            break;
        default:
            $result = array(
                'success' => false,
                'message' => 'Invalid action'
            );
    }
    
    echo json_encode($result);
} else {
    echo json_encode(array(
        'success' => false,
        'message' => 'Only GET requests are allowed'
    ));
}
?>
