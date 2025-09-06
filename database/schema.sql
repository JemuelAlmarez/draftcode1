-- Galit Digital Printing Database Schema
-- MySQL Database Setup

CREATE DATABASE IF NOT EXISTS galit_digital_printing;
USE galit_digital_printing;

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255),
    customer_phone VARCHAR(20),
    service_category ENUM('Shirts', 'Tarpaulin', 'Print & Xerox', 'Stickers', 'Others') NOT NULL,
    service_name VARCHAR(255),
    size VARCHAR(100),
    quantity INT NOT NULL DEFAULT 1,
    due_date DATE NOT NULL,
    instructions TEXT,
    delivery_preference ENUM('pickup', 'delivery') NOT NULL DEFAULT 'pickup',
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    additional_charges DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    order_status ENUM('pending', 'confirmed', 'in_production', 'ready', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    files_count INT NOT NULL DEFAULT 0,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_service_category (service_category),
    INDEX idx_order_date (order_date),
    INDEX idx_order_status (order_status)
);

-- Sales transactions table (for clerk sales and analytics)
CREATE TABLE IF NOT EXISTS sales_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_type ENUM('clerk_sale', 'customer_order') NOT NULL,
    category VARCHAR(50) NOT NULL,
    service VARCHAR(255),
    amount DECIMAL(10,2) NOT NULL,
    customer VARCHAR(255) NOT NULL,
    description TEXT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    order_id INT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_transaction_type (transaction_type)
);

-- Insert sample data for testing
INSERT INTO orders (customer_name, customer_email, customer_phone, service_category, service_name, quantity, due_date, instructions, delivery_preference, amount, base_price, additional_charges, order_status, files_count) VALUES
('John Smith', 'john@example.com', '+63 912 345 6789', 'Tarpaulin', 'Tarpaulin', 2, '2024-04-15', 'Large banner for store opening', 'pickup', 1550.00, 800.00, 750.00, 'confirmed', 1),
('Maria Garcia', 'maria@example.com', '+63 917 123 4567', 'Shirts', 'Shirts', 10, '2024-04-20', 'Company uniform shirts', 'delivery', 5000.00, 5000.00, 200.00, 'in_production', 2),
('ABC Company', 'orders@abc.com', '+63 918 765 4321', 'Others', 'Acrylic Plates', 5, '2024-04-18', 'Office name plates', 'pickup', 1500.00, 1500.00, 0.00, 'ready', 1),
('XYZ Store', 'info@xyz.com', '+63 919 876 5432', 'Stickers', 'Stickers', 100, '2024-04-22', 'Product labels', 'pickup', 2000.00, 2000.00, 0.00, 'pending', 0),
('Event Organizer', 'events@org.com', '+63 920 123 4567', 'Print & Xerox', 'Print & Xerox', 50, '2024-04-25', 'Event flyers', 'delivery', 2500.00, 2500.00, 200.00, 'confirmed', 1);

-- Insert corresponding sales transactions
INSERT INTO sales_transactions (transaction_type, category, service, amount, customer, description, order_id) VALUES
('customer_order', 'tarpaulin', 'Tarpaulin', 1550.00, 'John Smith', 'Order #1: Tarpaulin - Large banner for store opening', 1),
('customer_order', 'shirts', 'Shirts', 5000.00, 'Maria Garcia', 'Order #2: Shirts - Company uniform shirts', 2),
('customer_order', 'others', 'Acrylic Plates', 1500.00, 'ABC Company', 'Order #3: Others - Office name plates', 3),
('customer_order', 'stickers', 'Stickers', 2000.00, 'XYZ Store', 'Order #4: Stickers - Product labels', 4),
('customer_order', 'printXerox', 'Print & Xerox', 2500.00, 'Event Organizer', 'Order #5: Print & Xerox - Event flyers', 5);
