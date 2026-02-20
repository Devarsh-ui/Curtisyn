<?php
$pageTitle = 'My Products';
$activePage = 'materials';
require_once 'layout.php';
require_once __DIR__ . '/../config/database.php';

$supplierId = $_SESSION['user_id'];

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM raw_materials WHERE id = :id AND supplier_id = :supplier_id");
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':supplier_id', $supplierId);
    $stmt->execute();
    header('Location: ' . BASE_URL . 'supplier/materials.php');
    exit();
}

$materials = [];
if ($db) {
    $stmt = $db->prepare("SELECT * FROM raw_materials WHERE supplier_id = :supplier_id ORDER BY created_at DESC");
    $stmt->bindParam(':supplier_id', $supplierId);
    $stmt->execute();
    $materials = $stmt->fetchAll();
}
?>

<div style="margin-bottom: 1.5rem;">
    <a href="material-form.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Product
    </a>
</div>

<div class="content-card">
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Price/Unit</th>
                    <th>Dimensions</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materials as $material): ?>
                <tr>
                    <td><?php echo htmlspecialchars($material['name']); ?></td>
                    <td><?php echo htmlspecialchars($material['category']); ?></td>
                    <td><?php echo $material['unit']; ?></td>
                    <td>₹<?php echo number_format($material['price_per_unit'], 2); ?></td>
                    <td><?php echo htmlspecialchars($material['dimensions'] ?? 'N/A'); ?></td>
                    <td>
                        <a href="material-form.php?id=<?php echo $material['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                        <a href="materials.php?delete=<?php echo $material['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this material?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'layout-footer.php'; ?>
