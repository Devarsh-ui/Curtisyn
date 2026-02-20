<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config/database.php';
require_once 'config/razorpay.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

function jsonResponse($data) {
    ob_end_clean();
    echo json_encode($data);
    exit();
}

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Please login first']);
}

$database = new Database();
$db = $database->connect();
$userId = $_SESSION['user_id'];

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create_razorpay_order') {
        $productId = intval($_POST['product_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        $name = sanitizeInput($_POST['name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $city = sanitizeInput($_POST['city'] ?? '');
        $pincode = sanitizeInput($_POST['pincode'] ?? '');

        if (empty($name) || empty($phone) || empty($address) || empty($city) || empty($pincode)) {
            jsonResponse(['success' => false, 'message' => 'Please fill in all fields']);
        }

        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            jsonResponse(['success' => false, 'message' => 'Please enter a valid 10-digit phone number']);
        }

        if (!preg_match('/^[0-9]{6}$/', $pincode)) {
            jsonResponse(['success' => false, 'message' => 'Please enter a valid 6-digit pincode']);
        }

        $stmt = $db->prepare("SELECT * FROM products WHERE id = :id AND status = 'enabled' AND stock >= :qty");
        $stmt->bindParam(':id', $productId);
        $stmt->bindParam(':qty', $quantity);
        $stmt->execute();
        $product = $stmt->fetch();

        if (!$product) {
            jsonResponse(['success' => false, 'message' => 'Product not available']);
        }

        $commission = getGlobalCommission($db);
        $finalPrice = calculateFinalPrice($product['price'], $commission);
        $totalAmount = $finalPrice * $quantity;
        $fullAddress = $address . ', ' . $city . ' - ' . $pincode;
        $orderId = 'ORD' . date('Ymd') . strtoupper(substr(uniqid(), -6));

        $razorpayOrder = createRazorpayOrder($totalAmount, $orderId, $orderId);
        
        if (!$razorpayOrder) {
            jsonResponse(['success' => false, 'message' => 'Failed to create payment order. Please check server logs.']);
        }

        $stmt = $db->prepare("
            INSERT INTO customer_orders
            (order_id, user_id, product_id, quantity, price, total_amount, customer_name, customer_phone, customer_address, payment_method, payment_status, razorpay_order_id)
            VALUES (:order_id, :user_id, :product_id, :quantity, :price, :total_amount, :customer_name, :customer_phone, :customer_address, 'online', 'pending', :razorpay_order_id)
        ");

        $stmt->bindParam(':order_id', $orderId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':product_id', $productId);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':price', $finalPrice);
        $stmt->bindParam(':total_amount', $totalAmount);
        $stmt->bindParam(':customer_name', $name);
        $stmt->bindParam(':customer_phone', $phone);
        $stmt->bindParam(':customer_address', $fullAddress);
        $stmt->bindParam(':razorpay_order_id', $razorpayOrder['id']);

        if ($stmt->execute()) {
            jsonResponse([
                'success' => true,
                'order_id' => $orderId,
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $razorpayOrder['amount'],
                'currency' => $razorpayOrder['currency']
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to save order']);
        }
    }

    if ($action === 'verify_payment') {
        $orderId = sanitizeInput($_POST['order_id'] ?? '');
        $paymentId = sanitizeInput($_POST['razorpay_payment_id'] ?? '');
        $razorpayOrderId = sanitizeInput($_POST['razorpay_order_id'] ?? '');
        $signature = sanitizeInput($_POST['razorpay_signature'] ?? '');

        if (empty($orderId) || empty($paymentId) || empty($razorpayOrderId) || empty($signature)) {
            jsonResponse(['success' => false, 'message' => 'Invalid payment data']);
        }

        $stmt = $db->prepare("SELECT * FROM customer_orders WHERE order_id = :order_id AND user_id = :user_id");
        $stmt->bindParam(':order_id', $orderId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $order = $stmt->fetch();

        if (!$order) {
            jsonResponse(['success' => false, 'message' => 'Order not found']);
        }

        if (!verifyRazorpayPayment($paymentId, $razorpayOrderId, $signature)) {
            jsonResponse(['success' => false, 'message' => 'Payment verification failed']);
        }

        $paymentData = fetchRazorpayPayment($paymentId);

        if (!$paymentData || $paymentData['status'] !== 'captured') {
            jsonResponse(['success' => false, 'message' => 'Payment not completed']);
        }

        $db->beginTransaction();

        try {
            $stmt = $db->prepare("
                UPDATE customer_orders
                SET payment_status = 'paid',
                    razorpay_payment_id = :payment_id,
                    razorpay_signature = :signature,
                    order_status = 'confirmed'
                WHERE order_id = :order_id
            ");
            $stmt->bindParam(':payment_id', $paymentId);
            $stmt->bindParam(':signature', $signature);
            $stmt->bindParam(':order_id', $orderId);
            $stmt->execute();

            $updateStmt = $db->prepare("UPDATE products SET stock = stock - :qty WHERE id = :id");
            $updateStmt->bindParam(':qty', $order['quantity']);
            $updateStmt->bindParam(':id', $order['product_id']);
            $updateStmt->execute();

            $db->commit();

            require_once __DIR__ . '/includes/OrderEmailTrigger.php';
            $emailTrigger = new OrderEmailTrigger($db);
            try {
                $emailTrigger->onOrderPlaced($orderId);
            } catch (Exception $e) {
                error_log('Email sending failed: ' . $e->getMessage());
            }

            jsonResponse(['success' => true, 'message' => 'Payment successful']);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => 'Failed to update order']);
        }
    }

    jsonResponse(['success' => false, 'message' => 'Invalid action']);

} catch (Exception $e) {
    error_log('Payment error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
