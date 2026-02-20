<?php
$pageTitle = 'Contact Us';
$currentPage = 'contact';
require_once 'includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $subject = sanitizeInput($_POST['subject'] ?? '');
        $message = sanitizeInput($_POST['message'] ?? '');
        
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = 'All fields required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email.';
        } else {
            $success = 'Thank you for your message. We will get back to you soon.';
        }
    }
}

$csrfToken = generateCsrfToken();
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Contact Us</h1>
        
        <div class="form-container">
            <?php 
            if ($error) echo displayError($error);
            if ($success) echo displaySuccess($success);
            ?>
            
            <form method="POST" action="" onsubmit="return validateContactForm()">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" id="name" name="name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" id="subject" name="subject" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="message" class="form-label">Message</label>
                    <textarea id="message" name="message" class="form-input" rows="5" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Send Message</button>
            </form>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
