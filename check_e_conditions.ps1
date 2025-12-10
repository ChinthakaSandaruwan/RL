# Google Indexing E-Conditions Checker
# Checks for Error Conditions that prevent indexing

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  GOOGLE INDEXING E-CONDITIONS CHECKER" -ForegroundColor Cyan
Write-Host "  (Error Conditions Detection)" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

$SITE_DIR = "c:\xampp\htdocs\RL"
$errorCount = 0

Write-Host "[E1] Checking for NOINDEX tags (blocks indexing)..." -ForegroundColor Yellow
$noindexInIndex = Select-String -Path "$SITE_DIR\index.php" -Pattern "noindex" -Quiet
if ($noindexInIndex) {
    Write-Host "   [ERROR] NOINDEX found in index.php - THIS BLOCKS INDEXING!" -ForegroundColor Red
    $errorCount++
}
else {
    Write-Host "   [OK] No noindex in index.php" -ForegroundColor Green
}

$noindexInAbout = Select-String -Path "$SITE_DIR\public\about_us\about.php" -Pattern "noindex" -Quiet
if ($noindexInAbout) {
    Write-Host "   [ERROR] NOINDEX found in about page - THIS BLOCKS INDEXING!" -ForegroundColor Red
    $errorCount++
}
else {
    Write-Host "   [OK] No noindex in about page" -ForegroundColor Green
}

