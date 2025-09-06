# Database Integration Setup Guide

## Overview
This guide will help you set up the MySQL database integration for the Galit Digital Printing System.

## Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

## Database Schema

### Orders Table
The main `orders` table stores customer order information:

```sql
CREATE TABLE orders (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Sales Transactions Table
The `sales_transactions` table tracks all sales for analytics:

```sql
CREATE TABLE sales_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_type ENUM('clerk_sale', 'customer_order') NOT NULL,
    category VARCHAR(50) NOT NULL,
    service VARCHAR(255),
    amount DECIMAL(10,2) NOT NULL,
    customer VARCHAR(255) NOT NULL,
    description TEXT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    order_id INT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);
```

## Setup Instructions

### 1. Database Configuration
Edit `config/database.php` and update the connection parameters:

```php
private $host = 'localhost';
private $db_name = 'galit_digital_printing';
private $username = 'your_username';
private $password = 'your_password';
```

### 2. Run Database Setup
1. Open your web browser
2. Navigate to `http://your-domain/setup_database.php`
3. Follow the on-screen instructions
4. The script will create the database, tables, and insert sample data

### 3. Test the Integration
1. **Customer Order Form**: Visit `customer/place_order.html` and submit a test order
2. **Admin Orders View**: Check `admin/orders_database.html` to see orders from the database
3. **Analytics Dashboard**: View `admin/dashboard.html` for database-powered analytics

## API Endpoints

### Submit Order
- **URL**: `api/submit_order.php`
- **Method**: POST
- **Content-Type**: application/json

**Request Body**:
```json
{
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "customer_phone": "+63 912 345 6789",
    "service_category": "Tarpaulin",
    "service_name": "Tarpaulin",
    "size": "3ft x 5ft banner",
    "quantity": 2,
    "due_date": "2024-04-15",
    "instructions": "Large banner for store opening",
    "delivery_preference": "pickup",
    "files_count": 1
}
```

**Response**:
```json
{
    "success": true,
    "message": "Order submitted successfully",
    "order_id": "ORD-2024-0001",
    "order_db_id": 1,
    "amount": 1550.00
}
```

### Get Orders
- **URL**: `api/get_orders.php?action=orders`
- **Method**: GET

**Query Parameters**:
- `service_category`: Filter by service category
- `order_status`: Filter by order status
- `date_from`: Filter from date (YYYY-MM-DD)
- `date_to`: Filter to date (YYYY-MM-DD)
- `limit`: Limit number of results

### Get Analytics
- **URL**: `api/get_orders.php?action=analytics&period=month`
- **Method**: GET

**Period Options**: day, week, month, year

### Get Others Breakdown
- **URL**: `api/get_orders.php?action=others_breakdown`
- **Method**: GET

## Features

### Customer Order Form
- ✅ 5 main service categories (Shirts, Tarpaulin, Print & Xerox, Stickers, Others)
- ✅ Tarpaulin size input validation
- ✅ Others category submenu with 28 services
- ✅ Real-time price calculation
- ✅ Database submission with validation
- ✅ Loading states and error handling

### Admin Interface
- ✅ Orders database view with filtering
- ✅ Order statistics dashboard
- ✅ Export functionality (CSV)
- ✅ Order details modal
- ✅ Real-time analytics from database
- ✅ Others category breakdown

### Data Integration
- ✅ Automatic order ID generation
- ✅ Price calculation based on service and size
- ✅ Sales transaction tracking
- ✅ Analytics data aggregation
- ✅ Fallback to local storage if database unavailable

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check MySQL service is running
   - Verify credentials in `config/database.php`
   - Ensure database exists

2. **Orders Not Saving**
   - Check PHP error logs
   - Verify API endpoint is accessible
   - Check database permissions

3. **Analytics Not Loading**
   - System falls back to local storage
   - Check browser console for errors
   - Verify API endpoints are working

### Error Codes
- **400**: Invalid request data
- **500**: Server/database error
- **404**: API endpoint not found

## Security Considerations

1. **Input Validation**: All inputs are validated and sanitized
2. **SQL Injection Prevention**: Using prepared statements
3. **CORS Headers**: Configured for cross-origin requests
4. **Error Handling**: Sensitive information not exposed in errors

## Performance Optimization

1. **Database Indexes**: Added on frequently queried columns
2. **Query Optimization**: Efficient queries with proper joins
3. **Caching**: Consider implementing Redis for frequently accessed data
4. **Pagination**: Implement for large datasets

## Maintenance

### Regular Tasks
1. **Backup Database**: Daily automated backups recommended
2. **Monitor Performance**: Check slow query logs
3. **Update Statistics**: Run ANALYZE TABLE periodically
4. **Clean Old Data**: Archive completed orders older than 2 years

### Monitoring
- Monitor API response times
- Track database connection pool usage
- Monitor disk space for database files
- Set up alerts for failed order submissions

## Support

For technical support or questions about the database integration:
1. Check the browser console for JavaScript errors
2. Review PHP error logs
3. Test API endpoints directly
4. Verify database connectivity

## Version History

- **v1.0**: Initial database integration
- **v1.1**: Added analytics API endpoints
- **v1.2**: Enhanced error handling and validation
- **v1.3**: Added export functionality and filtering
