<?php
// Test PDF generation
require __DIR__ . '/invoice.php';

// Test invoice data
$testData = [
    'invoice_no' => 'INV-TEST001',
    'date' => date('Y-m-d'),
    'owner_name' => 'Test Owner',
    'owner_email' => 'test@example.com',
    'package_name' => 'Premium Package',
    'amount' => 5000.00,
    'payment_method' => 'Bank Transfer'
];

echo "Generating test invoice PDF...<br>";

$pdfPath = generate_invoice_pdf($testData);

if ($pdfPath) {
    if (file_exists($pdfPath)) {
        $fileExt = pathinfo($pdfPath, PATHINFO_EXTENSION);
        $fileSize = filesize($pdfPath);
        
        echo "✅ Success!<br>";
        echo "File: " . basename($pdfPath) . "<br>";
        echo "Type: ." . $fileExt . "<br>";
        echo "Size: " . number_format($fileSize) . " bytes<br>";
        echo "Path: " . $pdfPath . "<br><br>";
        
        if ($fileExt === 'pdf') {
            echo "🎉 <strong>PDF generation is working!</strong><br>";
            echo "<a href='generated/" . basename($pdfPath) . "' target='_blank'>View PDF</a>";
        } else {
            echo "⚠️ File is HTML, not PDF. Dompdf may not be loading correctly.";
        }
    } else {
        echo "❌ File was not created.";
    }
} else {
    echo "❌ PDF generation failed.";
}
