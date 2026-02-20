CREATE DATABASE IF NOT EXISTS curtains_ecommerce;
USE curtains_ecommerce;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','employee','supplier','customer') DEFAULT 'customer',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category_id INT,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255),
    status ENUM('enabled','disabled') DEFAULT 'enabled',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS raw_materials (
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
);

CREATE TABLE IF NOT EXISTS raw_material_orders (
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
);

-- Add supplier_product_id to products table for tracking
ALTER TABLE products ADD COLUMN IF NOT EXISTS supplier_product_id INT DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS source_type ENUM('manual','supplier') DEFAULT 'manual';

-- Settings table for global commission
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Supplier Products table (products offered by suppliers)
CREATE TABLE IF NOT EXISTS supplier_products (
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
);

-- Supplier Orders table (admin orders from suppliers)
CREATE TABLE IF NOT EXISTS supplier_orders (
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
);

-- Wishlist Table
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
);

-- Cart Table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Customer Orders Table
CREATE TABLE IF NOT EXISTS customer_orders (
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
    payment_method ENUM('cod','online') DEFAULT 'cod',
    payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    razorpay_order_id VARCHAR(100) DEFAULT NULL,
    razorpay_payment_id VARCHAR(100) DEFAULT NULL,
    razorpay_signature VARCHAR(255) DEFAULT NULL,
    order_status ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Alter existing customer_orders table if it exists (for upgrades)
ALTER TABLE customer_orders MODIFY COLUMN payment_method ENUM('cod','online') DEFAULT 'cod';
ALTER TABLE customer_orders ADD COLUMN IF NOT EXISTS payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending';
ALTER TABLE customer_orders ADD COLUMN IF NOT EXISTS razorpay_order_id VARCHAR(100) DEFAULT NULL;
ALTER TABLE customer_orders ADD COLUMN IF NOT EXISTS razorpay_payment_id VARCHAR(100) DEFAULT NULL;
ALTER TABLE customer_orders ADD COLUMN IF NOT EXISTS razorpay_signature VARCHAR(255) DEFAULT NULL;
ALTER TABLE customer_orders ADD COLUMN IF NOT EXISTS order_status ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending';
ALTER TABLE customer_orders DROP COLUMN IF EXISTS status;
ALTER TABLE customer_orders DROP COLUMN IF EXISTS order_date;

INSERT INTO categories (name, description) VALUES
('Curtains', 'Window curtains and drapes'),
('Blinds', 'Window blinds and shades'),
('Accessories', 'Curtain accessories and hardware');

INSERT INTO users (full_name, email, password, role, status) VALUES
('Admin User', 'admin@curtains.com', '$2y$10$hq.8GjT14nmMHZjM8snTbu7Rf8HPK0ymzHetQ8UDq8ZfCzr.X7IaW', 'admin', 'active');

-- Default commission setting (10%)
INSERT INTO settings (setting_key, setting_value) VALUES ('global_commission', '10')
ON DUPLICATE KEY UPDATE setting_value = '10';
