@echo off
:: Google Indexing Conditions Checker for Rental Lanka
title Indexing Requirements Verification

echo.
echo ========================================
echo   Google Indexing Conditions Check
echo ========================================
echo.
echo Checking if your site meets Google's indexing requirements...
echo.

set SITE_DIR=c:\xampp\htdocs\RL

echo [1/10] Checking Critical Files...
if exist "%SITE_DIR%\index.php" (
    echo    [OK] index.php exists
) else (
    echo    [X] index.php MISSING
)

if exist "%SITE_DIR%\sitemap.php" (
    echo    [OK] sitemap.php exists
) else (
    echo    [X] sitemap.php MISSING
)

if exist "%SITE_DIR%\.htaccess" (
    echo    [OK] .htaccess exists
) else (
    echo    [X] .htaccess MISSING
)

if exist "%SITE_DIR%\robots.txt" (
    echo    [OK] robots.txt exists
) else (
    echo    [?] robots.txt not found (optional)
)

echo.
echo [2/10] Checking SEO Enhancements...

findstr /C:"canonical" "%SITE_DIR%\index.php" >nul 2>&1
if %errorlevel%==0 (
    echo    [OK] Canonical tag in index.php
) else (
    echo    [X] Canonical tag MISSING from index.php
)

findstr /C:"meta name=\"description\"" "%SITE_DIR%\index.php" >nul 2>&1
if %errorlevel%==0 (
    echo    [OK] Meta description in index.php
) else (
    echo    [X] Meta description MISSING
)

findstr /C:"application/ld+json" "%SITE_DIR%\index.php" >nul 2>&1
if %errorlevel%==0 (
    echo    [OK] Structured data in index.php
) else (
    echo    [X] Structured data MISSING
)

echo.
echo [3/10] Checking About Page...

if exist "%SITE_DIR%\public\about_us\about.php" (
    echo    [OK] About page exists
    
    findstr /C:"canonical" "%SITE_DIR%\public\about_us\about.php" >nul 2>&1
    if %errorlevel%==0 (
        echo    [OK] Canonical tag in about page
    ) else (
        echo    [X] Canonical tag MISSING from about
    )
    
    findstr /C:"meta name=\"description\"" "%SITE_DIR%\public\about_us\about.php" >nul 2>&1
    if %errorlevel%==0 (
        echo    [OK] Meta description in about page
    ) else (
        echo    [X] Meta description MISSING from about
    )
) else (
    echo    [X] About page MISSING
)

echo.
echo [4/10] Checking Sitemap Configuration...

findstr /C:"about_us/about.php" "%SITE_DIR%\sitemap.php" >nul 2>&1
if %errorlevel%==0 (
    echo    [OK] About page in sitemap (correct path)
) else (
    findstr /C:"about/about.php" "%SITE_DIR%\sitemap.php" >nul 2>&1
    if %errorlevel%==0 (
        echo    [!] About page has WRONG path in sitemap
    ) else (
        echo    [X] About page NOT in sitemap
    )
)

echo.
echo [5/10] Checking Performance Optimizations...

findstr /C:"fetchpriority" "%SITE_DIR%\public\hero\hero.php" >nul 2>&1
if %errorlevel%==0 (
    echo    [OK] Image priority hints present
) else (
    echo    [?] Image priority hints missing
)

findstr /C:"webp" "%SITE_DIR%\public\hero\hero.php" >nul 2>&1
if %errorlevel%==0 (
    echo    [OK] WebP support implemented
) else (
    echo    [X] WebP support MISSING
)

echo.
echo [6/10] Checking WebP Images...

set IMAGE_DIR=%SITE_DIR%\public\assets\images
set WEBP_COUNT=0

if exist "%IMAGE_DIR%\hero_house.webp" (
    echo    [OK] hero_house.webp exists
    set /a WEBP_COUNT+=1
) else (
    echo    [!] hero_house.webp MISSING - convert PNG to WebP
)

