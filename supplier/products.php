<?php
require_once 'auth-check.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'My Products';

$database = new Database();
$db = $database->connect();

$supplierId = $_SESSION['user_id'];
$products = [];

if ($db) {
    $stmt = $db->prepare("
        SELECT sp.*, c.name as category_name 
        FROM supplier_products sp 
        LEFT JOIN categories c ON sp.category_id = c.id 
        WHERE sp.supplier_id = :supplier_id 
        ORDER BY sp.created_at DESC
    ");
    $stmt->bindParam(':supplier_id', $supplierId);
    $stmt->execute();
    $products = $stmt->fetchAll();
}
?>

<section class="section">
    <div class="container">
        <div class="page-header">
            <h1 class="section-title">My Products</h1>
            <a href="product-form.php" class="btn btn-primary">Add Product</a>
        </div>
        
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price/Unit</th>
                        <th>Unit</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <?php if ($product['image']): ?>
                                <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; background: #eee; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #999;">No Img</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                        <td>₹<?php echo number_format($product['price_per_unit'], 2); ?></td>
                        <td><?php echo htmlspecialchars($product['unit_type']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $product['status'] === 'active' ? 'active' : 'inactive'; ?>">
                                <?php echo ucfirst($product['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="product-form.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                            <a href="product-form.php?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this product?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem;">No products added yet.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
