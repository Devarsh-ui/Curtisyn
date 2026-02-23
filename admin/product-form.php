<?php
// Process form BEFORE including layout.php to allow header() redirects
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$database = new Database();
$db = $database->connect();

$product = [
    'id' => 0,
    'name' => '',
    'category_id' => '',
    'description' => '',
    'price' => '',
    'stock' => '',
    'status' => 'enabled'
];

$error = '';
$success = '';
$product_sizes = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    if ($db) {
        $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $product = $stmt->fetch();
        
        // Fetch existing sizes
        if ($product) {
            $sizeStmt = $db->prepare("SELECT * FROM product_sizes WHERE product_id = :id ORDER BY id ASC");
            $sizeStmt->bindParam(':id', $id);
            $sizeStmt->execute();
            $product_sizes = $sizeStmt->fetchAll();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $categoryId = intval($_POST['category_id'] ?? 0);
        $description = sanitizeInput($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $status = $_POST['status'] ?? 'enabled';

        if (empty($name) || $price <= 0) {
            $error = 'Name and valid price required.';
        } else {
            $imageName = $product['image'] ?? '';

            if (!empty($_FILES['image']['name'])) {
                $upload = uploadFile($_FILES['image'], __DIR__ . '/../uploads/products/');
                if (isset($upload['error'])) {
                    $error = $upload['error'];
                } else {
                    $imageName = $upload['filename'];
                }
            }

            if (empty($error)) {
                if ($product['id'] > 0) {
                    $stmt = $db->prepare("UPDATE products SET name = :name, category_id = :category_id, description = :description, price = :price, stock = :stock, status = :status, image = :image WHERE id = :id");
                    $stmt->bindParam(':id', $product['id']);
                } else {
                    $stmt = $db->prepare("INSERT INTO products (name, category_id, description, price, stock, status, image, created_by) VALUES (:name, :category_id, :description, :price, :stock, :status, :image, :created_by)");
                    $createdBy = $_SESSION['user_id'];
                    $stmt->bindParam(':created_by', $createdBy);
                }

                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':category_id', $categoryId);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':stock', $stock);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':image', $imageName);

                if ($stmt->execute()) {
                    $productId = $product['id'] > 0 ? $product['id'] : $db->lastInsertId();
                    
                    // Handle Sizes
                    $sizeNames = $_POST['size_name'] ?? [];
                    $sizeWidths = $_POST['size_width'] ?? [];
                    $sizeHeights = $_POST['size_height'] ?? [];
                    $sizePrices = $_POST['size_price'] ?? [];
                    $sizePricePerSqft = $_POST['size_price_per_sqft'] ?? [];
                    
                    // Clear existing sizes to easily handle updates/deletions (simple approach)
                    $delStmt = $db->prepare("DELETE FROM product_sizes WHERE product_id = :product_id");
                    $delStmt->bindParam(':product_id', $productId);
                    $delStmt->execute();
                    
                    for ($i = 0; $i < count($sizeNames); $i++) {
                        if (!empty($sizeNames[$i])) {
                            $sName = sanitizeInput($sizeNames[$i]);
                            $sWidth = !empty($sizeWidths[$i]) ? floatval($sizeWidths[$i]) : null;
                            $sHeight = !empty($sizeHeights[$i]) ? floatval($sizeHeights[$i]) : null;
                            $sPrice = !empty($sizePrices[$i]) ? floatval($sizePrices[$i]) : 0;
                            $sPriceSqft = !empty($sizePricePerSqft[$i]) ? floatval($sizePricePerSqft[$i]) : null;
                            
                            $sStmt = $db->prepare("INSERT INTO product_sizes (product_id, size_name, width, height, price, price_per_sqft) VALUES (:product_id, :size_name, :width, :height, :price, :price_per_sqft)");
                            $sStmt->bindParam(':product_id', $productId);
                            $sStmt->bindParam(':size_name', $sName);
                            $sStmt->bindParam(':width', $sWidth);
                            $sStmt->bindParam(':height', $sHeight);
                            $sStmt->bindParam(':price', $sPrice);
                            $sStmt->bindParam(':price_per_sqft', $sPriceSqft);
                            $sStmt->execute();
                        }
                    }

                    $success = $product['id'] > 0 ? 'Product updated.' : 'Product added.';
                    if ($product['id'] === 0) {
                        // Redirect BEFORE any HTML output
                        header('Location: ' . BASE_URL . 'admin/products.php');
                        exit();
                    } else {
                        // Refresh sizes after update
                        $sizeStmt = $db->prepare("SELECT * FROM product_sizes WHERE product_id = :id ORDER BY id ASC");
                        $sizeStmt->bindParam(':id', $product['id']);
                        $sizeStmt->execute();
                        $product_sizes = $sizeStmt->fetchAll();
                    }
                } else {
                    $error = 'Failed to save product.';
                }
            }
        }
    }
}

// Set page variables and include layout AFTER processing (so redirect above works)
$pageTitle = ($product['id'] > 0) ? 'Edit Product' : 'Add Product';
$activePage = ($product['id'] > 0) ? 'products' : 'add-product';
require_once 'layout.php';

$categories = [];
if ($db) {
    $stmt = $db->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
}

$csrfToken = generateCsrfToken();
?>

