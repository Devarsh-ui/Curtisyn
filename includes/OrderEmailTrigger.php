<?php
require_once __DIR__ . '/BrevoEmailService.php';
require_once __DIR__ . '/InvoiceGenerator.php';

class OrderEmailTrigger {
    private $db;
    private $emailService;
    private $invoiceDir;

    public function __construct($database) {
        $this->db = $database;
        $this->emailService = new BrevoEmailService();
        $this->invoiceDir = __DIR__ . '/../invoices/';

        if (!is_dir($this->invoiceDir)) {
            mkdir($this->invoiceDir, 0755, true);
        }
    }

    public function onOrderPlaced($orderId) {
        $orderData = $this->getOrderData($orderId);
        if (!$orderData) {
            return false;
        }

        // Generate invoice PDF
        $pdfPath = $this->generateInvoice($orderData);

        // Send email via Brevo
        $result = $this->emailService->sendInvoiceEmail(
            $orderData['customer_email'],
            $orderData['customer_name'],
            $orderData,
            $pdfPath
        );

        // Log email sent
        $this->logEmailSent($orderId, 'order_confirmation', $result);

        // Cleanup PDF after sending
        $this->cleanupInvoice($pdfPath);

        return $result;
    }

    public function onOrderStatusUpdate($orderId, $newStatus) {
        $orderData = $this->getOrderData($orderId);
        if (!$orderData) {
            return false;
        }

        // Check if email already sent for this status
        if ($this->isEmailAlreadySent($orderId, $newStatus)) {
            return true;
        }

        // Update order status in data
        $orderData['order_status'] = $newStatus;

        // Generate updated invoice PDF
        $pdfPath = $this->generateInvoice($orderData);

        // Send status update email via Brevo
        $result = $this->emailService->sendStatusUpdateEmail(
            $orderData['customer_email'],
            $orderData['customer_name'],
            $orderData,
            $pdfPath
        );

        // Log email sent
        $this->logEmailSent($orderId, 'status_update_' . $newStatus, $result);

        // Cleanup PDF after sending
        $this->cleanupInvoice($pdfPath);

        return $result;
    }

    private function getOrderData($orderId) {
        $stmt = $this->db->prepare("
            SELECT co.*, p.name as product_name, u.full_name as customer_name, u.email as customer_email, u.full_name as customer_name
            FROM customer_orders co
            JOIN products p ON co.product_id = p.id
            JOIN users u ON co.user_id = u.id
            WHERE co.order_id = :order_id
        ");
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        $order = $stmt->fetch();
        
        if ($order) {
            // Map fields to expected format
            $order['customer_phone'] = $order['customer_phone'] ?? '';
            $order['shipping_address'] = $order['customer_address'] ?? '';
            $order['order_status'] = $order['status'] ?? 'pending';
            $order['created_at'] = $order['order_date'] ?? date('Y-m-d H:i:s');
            $order['price_per_unit'] = $order['price'] ?? 0;
        }
        
        return $order;
    }

    private function generateInvoice($orderData) {
        $filename = 'invoice_' . $orderData['order_id'] . '_' . time() . '.pdf';
        $filepath = $this->invoiceDir . $filename;

        $generator = new InvoiceGenerator($orderData);
        $generator->generate($filepath);

        return $filepath;
    }

    private function cleanupInvoice($pdfPath) {
        // Delete PDF after 5 minutes (or immediately if preferred)
        if (file_exists($pdfPath)) {
            unlink($pdfPath);
        }
    }

    private function logEmailSent($orderId, $emailType, $success) {
        $stmt = $this->db->prepare("
            INSERT INTO email_logs (order_id, email_type, sent_at, status)
            VALUES (:order_id, :email_type, NOW(), :status)
        ");
        $status = $success ? 'sent' : 'failed';
        $stmt->bindParam(':order_id', $orderId);
        $stmt->bindParam(':email_type', $emailType);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
    }

    private function isEmailAlreadySent($orderId, $status) {
        $emailType = 'status_update_' . $status;
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM email_logs
            WHERE order_id = :order_id AND email_type = :email_type AND status = 'sent'
        ");
        $stmt->bindParam(':order_id', $orderId);
        $stmt->bindParam(':email_type', $emailType);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
}
