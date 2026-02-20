<?php
$pageTitle = 'Home';
$currentPage = 'home';
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->connect();

$products = [];
$commission = 10;

if ($db) {
    $commission = getGlobalCommission($db);
    $stmt = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'enabled' AND p.stock > 0 ORDER BY p.created_at DESC LIMIT 4");
    $products = $stmt->fetchAll();
}
?>

<section class="hero">
    <div class="container">
        <h1>Elegant Curtains for Every Home</h1>
        <p>Transform your living spaces with our premium collection</p>
        <a href="products.php" class="btn btn-primary">Shop Now</a>
    </div>
</section>

<section class="section" id="products">
    <div class="container">
        <h2 class="section-title">Featured Products</h2>
        <div class="products-grid">
            <?php foreach ($products as $product): 
                $finalPrice = calculateFinalPrice($product['price'], $commission);
            ?>
            <div class="product-card">
                <div class="product-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <?php if ($product['image']): ?>
                        <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="product-price">₹<?php echo number_format($finalPrice, 2); ?></p>
                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
