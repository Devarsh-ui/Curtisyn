<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id === 0) {
    redirect('products.php');
}

require_once 'includes/header.php';

$database = new Database();
$db = $database->connect();

$product = null;
$commission = 10;
$isInWishlist = false;
$cartCount = 0;

// Get messages from session
$wishlistMessage = $_SESSION['wishlist_message'] ?? null;
$cartMessage = $_SESSION['cart_message'] ?? null;
unset($_SESSION['wishlist_message'], $_SESSION['cart_message']);

if ($db) {
    $commission = getGlobalCommission($db);
    $stmt = $db->prepare("SELECT p.*, c.name as category_name, u.full_name as creator_name FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN users u ON p.created_by = u.id WHERE p.id = :id AND p.status = 'enabled' AND p.stock > 0");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $product = $stmt->fetch();
    
    // Check if in wishlist (for logged in users)
    if ($product && isLoggedIn()) {
        $userId = $_SESSION['user_id'];
        $wishStmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
        $wishStmt->bindParam(':user_id', $userId);
        $wishStmt->bindParam(':product_id', $id);
        $wishStmt->execute();
        $isInWishlist = $wishStmt->rowCount() > 0;
        
        // Get cart count
        $cartStmt = $db->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = :user_id");
        $cartStmt->bindParam(':user_id', $userId);
        $cartStmt->execute();
        $cartCount = $cartStmt->fetch()['count'] ?? 0;
    }

    // Fetch Product Sizes
    $sizesStmt = $db->prepare("SELECT * FROM product_sizes WHERE product_id = :id ORDER BY id ASC");
    $sizesStmt->bindParam(':id', $id);
    $sizesStmt->execute();
    $product_sizes = $sizesStmt->fetchAll();
}

if (!$product) {
    redirect('products.php');
}

$finalPrice = calculateFinalPrice($product['price'], $commission);

$displayPrice = $finalPrice;
$mrp = !empty($product['mrp']) ? floatval($product['mrp']) : null;
$offerPrice = !empty($product['offer_price']) ? floatval($product['offer_price']) : null;
$hasDiscount = false;
$discountPercent = 0;

if ($mrp && $offerPrice && $offerPrice < $mrp) {
    $hasDiscount = true;
    $displayPrice = $offerPrice;
    $discountPercent = round((($mrp - $offerPrice) / $mrp) * 100);
}

$platformCharge = $finalPrice - $product['price'];
$pageTitle = $product['name'];
$currentPage = 'products';
$isLoggedIn = isLoggedIn();
$userRole = getUserRole();
?>

