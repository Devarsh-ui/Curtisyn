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

<style>
    .section-title {
        position: relative;
        display: inline-block;
        margin-bottom: 2rem;
    }
    
    .section-title:after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 4px;
        background: var(--gradient-secondary);
        border-radius: 2px;
    }
    
    .filter-section {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .category-badge {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .filter-section .form-control,
    .filter-section .form-select,
    .filter-section .btn {
        height: 48px;
        padding: 0 18px;
        border-radius: 8px;
        font-size: 1rem;
        border: 1px solid #ced4da;
    }
    
    .filter-section .form-control:focus,
    .filter-section .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(var(--primary-color-rgb), 0.25);
    }
    
    .filter-section .btn-search {
        min-width: 90px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        transition: all 0.3s ease;
        padding: 0 24px;
    }
    
    .filter-section .btn-clear {
        width: 48px;
        height: 48px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }
    
    .filter-section .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    
    .filter-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 15px;
    }
    
    .filter-item {
        flex: 1;
        min-width: 200px;
    }
    
    .filter-actions {
        display: flex;
        gap: 15px;
        align-items: center;
    }
</style>

<section class="section py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="section-title text-center mb-5">Our Products</h1>
                
                <!-- Filter Section -->
                <div class="filter-section mb-5">
                    <form method="GET" action="products.php" class="filter-toolbar">
                        <!-- Search by Name -->
                        <div class="filter-item" style="flex: 1.5;">
                            <label class="form-label fw-bold mb-2">Search Product</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Enter product name..." class="form-control">
                        </div>
                        
                        <!-- Filter by Category -->
                        <div class="filter-item">
                            <label class="form-label fw-bold mb-2">Category</label>
                            <select name="category" class="form-select">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $categoryFilter === intval($category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Sort By -->
                        <div class="filter-item">
                            <label class="form-label fw-bold mb-2">Sort By</label>
                            <select name="sort" class="form-select">
                                <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="price_low" <?php echo $sortBy === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sortBy === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name: A to Z</option>
                            </select>
                        </div>
                        
                        <!-- Buttons -->
                        <div class="filter-actions mt-3 mt-md-0">
                            <button type="submit" class="btn btn-primary btn-search">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                            <a href="products.php" class="btn btn-secondary btn-clear" title="Clear Filters">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
                
                <div class="row">
                    <?php if(count($products) == 0): ?>
                        <div class="col-12 text-center">
                            <div class="p-5">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <h3 class="text-muted">No products found</h3>
                                <p class="text-muted">Try adjusting your search criteria or browse our categories</p>
                                <a href="products.php" class="btn btn-primary px-4 py-2">Reset Filters</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): 
                            $finalPrice = calculateFinalPrice($product['price'], $commission);
                            $mrp = !empty($product['mrp']) ? floatval($product['mrp']) : null;
                            $offerPrice = !empty($product['offer_price']) ? floatval($product['offer_price']) : null;
                            $hasDiscount = false;
                            $discountPercent = 0;
                            
                            $displayPrice = $finalPrice;
                            if ($mrp && $offerPrice && $offerPrice < $mrp) {
                                $hasDiscount = true;
                                $displayPrice = $offerPrice;
                                $discountPercent = round((($mrp - $offerPrice) / $mrp) * 100);
                            }
                            
                            $imgPath = !empty($product['image_path']) ? htmlspecialchars($product['image_path']) : 'uploads/products/' . htmlspecialchars($product['image']);
                        ?>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
                            <div class="product-card h-100 position-relative">
                                <?php if ($hasDiscount): ?>
                                    <div class="badge bg-danger position-absolute" style="top: 10px; right: 10px; z-index: 3; font-size: 0.9rem; padding: 0.5rem 0.75rem; border-radius: 5px;">
                                        <?php echo $discountPercent; ?>% OFF
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($product['stock'] <= 0): ?>
                                    <div class="badge bg-secondary position-absolute" style="top: 10px; left: 10px; z-index: 3; font-size: 0.9rem; padding: 0.5rem 0.75rem; border-radius: 5px;">
                                        Out of Stock
                                    </div>
                                    <div class="position-absolute w-100 h-100" style="background: rgba(255,255,255,0.5); z-index: 1;"></div>
                                <?php endif; ?>
                                
                                <div class="product-image" style="background: #fff; text-align: center; height: 220px; display: flex; align-items: center; justify-content: center; overflow: hidden; border-radius: 8px 8px 0 0;">
                                    <?php if ($product['image'] || $product['image_path']): ?>
                                        <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 100%; height: 100%; object-fit: contain; padding: 10px;">
                                    <?php else: ?>
                                        <div class="text-muted p-4">
                                            <i class="fas fa-image fa-3x mb-2 text-secondary"></i>
                                            <p>No Image</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info p-3">
                                    <span class="category-badge mb-2 d-inline-block">
                                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                    </span>
                                    <h3 class="product-title" style="font-size: 1.1rem; margin-bottom: 0.5rem;">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </h3>
                                    
                                    <div class="price-container d-flex align-items-center gap-2 mb-3">
                                        <span class="product-price mb-0" style="font-size: 1.25rem; font-weight: 700; color: #27ae60;">
                                            ₹<?php echo number_format($displayPrice, 2); ?>
                                        </span>
                                        <?php if ($hasDiscount): ?>
                                            <span class="text-muted text-decoration-line-through" style="font-size: 0.9rem;">
                                                ₹<?php echo number_format($mrp, 2); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary w-100 btn-sm">View Details</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