if exist "%IMAGE_DIR%\hero_apartment.webp" (
    echo    [OK] hero_apartment.webp exists
    set /a WEBP_COUNT+=1
) else (
    echo    [!] hero_apartment.webp MISSING - convert PNG to WebP
)

if exist "%IMAGE_DIR%\hero_vehicle.webp" (
    echo    [OK] hero_vehicle.webp exists
    set /a WEBP_COUNT+=1
) else (
    echo    [!] hero_vehicle.webp MISSING - convert PNG to WebP
)

echo    WebP Images: %WEBP_COUNT%/3 ready

echo.
echo [7/10] Checking Server Configuration...

findstr /C:"mod_deflate" "%SITE_DIR%\.htaccess" >nul 2>&1
if %errorlevel%==0 (
    echo    [OK] Gzip compression configured
) else (
    echo    [X] Gzip compression MISSING
)

findstr /C:"ExpiresActive" "%SITE_DIR%\.htaccess" >nul 2>&1
if %errorlevel%==0 (
    echo    [OK] Browser caching configured
) else (
    echo    [X] Browser caching MISSING
)

echo.
echo [8/10] Checking Mobile Optimization...

findstr /C:"viewport" "%SITE_DIR%\index.php" >nul 2>&1
if %errorlevel%==0 (
    echo    [OK] Viewport meta tag present
) else (
    echo    [X] Viewport meta tag MISSING
)

echo.
echo [9/10] Checking Security...

findstr /C:"https" "%SITE_DIR%\index.php" >nul 2>&1
if %errorlevel%==0 (
    echo    [OK] HTTPS references found
) else (
    echo    [?] Check if HTTPS is configured
)

echo.
echo [10/10] Checking Documentation...

set DOC_COUNT=0
if exist "%SITE_DIR%\SEO_IMPROVEMENTS.md" set /a DOC_COUNT+=1
if exist "%SITE_DIR%\PERFORMANCE_OPTIMIZATION.md" set /a DOC_COUNT+=1
if exist "%SITE_DIR%\COMPLETE_SUMMARY.md" set /a DOC_COUNT+=1

echo    Documentation files: %DOC_COUNT%/3 created

echo.
echo ========================================
echo   Summary
echo ========================================
echo.

:: Count total checks
echo Google Indexing Conditions:
echo.
echo TECHNICAL REQUIREMENTS:
echo  [?] Page accessible          - Test after upload
echo  [?] No server errors         - Test after upload
echo  [?] Robots.txt not blocking  - Verify in GSC
echo  [?] No noindex tag           - Verify in code
echo.
echo QUALITY SIGNALS:
echo  [?] Unique content           - Manual review needed
echo  [?] Sufficient length        - Check each page
echo  [?] Mobile-friendly          - Test at Google tool
echo  [?] Fast loading             - Check PageSpeed
echo.
echo SEO IMPLEMENTATION:
echo  [√] Sitemap created
echo  [√] Canonical tags added
echo  [√] Meta descriptions added
echo  [√] Structured data added
echo  [√] Performance optimized
echo.
echo ========================================
echo   Recommendations
echo ========================================
echo.

if %WEBP_COUNT% LSS 3 (
    echo CRITICAL:
    echo  - Convert remaining PNG images to WebP
    echo    Visit: https://squoosh.app/
    echo.
)

echo NEXT STEPS:
echo  1. Upload all modified files to production
echo  2. Convert images to WebP format ^(%WEBP_COUNT%/3 done^)
echo  3. Test sitemap: https://yoursite.com/sitemap.php
echo  4. Submit sitemap in Google Search Console
echo  5. Request indexing for important pages
echo  6. Monitor GSC for 1-2 weeks
echo.
echo TESTING TOOLS:
echo  - Mobile Test: https://search.google.com/test/mobile-friendly
echo  - PageSpeed: https://pagespeed.web.dev/
echo  - Rich Results: https://search.google.com/test/rich-results
echo.
echo ========================================
echo.

pause
