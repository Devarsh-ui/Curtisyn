<?php
$pageTitle = 'Supplier Orders';
$activePage = 'orders';
require_once 'layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$orders = [];

if ($db) {
    $stmt = $db->query("
        SELECT so.*, sp.name as product_name, sp.image, u.full_name as supplier_name
        FROM supplier_orders so
        JOIN supplier_products sp ON so.supplier_product_id = sp.id
        JOIN users u ON so.supplier_id = u.id
        ORDER BY so.order_date DESC
    ");
    $orders = $stmt->fetchAll();
}
?>

<?php if (isset($_GET['success'])): ?>
    <?php echo displaySuccess($_GET['success']); ?>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <?php echo displayError($_GET['error']); ?>
<?php endif; ?>

<div class="orders-filter">
    <a href="purchase-supplier.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> New Purchase
    </a>
</div>

<?php if (empty($orders)): ?>
    <div class="content-card">
        <div class="card-body" style="text-align: center; padding: 3rem;">
            <i class="fas fa-box-open" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
            <p>No orders yet.</p>
        </div>
    </div>
<?php else: ?>
    <!-- Desktop Table View -->
    <div class="content-card desktop-table">
        <div class="card-body" style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Product</th>
                        <th>Supplier</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($order['order_id']); ?></strong></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <?php if ($order['image']): ?>
                                    <img src="../uploads/products/<?php echo htmlspecialchars($order['image']); ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                                <?php endif; ?>
                                <span style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($order['product_name']); ?></span>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                        <td><?php echo $order['requested_quantity']; ?> <?php if($order['approved_quantity']): ?><small style="color: #27ae60;">(<?php echo $order['approved_quantity']; ?>)</small><?php endif; ?></td>
                        <td style="font-weight: 600;">₹<?php echo number_format($order['total_cost'], 2); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $order['status']; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                            </span>
                            <?php if ($order['is_synced']): ?>
                                <span class="badge badge-active" style="margin-left: 5px;">Synced</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile/Laptop Card View -->
    <div class="mobile-cards">
        <?php foreach ($orders as $order): ?>
        <div class="order-card">
            <div class="order-card-header">
                <div class="order-id"><?php echo htmlspecialchars($order['order_id']); ?></div>
                <span class="badge badge-<?php echo $order['status']; ?>"><?php echo ucwords(str_replace('_', ' ', $order['status'])); ?></span>
            </div>
            <div class="order-card-body">
                <div class="order-info-row">
                    <span class="label">Product:</span>
                    <span class="value">
                        <?php if ($order['image']): ?>
                            <img src="../uploads/products/<?php echo htmlspecialchars($order['image']); ?>" alt="" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px; vertical-align: middle; margin-right: 5px;">
                        <?php endif; ?>
                        <?php echo htmlspecialchars($order['product_name']); ?>
                    </span>
                </div>
                <div class="order-info-row">
                    <span class="label">Supplier:</span>
                    <span class="value"><?php echo htmlspecialchars($order['supplier_name']); ?></span>
                </div>
                <div class="order-info-row">
                    <span class="label">Requested:</span>
                    <span class="value"><?php echo $order['requested_quantity']; ?></span>
                </div>
                <?php if ($order['approved_quantity']): ?>
                <div class="order-info-row">
                    <span class="label">Approved:</span>
                    <span class="value" style="color: #27ae60;"><?php echo $order['approved_quantity']; ?></span>
                </div>
                <?php endif; ?>
                <div class="order-info-row">
                    <span class="label">Price/Unit:</span>
                    <span class="value">₹<?php echo number_format($order['price_per_unit'], 2); ?></span>
                </div>
                <div class="order-info-row">
                    <span class="label">Total:</span>
                    <span class="value price">₹<?php echo number_format($order['total_cost'], 2); ?></span>
                </div>
                <div class="order-info-row">
                    <span class="label">Date:</span>
                    <span class="value"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'layout-footer.php'; ?>
