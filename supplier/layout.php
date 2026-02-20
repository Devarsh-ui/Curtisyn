<?php
// Supplier Layout Template
if (!isset($pageTitle)) $pageTitle = 'Supplier';
if (!isset($activePage)) $activePage = '';

require_once 'auth-check.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->connect();

$supplierId = $_SESSION['user_id'];

// Get stats for sidebar
$stats = ['my_products' => 0, 'pending_orders' => 0, 'total_orders' => 0];
if ($db) {
    $stats['my_products'] = $db->query("SELECT COUNT(*) FROM supplier_products WHERE supplier_id = $supplierId")->fetchColumn();
    $stats['pending_orders'] = $db->query("SELECT COUNT(*) FROM supplier_orders WHERE supplier_id = $supplierId AND status = 'pending'")->fetchColumn();
    $stats['total_orders'] = $db->query("SELECT COUNT(*) FROM supplier_orders WHERE supplier_id = $supplierId")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Supplier Panel</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="<?php echo BASE_URL; ?>supplier/dashboard-new.php" class="sidebar-brand">
                    <i class="fas fa-couch"></i>
                    <span>Supplier Panel</span>
                </a>
            </div>
            
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Supplier'); ?></div>
                <div class="user-role">Supplier</div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>supplier/dashboard-new.php" class="nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                                <i class="fas fa-home"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Products</div>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>supplier/materials.php" class="nav-link <?php echo $activePage === 'materials' ? 'active' : ''; ?>">
                                <i class="fas fa-box"></i>
                                <span>My Products</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>supplier/material-form.php" class="nav-link <?php echo $activePage === 'add-material' ? 'active' : ''; ?>">
                                <i class="fas fa-plus"></i>
                                <span>Add Product</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Orders</div>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>supplier/orders.php" class="nav-link <?php echo $activePage === 'orders' ? 'active' : ''; ?>">
                                <i class="fas fa-list"></i>
                                <span>Orders</span>
                                <?php if ($stats['pending_orders'] > 0): ?>
                                    <span style="background: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; margin-left: auto;"><?php echo $stats['pending_orders']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <a href="<?php echo BASE_URL; ?>index.php" target="_blank" class="view-site-btn">
                    <i class="fas fa-external-link-alt"></i>
                    <span>View Website</span>
                </a>
                <a href="<?php echo BASE_URL; ?>logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title"><?php echo $pageTitle; ?></h1>
            </div>
            
            <!-- Page Content Goes Here -->
