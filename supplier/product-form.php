<?php
require_once 'auth-check.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Add Product';
$database = new Database();
$db = $database->connect();

$supplierId = $_SESSION['user_id'];
$product = null;
$error = '';
$success = '';

$categories = [];
if ($db) {
    $catStmt = $db->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $catStmt->fetchAll();
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    $checkStmt = $db->prepare("SELECT id FROM supplier_products WHERE id = :id AND supplier_id = :supplier_id");
    $checkStmt->bindParam(':id', $deleteId);
    $checkStmt->bindParam(':supplier_id', $supplierId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        $deleteStmt = $db->prepare("DELETE FROM supplier_products WHERE id = :id");
        $deleteStmt->bindParam(':id', $deleteId);
        $deleteStmt->execute();
        redirect('products.php');
    }
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $pageTitle = 'Edit Product';
    $editStmt = $db->prepare("SELECT * FROM supplier_products WHERE id = :id AND supplier_id = :supplier_id");
    $editStmt->bindParam(':id', $_GET['id']);
    $editStmt->bindParam(':supplier_id', $supplierId);
    $editStmt->execute();
    $product = $editStmt->fetch();
    
    if (!$product) {
        redirect('products.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $categoryId = $_POST['category_id'] ?? null;
        $description = sanitizeInput($_POST['description'] ?? '');
        $pricePerUnit = $_POST['price_per_unit'] ?? 0;
        $unitType = $_POST['unit_type'] ?? 'piece';
        $dimensions = sanitizeInput($_POST['dimensions'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $productId = $_POST['product_id'] ?? null;
        
        if (empty($name) || empty($pricePerUnit)) {
            $error = 'Product name and price are required.';
        } elseif (!is_numeric($pricePerUnit) || $pricePerUnit <= 0) {
            $error = 'Price must be a positive number.';
        } else {
            $imageName = $product ? $product['image'] : null;
            
            if (!empty($_FILES['image']['name'])) {
                $upload = uploadFile($_FILES['image'], __DIR__ . '/../uploads/products/');
                if (isset($upload['error'])) {
                    $error = $upload['error'];
                } else {
                    $imageName = $upload['filename'];
                    if ($product && $product['image'] && file_exists(__DIR__ . '/../uploads/products/' . $product['image'])) {
                        unlink(__DIR__ . '/../uploads/products/' . $product['image']);
                    }
                }
            }
            
            if (empty($error)) {
                if ($productId) {
                    $stmt = $db->prepare("
                        UPDATE supplier_products 
                        SET name = :name, category_id = :category_id, description = :description,
                            price_per_unit = :price_per_unit, unit_type = :unit_type, dimensions = :dimensions,
                            image = :image, status = :status
                        WHERE id = :id AND supplier_id = :supplier_id
                    ");
                    $stmt->bindParam(':id', $productId);
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO supplier_products 
                        (supplier_id, name, category_id, description, price_per_unit, unit_type, dimensions, image, status)
                        VALUES (:supplier_id, :name, :category_id, :description, :price_per_unit, :unit_type, :dimensions, :image, :status)
                    ");
                    $stmt->bindParam(':supplier_id', $supplierId);
                }
                
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':category_id', $categoryId);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':price_per_unit', $pricePerUnit);
                $stmt->bindParam(':unit_type', $unitType);
                $stmt->bindParam(':dimensions', $dimensions);
                $stmt->bindParam(':image', $imageName);
                $stmt->bindParam(':status', $status);
                
                if ($stmt->execute()) {
                    redirect('products.php');
                } else {
                    $error = 'Failed to save product.';
                }
            }
        }
    }
}

$csrfToken = generateCsrfToken();
?>

<section class="section">
    <div class="container">
        <h1 class="section-title"><?php echo $product ? 'Edit Product' : 'Add Product'; ?></h1>
        
        <div class="form-container">
            <?php if ($error) echo displayError($error); ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <?php if ($product): ?>
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">Product Name *</label>
                    <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-input">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($product['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="3"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Price Per Unit (₹) *</label>
                        <input type="number" name="price_per_unit" class="form-input" step="0.01" min="0" value="<?php echo htmlspecialchars($product['price_per_unit'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit Type</label>
                        <select name="unit_type" class="form-input">
                            <option value="piece" <?php echo ($product['unit_type'] ?? '') === 'piece' ? 'selected' : ''; ?>>Piece</option>
                            <option value="kg" <?php echo ($product['unit_type'] ?? '') === 'kg' ? 'selected' : ''; ?>>Kg</option>
                            <option value="meter" <?php echo ($product['unit_type'] ?? '') === 'meter' ? 'selected' : ''; ?>>Meter</option>
                            <option value="litre" <?php echo ($product['unit_type'] ?? '') === 'litre' ? 'selected' : ''; ?>>Litre</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Dimensions (optional)</label>
                    <input type="text" name="dimensions" class="form-input" value="<?php echo htmlspecialchars($product['dimensions'] ?? ''); ?>" placeholder="e.g., 10x20x30 cm">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Product Image</label>
                    <?php if ($product && $product['image']): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="" style="max-width: 150px; border-radius: 5px;">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="image" class="form-input" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="active" <?php echo ($product['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($product['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?php echo $product ? 'Update' : 'Add'; ?> Product</button>
                    <a href="products.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