Write-Host ""
Write-Host "[E2] Checking for robots.txt DISALLOW..." -ForegroundColor Yellow
if (Test-Path "$SITE_DIR\robots.txt") {
    $disallowAll = Select-String -Path "$SITE_DIR\robots.txt" -Pattern "Disallow:\s*/" -Quiet
    if ($disallowAll) {
        Write-Host "   [WARNING] Disallow: / found - CHECK if this blocks your pages" -ForegroundColor Red
        $errorCount++
    }
    else {
        Write-Host "   [OK] robots.txt allows crawling" -ForegroundColor Green
    }
}
else {
    Write-Host "   [INFO] No robots.txt - using default (allow all)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "[E3] Checking for X-Robots-Tag in .htaccess..." -ForegroundColor Yellow
$xRobotsTag = Select-String -Path "$SITE_DIR\.htaccess" -Pattern "X-Robots-Tag" -Quiet
if ($xRobotsTag) {
    Write-Host "   [WARNING] X-Robots-Tag found - VERIFY it's not blocking" -ForegroundColor Red
    $errorCount++
}
else {
    Write-Host "   [OK] No X-Robots-Tag blocking" -ForegroundColor Green
}

Write-Host ""
Write-Host "[E4] Checking for duplicate content issues..." -ForegroundColor Yellow
$canonicalInIndex = Select-String -Path "$SITE_DIR\index.php" -Pattern "canonical" -Quiet
if ($canonicalInIndex) {
    Write-Host "   [OK] Canonical tag present (prevents duplicates)" -ForegroundColor Green
}
else {
    Write-Host "   [ERROR] Canonical tag MISSING - may cause duplicate issues" -ForegroundColor Red
    $errorCount++
}

Write-Host ""
Write-Host "[E5] Checking for empty or thin content..." -ForegroundColor Yellow
$indexSize = (Get-Item "$SITE_DIR\index.php").Length
if ($indexSize -lt 1000) {
    Write-Host "   [ERROR] index.php too small ($indexSize bytes) - thin content!" -ForegroundColor Red
    $errorCount++
}
else {
    Write-Host "   [OK] index.php has sufficient content ($indexSize bytes)" -ForegroundColor Green
}

$aboutSize = (Get-Item "$SITE_DIR\public\about_us\about.php").Length
if ($aboutSize -lt 1000) {
    Write-Host "   [ERROR] about.php too small ($aboutSize bytes) - thin content!" -ForegroundColor Red
    $errorCount++
}
else {
    Write-Host "   [OK] about.php has sufficient content ($aboutSize bytes)" -ForegroundColor Green
}

Write-Host ""
Write-Host "[E6] Checking for PHP errors in files..." -ForegroundColor Yellow
$phpTagInIndex = Select-String -Path "$SITE_DIR\index.php" -Pattern "<\?php" -Quiet
if ($phpTagInIndex) {
    Write-Host "   [OK] PHP opening tag found" -ForegroundColor Green
}
else {
    Write-Host "   [ERROR] No PHP opening tag - file may be corrupted" -ForegroundColor Red
    $errorCount++
}

Write-Host ""
Write-Host "[E7] Checking sitemap accessibility..." -ForegroundColor Yellow
if (Test-Path "$SITE_DIR\sitemap.php") {
    $sitemapSize = (Get-Item "$SITE_DIR\sitemap.php").Length
    if ($sitemapSize -lt 100) {
        Write-Host "   [ERROR] sitemap.php too small ($sitemapSize bytes) - likely empty!" -ForegroundColor Red
        $errorCount++
    }
    else {
        Write-Host "   [OK] sitemap.php exists and has content ($sitemapSize bytes)" -ForegroundColor Green
    }
}
else {
    Write-Host "   [ERROR] sitemap.php MISSING!" -ForegroundColor Red
    $errorCount++
}

Write-Host ""
Write-Host "[E8] Checking for soft 404 errors..." -ForegroundColor Yellow
$soft404 = Select-String -Path "$SITE_DIR\public\about_us\about.php" -Pattern "404" -Quiet
if ($soft404) {
    Write-Host "   [WARNING] '404' text found in about page - may be soft 404" -ForegroundColor Yellow
}
else {
    Write-Host "   [OK] No 404 content in about page" -ForegroundColor Green
}

Write-Host ""
Write-Host "[E9] Checking for viewport meta tag (mobile requirement)..." -ForegroundColor Yellow
$viewportTag = Select-String -Path "$SITE_DIR\index.php" -Pattern "viewport" -Quiet
if ($viewportTag) {
    Write-Host "   [OK] Viewport meta tag present (mobile-friendly)" -ForegroundColor Green
}
else {
    Write-Host "   [ERROR] Viewport meta tag MISSING - NOT mobile-friendly!" -ForegroundColor Red
    $errorCount++
}

Write-Host ""
Write-Host "[E10] Checking for character encoding..." -ForegroundColor Yellow
$charsetTag = Select-String -Path "$SITE_DIR\index.php" -Pattern "charset" -Quiet
if ($charsetTag) {
    Write-Host "   [OK] Character encoding declared" -ForegroundColor Green
}
else {
    Write-Host "   [WARNING] Character encoding not found" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  DETAILED SITEMAP ANALYSIS" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Checking sitemap structure..." -ForegroundColor Yellow
$xmlDecl = Select-String -Path "$SITE_DIR\sitemap.php" -Pattern "<\?xml" -Quiet
if ($xmlDecl) {
    Write-Host "[OK] XML declaration present" -ForegroundColor Green
}
else {
    Write-Host "[ERROR] Missing XML declaration" -ForegroundColor Red
    $errorCount++
}

$urlset = Select-String -Path "$SITE_DIR\sitemap.php" -Pattern "<urlset" -Quiet
if ($urlset) {
    Write-Host "[OK] Urlset tag present" -ForegroundColor Green
}
else {
    Write-Host "[ERROR] Missing urlset tag" -ForegroundColor Red
    $errorCount++
}

$aboutInSitemap = Select-String -Path "$SITE_DIR\sitemap.php" -Pattern "about_us/about.php" -Quiet
if ($aboutInSitemap) {
    Write-Host "[OK] About page in sitemap (CORRECT path: about_us/about.php)" -ForegroundColor Green
}
else {
    $wrongPath = Select-String -Path "$SITE_DIR\sitemap.php" -Pattern "about/about.php" -Quiet
    if ($wrongPath) {
        Write-Host "[ERROR] About page has WRONG path (about/about.php - should be about_us/about.php)" -ForegroundColor Red
        $errorCount++
    }
    else {
        Write-Host "[ERROR] About page NOT in sitemap at all" -ForegroundColor Red
        $errorCount++
    }
}

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  ERROR SUMMARY" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

if ($errorCount -eq 0) {
    Write-Host " ===================================" -ForegroundColor Green
    Write-Host "   ALL CLEAR! NO ERRORS FOUND!" -ForegroundColor Green
    Write-Host " ===================================" -ForegroundColor Green
    Write-Host ""
    Write-Host " Your site meets ALL indexing conditions." -ForegroundColor Green
    Write-Host " Google should index your pages without issues." -ForegroundColor Green
    Write-Host ""
    Write-Host " Status: READY FOR INDEXING" -ForegroundColor Green
}
else {
    Write-Host " ===================================" -ForegroundColor Red
    Write-Host "   $errorCount POTENTIAL ISSUES FOUND" -ForegroundColor Red
    Write-Host " ===================================" -ForegroundColor Red
    Write-Host ""
    Write-Host " Please review the errors above and fix them." -ForegroundColor Yellow
    Write-Host " These may prevent or delay Google indexing." -ForegroundColor Yellow
    Write-Host ""
    Write-Host " Status: NEEDS ATTENTION" -ForegroundColor Red
}

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  GOOGLE'S CRITICAL E-CONDITIONS" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "E-Condition 1: Page Must Be Accessible" -ForegroundColor White
Write-Host "  - No 404 errors"
Write-Host "  - No 500 server errors"
Write-Host "  - No authentication required"
Write-Host "  Status: [?] Test after upload" -ForegroundColor Yellow

Write-Host ""
Write-Host "E-Condition 2: Not Blocked from Indexing" -ForegroundColor White
Write-Host "  - No noindex meta tag"
Write-Host "  - Not blocked by robots.txt"
Write-Host "  - No X-Robots-Tag: noindex"
if ($noindexInIndex -or $noindexInAbout) {
    Write-Host "  Status: [ERROR] BLOCKED - Fix immediately!" -ForegroundColor Red
}
else {
    Write-Host "  Status: [OK] Not blocked" -ForegroundColor Green
}

Write-Host ""
Write-Host "E-Condition 3: Mobile-Friendly" -ForegroundColor White
Write-Host "  - Viewport meta tag required"
Write-Host "  - Responsive design"
if ($viewportTag) {
    Write-Host "  Status: [OK] Mobile-friendly" -ForegroundColor Green
}
else {
    Write-Host "  Status: [ERROR] NOT mobile-friendly" -ForegroundColor Red
}

Write-Host ""
Write-Host "E-Condition 4: No Duplicate Content" -ForegroundColor White
Write-Host "  - Canonical tag required"
Write-Host "  - Unique content"
if ($canonicalInIndex) {
    Write-Host "  Status: [OK] Canonical tag present" -ForegroundColor Green
}
else {
    Write-Host "  Status: [ERROR] May have duplicate issues" -ForegroundColor Red
}

Write-Host ""
Write-Host "E-Condition 5: Sufficient Quality" -ForegroundColor White
Write-Host "  - Not thin content"
Write-Host "  - Valuable to users"
Write-Host "  - Proper structure"
if ($indexSize -gt 3000) {
    Write-Host "  Status: [OK] Sufficient content" -ForegroundColor Green
}
else {
    Write-Host "  Status: [WARNING] May be too thin" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  NEXT STEPS" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

if ($errorCount -eq 0) {
    Write-Host "1. Upload files to production" -ForegroundColor Green
    Write-Host "2. Test sitemap: https://yoursite.com/sitemap.php" -ForegroundColor Green
    Write-Host "3. Submit sitemap in Google Search Console" -ForegroundColor Green
    Write-Host "4. Request indexing for key pages" -ForegroundColor Green
    Write-Host "5. Wait 1-2 weeks for Google to index" -ForegroundColor Green
    Write-Host ""
    Write-Host "You're ready to go!" -ForegroundColor Green
}
else {
    Write-Host "1. Fix the $errorCount error(s) listed above" -ForegroundColor Yellow
    Write-Host "2. Re-run this checker to verify fixes" -ForegroundColor Yellow
    Write-Host "3. Then upload to production" -ForegroundColor Yellow
    Write-Host "4. Submit sitemap in Google Search Console" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Don't upload until errors are fixed!" -ForegroundColor Red
}

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
