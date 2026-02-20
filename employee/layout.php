<?php
// Employee Layout Template
if (!isset($pageTitle)) $pageTitle = 'Employee';
if (!isset($activePage)) $activePage = '';

require_once 'auth-check.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->connect();

// Get stats for sidebar
$stats = ['total_products' => 0, 'enabled' => 0, 'disabled' => 0];
if ($db) {
    $stats['total_products'] = $db->query("SELECT COUNT(*) FROM products WHERE created_by = " . ($_SESSION['user_id'] ?? 0))->fetchColumn();
    $stats['enabled'] = $db->query("SELECT COUNT(*) FROM products WHERE created_by = " . ($_SESSION['user_id'] ?? 0) . " AND status = 'enabled'")->fetchColumn();
    $stats['disabled'] = $db->query("SELECT COUNT(*) FROM products WHERE created_by = " . ($_SESSION['user_id'] ?? 0) . " AND status = 'disabled'")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Employee Panel</title>
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
                <a href="<?php echo BASE_URL; ?>employee/dashboard-new.php" class="sidebar-brand">
                    <i class="fas fa-couch"></i>
                    <span>Employee Panel</span>
                </a>
            </div>
            
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Employee'); ?></div>
                <div class="user-role">Employee</div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>employee/dashboard-new.php" class="nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
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
                            <a href="<?php echo BASE_URL; ?>employee/products.php" class="nav-link <?php echo $activePage === 'products' ? 'active' : ''; ?>">
                                <i class="fas fa-box"></i>
                                <span>All Products</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>employee/product-form.php" class="nav-link <?php echo $activePage === 'add-product' ? 'active' : ''; ?>">
                                <i class="fas fa-plus"></i>
                                <span>Add Product</span>
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
