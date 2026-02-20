<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->connect();

if (!$db) {
    die("Database connection failed.");
}

// Check if admin exists
$stmt = $db->prepare("SELECT id FROM users WHERE email = 'admin@curtains.com'");
$stmt->execute();
$existing = $stmt->fetch();

if ($existing) {
    // Update password for existing admin
    $password = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE users SET password = :password, role = 'admin', status = 'active' WHERE email = 'admin@curtains.com'");
    $stmt->bindParam(':password', $password);
    
    if ($stmt->execute()) {
        echo "Admin password updated successfully!<br>";
        echo "Email: admin@curtains.com<br>";
        echo "Password: admin123<br>";
        echo "<a href='login.php'>Go to Login</a>";
    } else {
        echo "Failed to update admin password.";
    }
} else {
    // Create new admin with password 'admin123'
    $password = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (full_name, email, password, role, status) VALUES ('Admin User', 'admin@curtains.com', :password, 'admin', 'active')");
    $stmt->bindParam(':password', $password);
    
    if ($stmt->execute()) {
        echo "Admin user created successfully!<br>";
        echo "Email: admin@curtains.com<br>";
        echo "Password: admin123<br>";
        echo "<a href='login.php'>Go to Login</a>";
    } else {
        echo "Failed to create admin user.";
    }
}
