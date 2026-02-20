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
    $userId = $_SESSION['user_id'];
    
    if ($productId > 0) {
        $database = new Database();
        $db = $database->connect();
        
        if ($action === 'add') {
            // Add to wishlist
            $stmt = $db->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (:user_id, :product_id)");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':product_id', $productId);
            $stmt->execute();
            $_SESSION['wishlist_message'] = 'Added to your wishlist!';
        } elseif ($action === 'remove') {
            // Remove from wishlist
            $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':product_id', $productId);
            $stmt->execute();
            $_SESSION['wishlist_message'] = 'Removed from your wishlist!';
        }
    }
}

// Redirect back to product page
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'products.php';
header('Location: ' . $redirect);
exit();
