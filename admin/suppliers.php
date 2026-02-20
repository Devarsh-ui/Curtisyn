<?php
$pageTitle = 'Manage Suppliers';
$activePage = 'suppliers';
require_once 'layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id AND role = 'supplier'");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    header('Location: ' . BASE_URL . 'admin/suppliers.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_supplier'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $fullName = sanitizeInput($_POST['full_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($fullName) || empty($email) || empty($password)) {
            $error = 'All fields required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be 6+ characters.';
        } else {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = 'Email already exists.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $role = 'supplier';
                
                $stmt = $db->prepare("INSERT INTO users (full_name, email, password, role) VALUES (:full_name, :email, :password, :role)");
                $stmt->bindParam(':full_name', $fullName);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':role', $role);
                
                if ($stmt->execute()) {
                    $success = 'Supplier added successfully.';
                } else {
                    $error = 'Failed to add supplier.';
                }
            }
        }
    }
}

$suppliers = [];
if ($db) {
    $stmt = $db->query("SELECT * FROM users WHERE role = 'supplier' ORDER BY created_at DESC");
    $suppliers = $stmt->fetchAll();
}

$csrfToken = generateCsrfToken();
$showAddForm = isset($_GET['action']) && $_GET['action'] === 'add';
?>

<?php if ($showAddForm): ?>
<div class="content-card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Add Supplier</h2>
    </div>
    <div class="card-body">
        <?php 
        if ($error) echo displayError($error);
        if ($success) echo displaySuccess($success);
        ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="add_supplier" class="btn btn-primary">Add Supplier</button>
                <a href="<?php echo BASE_URL; ?>admin/suppliers.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div style="margin-bottom: 1.5rem;">
    <a href="suppliers.php?action=add" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Supplier
    </a>
</div>

<div class="content-card">
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $supplier): ?>
                <tr>
                    <td><?php echo $supplier['id']; ?></td>
                    <td><?php echo htmlspecialchars($supplier['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                    <td><span class="badge badge-<?php echo $supplier['status']; ?>"><?php echo ucfirst($supplier['status']); ?></span></td>
                    <td><?php echo date('Y-m-d', strtotime($supplier['created_at'])); ?></td>
                    <td>
                        <a href="suppliers.php?delete=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this supplier?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'layout-footer.php'; ?>
