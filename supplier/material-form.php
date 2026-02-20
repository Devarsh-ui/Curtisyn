<?php
$pageTitle = 'Add Product';
$activePage = 'add-material';
require_once 'layout.php';
require_once __DIR__ . '/../config/database.php';

$supplierId = $_SESSION['user_id'];

$material = [
    'id' => 0,
    'name' => '',
    'category' => '',
    'description' => '',
    'unit' => 'kg',
    'price_per_unit' => '',
    'dimensions' => ''
];

$error = '';
$success = '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $pageTitle = 'Edit Product';
    $activePage = 'materials';
    
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM raw_materials WHERE id = :id AND supplier_id = :supplier_id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':supplier_id', $supplierId);
        $stmt->execute();
        $material = $stmt->fetch();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $category = sanitizeInput($_POST['category'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $unit = $_POST['unit'] ?? 'kg';
        $pricePerUnit = floatval($_POST['price_per_unit'] ?? 0);
        $dimensions = sanitizeInput($_POST['dimensions'] ?? '');
        
        if (empty($name) || $pricePerUnit <= 0) {
            $error = 'Name and valid price required.';
        } else {
            $imageName = $material['image'] ?? '';
            
            if (!empty($_FILES['image']['name'])) {
                $upload = uploadFile($_FILES['image'], __DIR__ . '/../uploads/materials/');
                if (isset($upload['error'])) {
                    $error = $upload['error'];
                } else {
                    $imageName = $upload['filename'];
                }
            }
            
            if (empty($error)) {
                if ($material['id'] > 0) {
                    $stmt = $db->prepare("UPDATE raw_materials SET name = :name, category = :category, description = :description, unit = :unit, price_per_unit = :price_per_unit, dimensions = :dimensions, image = :image WHERE id = :id AND supplier_id = :supplier_id");
                    $stmt->bindParam(':id', $material['id']);
                    $stmt->bindParam(':supplier_id', $supplierId);
                } else {
                    $stmt = $db->prepare("INSERT INTO raw_materials (supplier_id, name, category, description, unit, price_per_unit, dimensions, image) VALUES (:supplier_id, :name, :category, :description, :unit, :price_per_unit, :dimensions, :image)");
                    $stmt->bindParam(':supplier_id', $supplierId);
                }
                
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':category', $category);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':unit', $unit);
                $stmt->bindParam(':price_per_unit', $pricePerUnit);
                $stmt->bindParam(':dimensions', $dimensions);
                $stmt->bindParam(':image', $imageName);
                
                if ($stmt->execute()) {
                    header('Location: ' . BASE_URL . 'supplier/materials.php');
                    exit();
                } else {
                    $error = 'Failed to save material.';
                }
            }
        }
    }
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
                <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($material['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Category</label>
                <input type="text" name="category" class="form-input" value="<?php echo htmlspecialchars($material['category']); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="3"><?php echo htmlspecialchars($material['description']); ?></textarea>
            </div>
            
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Unit</label>
                    <select name="unit" class="form-input">
                        <option value="kg" <?php echo $material['unit'] === 'kg' ? 'selected' : ''; ?>>Kilogram (kg)</option>
                        <option value="meter" <?php echo $material['unit'] === 'meter' ? 'selected' : ''; ?>>Meter</option>
                        <option value="litre" <?php echo $material['unit'] === 'litre' ? 'selected' : ''; ?>>Litre</option>
                        <option value="piece" <?php echo $material['unit'] === 'piece' ? 'selected' : ''; ?>>Piece</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Price per Unit (₹)</label>
                    <input type="number" name="price_per_unit" class="form-input" step="0.01" min="0.01" value="<?php echo $material['price_per_unit']; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Dimensions (optional)</label>
                <input type="text" name="dimensions" class="form-input" value="<?php echo htmlspecialchars($material['dimensions']); ?>" placeholder="e.g., 10m x 2m">
            </div>
            
            <div class="form-group">
                <label class="form-label">Image</label>
                <input type="file" name="image" class="form-input" accept="image/*">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Product</button>
                <a href="materials.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'layout-footer.php'; ?>
