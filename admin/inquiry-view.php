<?php
$pageTitle = 'View Inquiry';
$activePage = 'inquiries';
require_once 'layout.php';
require_once __DIR__ . '/../config/database.php';

$inquiryId = $_GET['id'] ?? '';

if (empty($inquiryId)) {
    redirect('inquiries.php');
}

$database = new Database();
$db = $database->connect();

$inquiry = null;
if ($db) {
    $stmt = $db->prepare("SELECT pi.*, p.name as product_name, p.price as product_price, u.full_name as customer_name FROM product_inquiries pi JOIN products p ON pi.product_id = p.id JOIN users u ON pi.customer_id = u.id WHERE pi.inquiry_id = :inquiry_id");
    $stmt->bindParam(':inquiry_id', $inquiryId);
    $stmt->execute();
    $inquiry = $stmt->fetch();
}

if (!$inquiry) {
    redirect('inquiries.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? 'pending';
    $adminComment = trim($_POST['admin_comment'] ?? '');

    $validStatuses = ['pending', 'accepted', 'in_progress', 'completed', 'rejected'];
    if (in_array($status, $validStatuses)) {
        $stmt = $db->prepare("UPDATE product_inquiries SET status = :status, admin_comment = :admin_comment WHERE inquiry_id = :inquiry_id");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':admin_comment', $adminComment);
        $stmt->bindParam(':inquiry_id', $inquiryId);

        if ($stmt->execute()) {
            $success = 'Inquiry updated successfully.';
            $inquiry['status'] = $status;
            $inquiry['admin_comment'] = $adminComment;
        } else {
            $error = 'Failed to update inquiry.';
        }
    } else {
        $error = 'Invalid status.';
    }
}
?>

<?php if ($error): ?>
<div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
</div>
<?php endif; ?>

<div class="grid-2">
    <!-- Product Information -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-box" style="color: var(--primary-color);"></i> Product Information</h3>
        </div>
        <div class="card-body">
            <p><strong>Product:</strong> <?php echo htmlspecialchars($inquiry['product_name']); ?></p>
            <p><strong>Price:</strong> ₹<?php echo number_format($inquiry['product_price'], 2); ?></p>
        </div>
    </div>

    <!-- Inquiry Information -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-info-circle" style="color: var(--accent-color);"></i> Inquiry Information</h3>
        </div>
        <div class="card-body">
            <p><strong>Inquiry ID:</strong> <?php echo htmlspecialchars($inquiry['inquiry_id']); ?></p>
            <p><strong>Status:</strong> <span class="badge badge-<?php echo $inquiry['status']; ?>"><?php echo ucwords(str_replace('_', ' ', $inquiry['status'])); ?></span></p>
            <p><strong>Submitted On:</strong> <?php echo date('M d, Y H:i', strtotime($inquiry['created_at'])); ?></p>
        </div>
    </div>
</div>

<!-- Customer Information -->
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user" style="color: var(--success-color);"></i> Customer Information</h3>
    </div>
    <div class="card-body">
        <div class="grid-2">
            <div>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($inquiry['full_name']); ?></p>
                <p><strong>Mobile:</strong> <?php echo htmlspecialchars($inquiry['mobile']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($inquiry['email']); ?></p>
            </div>
            <div>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($inquiry['address']); ?></p>
                <p><strong>City:</strong> <?php echo htmlspecialchars($inquiry['city']); ?></p>
                <p><strong>State:</strong> <?php echo htmlspecialchars($inquiry['state']); ?></p>
                <p><strong>Pincode:</strong> <?php echo htmlspecialchars($inquiry['pincode']); ?></p>
            </div>
        </div>
        <?php if ($inquiry['message']): ?>
        <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
            <strong>Customer Message:</strong><br>
            <?php echo nl2br(htmlspecialchars($inquiry['message'])); ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Admin Actions -->
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-edit" style="color: var(--warning-color);"></i> Update Inquiry</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Current Status</label>
                <select name="status" class="form-input">
                    <option value="pending" <?php echo $inquiry['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="accepted" <?php echo $inquiry['status'] === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                    <option value="in_progress" <?php echo $inquiry['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $inquiry['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="rejected" <?php echo $inquiry['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Admin Comment / Message to Customer</label>
                <textarea name="admin_comment" class="form-input" rows="4" placeholder="Enter your response or comment..."><?php echo htmlspecialchars($inquiry['admin_comment'] ?? ''); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Inquiry</button>
                <a href="inquiries.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Inquiries</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'layout-footer.php'; ?>
