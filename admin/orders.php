<?php
require_once 'auth-check.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Raw Material Orders';

$database = new Database();
$db = $database->connect();

$orders = [];
if ($db) {
    $stmt = $db->query("SELECT rmo.*, rm.name as material_name, rm.unit, u.full_name as supplier_name FROM raw_material_orders rmo JOIN raw_materials rm ON rmo.material_id = rm.id JOIN users u ON rmo.supplier_id = u.id ORDER BY rmo.order_date DESC");
    $orders = $stmt->fetchAll();
}
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Raw Material Orders</h1>
        
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Material</th>
                        <th>Supplier</th>
                        <th>Quantity</th>
                        <th>Approved</th>
                        <th>Total Cost</th>
                        <th>Status</th>
                        <th>Order Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo $order['id']; ?></td>
                        <td><?php echo htmlspecialchars($order['material_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                        <td><?php echo $order['quantity'] . ' ' . $order['unit']; ?></td>
                        <td><?php echo $order['approved_quantity'] ? $order['approved_quantity'] . ' ' . $order['unit'] : '-'; ?></td>
                        <td>$<?php echo number_format($order['total_cost'], 2); ?></td>
                        <td><span class="badge badge-<?php echo $order['status']; ?>"><?php echo ucwords(str_replace('_', ' ', $order['status'])); ?></span></td>
                        <td><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