<section class="section">
    <div class="container">
        <!-- Success Messages -->
        <?php if ($wishlistMessage): ?>
            <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: clamp(0.75rem, 2vw, 1rem); border-radius: 8px; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; font-size: clamp(0.9rem, 1.5vw, 1rem);">
                <i class="fas fa-heart" style="color: #e74c3c; flex-shrink: 0;"></i>
                <span><?php echo htmlspecialchars($wishlistMessage); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($cartMessage): ?>
            <div class="alert alert-success" style="background: #d1ecf1; color: #0c5460; padding: clamp(0.75rem, 2vw, 1rem); border-radius: 8px; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; font-size: clamp(0.9rem, 1.5vw, 1rem);">
                <i class="fas fa-shopping-cart" style="color: #3498db; flex-shrink: 0;"></i>
                <span><?php echo htmlspecialchars($cartMessage); ?></span>
            </div>
        <?php endif; ?>
        
        <div class="product-detail">
            <div class="product-detail-image position-relative" style="background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden; display: flex; align-items: center; justify-content: center; min-height: 400px; padding: 20px;">
                <?php if ($hasDiscount): ?>
                    <div class="badge bg-danger position-absolute" style="top: 15px; left: 15px; z-index: 3; font-size: 1.1rem; padding: 0.6rem 1rem; border-radius: 5px;">
                        <?php echo $discountPercent; ?>% OFF
                    </div>
                <?php endif; ?>
                <?php 
                    $imgPath = !empty($product['image_path']) ? htmlspecialchars($product['image_path']) : 'uploads/products/' . htmlspecialchars($product['image']);
                    if ($product['image'] || $product['image_path']): 
                ?>
                    <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 100%; height: 100%; max-height: 450px; object-fit: contain; display: block;">
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-image fa-4x mb-3"></i>
                        <p>No Image Available</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="product-detail-info">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="product-category"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                
                <div class="d-flex align-items-center gap-3 mb-2 mt-3">
                    <p class="product-price mb-0" id="display-price" style="font-size: 2rem; font-weight: 700; color: #27ae60;">
                        ₹<?php echo number_format($displayPrice, 2); ?>
                    </p>
                    <?php if ($hasDiscount): ?>
                        <span class="text-muted text-decoration-line-through" style="font-size: 1.25rem;">
                            ₹<?php echo number_format($mrp, 2); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <span style="font-size: 0.8rem; color: #666; display: block; margin-bottom: 1rem;">Base Price / Starting From</span>
                
                <p class="product-stock" style="font-weight: 500; font-size: 1.1rem; padding: 0.5rem 1rem; border-radius: 5px; display: inline-block; background: <?php echo $product['stock'] > 0 ? '#e8f5e9; color: #2e7d32;' : '#ffebee; color: #c62828;'; ?>">
                    <?php echo $product['stock'] > 0 ? '<i class="fas fa-check-circle me-1"></i> In Stock: ' . $product['stock'] : '<i class="fas fa-times-circle me-1"></i> Out of Stock'; ?>
                </p>
                <div class="product-description mt-3">
                    <?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?>
                </div>
                
                <!-- Action Buttons -->
                <div class="product-actions" style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    
                    <?php if (!empty($product_sizes)): ?>
                    <!-- Sizes Selection -->
                    <div class="size-selector" style="margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid #ddd;">
                        <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Select Size</h3>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <?php foreach ($product_sizes as $index => $size): ?>
                                <?php 
                                    $sizePrice = calculateFinalPrice($size['price'], $commission); 
                                    $sqftPrice = calculateFinalPrice($size['price_per_sqft'] ?? 0, $commission);
                                ?>
                                <button type="button" class="btn btn-outline-primary size-btn" 
                                    data-sizename="<?php echo htmlspecialchars($size['size_name']); ?>"
                                    data-price="<?php echo $sizePrice; ?>"
                                    data-sqft="<?php echo $sqftPrice; ?>"
                                    data-iscustom="<?php echo strtolower($size['size_name']) === 'custom' || $size['price_per_sqft'] > 0 ? 'true' : 'false'; ?>"
                                    style="border: 2px solid var(--primary-color); background: <?php echo $index === 0 ? 'var(--primary-color)' : 'transparent'; ?>; color: <?php echo $index === 0 ? '#fff' : 'var(--text-color)'; ?>; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; transition: all 0.3s; font-weight: 500;">
                                    <?php echo htmlspecialchars($size['size_name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <!-- Custom Dimension Inputs (Hidden by default) -->
                        <div id="custom-dimensions" style="display: none; margin-top: 1rem; background: #fff; padding: 1rem; border-radius: 8px; border: 1px dashed #ccc;">
                            <h4 style="margin-bottom: 0.5rem; font-size: 1rem;">Enter Custom Dimensions (ft)</h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <label class="form-label" style="font-size: 0.9rem;">Width</label>
                                    <input type="number" id="custom-width" class="form-input" step="0.1" min="0.1" placeholder="e.g. 5.5">
                                </div>
                                <div>
                                    <label class="form-label" style="font-size: 0.9rem;">Height</label>
                                    <input type="number" id="custom-height" class="form-input" step="0.1" min="0.1" placeholder="e.g. 6.0">
                                </div>
                            </div>
                            <div style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                                Price per SqFt: ₹<span id="sqft-display">0</span> | Area: <span id="area-display">0</span> sqft
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                            <!-- Quantity Selector -->
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <label style="font-weight: 600;">Qty:</label>
                                <input type="number" id="product-qty" value="1" min="1" max="<?php echo $product['stock']; ?>" style="width: 70px; padding: 0.5rem; border: 2px solid #ddd; border-radius: 5px; font-size: 1rem;">
                            </div>
                            
                            <!-- Wishlist Button (Heart Icon) -->
                            <form method="POST" action="wishlist-action.php" style="display: inline; margin: 0;">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="action" value="<?php echo $isInWishlist ? 'remove' : 'add'; ?>">
                                <button type="submit" style="background: none; border: none; cursor: pointer; padding: 8px;" title="<?php echo $isInWishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                                    <i class="fas fa-heart" style="font-size: 28px; color: <?php echo $isInWishlist ? '#e74c3c' : '#95a5a6'; ?>"></i>
                                </button>
                            </form>
                            
                            <!-- Add to Cart Button (Cart Icon) -->
                            <form id="add-to-cart-form" method="POST" action="cart-action.php" style="display: inline; margin: 0;">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="quantity" id="cart-qty" value="1">
                                <!-- Size Hidden Fields -->
                                <input type="hidden" name="size_name" id="cart-size-name" value="<?php echo !empty($product_sizes) ? htmlspecialchars($product_sizes[0]['size_name']) : ''; ?>">
                                <input type="hidden" name="custom_width" id="cart-custom-width" value="">
                                <input type="hidden" name="custom_height" id="cart-custom-height" value="">
                                <input type="hidden" name="final_price" id="cart-final-price" value="<?php echo !empty($product_sizes) ? calculateFinalPrice($product_sizes[0]['price'], $commission) : $finalPrice; ?>">
                                
                                <button type="button" onclick="submitCart()" style="background: none; border: none; cursor: pointer; padding: 8px;" title="Add to Cart">
                                    <i class="fas fa-shopping-cart" style="font-size: 28px; color: #3498db;"></i>
                                </button>
                            </form>
                            
                            <!-- Buy Now Form -->
                            <form id="buy-now-form" method="POST" action="buy-now.php" style="display: inline; margin: 0;">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="qty" id="buy-now-qty" value="1">
                                <!-- Size Hidden Fields -->
                                <input type="hidden" name="size_name" id="buy-size-name" value="<?php echo !empty($product_sizes) ? htmlspecialchars($product_sizes[0]['size_name']) : ''; ?>">
                                <input type="hidden" name="custom_width" id="buy-custom-width" value="">
                                <input type="hidden" name="custom_height" id="buy-custom-height" value="">
                                <input type="hidden" name="final_price" id="buy-final-price" value="<?php echo !empty($product_sizes) ? calculateFinalPrice($product_sizes[0]['price'], $commission) : $finalPrice; ?>">
                                
                                <button type="button" onclick="submitBuyNow()" class="btn btn-success" style="padding: 0.6rem 1.2rem;">
                                    <i class="fas fa-bolt"></i> Buy Now
                                </button>
                            </form>
                            
                            <!-- Inquiry Button -->
                            <a href="inquiry.php?product_id=<?php echo $product['id']; ?>" class="btn btn-secondary" style="padding: 0.6rem 1.2rem;">
                                <i class="fas fa-question-circle"></i> Inquiry
                            </a>
                        </div>
                        
                        <script>
                            // Quantity sync
                            document.getElementById('product-qty').addEventListener('change', function() {
                                var qty = this.value;
                                document.getElementById('cart-qty').value = qty;
                                document.getElementById('buy-now-qty').value = qty;
                            });

                            <?php if (!empty($product_sizes)): ?>
                            // Size Selection Logic
                            const sizeBtns = document.querySelectorAll('.size-btn');
                            const customDims = document.getElementById('custom-dimensions');
                            const customWidth = document.getElementById('custom-width');
                            const customHeight = document.getElementById('custom-height');
                            const displayPrice = document.getElementById('display-price');
                            
                            let currentSizeName = '<?php echo !empty($product_sizes) ? htmlspecialchars($product_sizes[0]['size_name']) : ''; ?>';
                            let isCustomSelected = false;
                            let currentPrice = <?php echo !empty($product_sizes) ? calculateFinalPrice($product_sizes[0]['price'], $commission) : $finalPrice; ?>;
                            let currentSqft = 0;

                            function updateHiddenFields() {
                                document.getElementById('cart-size-name').value = currentSizeName;
                                document.getElementById('buy-size-name').value = currentSizeName;
                                document.getElementById('cart-custom-width').value = customWidth.value;
                                document.getElementById('buy-custom-width').value = customWidth.value;
                                document.getElementById('cart-custom-height').value = customHeight.value;
                                document.getElementById('buy-custom-height').value = customHeight.value;
                                document.getElementById('cart-final-price').value = currentPrice;
                                document.getElementById('buy-final-price').value = currentPrice;
                            }

                            function calculateCustomPrice() {
                                if (isCustomSelected) {
                                    const w = parseFloat(customWidth.value) || 0;
                                    const h = parseFloat(customHeight.value) || 0;
                                    const area = w * h;
                                    document.getElementById('area-display').innerText = area.toFixed(2);
                                    
                                    if (area > 0) {
                                        currentPrice = area * currentSqft;
                                        displayPrice.innerText = '₹' + currentPrice.toFixed(2);
                                    } else {
                                        currentPrice = 0;
                                        displayPrice.innerText = 'Enter dimensions';
                                    }
                                    updateHiddenFields();
                                }
                            }

                            sizeBtns.forEach(btn => {
                                btn.addEventListener('click', function() {
                                    // Reset active state
                                    sizeBtns.forEach(b => {
                                        b.style.background = 'transparent';
                                        b.style.color = 'var(--text-color)';
                                    });
                                    this.style.background = 'var(--primary-color)';
                                    this.style.color = '#fff';

                                    currentSizeName = this.dataset.sizename;
                                    isCustomSelected = this.dataset.iscustom === 'true';
                                    
                                    if (isCustomSelected) {
                                        customDims.style.display = 'block';
                                        currentSqft = parseFloat(this.dataset.sqft);
                                        document.getElementById('sqft-display').innerText = currentSqft.toFixed(2);
                                        calculateCustomPrice();
                                    } else {
                                        customDims.style.display = 'none';
                                        currentPrice = parseFloat(this.dataset.price);
                                        displayPrice.innerText = '₹' + currentPrice.toFixed(2);
                                        // Clear custom inputs
                                        customWidth.value = '';
                                        customHeight.value = '';
                                        updateHiddenFields();
                                    }
                                });
                            });

                            customWidth.addEventListener('input', calculateCustomPrice);
                            customHeight.addEventListener('input', calculateCustomPrice);

                                // Trigger click on first size to initialize properly
                                if (sizeBtns.length > 0) {
                                    sizeBtns[0].click();
                                }
                            <?php endif; ?>

                            function validateSize() {
                                <?php if(empty($product_sizes)): ?> return true; <?php endif; ?>
                                
                                if (!currentSizeName) {
                                    alert('Please select a size first.');
                                    return false;
                                }
                                if (isCustomSelected) {
                                    const w = parseFloat(customWidth.value);
                                    const h = parseFloat(customHeight.value);
                                    if (isNaN(w) || isNaN(h) || w <= 0 || h <= 0) {
                                        alert('Please enter valid width and height for custom size.');
                                        return false;
                                    }
                                }
                                return true;
                            }

                            function submitCart() {
                                if (validateSize()) {
                                    document.getElementById('add-to-cart-form').submit();
                                }
                            }
                            
                            function submitBuyNow() {
                                if (validateSize()) {
                                    document.getElementById('buy-now-form').submit();
                                }
                            }
                        </script>
                    <?php else: ?>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; justify-content: center;">
                            <p style="margin: 0; color: #666;">
                                <i class="fas fa-info-circle"></i>
                                Please <a href="login.php" style="color: var(--accent-color);">login</a> to purchase.
                            </p>
                            <a href="inquiry.php?product_id=<?php echo $product['id']; ?>" class="btn btn-secondary" style="padding: 0.6rem 1.2rem;">
                                <i class="fas fa-question-circle"></i> Inquiry
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 1.5rem;">
                    <a href="products.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
