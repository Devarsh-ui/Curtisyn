<?php
require_once __DIR__ . '/../vendor/autoload.php';

class InvoiceGenerator {
    private $orderData;
    private $pdf;

    public function __construct($orderData) {
        $this->orderData = $orderData;
    }

    public function generate($outputPath = null) {
        if ($outputPath) {
            $this->createSimplePDF($outputPath);
            return $outputPath;
        }
        return $this->getInvoiceHTML();
    }

    private function getInvoiceHTML() {
        $order = $this->orderData;
        $invoiceNumber = 'INV-' . $order['order_id'];
        $invoiceDate = date('M d, Y');
        $orderDate = date('M d, Y', strtotime($order['updated_at'] ?? $order['created_at'] ?? date('Y-m-d H:i:s')));

        $productPrice = $order['price_per_unit'] * $order['quantity'];
        $platformCharge = $productPrice * 1;
        $total = $productPrice + $platformCharge;

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice ' . $invoiceNumber . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333; }
        .invoice-container { max-width: 800px; margin: 0 auto; padding: 40px; }
        .invoice-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; }
        .invoice-header h1 { font-size: 28px; margin-bottom: 10px; }
        .invoice-header p { opacity: 0.9; }
        .invoice-body { background: #fff; padding: 30px; border: 1px solid #e0e0e0; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 16px; font-weight: bold; color: #667eea; margin-bottom: 10px; border-bottom: 2px solid #667eea; padding-bottom: 5px; }
        .grid-2 { display: flex; justify-content: space-between; gap: 40px; }
        .grid-2 > div { flex: 1; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f8f9fa; font-weight: bold; color: #667eea; }
        .text-right { text-align: right; }
        .total-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .total-row.grand-total { font-size: 18px; font-weight: bold; color: #667eea; border-top: 2px solid #667eea; margin-top: 10px; padding-top: 10px; }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1ecf1; color: #0c5460; }
        .status-shipped { background: #e2e3f3; color: #383d7d; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <h1>Curtisyn</h1>
            <p>Premium Curtains & Drapes</p>
        </div>
        
        <div class="invoice-body">
            <div class="section">
                <div class="grid-2">
                    <div>
                        <div class="section-title">Invoice Details</div>
                        <p><strong>Invoice Number:</strong> ' . $invoiceNumber . '</p>
                        <p><strong>Invoice Date:</strong> ' . $invoiceDate . '</p>
                        <p><strong>Order ID:</strong> ' . htmlspecialchars($order['order_id']) . '</p>
                        <p><strong>Order Date:</strong> ' . $orderDate . '</p>
                        <p><strong>Payment Method:</strong> ' . strtoupper($order['payment_method']) . '</p>
                        <p><strong>Status:</strong> <span class="status-badge status-' . $order['order_status'] . '">' . ucfirst($order['order_status']) . '</span></p>
                    </div>
                    <div>
                        <div class="section-title">Customer Details</div>
                        <p><strong>Name:</strong> ' . htmlspecialchars($order['customer_name']) . '</p>
                        <p><strong>Email:</strong> ' . htmlspecialchars($order['customer_email']) . '</p>
                        <p><strong>Phone:</strong> ' . htmlspecialchars($order['customer_phone']) . '</p>
                        <p><strong>Address:</strong> ' . nl2br(htmlspecialchars($order['shipping_address'])) . '</p>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Order Items</div>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>' . htmlspecialchars($order['product_name']) . '</td>
                            <td>' . $order['quantity'] . '</td>
                            <td class="text-right">₹' . number_format($order['price_per_unit'], 2) . '</td>
                            <td class="text-right">₹' . number_format($productPrice, 2) . '</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="total-section">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>₹' . number_format($productPrice, 2) . '</span>
                </div>
                <div class="total-row">
                    <span>Platform Charges (100%):</span>
                    <span>₹' . number_format($platformCharge, 2) . '</span>
                </div>
                <div class="total-row grand-total">
                    <span>Grand Total:</span>
                    <span>₹' . number_format($total, 2) . '</span>
                </div>
            </div>

            <div class="footer">
                <p><strong>Thank you for your business!</strong></p>
                <p>For any queries, contact us at support@curtisyn.com</p>
                <p>&copy; ' . date('Y') . ' Curtisyn. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>';
    }

    private function convertHTMLToPDF($htmlPath, $pdfPath) {
        // Check if wkhtmltopdf is available
        $wkhtmltopdf = 'C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe';
        $wkhtmltopdf86 = 'C:\\Program Files (x86)\\wkhtmltopdf\\bin\\wkhtmltopdf.exe';

        $converter = null;
        if (file_exists($wkhtmltopdf)) {
            $converter = $wkhtmltopdf;
        } elseif (file_exists($wkhtmltopdf86)) {
            $converter = $wkhtmltopdf86;
        }

        if ($converter) {
            $cmd = '"' . $converter . '" --page-size A4 --margin-top 10mm --margin-bottom 10mm --margin-left 10mm --margin-right 10mm --encoding utf-8 "' . $htmlPath . '" "' . $pdfPath . '" 2>&1';
            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && file_exists($pdfPath) && filesize($pdfPath) > 0) {
                return true;
            }
        }

        // Fallback: Create a simple text-based PDF using FPDF-style approach
        $this->createSimplePDF($pdfPath);
        return true;
    }

    private function createSimplePDF($pdfPath) {
        $order = $this->orderData;
        $invoiceNumber = 'INV-' . $order['order_id'];
        $invoiceDate = date('M d, Y');

        $productPrice = $order['price_per_unit'] * $order['quantity'];
        $platformCharge = $productPrice * 1;
        $total = $productPrice + $platformCharge;

        // Create PDF using TCPDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Curtisyn');
        $pdf->SetAuthor('Curtisyn');
        $pdf->SetTitle('Invoice ' . $invoiceNumber);
        $pdf->SetSubject('Order Invoice');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 11);

        // Header with purple background
        $pdf->SetFillColor(102, 126, 234);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Rect(10, 10, 190, 35, 'F');
        $pdf->SetFont('helvetica', 'B', 24);
        $pdf->SetXY(10, 15);
        $pdf->Cell(190, 10, 'Curtisyn', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(190, 6, 'Premium Curtains & Drapes', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(190, 8, 'INVOICE', 0, 1, 'C');

        // Reset colors
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetY(50);

        // Invoice Details & Customer Details - Two columns
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(102, 126, 234);
        $pdf->Cell(95, 8, 'Invoice Details', 0, 0);
        $pdf->Cell(95, 8, 'Customer Details', 0, 1);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 10);

        // Row 1
        $pdf->Cell(40, 6, 'Invoice Number:', 0, 0);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(55, 6, $invoiceNumber, 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(30, 6, 'Name:', 0, 0);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(65, 6, $order['customer_name'], 0, 1);

        // Row 2
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Invoice Date:', 0, 0);
        $pdf->Cell(55, 6, $invoiceDate, 0, 0);
        $pdf->Cell(30, 6, 'Email:', 0, 0);
        $pdf->Cell(65, 6, $order['customer_email'], 0, 1);

        // Row 3
        $pdf->Cell(40, 6, 'Order ID:', 0, 0);
        $pdf->Cell(55, 6, $order['order_id'], 0, 0);
        $pdf->Cell(30, 6, 'Phone:', 0, 0);
        $pdf->Cell(65, 6, $order['customer_phone'], 0, 1);

        // Row 4
        $pdf->Cell(40, 6, 'Order Date:', 0, 0);
        $pdf->Cell(55, 6, date('M d, Y', strtotime($order['updated_at'] ?? $order['created_at'] ?? date('Y-m-d H:i:s'))), 0, 0);
        $pdf->Cell(30, 6, 'Address:', 0, 0);

        // Address multiline
        $address = $order['shipping_address'];
        $pdf->MultiCell(65, 6, $address, 0, 'L');

        // Row 5
        $pdf->Cell(40, 6, 'Payment Method:', 0, 0);
        $pdf->Cell(55, 6, strtoupper($order['payment_method']), 0, 1);

        // Row 6
        $pdf->Cell(40, 6, 'Status:', 0, 0);
        $pdf->Cell(55, 6, strtoupper($order['order_status']), 0, 1);

        $pdf->Ln(5);

        // Order Items Section
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(102, 126, 234);
        $pdf->Cell(0, 8, 'Order Items', 0, 1);
        $pdf->SetTextColor(0, 0, 0);

        // Table header
        $pdf->SetFillColor(248, 249, 250);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(80, 8, 'Product', 1, 0, 'L', true);
        $pdf->Cell(30, 8, 'Quantity', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Unit Price', 1, 0, 'R', true);
        $pdf->Cell(40, 8, 'Amount', 1, 1, 'R', true);

        // Table row
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(80, 8, $order['product_name'], 1, 0, 'L');
        $pdf->Cell(30, 8, $order['quantity'], 1, 0, 'C');
        $pdf->Cell(40, 8, 'Rs.' . number_format($order['price_per_unit'], 2), 1, 0, 'R');
        $pdf->Cell(40, 8, 'Rs.' . number_format($productPrice, 2), 1, 1, 'R');

        $pdf->Ln(5);

        // Pricing Summary Box
        $pdf->SetFillColor(248, 249, 250);
        $pdf->Rect(110, $pdf->GetY(), 90, 35, 'F');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(110, 8, '', 0, 0);
        $pdf->Cell(40, 8, 'Subtotal:', 0, 0);
        $pdf->Cell(50, 8, 'Rs.' . number_format($productPrice, 2), 0, 1, 'R');

        $pdf->Cell(110, 8, '', 0, 0);
        $pdf->Cell(40, 8, 'Platform Charges (100%):', 0, 0);
        $pdf->Cell(50, 8, 'Rs.' . number_format($platformCharge, 2), 0, 1, 'R');

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(102, 126, 234);
        $pdf->Cell(110, 10, '', 0, 0);
        $pdf->Cell(40, 10, 'Grand Total:', 0, 0);
        $pdf->Cell(50, 10, 'Rs.' . number_format($total, 2), 0, 1, 'R');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(15);

        // Footer
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Thank you for your business!', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, 'For any queries, contact us at support@curtisyn.com', 0, 1, 'C');
        $pdf->Cell(0, 5, '© ' . date('Y') . ' Curtisyn. All rights reserved.', 0, 1, 'C');

        // Output PDF
        $pdf->Output($pdfPath, 'F');
    }
}
