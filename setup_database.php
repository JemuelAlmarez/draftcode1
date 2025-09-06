<?php
/**
 * Database Setup Script
 * Run this script to set up the database for Galit Digital Printing System
 */

require_once 'config/database.php';

echo "<h2>Galit Digital Printing - Database Setup</h2>";

try {
    // Test database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "<p style='color: green;'>✓ Database connection successful!</p>";
        
        // Read and execute schema file
        $schemaFile = 'database/schema.sql';
        if (file_exists($schemaFile)) {
            $schema = file_get_contents($schemaFile);
            
            // Split SQL statements
            $statements = array_filter(array_map('trim', explode(';', $schema)));
            
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    try {
                        $conn->exec($statement);
                        $successCount++;
                    } catch (PDOException $e) {
                        $errorCount++;
                        echo "<p style='color: orange;'>⚠ Statement failed: " . substr($statement, 0, 50) . "...</p>";
                        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
                    }
                }
            }
            
            echo "<p style='color: green;'>✓ Database setup completed!</p>";
            echo "<p>Successful statements: $successCount</p>";
            echo "<p>Failed statements: $errorCount</p>";
            
            // Test data insertion
            echo "<h3>Testing Data Insertion</h3>";
            
            // Test order insertion
            $testOrder = array(
                'customer_name' => 'Test Customer',
                'customer_email' => 'test@example.com',
                'customer_phone' => '+63 912 345 6789',
                'service_category' => 'Shirts',
                'service_name' => 'Shirts',
                'quantity' => 5,
                'due_date' => '2024-04-15',
                'instructions' => 'Test order for database setup',
                'delivery_preference' => 'pickup',
                'amount' => 2500.00,
                'base_price' => 2500.00,
                'additional_charges' => 0.00,
                'files_count' => 1
            );
            
            $sql = "INSERT INTO orders (
                customer_name, customer_email, customer_phone, 
                service_category, service_name, quantity, 
                due_date, instructions, delivery_preference, 
                amount, base_price, additional_charges, 
                order_status, files_count
            ) VALUES (
                :customer_name, :customer_email, :customer_phone,
                :service_category, :service_name, :quantity,
                :due_date, :instructions, :delivery_preference,
                :amount, :base_price, :additional_charges,
                'pending', :files_count
            )";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute($testOrder);
            
            if ($result) {
                $orderId = $conn->lastInsertId();
                echo "<p style='color: green;'>✓ Test order inserted successfully (ID: $orderId)</p>";
                
                // Test sales transaction insertion
                $salesSql = "INSERT INTO sales_transactions (
                    transaction_type, category, service, amount, 
                    customer, description, order_id
                ) VALUES (
                    'customer_order', 'shirts', 'Shirts', :amount,
                    :customer, :description, :order_id
                )";
                
                $salesStmt = $conn->prepare($salesSql);
                $salesResult = $salesStmt->execute(array(
                    ':amount' => 2500.00,
                    ':customer' => 'Test Customer',
                    ':description' => "Order $orderId: Shirts - Test order for database setup",
                    ':order_id' => $orderId
                ));
                
                if ($salesResult) {
                    echo "<p style='color: green;'>✓ Test sales transaction inserted successfully</p>";
                } else {
                    echo "<p style='color: red;'>✗ Failed to insert test sales transaction</p>";
                }
            } else {
                echo "<p style='color: red;'>✗ Failed to insert test order</p>";
            }
            
            // Test data retrieval
            echo "<h3>Testing Data Retrieval</h3>";
            
            $testSql = "SELECT COUNT(*) as order_count FROM orders";
            $testStmt = $conn->prepare($testSql);
            $testStmt->execute();
            $result = $testStmt->fetch();
            
            echo "<p style='color: green;'>✓ Total orders in database: " . $result['order_count'] . "</p>";
            
            $salesTestSql = "SELECT COUNT(*) as transaction_count FROM sales_transactions";
            $salesTestStmt = $conn->prepare($salesTestSql);
            $salesTestStmt->execute();
            $salesResult = $salesTestStmt->fetch();
            
            echo "<p style='color: green;'>✓ Total sales transactions: " . $salesResult['transaction_count'] . "</p>";
            
            echo "<h3>Database Setup Complete!</h3>";
            echo "<p>Your database is now ready for the Galit Digital Printing System.</p>";
            echo "<p><strong>Next steps:</strong></p>";
            echo "<ul>";
            echo "<li>Test the customer order form: <a href='customer/place_order.html'>Place Order</a></li>";
            echo "<li>View orders in admin: <a href='admin/orders_database.html'>Orders Database</a></li>";
            echo "<li>Check analytics: <a href='admin/dashboard.html'>Admin Dashboard</a></li>";
            echo "</ul>";
            
        } else {
            echo "<p style='color: red;'>✗ Schema file not found: $schemaFile</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Database connection failed!</p>";
        echo "<p>Please check your database configuration in config/database.php</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f5f5f5;
}
h2, h3 {
    color: #333;
}
p {
    margin: 10px 0;
}
ul {
    background-color: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
a {
    color: #007bff;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>
