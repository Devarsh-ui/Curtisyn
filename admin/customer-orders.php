<?php
require_once __DIR__ . '/../config/database.php';

$statusFilter = $_GET['status'] ?? '';

// Handle status update BEFORE any output
if (isset($_GET['update_status']) && isset($_GET['order_id'])) {
    $orderId = $_GET['order_id'];
    $newStatus = $_GET['update_status'];
    
    $database = new Database();
    $db = $database->connect();
    
    $updateStmt = $db->prepare("UPDATE customer_orders SET order_status = :status WHERE order_id = :order_id");
    $updateStmt->bindParam(':status', $newStatus);
    $updateStmt->bindParam(':order_id', $orderId);
    $updateStmt->execute();
    
    // Send status update email via Brevo
    require_once __DIR__ . '/../includes/OrderEmailTrigger.php';
    $emailTrigger = new OrderEmailTrigger($db);
    
    try {
        $emailTrigger->onOrderStatusUpdate($orderId, $newStatus);
    } catch (Exception $e) {
        error_log('Status update email failed: ' . $e->getMessage());
    }
    
    header('Location: ' . BASE_URL . 'admin/customer-orders.php' . ($statusFilter ? '?status=' . $statusFilter : ''));
    exit();
}

$pageTitle = 'Customer Orders';
$activePage = 'customer-orders';
require_once 'layout.php';

$orders = [];

if ($db) {
    $sql = "
        SELECT co.*, p.name as product_name, p.image, u.full_name as customer_name, u.email as customer_email
        FROM customer_orders co
        JOIN products p ON co.product_id = p.id
        JOIN users u ON co.user_id = u.id
    ";
    
    if ($statusFilter) {
        $sql .= " WHERE co.order_status = :status";
    }
    
    $sql .= " ORDER BY co.id DESC";
    
    $stmt = $db->prepare($sql);
    
    if ($statusFilter) {
        $stmt->bindParam(':status', $statusFilter);
    }
    
    $stmt->execute();
    $orders = $stmt->fetchAll();
}
?>

<!-- Filter Buttons -->
<div class="orders-filter">
    <a href="<?php echo BASE_URL; ?>admin/customer-orders.php" class="btn <?php echo !$statusFilter ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
    <a href="<?php echo BASE_URL; ?>admin/customer-orders.php?status=pending" class="btn <?php echo $statusFilter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">Pending</a>
    <a href="<?php echo BASE_URL; ?>admin/customer-orders.php?status=confirmed" class="btn <?php echo $statusFilter === 'confirmed' ? 'btn-primary' : 'btn-secondary'; ?>">Confirmed</a>
    <a href="<?php echo BASE_URL; ?>admin/customer-orders.php?status=shipped" class="btn <?php echo $statusFilter === 'shipped' ? 'btn-primary' : 'btn-secondary'; ?>">Shipped</a>
    <a href="<?php echo BASE_URL; ?>admin/customer-orders.php?status=delivered" class="btn <?php echo $statusFilter === 'delivered' ? 'btn-primary' : 'btn-secondary'; ?>">Delivered</a>
</div>

<?php if (empty($orders)): ?>
    <div class="content-card">
        <div class="card-body" style="text-align: center; padding: 3rem;">
            <i class="fas fa-box-open" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
            <p>No orders found.</p>
        </div>
    </div>
<?php else: ?>
    <!-- Desktop Table View -->
    <div class="content-card desktop-table">
        <div class="card-body" style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($order['order_id']); ?></strong></td>
                        <td>
                            <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                            <small style="color: #666;"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <?php if ($order['image']): ?>
                                    <img src="../uploads/products/<?php echo htmlspecialchars($order['image']); ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                                <?php endif; ?>
                                <span style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($order['product_name']); ?></span>
                            </div>
                        </td>
                        <td><?php echo $order['quantity']; ?></td>
                        <td style="font-weight: 600;">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <div><?php echo $order['payment_method'] === 'cod' ? 'COD' : 'Online'; ?></div>
                            <small style="color: <?php echo $order['payment_status'] === 'paid' ? '#28a745' : '#ffc107'; ?>;">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $order['order_status']; ?>">
                                <?php echo ucfirst($order['order_status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($order['updated_at'] ?? $order['created_at'] ?? date('Y-m-d H:i:s'))); ?></td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>admin/order-detail.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-primary" style="margin-bottom: 0.5rem; display: block;">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <select onchange="if(this.value) window.location.href='<?php echo BASE_URL; ?>admin/customer-orders.php?order_id=<?php echo $order['order_id']; ?>&update_status='+this.value<?php echo $statusFilter ? "+'&status=" . $statusFilter . "'" : ""; ?>;" style="padding: 0.3rem; border-radius: 5px; border: 1px solid #ddd; font-size: 0.85rem; width: 100%;">
                                <option value="">Update</option>
                                <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $order['order_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile/Laptop Card View -->
    <div class="mobile-cards">
        <?php foreach ($orders as $order): ?>
        <div class="order-card">
            <div class="order-card-header">
                <div class="order-id"><?php echo htmlspecialchars($order['order_id']); ?></div>
                <span class="badge badge-<?php echo $order['order_status']; ?>"><?php echo ucfirst($order['order_status']); ?></span>
            </div>
            <div class="order-card-body">
                <div class="order-info-row">
                    <span class="label">Customer:</span>
                    <span class="value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                </div>
                <div class="order-info-row">
                    <span class="label">Product:</span>
                    <span class="value">
                        <?php if ($order['image']): ?>
                            <img src="../uploads/products/<?php echo htmlspecialchars($order['image']); ?>" alt="" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px; vertical-align: middle; margin-right: 5px;">
                        <?php endif; ?>
                        <?php echo htmlspecialchars($order['product_name']); ?>
                    </span>
                </div>
                <div class="order-info-row">
                    <span class="label">Quantity:</span>
                    <span class="value"><?php echo $order['quantity']; ?></span>
                </div>
                <div class="order-info-row">
                    <span class="label">Total:</span>
                    <span class="value price">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="order-info-row">
                    <span class="label">Phone:</span>
                    <span class="value"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                </div>
                <div class="order-info-row">
                    <span class="label">Date:</span>
                    <span class="value"><?php echo date('M d, Y', strtotime($order['updated_at'] ?? $order['created_at'] ?? date('Y-m-d H:i:s'))); ?></span>
                </div>
                <div class="order-info-row">
                    <span class="label">Payment:</span>
                    <span class="value">
                        <?php echo $order['payment_method'] === 'cod' ? 'COD' : 'Online'; ?> -
                        <span style="color: <?php echo $order['payment_status'] === 'paid' ? '#28a745' : '#ffc107'; ?>; font-weight: 600;">
                            <?php echo ucfirst($order['payment_status']); ?>
                        </span>
                    </span>
                </div>
            </div>
            <div class="order-card-footer">
                <a href="<?php echo BASE_URL; ?>admin/order-detail.php?id=<?php echo $order['order_id']; ?>" class="btn btn-primary">
                    <i class="fas fa-eye"></i> View Details
                </a>
                <select onchange="if(this.value) window.location.href='<?php echo BASE_URL; ?>admin/customer-orders.php?order_id=<?php echo $order['order_id']; ?>&update_status='+this.value<?php echo $statusFilter ? "+'&status=" . $statusFilter . "'" : ""; ?>;" class="status-select">
                    <option value="">Update Status</option>
                    <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?php echo $order['order_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'layout-footer.php'; ?>
