<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/database.php';

$role = getUserRole();
$isLoggedIn = isLoggedIn();

// Get cart count for logged in users
$cartCount = 0;
if ($isLoggedIn) {
    $database = new Database();
    $db = $database->connect();
    if ($db) {
        $stmt = $db->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $cartCount = $stmt->fetch()['count'] ?? 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>Curtisyn</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ── Three-dot dropdown ── */
        .nav-dots-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.4rem 0.6rem;
            border-radius: 50%;
            font-size: 1.35rem;
            color: var(--primary-color);
            line-height: 1;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .nav-dots-btn:hover {
            background: var(--bg-light);
            color: var(--secondary-color);
        }
        .nav-dots-wrapper {
            position: relative;
        }
        .nav-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            min-width: 200px;
            z-index: 2000;
            overflow: hidden;
            animation: dropFadeIn 0.18s ease;
        }
        .nav-dropdown.open { display: block; }
        @keyframes dropFadeIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .nav-dropdown a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.8rem 1.2rem;
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: background 0.15s ease;
        }
        .nav-dropdown a i { width: 18px; color: var(--accent-color); }
        .nav-dropdown a:hover { background: var(--bg-light); color: var(--secondary-color); }
        .nav-dropdown a:hover i { color: var(--secondary-color); }
        .nav-dropdown .dropdown-divider {
            border: none;
            border-top: 1px solid #eee;
            margin: 4px 0;
        }
        .nav-dropdown .dropdown-logout {
            color: var(--danger-color) !important;
        }
        .nav-dropdown .dropdown-logout i { color: var(--danger-color) !important; }
        .nav-cart-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-weight: 500;
        }
        .cart-bubble {
            background: var(--secondary-color);
            color: white;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 0.72rem;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="<?php echo BASE_URL; ?>index.php" class="logo">Curtisyn</a>

                <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                    <span></span><span></span><span></span>
                </button>

                <ul class="nav-menu" id="navMenu">
                    <li><a href="<?php echo BASE_URL; ?>index.php"    class="nav-link<?php echo ($currentPage??'') === 'home'     ? ' active' : ''; ?>">Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>products.php" class="nav-link<?php echo ($currentPage??'') === 'products' ? ' active' : ''; ?>">Products</a></li>
                    <li><a href="<?php echo BASE_URL; ?>contact.php"  class="nav-link<?php echo ($currentPage??'') === 'contact'  ? ' active' : ''; ?>">Contact</a></li>

                    <?php if ($isLoggedIn && $role === 'customer'): ?>
                        <!-- Cart link always visible for customers -->
                        <li>
                            <a href="<?php echo BASE_URL; ?>cart.php" class="nav-link nav-cart-badge<?php echo ($currentPage??'') === 'cart' ? ' active' : ''; ?>">
                                <i class="fas fa-shopping-cart"></i> Cart
                                <?php if ($cartCount > 0): ?>
                                    <span class="cart-bubble"><?php echo $cartCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <!-- Three-dot dropdown -->
                        <li class="nav-dots-wrapper">
                            <button class="nav-dots-btn" id="dotsBtn" aria-label="More options">&#8942;</button>
                            <div class="nav-dropdown" id="navDropdown">
                                <a href="<?php echo BASE_URL; ?>my-orders.php"><i class="fas fa-box-open"></i> My Orders</a>
                                <a href="<?php echo BASE_URL; ?>my-account.php"><i class="fas fa-user-circle"></i> My Account</a>
                                <a href="<?php echo BASE_URL; ?>my-inquiries.php"><i class="fas fa-question-circle"></i> My Inquiries</a>
                                <div class="dropdown-divider"></div>
                                <a href="<?php echo BASE_URL; ?>about.php"><i class="fas fa-info-circle"></i> About</a>
                                <div class="dropdown-divider"></div>
                                <a href="<?php echo BASE_URL; ?>logout.php" class="dropdown-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </li>

                    <?php elseif ($isLoggedIn && ($role === 'admin' || $role === 'employee' || $role === 'supplier')): ?>
                        <?php
                        $dashUrl = BASE_URL;
                        switch ($role) {
                            case 'admin':    $dashUrl = BASE_URL . 'admin/dashboard-new.php'; break;
                            case 'employee': $dashUrl = BASE_URL . 'employee/dashboard-new.php'; break;
                            case 'supplier': $dashUrl = BASE_URL . 'supplier/dashboard-new.php'; break;
                        }
                        ?>
                        <li><a href="<?php echo $dashUrl; ?>" class="nav-link" style="color: var(--accent-color);"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="<?php echo BASE_URL; ?>logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>

                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>login.php"    class="nav-link<?php echo ($currentPage??'') === 'login'    ? ' active' : ''; ?>">Login</a></li>
                        <li><a href="<?php echo BASE_URL; ?>register.php" class="nav-link<?php echo ($currentPage??'') === 'register' ? ' active' : ''; ?>">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggle  = document.getElementById('navToggle');
            var menu    = document.getElementById('navMenu');
            var dotsBtn = document.getElementById('dotsBtn');
            var navDropdown = document.getElementById('navDropdown');

            // ── Hamburger toggle ──
            if (toggle && menu) {
                toggle.addEventListener('click', function (e) {
                    e.stopPropagation();
                    menu.classList.toggle('active');
                    // animate hamburger lines
                    toggle.classList.toggle('open');
                });

                // Close menu when any nav link is clicked (mobile UX)
                menu.querySelectorAll('a.nav-link').forEach(function (link) {
                    link.addEventListener('click', function () {
                        menu.classList.remove('active');
                        toggle.classList.remove('open');
                    });
                });

                // Close menu when clicking outside
                document.addEventListener('click', function (e) {
                    if (!menu.contains(e.target) && !toggle.contains(e.target)) {
                        menu.classList.remove('active');
                        toggle.classList.remove('open');
                    }
                });
            }

            // ── Three-dot dropdown ──
            if (dotsBtn && navDropdown) {
                dotsBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    navDropdown.classList.toggle('open');
                });
                document.addEventListener('click', function () {
                    navDropdown.classList.remove('open');
                });
                navDropdown.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            }
        });
    </script>
