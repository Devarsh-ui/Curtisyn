<?php
$pageTitle = 'Products';
$currentPage = 'products';
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->connect();

$products = [];
$categoryFilter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$commission = 10;

if ($db) {
    $commission = getGlobalCommission($db);
    
    // Build query dynamically
    $whereClause = "p.status = 'enabled' AND p.stock > 0";
    $params = [];
    
    if ($categoryFilter > 0) {
        $whereClause .= " AND p.category_id = :category_id";
        $params[':category_id'] = $categoryFilter;
    }
    
    if (!empty($searchQuery)) {
        $whereClause .= " AND (p.name LIKE :search OR p.description LIKE :search)";
        $params[':search'] = '%' . $searchQuery . '%';
    }
    
    // Sort options
    $orderClause = "p.created_at DESC";
    switch ($sortBy) {
        case 'price_low':
            $orderClause = "p.price ASC";
            break;
        case 'price_high':
            $orderClause = "p.price DESC";
            break;
        case 'name':
            $orderClause = "p.name ASC";
            break;
    }
    
    $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $whereClause ORDER BY $orderClause";
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $products = $stmt->fetchAll();
}

// Get unique categories only
$categories = [];
if ($db) {
    $stmt = $db->query("SELECT DISTINCT id, name FROM categories WHERE id IN (SELECT DISTINCT category_id FROM products WHERE status = 'enabled' AND stock > 0) ORDER BY name");
    $categories = $stmt->fetchAll();
}
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Our Products</h1>
        
        <!-- Filter Section -->
        <div class="filter-section" style="background: #f8f9fa; padding: clamp(1rem, 3vw, 1.5rem); border-radius: 10px; margin-bottom: 2rem;">
            <form method="GET" action="products.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 170px), 1fr)); gap: clamp(0.5rem, 2vw, 1rem); align-items: flex-end;">
                <!-- Search by Name -->
                <div style="grid-column: span 1;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #555; font-size: clamp(0.9rem, 2vw, 1rem);">Search Product</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Enter product name..." style="width: 100%; padding: clamp(0.5rem, 1.5vw, 0.75rem); border: 2px solid #ddd; border-radius: 5px; font-size: clamp(0.9rem, 1.5vw, 1rem);">
                </div>
                
                <!-- Filter by Category -->
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #555; font-size: clamp(0.9rem, 2vw, 1rem);">Category</label>
                    <select name="category" style="width: 100%; padding: clamp(0.5rem, 1.5vw, 0.75rem); border: 2px solid #ddd; border-radius: 5px; font-size: clamp(0.9rem, 1.5vw, 1rem); background: white;">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $categoryFilter === intval($category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Sort By -->
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #555; font-size: clamp(0.9rem, 2vw, 1rem);">Sort By</label>
                    <select name="sort" style="width: 100%; padding: clamp(0.5rem, 1.5vw, 0.75rem); border: 2px solid #ddd; border-radius: 5px; font-size: clamp(0.9rem, 1.5vw, 1rem); background: white;">
                        <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="price_low" <?php echo $sortBy === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sortBy === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name: A to Z</option>
                    </select>
                </div>
                
                <!-- Buttons -->
                <div style="display: flex; gap: clamp(0.25rem, 2vw, 0.5rem); grid-column: span 1;">
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: clamp(0.5rem, 1.5vw, 0.75rem) clamp(0.75rem, 2vw, 1.5rem); font-size: clamp(0.85rem, 1.5vw, 1rem);">
                        <i class="fas fa-search"></i> <span style="display: none;">Search</span>
                    </button>
                    <a href="products.php" class="btn btn-secondary" style="width: 100%; padding: clamp(0.5rem, 1.5vw, 0.75rem) clamp(0.75rem, 2vw, 1.5rem); font-size: clamp(0.85rem, 1.5vw, 1rem); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-times"></i> <span style="display: none;">Clear</span>
                    </a>
                </div>
            </form>
        </div>
        
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
                    <p class="product-category"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                    <p class="product-price">₹<?php echo number_format($finalPrice, 2); ?></p>
                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
