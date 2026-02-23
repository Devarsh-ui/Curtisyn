<?php
require 'config/database.php';
$database = new Database();
$db = $database->connect();

try {
    // Check if category "Roller Curtain" exists, if not, create it
    $catStmt = $db->prepare("SELECT id FROM categories WHERE name = 'Roller Curtain'");
    $catStmt->execute();
    $cat = $catStmt->fetch();

    if ($cat) {
        $categoryId = $cat['id'];
    } else {
        $insertCat = $db->prepare("INSERT INTO categories (name, description) VALUES ('Roller Curtain', 'Roller curtain collection')");
        $insertCat->execute();
        $categoryId = $db->lastInsertId();
    }

    // Product 1
    $p1 = [
        'name' => 'Modern SunShield Roller Curtain',
        'desc' => 'High-quality polyester roller curtain designed for homes and offices. Provides 80–90% sunlight control while maintaining privacy. Smooth chain-operated rolling mechanism. Durable fabric. Available in Grey, Beige, and White. Custom sizes available.',
        'price' => 1899,
        'stock' => 40,
        'cat_id' => $categoryId,
        'mrp' => 2499,
        'offer_price' => 1699,
        'image_path' => 'public/images/products/modern-sunshield-roller.jpg',
        'status' => 'enabled'
    ];

    // Product 2
    $p2 = [
        'name' => 'Premium Blackout Roller Blind',
        'desc' => 'Heavy-duty blackout roller blind designed for complete light blocking. Ideal for bedrooms, conference rooms, and media rooms. Blocks up to 100% sunlight. Strong rolling mechanism and premium thick fabric. Available in Black, Navy, and Brown.',
        'price' => 2299,
        'stock' => 35,
        'cat_id' => $categoryId,
        'mrp' => 2999,
        'offer_price' => 1999,
        'image_path' => 'public/images/products/premium-blackout-roller.jpg',
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

    echo "Successfully inserted both products.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
