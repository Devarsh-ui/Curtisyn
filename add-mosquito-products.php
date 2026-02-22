<?php
require 'config/database.php';
$database = new Database();
$db = $database->connect();

try {
    // Check if category "Mosquito Net" exists, if not, create it
    $catStmt = $db->prepare("SELECT id FROM categories WHERE name = 'Mosquito Net'");
    $catStmt->execute();
    $cat = $catStmt->fetch();

    if ($cat) {
        $categoryId = $cat['id'];
    } else {
        $insertCat = $db->prepare("INSERT INTO categories (name, description) VALUES ('Mosquito Net', 'Mosquito Net collection')");
        $insertCat->execute();
        $categoryId = $db->lastInsertId();
    }

    // Product 1
    $p1 = [
        'name' => 'Premium Magnetic Door Mosquito Net',
        'desc' => 'Premium quality magnetic door mosquito net designed for easy walk-through access. Strong center magnetic closure automatically seals after passing through. Made with fine mesh fabric that allows airflow while blocking mosquitoes and insects. Easy installation without drilling. Washable and reusable.',
        'price' => 899,
        'stock' => 40,
        'cat_id' => $categoryId,
        'mrp' => 1199,
        'offer_price' => 749,
        'image_path' => 'public/images/products/premium-magnetic-door-net.jpg',
        'status' => 'enabled'
    ];

    // Product 2
    $p2 = [
        'name' => 'Sliding Window Mosquito Mesh Net',
        'desc' => 'High-quality sliding window mosquito mesh made with durable fiberglass mesh and strong aluminum frame. Provides protection from mosquitoes while maintaining ventilation and natural light. Smooth sliding mechanism and easy maintenance. Custom sizes available.',
        'price' => 1799,
        'stock' => 30,
        'cat_id' => $categoryId,
        'mrp' => 2499,
        'offer_price' => 1499,
        'image_path' => 'public/images/products/sliding-window-mosquito-net.jpg',
        'status' => 'enabled'
    ];

    $insertProd = $db->prepare("INSERT INTO products (name, description, price, stock, category_id, mrp, offer_price, image_path, status, created_by) VALUES (:name, :desc, :price, :stock, :cat_id, :mrp, :offer_price, :image_path, :status, 1)");

    foreach ([$p1, $p2] as $p) {
        $insertProd->bindParam(':name', $p['name']);
        $insertProd->bindParam(':desc', $p['desc']);
        $insertProd->bindParam(':price', $p['price']);
        $insertProd->bindParam(':stock', $p['stock']);
        $insertProd->bindParam(':cat_id', $p['cat_id']);
        $insertProd->bindParam(':mrp', $p['mrp']);
        $insertProd->bindParam(':offer_price', $p['offer_price']);
        $insertProd->bindParam(':image_path', $p['image_path']);
        $insertProd->bindParam(':status', $p['status']);
        $insertProd->execute();
        echo "Inserted: " . $p['name'] . "\n";
    }

    echo "Successfully inserted both mosquito net products.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
