<?php
$pageTitle = 'Manage Products';
$activePage = 'products';
require_once 'layout.php';
require_once __DIR__ . '/../config/database.php';

$products = [];
if ($db) {
    $stmt = $db->query("SELECT p.*, c.name as category_name, u.full_name as creator_name FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN users u ON p.created_by = u.id ORDER BY p.created_at DESC");
    $products = $stmt->fetchAll();
}
?>

<div style="margin-bottom: 1.5rem;">
    <a href="product-form.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Product
    </a>
</div>

<div class="content-card">
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                    <td>₹<?php echo number_format($product['price'], 2); ?></td>
                    <td><?php echo $product['stock']; ?></td>
                    <td><span class="badge badge-<?php echo $product['status']; ?>"><?php echo ucfirst($product['status']); ?></span></td>
                    <td>
                        <a href="product-form.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'layout-footer.php'; ?>
