<?php
class BrevoEmailService {
    private $apiKey;
    private $senderEmail;
    private $senderName;
    private $apiUrl;

    public function __construct() {
        $config = require __DIR__ . '/../config/brevo-config.php';
        $this->apiKey = $config['api_key'];
        $this->senderEmail = $config['sender_email'];
        $this->senderName = $config['sender_name'];
        $this->apiUrl = $config['api_url'];
    }

    public function sendEmail($toEmail, $toName, $subject, $htmlContent, $attachments = []) {
        $data = [
            'sender' => [
                'email' => $this->senderEmail,
                'name' => $this->senderName
            ],
            'to' => [
                [
                    'email' => $toEmail,
                    'name' => $toName
                ]
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent
        ];

        if (!empty($attachments)) {
            $data['attachment'] = $attachments;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'api-key: ' . $this->apiKey,
            'content-type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Log for debugging
        if ($httpCode !== 201 && $httpCode !== 200) {
            error_log('Brevo API Error - HTTP Code: ' . $httpCode);
            error_log('Brevo API Response: ' . $response);
            error_log('Brevo API Curl Error: ' . $curlError);
        }

        return $httpCode === 201 || $httpCode === 200;
    }

    public function sendInvoiceEmail($toEmail, $toName, $orderData, $pdfPath) {
        $subject = 'Your Order Confirmation & Invoice - ' . $orderData['order_id'];
        $htmlContent = $this->getInvoiceEmailTemplate($orderData);

        $attachments = [];
        if (file_exists($pdfPath)) {
            $attachments[] = [
                'name' => 'Invoice-' . $orderData['order_id'] . '.pdf',
                'content' => base64_encode(file_get_contents($pdfPath))
            ];
        }

        return $this->sendEmail($toEmail, $toName, $subject, $htmlContent, $attachments);
    }

    public function sendStatusUpdateEmail($toEmail, $toName, $orderData, $pdfPath) {
        $subject = 'Order Status Updated - ' . $orderData['order_id'] . ' - ' . ucfirst($orderData['order_status']);
        $htmlContent = $this->getStatusUpdateEmailTemplate($orderData);

        $attachments = [];
        if (file_exists($pdfPath)) {
            $attachments[] = [
                'name' => 'Invoice-' . $orderData['order_id'] . '.pdf',
                'content' => base64_encode(file_get_contents($pdfPath))
            ];
        }

        return $this->sendEmail($toEmail, $toName, $subject, $htmlContent, $attachments);
    }

    private function getInvoiceEmailTemplate($orderData) {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .order-info { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
        .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Thank You for Your Order!</h1>
            <p>Your order has been confirmed</p>
        </div>
        <div class="content">
            <p>Dear ' . htmlspecialchars($orderData['customer_name']) . ',</p>
            <p>Thank you for shopping with Curtisyn. Your order has been successfully placed and is being processed.</p>
            
            <div class="order-info">
                <h3>Order Details</h3>
                <p><strong>Order ID:</strong> ' . htmlspecialchars($orderData['order_id']) . '</p>
                <p><strong>Order Date:</strong> ' . date('M d, Y', strtotime($orderData['created_at'])) . '</p>
                <p><strong>Payment Method:</strong> ' . strtoupper($orderData['payment_method']) . '</p>
                <p><strong>Total Amount:</strong> ₹' . number_format($orderData['total_amount'], 2) . '</p>
                <p><strong>Status:</strong> ' . ucfirst($orderData['order_status']) . '</p>
            </div>
            
            <p>Please find your invoice attached to this email. You can also track your order status in your account dashboard.</p>
            
            <div class="footer">
                <p>If you have any questions, please contact us at support@curtisyn.com</p>
                <p>&copy; ' . date('Y') . ' Curtisyn. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>';
    }

    private function getStatusUpdateEmailTemplate($orderData) {
        $statusMessages = [
            'pending' => 'Your order is pending confirmation.',
            'confirmed' => 'Your order has been confirmed and is being prepared.',
            'shipped' => 'Your order has been shipped and is on its way!',
            'delivered' => 'Your order has been delivered. Enjoy!',
            'cancelled' => 'Your order has been cancelled.'
        ];

        $message = $statusMessages[$orderData['order_status']] ?? 'Your order status has been updated.';

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .status-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
        .status-' . $orderData['order_status'] . ' { color: ' . $this->getStatusColor($orderData['order_status']) . '; font-size: 24px; font-weight: bold; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Status Update</h1>
        </div>
        <div class="content">
            <p>Dear ' . htmlspecialchars($orderData['customer_name']) . ',</p>
            
            <div class="status-box">
                <p class="status-' . $orderData['order_status'] . '">' . strtoupper($orderData['order_status']) . '</p>
                <p>' . $message . '</p>
                <p><strong>Order ID:</strong> ' . htmlspecialchars($orderData['order_id']) . '</p>
            </div>
            
            <p>Your updated invoice is attached to this email. You can track your order anytime from your account.</p>
            
            <div class="footer">
                <p>Thank you for choosing Curtisyn!</p>
                <p>&copy; ' . date('Y') . ' Curtisyn. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>';
    }

    private function getStatusColor($status) {
        $colors = [
            'pending' => '#f39c12',
            'confirmed' => '#3498db',
            'shipped' => '#9b59b6',
            'delivered' => '#27ae60',
            'cancelled' => '#e74c3c'
        ];
        return $colors[$status] ?? '#333';
    }
}
