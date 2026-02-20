<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('products.php');
}

$productId = intval($_POST['product_id'] ?? 0);
$fullName = trim($_POST['full_name'] ?? '');
$mobile = trim($_POST['mobile'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$pincode = trim($_POST['pincode'] ?? '');
$state = trim($_POST['state'] ?? '');
$city = trim($_POST['city'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($productId === 0 || empty($fullName) || empty($mobile) || empty($email) || empty($address) || empty($pincode) || empty($state) || empty($city)) {
    redirect('products.php');
}

$database = new Database();
$db = $database->connect();

$product = null;
if ($db) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = :id AND status = 'enabled'");
    $stmt->bindParam(':id', $productId);
    $stmt->execute();
    $product = $stmt->fetch();
}

if (!$product) {
    redirect('products.php');
}

$customerId = $_SESSION['user_id'];
$inquiryId = 'INQ' . date('Ymd') . strtoupper(substr(uniqid(), -6));

if ($db) {
    $stmt = $db->prepare("INSERT INTO product_inquiries (inquiry_id, customer_id, product_id, full_name, mobile, email, address, pincode, state, city, message) VALUES (:inquiry_id, :customer_id, :product_id, :full_name, :mobile, :email, :address, :pincode, :state, :city, :message)");
    $stmt->bindParam(':inquiry_id', $inquiryId);
    $stmt->bindParam(':customer_id', $customerId);
    $stmt->bindParam(':product_id', $productId);
    $stmt->bindParam(':full_name', $fullName);
    $stmt->bindParam(':mobile', $mobile);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':pincode', $pincode);
    $stmt->bindParam(':state', $state);
    $stmt->bindParam(':city', $city);
    $stmt->bindParam(':message', $message);

    if ($stmt->execute()) {
        redirect('inquiry-success.php?inquiry_id=' . $inquiryId);
    } else {
        redirect('inquiry.php?product_id=' . $productId);
    }
} else {
    redirect('inquiry.php?product_id=' . $productId);
}
