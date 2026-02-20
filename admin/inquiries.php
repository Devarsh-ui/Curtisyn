<?php
$pageTitle = 'Product Inquiries';
$activePage = 'inquiries';
require_once 'layout.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->connect();

$inquiries = [];
if ($db) {
    $tableExists = $db->query("SHOW TABLES LIKE 'product_inquiries'")->rowCount() > 0;
    if ($tableExists) {
        $stmt = $db->query("SELECT pi.*, p.name as product_name, u.full_name as customer_name FROM product_inquiries pi JOIN products p ON pi.product_id = p.id JOIN users u ON pi.customer_id = u.id ORDER BY pi.created_at DESC");
        $inquiries = $stmt->fetchAll();
    }
}
?>

<!-- Inquiries Table -->
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-question-circle" style="color: var(--accent-color);"></i> All Product Inquiries</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Inquiry ID</th>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inquiries)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">No inquiries found.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($inquiries as $inquiry): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($inquiry['inquiry_id']); ?></strong></td>
                        <td><?php echo htmlspecialchars($inquiry['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($inquiry['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($inquiry['mobile']); ?></td>
                        <td><?php echo htmlspecialchars($inquiry['email']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($inquiry['created_at'])); ?></td>
                        <td><span class="badge badge-<?php echo $inquiry['status']; ?>"><?php echo ucwords(str_replace('_', ' ', $inquiry['status'])); ?></span></td>
                        <td><a href="inquiry-view.php?id=<?php echo $inquiry['inquiry_id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> View</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'layout-footer.php'; ?>
