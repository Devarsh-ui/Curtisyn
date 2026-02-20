<?php
// Admin Layout Template
// Usage: include this file at the top of admin pages with $pageTitle and $activePage set

if (!isset($pageTitle)) $pageTitle = 'Admin';
if (!isset($activePage)) $activePage = '';

require_once 'auth-check.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->connect();

// Get stats for sidebar
$stats = ['pending_orders' => 0];
if ($db) {
    $stats['pending_orders'] = $db->query("SELECT COUNT(*) FROM supplier_orders WHERE status = 'pending'")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
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
                <a href="<?php echo BASE_URL; ?>admin/dashboard-new.php" class="sidebar-brand">
                    <i class="fas fa-couch"></i>
                    <span>Admin Panel</span>
                </a>
            </div>
            
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></div>
                <div class="user-role">Administrator</div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>admin/dashboard-new.php" class="nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
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
                            <a href="<?php echo BASE_URL; ?>admin/products.php" class="nav-link <?php echo $activePage === 'products' ? 'active' : ''; ?>">
                                <i class="fas fa-box"></i>
                                <span>All Products</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>admin/product-form.php" class="nav-link <?php echo $activePage === 'add-product' ? 'active' : ''; ?>">
                                <i class="fas fa-plus"></i>
                                <span>Add Product</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>admin/purchase-supplier.php" class="nav-link <?php echo $activePage === 'purchase' ? 'active' : ''; ?>">
                                <i class="fas fa-shopping-cart"></i>
                                <span>Purchase from Supplier</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>admin/supplier-orders.php" class="nav-link <?php echo $activePage === 'orders' ? 'active' : ''; ?>">
                                <i class="fas fa-list"></i>
                                <span>Supplier Orders</span>
                                <?php if ($stats['pending_orders'] > 0): ?>
                                    <span style="background: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; margin-left: auto;"><?php echo $stats['pending_orders']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>admin/customer-orders.php" class="nav-link <?php echo $activePage === 'customer-orders' ? 'active' : ''; ?>">
                                <i class="fas fa-shopping-bag"></i>
                                <span>Customer Orders</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>admin/inquiries.php" class="nav-link <?php echo $activePage === 'inquiries' ? 'active' : ''; ?>">
                                <i class="fas fa-question-circle"></i>
                                <span>Product Inquiries</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Management</div>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>admin/customers.php" class="nav-link <?php echo $activePage === 'customers' ? 'active' : ''; ?>">
                                <i class="fas fa-users"></i>
                                <span>Customers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>admin/suppliers.php" class="nav-link <?php echo $activePage === 'suppliers' ? 'active' : ''; ?>">
                                <i class="fas fa-truck"></i>
                                <span>Suppliers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>admin/employees.php" class="nav-link <?php echo $activePage === 'employees' ? 'active' : ''; ?>">
                                <i class="fas fa-user-tie"></i>
                                <span>Employees</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>admin/commission-settings.php" class="nav-link <?php echo $activePage === 'commission' ? 'active' : ''; ?>">
                                <i class="fas fa-percentage"></i>
                                <span>Commission Settings</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>admin/test-email.php" class="nav-link <?php echo $activePage === 'test-email' ? 'active' : ''; ?>">
                                <i class="fas fa-paper-plane"></i>
                                <span>Test Email</span>
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
