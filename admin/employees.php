<?php
$pageTitle = 'Manage Employees';
$activePage = 'employees';
require_once 'layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id AND role = 'employee'");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    header('Location: ' . BASE_URL . 'admin/employees.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
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
                $role = 'employee';
                
                $stmt = $db->prepare("INSERT INTO users (full_name, email, password, role) VALUES (:full_name, :email, :password, :role)");
                $stmt->bindParam(':full_name', $fullName);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':role', $role);
                
                if ($stmt->execute()) {
                    $success = 'Employee added successfully.';
                } else {
                    $error = 'Failed to add employee.';
                }
            }
        }
    }
}

$employees = [];
if ($db) {
    $stmt = $db->query("SELECT * FROM users WHERE role = 'employee' ORDER BY created_at DESC");
    $employees = $stmt->fetchAll();
}

$csrfToken = generateCsrfToken();
$showAddForm = isset($_GET['action']) && $_GET['action'] === 'add';
?>

<?php if ($showAddForm): ?>
<div class="content-card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Add Employee</h2>
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
                <button type="submit" name="add_employee" class="btn btn-primary">Add Employee</button>
                <a href="<?php echo BASE_URL; ?>admin/employees.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div style="margin-bottom: 1.5rem;">
    <a href="employees.php?action=add" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Employee
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
                <?php foreach ($employees as $employee): ?>
                <tr>
                    <td><?php echo $employee['id']; ?></td>
                    <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($employee['email']); ?></td>
                    <td><span class="badge badge-<?php echo $employee['status']; ?>"><?php echo ucfirst($employee['status']); ?></span></td>
                    <td><?php echo date('Y-m-d', strtotime($employee['created_at'])); ?></td>
                    <td>
                        <a href="employees.php?delete=<?php echo $employee['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this employee?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'layout-footer.php'; ?>
