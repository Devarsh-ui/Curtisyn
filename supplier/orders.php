<?php
$pageTitle = 'Orders';
$activePage = 'orders';
require_once 'layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$supplierId = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['action'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $orderId = $_POST['order_id'];
        $action = $_POST['action'];
        $approvedQty = isset($_POST['approved_quantity']) ? (int)$_POST['approved_quantity'] : null;
        
        $orderStmt = $db->prepare("
            SELECT * FROM supplier_orders 
            WHERE id = :id AND supplier_id = :supplier_id AND status = 'pending'
        ");
        $orderStmt->bindParam(':id', $orderId);
        $orderStmt->bindParam(':supplier_id', $supplierId);
        $orderStmt->execute();
        $order = $orderStmt->fetch();
        
        if ($order) {
            $newStatus = '';
            $finalApprovedQty = null;
            
            switch ($action) {
                case 'accept':
                    $newStatus = 'accepted';
                    $finalApprovedQty = $order['requested_quantity'];
                    break;
                case 'reject':
                    $newStatus = 'rejected';
                    break;
                case 'partial':
                    if ($approvedQty === null || $approvedQty <= 0 || $approvedQty > $order['requested_quantity']) {
                        $error = 'Invalid approved quantity.';
                    } else {
                        $newStatus = 'partially_accepted';
                        $finalApprovedQty = $approvedQty;
                    }
                    break;
            }
            
            if (empty($error) && $newStatus) {
                $updateStmt = $db->prepare("
                    UPDATE supplier_orders 
                    SET status = :status, approved_quantity = :approved_quantity, response_date = NOW()
                    WHERE id = :id
                ");
                $updateStmt->bindParam(':status', $newStatus);
                $updateStmt->bindParam(':approved_quantity', $finalApprovedQty);
                $updateStmt->bindParam(':id', $orderId);
                
                if ($updateStmt->execute()) {
                    if (in_array($newStatus, ['accepted', 'partially_accepted'])) {
                        syncSupplierProductToInventory($db, $orderId);
                    }
                    $success = 'Order ' . str_replace('_', ' ', $newStatus) . ' successfully.';
                } else {
                    $error = 'Failed to update order.';
                }
            }
        } else {
            $error = 'Order not found or already processed.';
        }
    }
}

$orders = [];
if ($db) {
    $stmt = $db->prepare("
        SELECT so.*, sp.name as product_name, sp.image, sp.unit_type, u.full_name as admin_name
        FROM supplier_orders so
        JOIN supplier_products sp ON so.supplier_product_id = sp.id
        JOIN users u ON so.admin_id = u.id
        WHERE so.supplier_id = :supplier_id
        ORDER BY so.order_date DESC
    ");
    $stmt->bindParam(':supplier_id', $supplierId);
    $stmt->execute();
    $orders = $stmt->fetchAll();
}

$csrfToken = generateCsrfToken();
?>

<?php if ($error) echo displayError($error); ?>
<?php if ($success) echo displaySuccess($success); ?>

<div class="content-card">
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Product</th>
                    <th>Requested</th>
                    <th>Approved</th>
                    <th>Price/Unit</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?php if ($order['image']): ?>
                                <img src="../uploads/products/<?php echo htmlspecialchars($order['image']); ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($order['product_name']); ?>
                        </div>
                    </td>
                    <td><?php echo $order['requested_quantity'] . ' ' . htmlspecialchars($order['unit_type']); ?></td>
                    <td><?php echo $order['approved_quantity'] ? $order['approved_quantity'] . ' ' . htmlspecialchars($order['unit_type']) : '-'; ?></td>
                    <td>₹<?php echo number_format($order['price_per_unit'], 2); ?></td>
                    <td>₹<?php echo number_format($order['total_cost'], 2); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $order['status']; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                        </span>
                        <?php if ($order['is_synced']): ?>
                            <span class="badge badge-active" style="margin-left: 5px;">Listed</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($order['status'] === 'pending'): ?>
                        <form method="POST" action="" id="form_<?php echo $order['id']; ?>" style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            
                            <div id="buttons_<?php echo $order['id']; ?>" style="display: flex; gap: 0.5rem;">
                                <button type="submit" name="action" value="accept" class="btn btn-sm btn-success" onclick="return confirm('Accept full order (<?php echo $order['requested_quantity']; ?> <?php echo htmlspecialchars($order['unit_type']); ?>)?')">Accept</button>
                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" onclick="return confirm('Reject this order?')">Reject</button>
                                <button type="button" class="btn btn-sm btn-warning" onclick="showPartial(<?php echo $order['id']; ?>, <?php echo $order['requested_quantity']; ?>)" style="background: #f39c12; color: white;">Partial</button>
                            </div>
                            
                            <div id="partial_<?php echo $order['id']; ?>" style="display: none; gap: 0.3rem; align-items: center;">
                                <input type="number" name="approved_quantity" id="qty_<?php echo $order['id']; ?>" placeholder="Enter Qty (Max: <?php echo $order['requested_quantity']; ?>)" min="1" max="<?php echo $order['requested_quantity']; ?>" style="width: 150px; padding: 0.3rem; border: 1px solid #ddd; border-radius: 3px;">
                                <button type="submit" name="action" value="partial" class="btn btn-sm btn-success" onclick="return submitPartial(<?php echo $order['id']; ?>, <?php echo $order['requested_quantity']; ?>)" style="background: #27ae60;">Submit</button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="cancelPartial(<?php echo $order['id']; ?>)" style="background: #95a5a6;">Cancel</button>
                            </div>
                        </form>
                        <?php else: ?>
                            <span style="color: #666; font-size: 0.9rem;">Processed</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2rem;">No orders received yet.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function showPartial(orderId, maxQty) {
    document.getElementById('buttons_' + orderId).style.display = 'none';
    document.getElementById('partial_' + orderId).style.display = 'flex';
}

function cancelPartial(orderId) {
    document.getElementById('buttons_' + orderId).style.display = 'flex';
    document.getElementById('partial_' + orderId).style.display = 'none';
    document.getElementById('qty_' + orderId).value = '';
}

function submitPartial(orderId, maxQty) {
    var qtyInput = document.getElementById('qty_' + orderId);
    var qty = parseInt(qtyInput.value);
    
    if (!qty || qty <= 0) {
        alert('Please enter a valid quantity');
        return false;
    }
    
    if (qty > maxQty) {
        alert('Approved quantity cannot exceed requested quantity (' + maxQty + ')');
        return false;
    }
    
    return confirm('Accept partial order with ' + qty + ' quantity?');
}
</script>

<?php require_once 'layout-footer.php'; ?>
