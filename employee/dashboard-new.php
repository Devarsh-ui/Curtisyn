<?php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once 'layout.php';

$stats = [
    'products' => 0,
    'enabled' => 0,
    'disabled' => 0
];

if ($db) {
    $stats['products'] = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $stats['enabled'] = $db->query("SELECT COUNT(*) FROM products WHERE status = 'enabled'")->fetchColumn();
    $stats['disabled'] = $db->query("SELECT COUNT(*) FROM products WHERE status = 'disabled'")->fetchColumn();
}
?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-label">Total Products</div>
        <div class="stat-value"><?php echo $stats['products']; ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-label">Enabled Products</div>
        <div class="stat-value"><?php echo $stats['enabled']; ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-pause-circle"></i>
        </div>
        <div class="stat-label">Disabled Products</div>
        <div class="stat-value"><?php echo $stats['disabled']; ?></div>
    </div>
</div>

<!-- Quick Actions -->
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-bolt" style="color: var(--warning-color);"></i> Quick Actions</h2>
    </div>
    <div class="card-body">
        <div class="quick-actions">
            <a href="/employee/product-form.php" class="quick-action-card">
                <i class="fas fa-plus-circle"></i>
                <div>Add Product</div>
            </a>
            <a href="/employee/products.php" class="quick-action-card">
                <i class="fas fa-boxes"></i>
                <div>Manage Products</div>
            </a>
            <a href="/employee/products.php?status=enabled" class="quick-action-card">
                <i class="fas fa-check" style="color: var(--success-color);"></i>
                <div>Enabled Products</div>
            </a>
            <a href="/employee/products.php?status=disabled" class="quick-action-card">
                <i class="fas fa-ban" style="color: var(--danger-color);"></i>
                <div>Disabled Products</div>
            </a>
        </div>
    </div>
</div>

<!-- Overview -->
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-chart-pie" style="color: var(--accent-color);"></i> Inventory Overview</h2>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; color: white;">
                <i class="fas fa-warehouse" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.9;"></i>
                <div style="font-size: 2rem; font-weight: 700;"><?php echo $stats['products']; ?></div>
                <div style="opacity: 0.9;">Total Products</div>
            </div>
            <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border-radius: 10px; color: white;">
                <i class="fas fa-check-double" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.9;"></i>
                <div style="font-size: 2rem; font-weight: 700;"><?php echo $stats['enabled']; ?></div>
                <div style="opacity: 0.9;">Active Products</div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layout-footer.php'; ?>
