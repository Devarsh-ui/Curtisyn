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
    
    // New Size Parameters
    $sizeName = $_POST['size_name'] ?? null;
    $customWidth = !empty($_POST['custom_width']) ? floatval($_POST['custom_width']) : null;
    $customHeight = !empty($_POST['custom_height']) ? floatval($_POST['custom_height']) : null;
    $finalPrice = !empty($_POST['final_price']) ? floatval($_POST['final_price']) : null;
    
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
            // Check if already in cart (matching product AND exact size)
            $checkStmt = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id AND (size_name = :size_name OR (size_name IS NULL AND :size_name IS NULL)) AND (custom_width = :c_width OR (custom_width IS NULL AND :c_width IS NULL)) AND (custom_height = :c_height OR (custom_height IS NULL AND :c_height IS NULL))");
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->bindParam(':product_id', $productId);
            $checkStmt->bindParam(':size_name', $sizeName);
            $checkStmt->bindParam(':c_width', $customWidth);
            $checkStmt->bindParam(':c_height', $customHeight);
            
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
                $insertStmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity, size_name, custom_width, custom_height, custom_price) VALUES (:user_id, :product_id, :quantity, :size_name, :custom_width, :custom_height, :custom_price)");
                $insertStmt->bindParam(':user_id', $userId);
                $insertStmt->bindParam(':product_id', $productId);
                $insertStmt->bindParam(':quantity', $quantity);
                $insertStmt->bindParam(':size_name', $sizeName);
                $insertStmt->bindParam(':custom_width', $customWidth);
                $insertStmt->bindParam(':custom_height', $customHeight);
                $insertStmt->bindParam(':custom_price', $finalPrice);
                
                $insertStmt->execute();
                $_SESSION['cart_message'] = 'Added to your cart!';
            }
        } elseif ($action === 'update') {
            // If we are updating quantity from cart.php, we pass a specific cart item ID instead of just product_id
            $cartItemId = intval($_POST['cart_id'] ?? 0);
            
            if ($cartItemId > 0) {
                $quantity = max(1, min($quantity, $stock));
                $stmt = $db->prepare("UPDATE cart SET quantity = :quantity WHERE id = :id AND user_id = :user_id");
                $stmt->bindParam(':quantity', $quantity);
                $stmt->bindParam(':id', $cartItemId);
                $stmt->bindParam(':user_id', $userId);
                $stmt->execute();
            } else {
                // Fallback (older buttons)
                $quantity = max(1, min($quantity, $stock));
                $stmt = $db->prepare("UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
                $stmt->bindParam(':quantity', $quantity);
                $stmt->bindParam(':user_id', $userId);
                $stmt->bindParam(':product_id', $productId);
                $stmt->execute();
            }
        } elseif ($action === 'remove') {
            $cartItemId = intval($_POST['cart_id'] ?? 0);
            if ($cartItemId > 0) {
                $stmt = $db->prepare("DELETE FROM cart WHERE id = :id AND user_id = :user_id");
                $stmt->bindParam(':id', $cartItemId);
                $stmt->bindParam(':user_id', $userId);
                $stmt->execute();
            } else {
                $stmt = $db->prepare("DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id");
                $stmt->bindParam(':user_id', $userId);
                $stmt->bindParam(':product_id', $productId);
                $stmt->execute();
            }
        }
    }
}

// Redirect back to cart page or product page
$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'cart.php');
header('Location: ' . $redirect);
exit();
