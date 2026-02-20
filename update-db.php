<?php
require_once 'config/database.php';

echo "Updating database...<br>";

$database = new Database();
$db = $database->connect();

try {
    // Add payment_status column
    $db->exec("ALTER TABLE customer_orders ADD COLUMN IF NOT EXISTS payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending'");
    echo "Added payment_status column<br>";

    // Add razorpay_order_id column
    $db->exec("ALTER TABLE customer_orders ADD COLUMN IF NOT EXISTS razorpay_order_id VARCHAR(100) DEFAULT NULL");
    echo "Added razorpay_order_id column<br>";

    // Add razorpay_payment_id column
    $db->exec("ALTER TABLE customer_orders ADD COLUMN IF NOT EXISTS razorpay_payment_id VARCHAR(100) DEFAULT NULL");
    echo "Added razorpay_payment_id column<br>";

    // Add razorpay_signature column
    $db->exec("ALTER TABLE customer_orders ADD COLUMN IF NOT EXISTS razorpay_signature VARCHAR(255) DEFAULT NULL");
    echo "Added razorpay_signature column<br>";

    // Add order_status column
    $db->exec("ALTER TABLE customer_orders ADD COLUMN IF NOT EXISTS order_status ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending'");
    echo "Added order_status column<br>";

    // Update existing orders
    $db->exec("UPDATE customer_orders SET order_status = status WHERE order_status IS NULL AND status IS NOT NULL");
    echo "Updated existing orders<br>";

    // Modify payment_method to include 'online'
    $db->exec("ALTER TABLE customer_orders MODIFY COLUMN payment_method ENUM('cod','online') DEFAULT 'cod'");
    echo "Updated payment_method enum<br>";

    echo "<br>Database update completed successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