<div class="content-card" style="max-width: 700px;">
    <div class="card-header">
        <h2 class="card-title"><?php echo $pageTitle; ?></h2>
    </div>
    <div class="card-body">
        <?php 
        if ($error) echo displayError($error);
        if ($success) echo displaySuccess($success);
        ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="form-group">
                <label class="form-label">Product Name</label>
                <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-input">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Price (₹)</label>
                    <input type="number" name="price" class="form-input" step="0.01" min="0" value="<?php echo $product['price']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Stock</label>
                    <input type="number" name="stock" class="form-input" min="0" value="<?php echo $product['stock']; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-input">
                    <option value="enabled" <?php echo $product['status'] === 'enabled' ? 'selected' : ''; ?>>Enabled</option>
                    <option value="disabled" <?php echo $product['status'] === 'disabled' ? 'selected' : ''; ?>>Disabled</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Image</label>
                <input type="file" name="image" class="form-input" accept="image/*">
            </div>

            <!-- Product Sizes Section -->
            <div class="form-group" style="margin-top: 2rem; border-top: 1px solid #ddd; padding-top: 1rem;">
                <h3 style="margin-bottom: 1rem;">Product Sizes & Variations</h3>
                <p style="font-size: 0.9rem; color: #666; margin-bottom: 1rem;">Add predefined sizes (e.g., 3x4 ft) or a "Custom" size option.</p>
                
                <div id="sizes-container">
                    <?php if (empty($product_sizes)): ?>
                        <!-- Default empty row -->
                        <div class="size-row" style="background: #f9f9f9; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #eee; position: relative;">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem;">
                                <div>
                                    <label class="form-label">Size Name</label>
                                    <input type="text" name="size_name[]" class="form-input" placeholder="e.g., 3x4 ft or Custom" required>
                                </div>
                                <div class="dim-group">
                                    <label class="form-label">Width</label>
                                    <input type="number" name="size_width[]" class="form-input" step="0.01" placeholder="Optional">
                                </div>
                                <div class="dim-group">
                                    <label class="form-label">Height</label>
                                    <input type="number" name="size_height[]" class="form-input" step="0.01" placeholder="Optional">
                                </div>
                                <div>
                                    <label class="form-label">Fixed Price (₹)</label>
                                    <input type="number" name="size_price[]" class="form-input" step="0.01" placeholder="0 if calculated">
                                </div>
                                <div>
                                    <label class="form-label">Price / SqFt (₹)</label>
                                    <input type="number" name="size_price_per_sqft[]" class="form-input" step="0.01" placeholder="For custom sizes">
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger remove-size" style="position: absolute; top: 10px; right: 10px;">&times;</button>
                        </div>
                    <?php else: ?>
                        <!-- Existing sizes -->
                        <?php foreach ($product_sizes as $size): ?>
                            <div class="size-row" style="background: #f9f9f9; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #eee; position: relative;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem;">
                                    <div>
                                        <label class="form-label">Size Name</label>
                                        <input type="text" name="size_name[]" class="form-input" value="<?php echo htmlspecialchars($size['size_name']); ?>" required>
                                    </div>
                                    <div class="dim-group">
                                        <label class="form-label">Width</label>
                                        <input type="number" name="size_width[]" class="form-input" step="0.01" value="<?php echo htmlspecialchars($size['width'] ?? ''); ?>">
                                    </div>
                                    <div class="dim-group">
                                        <label class="form-label">Height</label>
                                        <input type="number" name="size_height[]" class="form-input" step="0.01" value="<?php echo htmlspecialchars($size['height'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Fixed Price (₹)</label>
                                        <input type="number" name="size_price[]" class="form-input" step="0.01" value="<?php echo htmlspecialchars($size['price']); ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Price / SqFt (₹)</label>
                                        <input type="number" name="size_price_per_sqft[]" class="form-input" step="0.01" value="<?php echo htmlspecialchars($size['price_per_sqft'] ?? ''); ?>">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger remove-size" style="position: absolute; top: 10px; right: 10px;">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <button type="button" id="add-size-btn" class="btn btn-sm btn-secondary" style="margin-top: 0.5rem;">+ Add Size Variation</button>
            </div>
            
            <script>
                document.getElementById('add-size-btn').addEventListener('click', function() {
                    const container = document.getElementById('sizes-container');
                    const row = document.createElement('div');
                    row.className = 'size-row';
                    row.style.cssText = 'background: #f9f9f9; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #eee; position: relative;';
                    
                    row.innerHTML = `
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem;">
                            <div>
                                <label class="form-label">Size Name</label>
                                <input type="text" name="size_name[]" class="form-input" placeholder="e.g., 3x4 ft or Custom" required>
                            </div>
                            <div class="dim-group">
                                <label class="form-label">Width</label>
                                <input type="number" name="size_width[]" class="form-input" step="0.01" placeholder="Optional">
                            </div>
                            <div class="dim-group">
                                <label class="form-label">Height</label>
                                <input type="number" name="size_height[]" class="form-input" step="0.01" placeholder="Optional">
                            </div>
                            <div>
                                <label class="form-label">Fixed Price (₹)</label>
                                <input type="number" name="size_price[]" class="form-input" step="0.01" placeholder="0 if calculated">
                            </div>
                            <div>
                                <label class="form-label">Price / SqFt (₹)</label>
                                <input type="number" name="size_price_per_sqft[]" class="form-input" step="0.01" placeholder="For custom sizes">
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-danger remove-size" style="position: absolute; top: 10px; right: 10px;">&times;</button>
                    `;
                    
                    container.appendChild(row);
                });

                document.getElementById('sizes-container').addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-size')) {
                        if (document.querySelectorAll('.size-row').length > 1) {
                            e.target.closest('.size-row').remove();
                        } else {
                            alert('At least one size variation is required.');
                        }
                    }
                });
            </script>
            
            <div class="form-actions" style="margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">Save Product</button>
                <a href="<?php echo BASE_URL; ?>admin/products.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'layout-footer.php'; ?>
