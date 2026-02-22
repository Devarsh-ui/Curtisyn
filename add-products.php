<?php
require 'config/database.php';
$db = (new Database())->connect();

$columns = [];
$stmt = $db->query("DESCRIBE products");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $columns[] = $row['Field'];
}

if (!in_array('mrp', $columns)) {
    $db->exec("ALTER TABLE products ADD COLUMN mrp DECIMAL(10,2) DEFAULT NULL AFTER price");
}
if (!in_array('offer_price', $columns)) {
    $db->exec("ALTER TABLE products ADD COLUMN offer_price DECIMAL(10,2) DEFAULT NULL AFTER mrp");
}
if (!in_array('image_path', $columns)) {
    $db->exec("ALTER TABLE products ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER image");
}

$stmt = $db->query("SELECT id FROM categories WHERE name LIKE '%Curtain%' LIMIT 1");
$category = $stmt->fetch(PDO::FETCH_ASSOC);
$categoryId = $category ? $category['id'] : 1;

$insertStmt = $db->prepare("
    INSERT INTO products (name, category_id, description, price, mrp, offer_price, stock, image_path, status)
    VALUES (:name, :category_id, :description, :price, :mrp, :offer_price, :stock, :image_path, 'enabled')
");

$insertStmt->execute([
    ':name' => 'Royal Velvet Blackout Curtain',
    ':category_id' => $categoryId,
    ':description' => 'Luxury heavy blackout curtain designed for bedrooms and hotels. Blocks 90–100% sunlight. Made with premium velvet fabric. Soft texture, durable stitching, elegant modern look. Available in 5ft and 7ft sizes.',
    ':price' => 1499.00,
    ':mrp' => 1999.00,
    ':offer_price' => 1199.00,
    ':stock' => 50,
    ':image_path' => 'public/images/products/royal-velvet-blackout.jpg'
]);

$insertStmt->execute([
    ':name' => 'Elegant Floral Room Darkening Curtain',
    ':category_id' => $categoryId,
    ':description' => 'Beautiful pastel floral curtain designed for living rooms and bedrooms. Provides 70–80% light blocking while maintaining privacy. Soft-touch fabric, washable and durable. Available in multiple sizes.',
    ':price' => 1199.00,
    ':mrp' => 1799.00,
    ':offer_price' => 999.00,
    ':stock' => 50,
    ':image_path' => 'public/images/products/elegant-floral-curtain.jpg'
]);
echo "Products inserted successfully.\n";
