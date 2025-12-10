<?php

function get_invoice_html($data) {
    $invoiceNo = $data['invoice_no'];
    $date = $data['date'];
    $ownerName = $data['owner_name'];
    $ownerEmail = $data['owner_email'];
    $packageName = $data['package_name'];
    $amount = number_format($data['amount'], 2);
    $paymentMethod = ucfirst($data['payment_method']);
    
    $appUrl = app_url(); // Ensure app_url helper is available
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Invoice - Rental Lanka</title>
        <style>
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #555; max-width: 800px; margin: 0 auto; line-height: 24px; }
            .invoice-box { border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); padding: 30px; border-radius: 8px; }
            .header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
            .company-info { font-size: 24px; font-weight: bold; color: #2D5016; }
            .invoice-details { text-align: right; }
            .invoice-details h2 { margin: 0; color: #333; }
            .invoice-info { margin-bottom: 40px; }
            .info-group { margin-bottom: 15px; }
            .info-label { font-weight: bold; color: #333; }
            .table-container { margin-bottom: 30px; }
            table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
            table th { background: #f8f9fa; color: #333; font-weight: bold; padding: 12px; border-bottom: 2px solid #eee; }
            table td { padding: 12px; border-bottom: 1px solid #eee; }
            .total-row td { font-weight: bold; background: #fff; border-bottom: none; font-size: 18px; color: #2D5016; }
            .footer { text-align: center; margin-top: 50px; padding-top: 20px; border-top: 1px solid #eee; font-size: 14px; color: #888; }
            .status-paid { color: #2D5016; border: 2px solid #2D5016; padding: 5px 15px; border-radius: 4px; font-weight: bold; text-transform: uppercase; display: inline-block; transform: rotate(-5deg); margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class='invoice-box'>
            <div class='header'>
                <div>
                    <div class='company-info'>Rental Lanka</div>
                    <div style='font-size: 14px; color: #777; margin-top: 5px;'>Your Trusted Rental Partner</div>
                </div>
                <div class='invoice-details'>
                    <h2>INVOICE</h2>
                    <div class='status-paid'>PAID</div>
                </div>
            </div>

            <div class='invoice-info'>
                <table cellpadding='0' cellspacing='0'>
                    <tr>
                        <td style='border: none; padding-left: 0;'>
                            <div class='info-group'>
                                <div class='info-label'>Invoiced To:</div>
                                <div>{$ownerName}</div>
                                <div>{$ownerEmail}</div>
                            </div>
                        </td>
                        <td style='border: none; text-align: right; padding-right: 0;'>
                            <div class='info-group'>
                                <div class='info-label'>Invoice #: {$invoiceNo}</div>
                                <div class='info-label'>Date: {$date}</div>
                                <div class='info-label'>Payment Method: {$paymentMethod}</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class='table-container'>
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th style='text-align: right;'>Amount (LKR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>{$packageName}</strong><br>
                                <span style='font-size: 13px; color: #777;'>Ads Package Subscription</span>
                            </td>
                            <td style='text-align: right;'>{$amount}</td>
                        </tr>
                        <tr class='total-row'>
                            <td style='text-align: right; padding-right: 20px;'>Total:</td>
                            <td style='text-align: right;'>{$amount} LKR</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class='footer'>
                <p>Thank you for choosing Rental Lanka!</p>
                <p>If you have any questions about this invoice, please contact support@rentallanka.com</p>
                <p>&copy; " . date('Y') . " Rental Lanka. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Generate invoice PDF and return file path
 * 
 * @param array $data Invoice data
 * @return string|false Path to generated PDF or false on failure
 */
function generate_invoice_pdf($data) {
    // Check if Dompdf is available (installed via composer)
    $dompdfPath = __DIR__ . '/../../../../vendor/autoload.php';
    
    if (file_exists($dompdfPath)) {
        // Use Dompdf library
        require_once $dompdfPath;
        
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new \Dompdf\Dompdf($options);
        
        // Get invoice HTML
        $html = get_invoice_html($data);
        
        // Load HTML
        $dompdf->loadHtml($html);
        
        // Set paper size
        $dompdf->setPaper('A4', 'portrait');
        
        // Render PDF (generates the PDF)
        $dompdf->render();
        
        // Create invoices directory if it doesn't exist
        $invoiceDir = __DIR__ . '/generated';
        if (!is_dir($invoiceDir)) {
            mkdir($invoiceDir, 0755, true);
        }
        
        // Generate filename
        $filename = 'invoice_' . $data['invoice_no'] . '_' . time() . '.pdf';
        $filepath = $invoiceDir . '/' . $filename;
        
        // Save PDF to file
        file_put_contents($filepath, $dompdf->output());
        
        return $filepath;
    }
    
    // Fallback: Save as HTML file if Dompdf not available
    $invoiceDir = __DIR__ . '/generated';
    if (!is_dir($invoiceDir)) {
        mkdir($invoiceDir, 0755, true);
    }
    
    // Generate filename
    $filename = 'invoice_' . $data['invoice_no'] . '_' . time() . '.html';
    $filepath = $invoiceDir . '/' . $filename;
    
    // Get invoice HTML
    $html = get_invoice_html($data);
    
    // Save as HTML
    file_put_contents($filepath, $html);
    
    // Note: To get actual PDF, install Dompdf via: composer require dompdf/dompdf
    return $filepath;
}

/**
 * Clean up old invoice files (older than 24 hours)
 */
function cleanup_old_invoices() {
    $invoiceDir = __DIR__ . '/generated';
    if (!is_dir($invoiceDir)) {
        return;
    }
    
    $files = glob($invoiceDir . '/*');
    $now = time();
    
    foreach ($files as $file) {
        if (is_file($file)) {
            // Delete files older than 24 hours
            if ($now - filemtime($file) >= 24 * 3600) {
                unlink($file);
            }
        }
    }
}
