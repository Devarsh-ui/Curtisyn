<?php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once 'layout.php';

$supplierId = $_SESSION['user_id'];

$stats = [
    'products' => 0,
    'pending_orders' => 0,
    'total_orders' => 0
];

if ($db) {
    $stats['products'] = $db->prepare("SELECT COUNT(*) FROM supplier_products WHERE supplier_id = :supplier_id");
    $stats['products']->bindParam(':supplier_id', $supplierId);
    $stats['products']->execute();
    $stats['products'] = $stats['products']->fetchColumn();
    
    $stats['pending_orders'] = $db->prepare("SELECT COUNT(*) FROM supplier_orders WHERE supplier_id = :supplier_id AND status = 'pending'");
    $stats['pending_orders']->bindParam(':supplier_id', $supplierId);
    $stats['pending_orders']->execute();
    $stats['pending_orders'] = $stats['pending_orders']->fetchColumn();
    
    $stats['total_orders'] = $db->prepare("SELECT COUNT(*) FROM supplier_orders WHERE supplier_id = :supplier_id");
    $stats['total_orders']->bindParam(':supplier_id', $supplierId);
    $stats['total_orders']->execute();
    $stats['total_orders'] = $stats['total_orders']->fetchColumn();
}
?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-label">My Products</div>
        <div class="stat-value"><?php echo $stats['products']; ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-label">Pending Orders</div>
        <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-list"></i>
        </div>
        <div class="stat-label">Total Orders</div>
        <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
    </div>
</div>

<!-- Quick Actions -->
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-bolt" style="color: var(--warning-color);"></i> Quick Actions</h2>
    </div>
    <div class="card-body">
        <div class="quick-actions">
            <a href="/supplier/material-form.php" class="quick-action-card">
                <i class="fas fa-plus-circle"></i>
                <div>Add Product</div>
            </a>
            <a href="/supplier/materials.php" class="quick-action-card">
                <i class="fas fa-boxes"></i>
                <div>My Products</div>
            </a>
            <a href="/supplier/orders.php" class="quick-action-card">
                <i class="fas fa-clipboard-list"></i>
                <div>View Orders</div>
            </a>
            <?php if ($stats['pending_orders'] > 0): ?>
            <a href="/supplier/orders.php?status=pending" class="quick-action-card" style="background: linear-gradient(135deg, #ff6b6b, #ee5a5a); color: white;">
                <i class="fas fa-bell" style="color: white;"></i>
                <div><?php echo $stats['pending_orders']; ?> Pending Orders</div>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Overview -->
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-chart-bar" style="color: var(--success-color);"></i> Business Overview</h2>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; color: white;">
                <i class="fas fa-cubes" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.9;"></i>
                <div style="font-size: 2rem; font-weight: 700;"><?php echo $stats['products']; ?></div>
                <div style="opacity: 0.9;">Products Listed</div>
            </div>
            <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 10px; color: white;">
                <i class="fas fa-shopping-cart" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.9;"></i>
                <div style="font-size: 2rem; font-weight: 700;"><?php echo $stats['total_orders']; ?></div>
                <div style="opacity: 0.9;">Total Orders Received</div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layout-footer.php'; ?>
