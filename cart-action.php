<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = intval($_POST['product_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 1);
    $userId = $_SESSION['user_id'];
    
    if ($productId > 0) {
        $database = new Database();
        $db = $database->connect();
        
        // Check product stock
        $stockStmt = $db->prepare("SELECT stock FROM products WHERE id = :id");
        $stockStmt->bindParam(':id', $productId);
        $stockStmt->execute();
        $stock = $stockStmt->fetchColumn();
        
        if ($action === 'add') {
            // Check if already in cart
            $checkStmt = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id");
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->bindParam(':product_id', $productId);
            $checkStmt->execute();
            $existing = $checkStmt->fetch();
            
            if ($existing) {
                // Update quantity
                $newQty = min($existing['quantity'] + $quantity, $stock);
                $updateStmt = $db->prepare("UPDATE cart SET quantity = :quantity WHERE id = :id");
                $updateStmt->bindParam(':quantity', $newQty);
                $updateStmt->bindParam(':id', $existing['id']);
                $updateStmt->execute();
                $_SESSION['cart_message'] = 'Cart updated!';
            } else {
                // Add new item
                $quantity = min($quantity, $stock);
                $insertStmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
                $insertStmt->bindParam(':user_id', $userId);
                $insertStmt->bindParam(':product_id', $productId);
                $insertStmt->bindParam(':quantity', $quantity);
                $insertStmt->execute();
                $_SESSION['cart_message'] = 'Added to your cart!';
            }
        } elseif ($action === 'update') {
            $quantity = max(1, min($quantity, $stock));
            $stmt = $db->prepare("UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':product_id', $productId);
            $stmt->execute();
        } elseif ($action === 'remove') {
            $stmt = $db->prepare("DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':product_id', $productId);
            $stmt->execute();
        }
    }
}

// Redirect back to cart page or product page
$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'cart.php');
header('Location: ' . $redirect);
exit();
