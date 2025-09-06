<?php
/**
 * Order Submission API
 * Handles customer order form submissions
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

class OrderSubmission {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Validate order data
     */
    private function validateOrderData($data) {
        $errors = array();
        
        // Required fields
        if (empty($data['customer_name'])) {
            $errors[] = 'Customer name is required';
        }
        
        if (empty($data['service_category'])) {
            $errors[] = 'Service category is required';
        }
        
        if (empty($data['quantity']) || $data['quantity'] < 1) {
            $errors[] = 'Valid quantity is required';
        }
        
        if (empty($data['due_date'])) {
            $errors[] = 'Due date is required';
        }
        
        // Validate service category
        $validCategories = array('Shirts', 'Tarpaulin', 'Print & Xerox', 'Stickers', 'Others');
        if (!in_array($data['service_category'], $validCategories)) {
            $errors[] = 'Invalid service category';
        }
        
        // Tarpaulin specific validation
        if ($data['service_category'] === 'Tarpaulin') {
            if (empty($data['size'])) {
                $errors[] = 'Size is required for Tarpaulin orders';
            }
        }
        
        // Others specific validation
        if ($data['service_category'] === 'Others') {
            if (empty($data['service_name'])) {
                $errors[] = 'Specific service is required for Others category';
            }
        }
        
        return $errors;
    }
    
    /**
     * Calculate order pricing
     */
    private function calculatePricing($data) {
        $basePrice = 0;
        $additionalCharges = 0;
        
        // Base pricing
        $pricing = array(
            'Shirts' => 500,
            'Tarpaulin' => 800,
            'Print & Xerox' => 50,
            'Stickers' => 200,
            'Others' => 300
        );
        
        $basePrice = $pricing[$data['service_category']] ?? 0;
        
        // Tarpaulin size-based pricing
        if ($data['service_category'] === 'Tarpaulin' && !empty($data['size'])) {
            // Extract dimensions from size string (e.g., "3ft x 5ft")
            if (preg_match('/(\d+(?:\.\d+)?)\s*ft\s*x\s*(\d+(?:\.\d+)?)\s*ft/', $data['size'], $matches)) {
                $width = floatval($matches[1]);
                $height = floatval($matches[2]);
                $area = $width * $height;
                $additionalCharges = round($area * 50); // ₱50 per sq ft
            }
        }
        
        // Delivery charges
        if ($data['delivery_preference'] === 'delivery') {
            $additionalCharges += 200; // ₱200 delivery fee
        }
        
        $total = $basePrice + $additionalCharges;
        
        return array(
            'base_price' => $basePrice,
            'additional_charges' => $additionalCharges,
            'total' => $total
        );
    }
    
    /**
     * Submit order to database
     */
    public function submitOrder($data) {
        try {
            // Validate data
            $errors = $this->validateOrderData($data);
            if (!empty($errors)) {
                return array(
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                );
            }
            
            // Calculate pricing
            $pricing = $this->calculatePricing($data);
            
            // Generate order ID
            $orderId = 'ORD-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Insert into orders table
            $sql = "INSERT INTO orders (
                customer_name, customer_email, customer_phone, 
                service_category, service_name, size, quantity, 
                due_date, instructions, delivery_preference, 
                amount, base_price, additional_charges, 
                order_status, files_count
            ) VALUES (
                :customer_name, :customer_email, :customer_phone,
                :service_category, :service_name, :size, :quantity,
                :due_date, :instructions, :delivery_preference,
                :amount, :base_price, :additional_charges,
                'pending', :files_count
            )";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute(array(
                ':customer_name' => $data['customer_name'],
                ':customer_email' => $data['customer_email'] ?? '',
                ':customer_phone' => $data['customer_phone'] ?? '',
                ':service_category' => $data['service_category'],
                ':service_name' => $data['service_name'] ?? $data['service_category'],
                ':size' => $data['size'] ?? '',
                ':quantity' => intval($data['quantity']),
                ':due_date' => $data['due_date'],
                ':instructions' => $data['instructions'] ?? '',
                ':delivery_preference' => $data['delivery_preference'] ?? 'pickup',
                ':amount' => $pricing['total'],
                ':base_price' => $pricing['base_price'],
                ':additional_charges' => $pricing['additional_charges'],
                ':files_count' => intval($data['files_count'] ?? 0)
            ));
            
            if ($result) {
                $orderDbId = $this->conn->lastInsertId();
                
                // Insert into sales_transactions table
                $salesSql = "INSERT INTO sales_transactions (
                    transaction_type, category, service, amount, 
                    customer, description, order_id
                ) VALUES (
                    'customer_order', :category, :service, :amount,
                    :customer, :description, :order_id
                )";
                
                $salesStmt = $this->conn->prepare($salesSql);
                $salesStmt->execute(array(
                    ':category' => strtolower(str_replace(' & ', '', $data['service_category'])),
                    ':service' => $data['service_name'] ?? $data['service_category'],
                    ':amount' => $pricing['total'],
                    ':customer' => $data['customer_name'],
                    ':description' => "Order $orderId: {$data['service_category']}" . 
                                    ($data['service_name'] && $data['service_name'] !== $data['service_category'] ? " - {$data['service_name']}" : ''),
                    ':order_id' => $orderDbId
                ));
                
                return array(
                    'success' => true,
                    'message' => 'Order submitted successfully',
                    'order_id' => $orderId,
                    'order_db_id' => $orderDbId,
                    'amount' => $pricing['total'],
                    'data' => $data
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Failed to insert order into database'
                );
            }
            
        } catch(PDOException $e) {
            return array(
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            );
        }
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input === null) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Invalid JSON data'
        ));
        exit;
    }
    
    $orderSubmission = new OrderSubmission();
    $result = $orderSubmission->submitOrder($input);
    
    echo json_encode($result);
} else {
    echo json_encode(array(
        'success' => false,
        'message' => 'Only POST requests are allowed'
    ));
}
?>
