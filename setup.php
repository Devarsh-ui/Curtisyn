<?php
require_once 'config/database.php';

echo "<h2>Database Setup</h2>";

$database = new Database();
$db = $database->connect();

if (!$db) {
    die("Database connection failed.");
}

try {
    // Add role and status to users
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('admin','employee','supplier','customer') DEFAULT 'customer' AFTER password");
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('active','inactive') DEFAULT 'active' AFTER role");
    echo "Users table updated.<br>";
} catch (PDOException $e) {
    echo "Users table: " . $e->getMessage() . "<br>";
}

try {
    // Add columns to products
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS category_id INT AFTER name");
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS stock INT DEFAULT 0 AFTER price");
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS status ENUM('enabled','disabled') DEFAULT 'enabled' AFTER stock");
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS created_by INT AFTER status");
    echo "Products table updated.<br>";
} catch (PDOException $e) {
    echo "Products table: " . $e->getMessage() . "<br>";
}

try {
    // Create categories table
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT
    )");
    
    $stmt = $db->query("SELECT COUNT(*) FROM categories");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO categories (name, description) VALUES
        ('Curtains', 'Window curtains and drapes'),
        ('Blinds', 'Window blinds and shades'),
        ('Accessories', 'Curtain accessories and hardware')");
    }
    echo "Categories table created.<br>";
} catch (PDOException $e) {
    echo "Categories table: " . $e->getMessage() . "<br>";
}

try {
    // Create raw_materials table
    $db->exec("CREATE TABLE IF NOT EXISTS raw_materials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT NOT NULL,
        name VARCHAR(200) NOT NULL,
        category VARCHAR(100),
        description TEXT,
        unit ENUM('kg','meter','litre','piece') NOT NULL,
        price_per_unit DECIMAL(10,2) NOT NULL,
        dimensions VARCHAR(100),
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES users(id)
    )");
    echo "Raw materials table created.<br>";
} catch (PDOException $e) {
    echo "Raw materials table: " . $e->getMessage() . "<br>";
}

try {
    // Create raw_material_orders table
    $db->exec("CREATE TABLE IF NOT EXISTS raw_material_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        supplier_id INT NOT NULL,
        material_id INT NOT NULL,
        quantity DECIMAL(10,2) NOT NULL,
        approved_quantity DECIMAL(10,2) DEFAULT NULL,
        total_cost DECIMAL(10,2) NOT NULL,
        status ENUM('pending','accepted','rejected','partially_accepted','completed') DEFAULT 'pending',
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        response_date TIMESTAMP NULL,
        FOREIGN KEY (admin_id) REFERENCES users(id),
        FOREIGN KEY (supplier_id) REFERENCES users(id),
        FOREIGN KEY (material_id) REFERENCES raw_materials(id)
    )");
    echo "Raw material orders table created.<br>";
} catch (PDOException $e) {
    echo "Raw material orders table: " . $e->getMessage() . "<br>";
}

try {
    // Add admin user
    $stmt = $db->prepare("SELECT id FROM users WHERE email = 'admin@curtains.com'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $stmt = $db->prepare("INSERT INTO users (full_name, email, password, role, status) VALUES ('Admin User', 'admin@curtains.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active')");
        $stmt->execute();
        echo "Admin user created.<br>";
    } else {
        echo "Admin user already exists.<br>";
    }
} catch (PDOException $e) {
    echo "Admin user: " . $e->getMessage() . "<br>";
}

try {
    // Add supplier_product_id and source_type to products
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS supplier_product_id INT DEFAULT NULL AFTER created_by");
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS source_type ENUM('manual','supplier') DEFAULT 'manual' AFTER supplier_product_id");
    echo "Products table updated with supplier fields.<br>";
} catch (PDOException $e) {
    echo "Products supplier fields: " . $e->getMessage() . "<br>";
}

try {
    // Create settings table
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value VARCHAR(255) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Insert default commission
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('global_commission', '10') ON DUPLICATE KEY UPDATE setting_value = '10'");
    $stmt->execute();
    echo "Settings table created.<br>";
} catch (PDOException $e) {
    echo "Settings table: " . $e->getMessage() . "<br>";
}

try {
    // Create supplier_products table
    $db->exec("CREATE TABLE IF NOT EXISTS supplier_products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT NOT NULL,
        name VARCHAR(200) NOT NULL,
        category_id INT,
        description TEXT,
        price_per_unit DECIMAL(10,2) NOT NULL,
        unit_type ENUM('piece','kg','meter','litre') DEFAULT 'piece',
        dimensions VARCHAR(100),
        image VARCHAR(255),
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES users(id),
        FOREIGN KEY (category_id) REFERENCES categories(id)
    )");
    echo "Supplier products table created.<br>";
} catch (PDOException $e) {
    echo "Supplier products table: " . $e->getMessage() . "<br>";
}

try {
    // Create supplier_orders table
    $db->exec("CREATE TABLE IF NOT EXISTS supplier_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id VARCHAR(50) NOT NULL UNIQUE,
        admin_id INT NOT NULL,
        supplier_id INT NOT NULL,
        supplier_product_id INT NOT NULL,
        requested_quantity INT NOT NULL,
        approved_quantity INT DEFAULT NULL,
        price_per_unit DECIMAL(10,2) NOT NULL,
        total_cost DECIMAL(10,2) NOT NULL,
        status ENUM('pending','accepted','rejected','partially_accepted','completed') DEFAULT 'pending',
        is_synced TINYINT(1) DEFAULT 0,
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        response_date TIMESTAMP NULL,
        FOREIGN KEY (admin_id) REFERENCES users(id),
        FOREIGN KEY (supplier_id) REFERENCES users(id),
        FOREIGN KEY (supplier_product_id) REFERENCES supplier_products(id)
    )");
    echo "Supplier orders table created.<br>";
} catch (PDOException $e) {
    echo "Supplier orders table: " . $e->getMessage() . "<br>";
}

echo "<br><strong>Setup complete!</strong><br>";
echo "<a href='index.php'>Go to website</a>";
