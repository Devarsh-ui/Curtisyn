<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->connect();

if (!$db) {
    die("Database connection failed");
}

$sql = "CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) NOT NULL,
    email_type VARCHAR(50) NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'failed') DEFAULT 'sent'
)";

try {
    $db->exec($sql);
    echo "Table 'email_logs' created successfully!";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
