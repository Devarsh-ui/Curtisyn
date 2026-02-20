<?php
$pageTitle = 'Login';
$currentPage = 'login';
require_once 'includes/header.php';
require_once 'config/database.php';

if (isLoggedIn()) {
    $role = getUserRole();
    switch ($role) {
        case 'admin': redirect(BASE_URL . 'admin/dashboard-new.php');
        case 'employee': redirect(BASE_URL . 'employee/dashboard-new.php');
        case 'supplier': redirect(BASE_URL . 'supplier/dashboard-new.php');
        default: redirect(BASE_URL . 'index.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Please enter email and password.';
        } else {
            $database = new Database();
            $db = $database->connect();
            
            if ($db) {
                try {
                    $stmt = $db->prepare("SELECT id, full_name, password, role, status FROM users WHERE email = :email");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() === 1) {
                        $user = $stmt->fetch();
                        
                        if ($user['status'] !== 'active') {
                            $error = 'Account is inactive.';
                        } elseif (password_verify($password, $user['password'])) {
                            session_regenerate_id(true);
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['full_name'];
                            $_SESSION['user_role'] = $user['role'];
                            
                            switch ($user['role']) {
                                case 'admin': redirect(BASE_URL . 'admin/dashboard-new.php');
                                case 'employee': redirect(BASE_URL . 'employee/dashboard-new.php');
                                case 'supplier': redirect(BASE_URL . 'supplier/dashboard-new.php');
                                default: redirect(BASE_URL . 'index.php');
                            }
                        } else {
                            $error = 'Invalid credentials.';
                        }
                    } else {
                        $error = 'Invalid credentials.';
                    }
                } catch (PDOException $e) {
                    $error = 'Database error.';
                }
            } else {
                $error = 'Connection failed.';
            }
        }
    }
}

$csrfToken = generateCsrfToken();
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Login</h1>
        
        <div class="form-container">
            <?php if ($error) echo displayError($error); ?>
            
            <form method="POST" action="" onsubmit="return validateLoginForm()">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>
            
            <p style="text-align: center; margin-top: 1.5rem;">
                No account? <a href="<?php echo BASE_URL; ?>register.php">Register</a>
            </p>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
