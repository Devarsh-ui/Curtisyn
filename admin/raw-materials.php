<?php
require_once 'auth-check.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Order Raw Materials';

$database = new Database();
$db = $database->connect();

$materials = [];
if ($db) {
    $stmt = $db->query("SELECT rm.*, u.full_name as supplier_name FROM raw_materials rm JOIN users u ON rm.supplier_id = u.id WHERE u.status = 'active' ORDER BY rm.name");
    $materials = $stmt->fetchAll();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_material'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $materialId = intval($_POST['material_id'] ?? 0);
        $quantity = floatval($_POST['quantity'] ?? 0);
        
        if ($materialId <= 0 || $quantity <= 0) {
            $error = 'Select material and enter valid quantity.';
        } else {
            $stmt = $db->prepare("SELECT * FROM raw_materials WHERE id = :id");
            $stmt->bindParam(':id', $materialId);
            $stmt->execute();
            $material = $stmt->fetch();
            
            if ($material) {
                $totalCost = $quantity * $material['price_per_unit'];
                $adminId = $_SESSION['user_id'];
                $supplierId = $material['supplier_id'];
                
                $stmt = $db->prepare("INSERT INTO raw_material_orders (admin_id, supplier_id, material_id, quantity, total_cost, status) VALUES (:admin_id, :supplier_id, :material_id, :quantity, :total_cost, 'pending')");
                $stmt->bindParam(':admin_id', $adminId);
                $stmt->bindParam(':supplier_id', $supplierId);
                $stmt->bindParam(':material_id', $materialId);
                $stmt->bindParam(':quantity', $quantity);
                $stmt->bindParam(':total_cost', $totalCost);
                
                if ($stmt->execute()) {
                    $success = 'Order placed successfully. Total: $' . number_format($totalCost, 2);
                } else {
                    $error = 'Failed to place order.';
                }
            } else {
                $error = 'Material not found.';
            }
        }
    }
}

$csrfToken = generateCsrfToken();
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Order Raw Materials</h1>
        
        <?php if ($error) echo displayError($error); ?>
        <?php if ($success) echo displaySuccess($success); ?>
        
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Material</th>
                        <th>Supplier</th>
                        <th>Category</th>
                        <th>Unit</th>
                        <th>Price/Unit</th>
                        <th>Order</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materials as $material): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($material['name']); ?></td>
                        <td><?php echo htmlspecialchars($material['supplier_name']); ?></td>
                        <td><?php echo htmlspecialchars($material['category']); ?></td>
                        <td><?php echo $material['unit']; ?></td>
                        <td>$<?php echo number_format($material['price_per_unit'], 2); ?></td>
                        <td>
                            <form method="POST" action="" class="order-form">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="material_id" value="<?php echo $material['id']; ?>">
                                <input type="number" name="quantity" class="form-input" step="0.01" min="0.01" placeholder="Qty" required style="width: 80px; display: inline-block;">
                                <button type="submit" name="order_material" class="btn btn-sm btn-primary">Order</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
