<?php
$pageTitle = 'Test Email';
$activePage = 'test-email';
require_once 'layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/BrevoEmailService.php';

$database = new Database();
$db = $database->connect();

$result = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toEmail = trim($_POST['to_email'] ?? '');
    $toName = trim($_POST['to_name'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($toEmail) || empty($subject) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $emailService = new BrevoEmailService();

        $htmlContent = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Test Email from Curtisyn</h1>
        </div>
        <div class="content">
            ' . nl2br(htmlspecialchars($message)) . '
        </div>
        <div class="footer">
            <p>This is a test email sent from Curtisyn Admin Panel</p>
            <p>&copy; ' . date('Y') . ' Curtisyn. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';

        $success = $emailService->sendEmail($toEmail, $toName, $subject, $htmlContent);

        if ($success) {
            $result = 'Email sent successfully to ' . htmlspecialchars($toEmail);
        } else {
            $error = 'Failed to send email. Common issues:<br>';
            $error .= '1. Sender email (devarshpathak447@gmail.com) not verified in Brevo<br>';
            $error .= '2. Invalid API key<br>';
            $error .= '3. Brevo account limits reached<br>';
            $error .= '4. IP address not whitelisted (if using dedicated IP)<br><br>';
            $error .= 'Check your server error logs for details.';
        }
    }
}

// Get all customers for quick selection
$customers = [];
if ($db) {
    $stmt = $db->query("SELECT id, full_name, email FROM users WHERE role = 'customer' AND status = 'active' ORDER BY full_name");
    $customers = $stmt->fetchAll();
}
?>

<?php if ($error): ?>
<div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
</div>
<?php endif; ?>

<?php if ($result): ?>
<div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fas fa-check-circle"></i> <?php echo $result; ?>
</div>
<?php endif; ?>

<div class="grid-2">
    <!-- Test Email Form -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-paper-plane" style="color: var(--primary-color);"></i> Send Test Email</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">To Email <span style="color: #e74c3c;">*</span></label>
                    <input type="email" name="to_email" class="form-input" placeholder="customer@example.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label">To Name</label>
                    <input type="text" name="to_name" class="form-input" placeholder="Customer Name">
                </div>

                <div class="form-group">
                    <label class="form-label">Subject <span style="color: #e74c3c;">*</span></label>
                    <input type="text" name="subject" class="form-input" placeholder="Test Email Subject" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Message <span style="color: #e74c3c;">*</span></label>
                    <textarea name="message" class="form-input" rows="6" placeholder="Enter your test message here..." required></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Test Email
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Select Customer -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-users" style="color: var(--success-color);"></i> Quick Select Customer</h3>
        </div>
        <div class="card-body">
            <?php if (empty($customers)): ?>
                <p style="color: #666; text-align: center;">No customers found.</p>
            <?php else: ?>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($customers as $customer): ?>
                    <div class="customer-item" style="padding: 1rem; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s;" onclick="fillEmail('<?php echo htmlspecialchars($customer['email']); ?>', '<?php echo htmlspecialchars($customer['full_name']); ?>')">
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($customer['full_name']); ?></div>
                        <div style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($customer['email']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Brevo Configuration Info -->
<div class="content-card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cog" style="color: var(--warning-color);"></i> Brevo Configuration</h3>
    </div>
    <div class="card-body">
        <?php
        $config = require __DIR__ . '/../config/brevo-config.php';
        ?>
        <p><strong>Sender Email:</strong> <?php echo htmlspecialchars($config['sender_email']); ?></p>
        <p><strong>Sender Name:</strong> <?php echo htmlspecialchars($config['sender_name']); ?></p>
        <p style="color: #666; margin-top: 1rem;">
            <i class="fas fa-info-circle"></i>
            Make sure the sender email is verified in your Brevo account.
        </p>
    </div>
</div>

<script>
function fillEmail(email, name) {
    document.querySelector('input[name="to_email"]').value = email;
    document.querySelector('input[name="to_name"]').value = name;
    document.querySelector('input[name="subject"]').focus();
}

// Add hover effect for customer items
document.querySelectorAll('.customer-item').forEach(item => {
    item.addEventListener('mouseenter', function() {
        this.style.background = '#f8f9fa';
    });
    item.addEventListener('mouseleave', function() {
        this.style.background = 'transparent';
    });
});
</script>

<?php require_once 'layout-footer.php'; ?>
