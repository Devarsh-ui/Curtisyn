<?php
$pageTitle = 'Commission Settings';
$activePage = 'commission';
require_once 'layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';

$currentCommission = 10;
if ($db) {
    $currentCommission = getGlobalCommission($db);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $commission = (float)($_POST['commission'] ?? 0);
        
        if ($commission < 0 || $commission > 100) {
            $error = 'Commission must be between 0 and 100.';
        } else {
            $stmt = $db->prepare("
                INSERT INTO settings (setting_key, setting_value) 
                VALUES ('global_commission', :value)
                ON DUPLICATE KEY UPDATE setting_value = :value
            ");
            $stmt->bindParam(':value', $commission);
            
            if ($stmt->execute()) {
                $success = 'Commission updated successfully.';
                $currentCommission = $commission;
            } else {
                $error = 'Failed to update commission.';
            }
        }
    }
}

$csrfToken = generateCsrfToken();
?>

<div class="content-card" style="max-width: 600px;">
    <div class="card-header">
        <h2 class="card-title">Global Commission Settings</h2>
    </div>
    <div class="card-body">
        <?php if ($error) echo displayError($error); ?>
        <?php if ($success) echo displaySuccess($success); ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="form-group">
                <label class="form-label">Commission Percentage (%)</label>
                <input type="number" name="commission" class="form-input" 
                       value="<?php echo $currentCommission; ?>" 
                       step="0.01" min="0" max="100" required>
                <small style="color: #666; display: block; margin-top: 0.5rem;">
                    This percentage will be added to the base price for all products.
                </small>
            </div>
            
            <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; margin: 1.5rem 0;">
                <h4 style="margin-bottom: 0.5rem;">Price Calculation Example</h4>
                <p style="color: #666; font-size: 0.9rem;">
                    Base Price: ₹100<br>
                    Commission: <?php echo $currentCommission; ?>%<br>
                    <strong>Final Price: ₹<?php echo number_format(calculateFinalPrice(100, $currentCommission), 2); ?></strong>
                </p>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Commission</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'layout-footer.php'; ?>
