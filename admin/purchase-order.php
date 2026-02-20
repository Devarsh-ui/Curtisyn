<?php
require_once 'auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'admin/purchase-supplier.php');
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    die('Invalid request');
}

$database = new Database();
$db = $database->connect();

$supplierProductId = $_POST['supplier_product_id'] ?? 0;
$quantity = (int)($_POST['quantity'] ?? 0);
$pricePerUnit = (float)($_POST['price_per_unit'] ?? 0);
$adminId = $_SESSION['user_id'];

if ($quantity <= 0 || $pricePerUnit <= 0) {
    redirect(BASE_URL . 'admin/purchase-supplier.php');
}

$productStmt = $db->prepare("
    SELECT sp.*, u.id as supplier_id 
    FROM supplier_products sp
    JOIN users u ON sp.supplier_id = u.id
    WHERE sp.id = :id AND sp.status = 'active'
");
$productStmt->bindParam(':id', $supplierProductId);
$productStmt->execute();
$product = $productStmt->fetch();

if (!$product) {
    redirect(BASE_URL . 'admin/purchase-supplier.php');
}

$totalCost = $quantity * $pricePerUnit;
$orderId = generateOrderId();

$insertStmt = $db->prepare("
    INSERT INTO supplier_orders 
    (order_id, admin_id, supplier_id, supplier_product_id, requested_quantity, price_per_unit, total_cost, status, is_synced, order_date)
    VALUES (:order_id, :admin_id, :supplier_id, :supplier_product_id, :requested_quantity, :price_per_unit, :total_cost, 'pending', 0, NOW())
");

$insertStmt->bindParam(':order_id', $orderId);
$insertStmt->bindParam(':admin_id', $adminId);
$insertStmt->bindParam(':supplier_id', $product['supplier_id']);
$insertStmt->bindParam(':supplier_product_id', $supplierProductId);
$insertStmt->bindParam(':requested_quantity', $quantity);
$insertStmt->bindParam(':price_per_unit', $pricePerUnit);
$insertStmt->bindParam(':total_cost', $totalCost);

if ($insertStmt->execute()) {
    header('Location: ' . BASE_URL . 'admin/supplier-orders.php?success=Order placed successfully');
    exit();
} else {
    header('Location: ' . BASE_URL . 'admin/purchase-supplier.php?error=Failed to place order');
    exit();
}
