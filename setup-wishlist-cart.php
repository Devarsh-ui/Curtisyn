<?php
require_once 'config/database.php';

echo "<h1>Setting up Wishlist, Cart, and Customer Orders Tables</h1>";

$database = new Database();
$db = $database->connect();

if (!$db) {
    die("Database connection failed!");
}

try {
    // Create wishlist table
    $db->exec("CREATE TABLE IF NOT EXISTS wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_wishlist (user_id, product_id)
    )");
    echo "<p style='color: green;'>✓ Wishlist table created successfully!</p>";

    // Create cart table
    $db->exec("CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");
    echo "<p style='color: green;'>✓ Cart table created successfully!</p>";

    // Create customer_orders table
    $db->exec("CREATE TABLE IF NOT EXISTS customer_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id VARCHAR(50) NOT NULL UNIQUE,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        customer_name VARCHAR(100) NOT NULL,
        customer_phone VARCHAR(20) NOT NULL,
        customer_address TEXT NOT NULL,
        payment_method ENUM('cod') DEFAULT 'cod',
        status ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");
    echo "<p style='color: green;'>✓ Customer Orders table created successfully!</p>";

    echo "<h2 style='color: green;'>All tables created successfully!</h2>";
    echo "<p><a href='index.php' style='padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px;'>Go to Home Page</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
