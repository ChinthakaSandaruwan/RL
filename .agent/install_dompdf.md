# Dompdf Installation for PDF Invoice Generation

## Quick Install (Recommended)

### Option 1: Using Composer
```bash
cd c:\xampp\htdocs\RL
composer require dompdf/dompdf
```

After installation, the code will automatically use Dompdf to generate real PDFs.

---

## Option 2: Manual Download (Alternative)

1. Download latest release: https://github.com/dompdf/dompdf/releases
2. Extract the zip file
3. Move the extracted folder to: `c:\xampp\htdocs\RL\dompdf\`
4. Ensure the autoload file is at: `c:\xampp\htdocs\RL\dompdf\autoload.inc.php`

---

## Verification

After installation, check if Dompdf is working:

1. **Approve a test package request**
2. **Check the generated file:**
   - Location: `admin/bought_ads_package/approve/invoice/generated/`
   - Extension should be `.pdf` (not `.html`)
3. **Open the PDF** and verify it renders correctly
4. **Check email attachment** - should be PDF format

---

## Current Status (Without Dompdf)

The system is currently working with an HTML fallback:
- ✅ Invoices are generated as `.html` files
- ✅ Emails are sent with HTML attachments
- ✅ Recipients can view the invoice in browser
- ✅ Recipients can save/print as PDF from browser

This is functional but installing Dompdf provides:
- ✅ True PDF generation
- ✅ Better cross-platform compatibility
- ✅ Professional appearance
- ✅ Smaller file sizes

---

## Installation Video (Composer)

If you don't have Composer installed:
1. Download Composer: https://getcomposer.org/download/
2. Run the installer
3. Open Command Prompt in `c:\xampp\htdocs\RL`
4. Run: `composer require dompdf/dompdf`

Done! The system will automatically detect and use Dompdf.
