<?php
require_once 'config/database.php';

echo "Updating database for product sizes...<br>";

$database = new Database();
$db = $database->connect();

try {
    // 1. Create product_sizes table
    $db->exec("CREATE TABLE IF NOT EXISTS product_sizes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        size_name VARCHAR(100) NOT NULL,
        width DECIMAL(10,2) DEFAULT NULL,
        height DECIMAL(10,2) DEFAULT NULL,
        price DECIMAL(10,2) NOT NULL,
        price_per_sqft DECIMAL(10,2) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");
    echo "Created product_sizes table.<br>";

    // 2. Update cart table
    $db->exec("ALTER TABLE cart ADD COLUMN IF NOT EXISTS size_name VARCHAR(100) DEFAULT NULL");
    $db->exec("ALTER TABLE cart ADD COLUMN IF NOT EXISTS custom_width DECIMAL(10,2) DEFAULT NULL");
    $db->exec("ALTER TABLE cart ADD COLUMN IF NOT EXISTS custom_height DECIMAL(10,2) DEFAULT NULL");
    $db->exec("ALTER TABLE cart ADD COLUMN IF NOT EXISTS custom_price DECIMAL(10,2) DEFAULT NULL");
    echo "Updated cart table with size columns.<br>";

    // 3. Update customer_orders table
    $db->exec("ALTER TABLE customer_orders ADD COLUMN IF NOT EXISTS size_name VARCHAR(100) DEFAULT NULL");
    $db->exec("ALTER TABLE customer_orders ADD COLUMN IF NOT EXISTS custom_width DECIMAL(10,2) DEFAULT NULL");
    $db->exec("ALTER TABLE customer_orders ADD COLUMN IF NOT EXISTS custom_height DECIMAL(10,2) DEFAULT NULL");
    echo "Updated customer_orders table with size columns.<br>";

    echo "<br>Database size update completed successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
