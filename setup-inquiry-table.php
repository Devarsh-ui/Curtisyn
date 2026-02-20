<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->connect();

if (!$db) {
    die("Database connection failed");
}

$sql = "CREATE TABLE IF NOT EXISTS product_inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inquiry_id VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    state VARCHAR(50) NOT NULL,
    city VARCHAR(50) NOT NULL,
    message TEXT,
    status ENUM('pending','accepted','in_progress','completed','rejected') DEFAULT 'pending',
    admin_comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
)";

try {
    $db->exec($sql);
    echo "Table 'product_inquiries' created successfully!";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
