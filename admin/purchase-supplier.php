<?php
$pageTitle = 'Purchase From Supplier';
$activePage = 'purchase';
require_once 'layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$products = [];
$suppliers = [];
$selectedSupplier = $_GET['supplier'] ?? '';

if ($db) {
    $suppStmt = $db->query("SELECT id, full_name FROM users WHERE role = 'supplier' AND status = 'active' ORDER BY full_name");
    $suppliers = $suppStmt->fetchAll();
    
    $sql = "
        SELECT sp.*, u.full_name as supplier_name, c.name as category_name
        FROM supplier_products sp
        JOIN users u ON sp.supplier_id = u.id
        LEFT JOIN categories c ON sp.category_id = c.id
        WHERE sp.status = 'active'
    ";
    
    if ($selectedSupplier) {
        $sql .= " AND sp.supplier_id = :supplier_id";
    }
    
    $sql .= " ORDER BY sp.created_at DESC";
    
    $stmt = $db->prepare($sql);
    if ($selectedSupplier) {
        $stmt->bindParam(':supplier_id', $selectedSupplier);
    }
    $stmt->execute();
    $products = $stmt->fetchAll();
}
?>

<div class="content-card">
    <div class="card-header">
        <h2 class="card-title">Filter by Supplier</h2>
    </div>
    <div class="card-body">
        <form method="GET" action="" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <select name="supplier" class="form-input" style="width: auto; min-width: 250px;">
                <option value="">All Suppliers</option>
                <?php foreach ($suppliers as $supp): ?>
                <option value="<?php echo $supp['id']; ?>" <?php echo $selectedSupplier == $supp['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($supp['full_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($selectedSupplier): ?>
            <a href="purchase-supplier.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="products-grid" style="margin-top: 1.5rem;">
    <?php foreach ($products as $product): ?>
    <div class="product-card">
        <div style="height: 200px; background: #f5f5f5; display: flex; align-items: center; justify-content: center;">
            <?php if ($product['image']): ?>
                <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="" style="max-width: 100%; max-height: 100%; object-fit: cover;">
            <?php else: ?>
                <span style="color: #999;">No Image</span>
            <?php endif; ?>
        </div>
        <div class="product-info">
            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
            <p class="product-category"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
            <p style="color: #666; font-size: 0.9rem; margin-bottom: 0.5rem;">
                Supplier: <?php echo htmlspecialchars($product['supplier_name']); ?>
            </p>
            <p style="color: #666; font-size: 0.9rem; margin-bottom: 0.5rem;">
                Unit: <?php echo htmlspecialchars($product['unit_type']); ?>
            </p>
            <?php if ($product['dimensions']): ?>
            <p style="color: #666; font-size: 0.9rem; margin-bottom: 0.5rem;">
                Dimensions: <?php echo htmlspecialchars($product['dimensions']); ?>
            </p>
            <?php endif; ?>
            <p class="product-price">₹<?php echo number_format($product['price_per_unit'], 2); ?> / <?php echo htmlspecialchars($product['unit_type']); ?></p>
            
            <form method="POST" action="purchase-order.php" class="purchase-form" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="supplier_product_id" value="<?php echo $product['id']; ?>">
                <input type="hidden" name="price_per_unit" value="<?php echo $product['price_per_unit']; ?>">
                
                <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">
                    <input type="number" name="quantity" class="form-input quantity-input" 
                           placeholder="Qty" min="1" required 
                           style="width: 80px; padding: 0.5rem;"
                           data-price="<?php echo $product['price_per_unit']; ?>">
                    <span style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($product['unit_type']); ?></span>
                </div>
                
                <div class="cost-display" style="font-weight: 600; color: #27ae60; margin-bottom: 0.5rem;">
                    Total: ₹<span class="total-cost">0.00</span>
                </div>
                
                <button type="submit" class="btn btn-primary place-order-btn" style="width: 100%;">Place Order</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if (empty($products)): ?>
    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: #666;">
        No supplier products available.
    </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.purchase-form').forEach(form => {
    const quantityInput = form.querySelector('.quantity-input');
    const totalCostSpan = form.querySelector('.total-cost');
    const pricePerUnit = parseFloat(quantityInput.dataset.price);
    const submitBtn = form.querySelector('.place-order-btn');
    
    quantityInput.addEventListener('input', function() {
        const quantity = parseInt(this.value) || 0;
        const total = quantity * pricePerUnit;
        totalCostSpan.textContent = total.toFixed(2);
    });
    
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';
    });
});
</script>

<?php require_once 'layout-footer.php'; ?>
