<?php
require_once __DIR__ . '/../config/database.php';

$orderId = $_GET['id'] ?? '';

if (empty($orderId)) {
    header('Location: ' . BASE_URL . 'admin/customer-orders.php');
    exit();
}

$database = new Database();
$db = $database->connect();

$order = null;

if ($db) {
    $stmt = $db->prepare("
        SELECT co.*, p.name as product_name, p.image, p.description, u.full_name as customer_name, u.email as customer_email
        FROM customer_orders co
        JOIN products p ON co.product_id = p.id
        JOIN users u ON co.user_id = u.id
        WHERE co.order_id = :order_id
    ");
    $stmt->bindParam(':order_id', $orderId);
    $stmt->execute();
    $order = $stmt->fetch();
}

if (!$order) {
    header('Location: ' . BASE_URL . 'admin/customer-orders.php');
    exit();
}

$pageTitle = 'Order Details - ' . $orderId;
$activePage = 'customer-orders';
require_once 'layout.php';
?>

<div style="margin-bottom: 1.5rem;">
    <a href="<?php echo BASE_URL; ?>admin/customer-orders.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Orders
    </a>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Order Info -->
    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-info-circle"></i> Order Information</h2>
        </div>
        <div class="card-body">
            <div style="margin-bottom: 1rem;">
                <label style="font-weight: 600; color: #666;">Order ID:</label>
                <p style="font-size: 1.1rem; margin-top: 0.25rem;"><?php echo htmlspecialchars($order['order_id']); ?></p>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="font-weight: 600; color: #666;">Order Date:</label>
                <p style="margin-top: 0.25rem;"><?php echo date('F d, Y h:i A', strtotime($order['updated_at'] ?? $order['created_at'] ?? date('Y-m-d H:i:s'))); ?></p>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="font-weight: 600; color: #666;">Status:</label>
                <p style="margin-top: 0.25rem;">
                    <span class="badge badge-<?php echo $order['order_status']; ?>">
                        <?php echo ucfirst($order['order_status']); ?>
                    </span>
                </p>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="font-weight: 600; color: #666;">Payment Method:</label>
                <p style="margin-top: 0.25rem;">
                    <i class="fas fa-money-bill-wave" style="color: #27ae60;"></i> 
                    <?php echo $order['payment_method'] === 'online' ? 'Online (Razorpay)' : 'Cash on Delivery'; ?>
                </p>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="font-weight: 600; color: #666;">Payment Status:</label>
                <p style="margin-top: 0.25rem;">
                    <span class="badge badge-<?php echo $order['payment_status']; ?>">
                        <?php echo ucfirst($order['payment_status']); ?>
                    </span>
                </p>
            </div>
            
            <?php if ($order['payment_method'] === 'online'): ?>
            <div style="margin-bottom: 1rem;">
                <label style="font-weight: 600; color: #666;">Razorpay Payment ID:</label>
                <p style="margin-top: 0.25rem; font-family: monospace; background: #f5f5f5; padding: 0.5rem; border-radius: 4px;">
                    <?php echo $order['razorpay_payment_id'] ? htmlspecialchars($order['razorpay_payment_id']) : '<em>Not processed yet</em>'; ?>
                </p>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="font-weight: 600; color: #666;">Razorpay Order ID:</label>
                <p style="margin-top: 0.25rem; font-family: monospace; background: #f5f5f5; padding: 0.5rem; border-radius: 4px;">
                    <?php echo $order['razorpay_order_id'] ? htmlspecialchars($order['razorpay_order_id']) : '<em>Not generated yet</em>'; ?>
                </p>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="font-weight: 600; color: #666;">Razorpay Signature:</label>
                <p style="margin-top: 0.25rem; font-family: monospace; background: #f5f5f5; padding: 0.5rem; border-radius: 4px; word-break: break-all;">
                    <?php echo $order['razorpay_signature'] ? htmlspecialchars($order['razorpay_signature']) : '<em>Not verified</em>'; ?>
                </p>
            </div>
            <?php endif; ?>
            
            <div>
                <label style="font-weight: 600; color: #666;">Total Amount:</label>
                <p style="font-size: 1.5rem; font-weight: 700; color: #27ae60; margin-top: 0.25rem;">
                    ₹<?php echo number_format($order['total_amount'], 2); ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Customer Info -->
    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-user"></i> Customer Information</h2>
        </div>
        <div class="card-body">
            <div style="margin-bottom: 1rem;">
                <label style="font-weight: 600; color: #666;">Name:</label>
                <p style="margin-top: 0.25rem;"><?php echo htmlspecialchars($order['customer_name']); ?></p>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="font-weight: 600; color: #666;">Email:</label>
                <p style="margin-top: 0.25rem;"><?php echo htmlspecialchars($order['customer_email']); ?></p>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="font-weight: 600; color: #666;">Phone:</label>
                <p style="margin-top: 0.25rem;">
                    <i class="fas fa-phone" style="color: #3498db;"></i> 
                    <?php echo htmlspecialchars($order['customer_phone']); ?>
                </p>
            </div>
            
            <div>
                <label style="font-weight: 600; color: #666;">Delivery Address:</label>
                <p style="margin-top: 0.25rem; line-height: 1.6;">
                    <i class="fas fa-map-marker-alt" style="color: #e74c3c;"></i> 
                    <?php echo nl2br(htmlspecialchars($order['customer_address'])); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Product Info -->
<div class="content-card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-box"></i> Product Details</h2>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
            <?php if ($order['image']): ?>
                <img src="../uploads/products/<?php echo htmlspecialchars($order['image']); ?>" alt="" style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px;">
            <?php endif; ?>
            <div style="flex: 1;">
                <h3 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($order['product_name']); ?></h3>
                
                <?php if (!empty($order['size_name'])): ?>
                    <div style="font-size: 0.95rem; color: #555; margin-bottom: 0.5rem; background: #eef2f3; padding: 0.5rem 1rem; border-radius: 4px; display: inline-block;">
                        <i class="fas fa-ruler-combined" style="margin-right: 0.5rem; color: #6c757d;"></i>
                        Size: <strong><?php echo htmlspecialchars($order['size_name']); ?></strong>
                        <?php if ($order['custom_width'] && $order['custom_height']): ?>
                            (<?php echo htmlspecialchars($order['custom_width']); ?> x <?php echo htmlspecialchars($order['custom_height']); ?> ft)
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <p style="color: #666; margin-bottom: 1rem; margin-top: 0.5rem;"><?php echo htmlspecialchars($order['description'] ?? 'No description'); ?></p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.85rem; color: #666;">Unit Price</label>
                        <p style="font-weight: 600;">₹<?php echo number_format($order['price'], 2); ?></p>
                    </div>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.85rem; color: #666;">Quantity</label>
                        <p style="font-weight: 600;"><?php echo $order['quantity']; ?></p>
                    </div>
                    <div style="background: #e8f5e9; padding: 1rem; border-radius: 8px;">
                        <label style="font-size: 0.85rem; color: #666;">Total</label>
                        <p style="font-weight: 700; color: #27ae60;">₹<?php echo number_format($order['total_amount'], 2); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status -->
<div class="content-card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-edit"></i> Update Order Status</h2>
    </div>
    <div class="card-body">
        <form method="GET" action="<?php echo BASE_URL; ?>admin/customer-orders.php" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
            <select name="update_status" class="form-select" style="padding: 0.6rem 1rem; border-radius: 5px; border: 1px solid #ddd; min-width: 200px;">
                <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo $order['order_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Status
            </button>
        </form>
    </div>
</div>

<?php require_once 'layout-footer.php'; ?>
