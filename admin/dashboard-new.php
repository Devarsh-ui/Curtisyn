<?php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once 'layout.php';

$stats = [
    'products' => 0,
    'customers' => 0,
    'suppliers' => 0,
    'pending_orders' => 0
];

if ($db) {
    $stats['products'] = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $stats['customers'] = $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
    $stats['suppliers'] = $db->query("SELECT COUNT(*) FROM users WHERE role = 'supplier'")->fetchColumn();
    $stats['pending_orders'] = $db->query("SELECT COUNT(*) FROM supplier_orders WHERE status = 'pending'")->fetchColumn();
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
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-label">Customers</div>
        <div class="stat-value"><?php echo $stats['customers']; ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-truck"></i>
        </div>
        <div class="stat-label">Suppliers</div>
        <div class="stat-value"><?php echo $stats['suppliers']; ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-label">Pending Orders</div>
        <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
    </div>
</div>

<!-- Quick Actions -->
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-bolt" style="color: var(--warning-color);"></i> Quick Actions</h2>
    </div>
    <div class="card-body">
        <div class="quick-actions">
            <a href="/admin/product-form.php" class="quick-action-card">
                <i class="fas fa-plus-circle"></i>
                <div>Add Product</div>
            </a>
            <a href="/admin/purchase-supplier.php" class="quick-action-card">
                <i class="fas fa-shopping-cart"></i>
                <div>Purchase</div>
            </a>
            <a href="/admin/supplier-orders.php" class="quick-action-card">
                <i class="fas fa-clipboard-list"></i>
                <div>View Orders</div>
            </a>
            <a href="/admin/customers.php" class="quick-action-card">
                <i class="fas fa-user-friends"></i>
                <div>Customers</div>
            </a>
            <a href="/admin/suppliers.php" class="quick-action-card">
                <i class="fas fa-truck-loading"></i>
                <div>Suppliers</div>
            </a>
            <a href="/admin/commission-settings.php" class="quick-action-card">
                <i class="fas fa-percentage"></i>
                <div>Commission</div>
            </a>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-chart-line" style="color: var(--accent-color);"></i> Overview</h2>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; color: white;">
                <i class="fas fa-box-open" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.9;"></i>
                <div style="font-size: 2rem; font-weight: 700;"><?php echo $stats['products']; ?></div>
                <div style="opacity: 0.9;">Products in Inventory</div>
            </div>
            <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 10px; color: white;">
                <i class="fas fa-shopping-bag" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.9;"></i>
                <div style="font-size: 2rem; font-weight: 700;"><?php echo $stats['pending_orders']; ?></div>
                <div style="opacity: 0.9;">Pending Supplier Orders</div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layout-footer.php'; ?>
