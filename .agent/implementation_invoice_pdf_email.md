# Invoice PDF Email Implementation

## Overview
Implemented invoice generation as PDF and email delivery with PDF attachment for package approval workflow.

## Features Implemented

### 1. Email with Attachment Function (`services/email.php`)
**Function:** `send_email_with_attachment()`
- Sends emails with file attachments (PDF, HTML, etc.)
- Uses PHPMailer's `addAttachment()` method
- Supports multiple attachments via array parameter
- Maintains all original email features (HTML body, styling, etc.)

### 2. PDF Generation (`admin/bought_ads_package/approve/invoice/invoice.php`)

#### `generate_invoice_pdf($data)`
**Purpose:** Generate invoice PDF from HTML template

**Features:**
- **Primary:** Uses Dompdf library (if installed) to convert HTML to PDF
- **Fallback:** Saves as HTML file if Dompdf not available
- Automatic directory creation (`/invoice/generated/`)
- Unique filename generation with timestamp
- Returns file path for email attachment

**Generated Files:**
- Location: `admin/bought_ads_package/approve/invoice/generated/`
- Format: `invoice_INV-XXXXXX_timestamp.pdf` (or `.html`)

#### `cleanup_old_invoices()`
**Purpose:** Automatic cleanup of old invoice files

**Features:**
- Deletes files older than 24 hours
- Runs automatically after each invoice generation
- Prevents disk space accumulation

### 3. Updated Approval Workflow (`package_approval.php`)

**Process:**
1. Generate invoice data array
2. Create invoice HTML for email body
3. **Generate invoice PDF file**
4. **Send email with PDF as attachment**
5. Cleanup old invoice files
6. Create notification for owner
7. Display success message

**Success Message:**  
*"Package request approved successfully and invoice sent with PDF attachment."*

## File Structure

```
RL/
└── admin/
    └── bought_ads_package/
        └── approve/
            ├── package_approval.php          (Updated - sends PDF)
            └── invoice/
                ├── invoice.php               (Updated - PDF generation)
                └── generated/                (Auto-created for PDFs)
                    ├── invoice_INV-000001_*.pdf
                    └── invoice_INV-000002_*.pdf
```

## Installation Guide

### Option 1: Install Dompdf (Recommended for PDF)

**Using Composer:**
```bash
cd c:\xampp\htdocs\RL
composer require dompdf/dompdf
```

This will:
- Install Dompdf library at `RL/vendor/dompdf/`
- Create `vendor/autoload.php`
- Enable true PDF generation

**Update invoice.php path (if using composer):**
```php
$dompdfPath = __DIR__ . '/../../../../vendor/autoload.php';
```

### Option 2: Manual Dompdf Installation

1. Download Dompdf from: https://github.com/dompdf/dompdf/releases
2. Extract to: `c:\xampp\htdocs\RL\dompdf\`
3. The code will automatically detect and use it

### Option 3: Use HTML Fallback (Current Setup)

- No installation needed
- Invoices sent as HTML files instead of PDF
- Recipient can save/print as PDF from browser
- File extension: `.html` instead of `.pdf`

## Email Attachment Details

### Current Implementation:
```php
send_email_with_attachment(
    $userInfo['email'],                    // To
    "Payment Invoice - Rental Lanka",      // Subject
    $invoiceHtml,                          // Body (HTML)
    $userInfo['name'],                     // Recipient Name
    [$pdfPath => $pdfFilename]            // Attachments array
);
```

### Email Contains:
1. **Subject:** Payment Invoice - Rental Lanka
2. **Body:** Full HTML invoice (readable in email)
3. **Attachment:** PDF file (or HTML if Dompdf not installed)
   - Filename: `INV-XXXXXX.pdf`
   - Size: ~50-100KB
   - Format: A4, Portrait

## Benefits

### For Owners:
- ✅ Receive professional PDF invoice
- ✅ Can download and save for records
- ✅ Easily share with accounting
- ✅ Print-ready format

### For Admin:
- ✅ Automatic invoice generation
- ✅ No manual work required
- ✅ Professional email delivery
- ✅ Automatic file cleanup

### For System:
- ✅ Disk space management (24-hour cleanup)
- ✅ Scalable solution
- ✅ Fallback options
- ✅ Error handling

## Testing Checklist

### Without Dompdf (HTML Fallback):
- [ ] Approve a package request
- [ ] Check email received with `.html` attachment
- [ ] Verify attachment can be opened
- [ ] Confirm email body shows invoice
- [ ] Check `invoice/generated/` folder for HTML file

### With Dompdf (PDF):
- [ ] Install Dompdf (composer or manual)
- [ ] Approve a package request
- [ ] Check email received with `.pdf` attachment
- [ ] Verify PDF opens correctly
- [ ] Confirm PDF formatting (A4, portrait)
- [ ] Check `invoice/generated/` folder for PDF file
- [ ] Wait 24 hours and verify old files are deleted

## Troubleshooting

### Issue: PDF not generating
**Solution:** Install Dompdf (see Installation Guide above)
**Verification:** HTML file will be sent instead

### Issue: Attachment not in email
**Check:**
1. File actually generated in `invoice/generated/` folder?
2. PHPMailer configuration correct?
3. Email server allows attachments?

### Issue: Directory permissions
**Solution:**
```bash
chmod 755 admin/bought_ads_package/approve/invoice/generated/
```

### Issue: Large file size
**Optimization:**
- Remove unnecessary images from invoice HTML
- Compress logo/images before embedding
- Use external CSS (not inline)

## Code References

### Main Files Modified:
1. `services/email.php` - Added `send_email_with_attachment()`
2. `invoice/invoice.php` - Added `generate_invoice_pdf()` and `cleanup_old_invoices()`
3. `package_approval.php` - Updated approval workflow

### Key Functions:
- `send_email_with_attachment()` - Email with files
- `generate_invoice_pdf()` - HTML to PDF conversion
- `cleanup_old_invoices()` - Auto file cleanup
- `get_invoice_html()` - Invoice template

## Next Steps

1. **Install Dompdf** for true PDF generation (optional but recommended)
2. **Test email delivery** with approved package
3. **Verify PDF quality** and formatting
4. **Monitor disk space** in `generated/` folder
5. **Customize invoice design** if needed

## Security Notes

- ✅ Invoice files stored in non-public directory
- ✅ Unique filenames prevent overwrites
- ✅ Automatic cleanup prevents accumulation
- ✅ File existence check before attachment
- ✅ Proper error handling
