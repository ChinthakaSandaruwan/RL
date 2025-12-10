@echo off
:: Enhanced Error Condition Checker for Google Indexing
title E-Condition Checker (Error Conditions)

color 0A
echo.
echo ================================================
echo    GOOGLE INDEXING E-CONDITIONS CHECKER
echo    (Error Conditions Detection)
echo ================================================
echo.
echo Checking for potential errors that prevent indexing...
echo.

set SITE_DIR=c:\xampp\htdocs\RL
set ERROR_COUNT=0

echo [E1] Checking for NOINDEX tags (blocks indexing)...
findstr /I /C:"noindex" "%SITE_DIR%\index.php" >nul 2>&1
if %errorlevel%==0 (
    echo    [ERROR] NOINDEX found in index.php - THIS BLOCKS INDEXING!
    set /a ERROR_COUNT+=1
) else (
    echo    [OK] No noindex in index.php
)

findstr /I /C:"noindex" "%SITE_DIR%\public\about_us\about.php" >nul 2>&1
if %errorlevel%==0 (
    echo    [ERROR] NOINDEX found in about page - THIS BLOCKS INDEXING!
    set /a ERROR_COUNT+=1
) else (
    echo    [OK] No noindex in about page
)

echo.
echo [E2] Checking for robots.txt DISALLOW...
if exist "%SITE_DIR%\robots.txt" (
    findstr /I /C:"Disallow: /" "%SITE_DIR%\robots.txt" >nul 2>&1
    if %errorlevel%==0 (
        echo    [WARNING] Disallow: / found - CHECK if this blocks your pages
        set /a ERROR_COUNT+=1
    ) else (
        echo    [OK] robots.txt allows crawling
    )
) else (
    echo    [INFO] No robots.txt - using default (allow all)
)

echo.
echo [E3] Checking for X-Robots-Tag in .htaccess...
findstr /I /C:"X-Robots-Tag" "%SITE_DIR%\.htaccess" >nul 2>&1
if %errorlevel%==0 (
    echo    [WARNING] X-Robots-Tag found - VERIFY it's not blocking
    set /a ERROR_COUNT+=1
) else (
    echo    [OK] No X-Robots-Tag blocking
)

echo.
echo [E4] Checking for duplicate content issues...
findstr /C:"canonical" "%SITE_DIR%\index.php" >nul 2>&1
if %errorlevel%==0 (
    echo    [OK] Canonical tag present (prevents duplicates)
) else (
    echo    [ERROR] Canonical tag MISSING - may cause duplicate issues
    set /a ERROR_COUNT+=1
)

echo.
echo [E5] Checking for empty or thin content...
for %%F in ("%SITE_DIR%\index.php") do set SIZE=%%~zF
if %SIZE% LSS 1000 (
    echo    [ERROR] index.php too small (%SIZE% bytes) - thin content!
    set /a ERROR_COUNT+=1
) else (
    echo    [OK] index.php has sufficient content (%SIZE% bytes)
)

for %%F in ("%SITE_DIR%\public\about_us\about.php") do set SIZE=%%~zF
if %SIZE% LSS 1000 (
    echo    [ERROR] about.php too small (%SIZE% bytes) - thin content!
    set /a ERROR_COUNT+=1
) else (
    echo    [OK] about.php has sufficient content (%SIZE% bytes)
)

echo.
echo [E6] Checking for PHP errors in files...
findstr /C:"<?php" "%SITE_DIR%\index.php" >nul 2>&1
if %errorlevel%==0 (
    echo    [OK] PHP opening tag found
) else (
    echo    [ERROR] No PHP opening tag - file may be corrupted
    set /a ERROR_COUNT+=1
)

echo.
echo [E7] Checking sitemap accessibility...
if exist "%SITE_DIR%\sitemap.php" (
    for %%F in ("%SITE_DIR%\sitemap.php") do set SIZE=%%~zF
    if %SIZE% LSS 100 (
        echo    [ERROR] sitemap.php too small (%SIZE% bytes) - likely empty!
        set /a ERROR_COUNT+=1
    ) else (
        echo    [OK] sitemap.php exists and has content (%SIZE% bytes)
    )
) else (
    echo    [ERROR] sitemap.php MISSING!
    set /a ERROR_COUNT+=1
)

echo.
echo [E8] Checking for soft 404 errors...
findstr /I /C:"404" "%SITE_DIR%\public\about_us\about.php" >nul 2>&1
if %errorlevel%==0 (
    echo    [WARNING] "404" text found in about page - may be soft 404
    set /a ERROR_COUNT+=1
) else (
    echo    [OK] No 404 content in about page
)

echo.
echo [E9] Checking for viewport meta tag (mobile requirement)...
findstr /C:"viewport" "%SITE_DIR%\index.php" >nul 2>&1
if %errorlevel%==0 (
    echo    [OK] Viewport meta tag present (mobile-friendly)
) else (
    echo    [ERROR] Viewport meta tag MISSING - NOT mobile-friendly!
    set /a ERROR_COUNT+=1
)

echo.
echo [E10] Checking for character encoding...
findstr /C:"charset" "%SITE_DIR%\index.php" >nul 2>&1
if %errorlevel%==0 (
    echo    [OK] Character encoding declared
) else (
    echo    [WARNING] Character encoding not found
    set /a ERROR_COUNT+=1
)

echo.
echo ================================================
echo    DETAILED SITEMAP ANALYSIS
echo ================================================
echo.

echo Checking sitemap structure...
findstr /C:"<?xml" "%SITE_DIR%\sitemap.php" >nul 2>&1
if %errorlevel%==0 (
    echo [OK] XML declaration present
) else (
    echo [ERROR] Missing XML declaration
    set /a ERROR_COUNT+=1
)

findstr /C:"<urlset" "%SITE_DIR%\sitemap.php" >nul 2>&1
if %errorlevel%==0 (
    echo [OK] Urlset tag present
) else (
    echo [ERROR] Missing urlset tag
    set /a ERROR_COUNT+=1
)

findstr /C:"about_us/about.php" "%SITE_DIR%\sitemap.php" >nul 2>&1
if %errorlevel%==0 (
    echo [OK] About page in sitemap (CORRECT path: about_us/about.php)
) else (
    findstr /C:"about/about.php" "%SITE_DIR%\sitemap.php" >nul 2>&1
    if %errorlevel%==0 (
        echo [ERROR] About page has WRONG path (about/about.php - should be about_us/about.php)
        set /a ERROR_COUNT+=1
    ) else (
        echo [ERROR] About page NOT in sitemap at all
        set /a ERROR_COUNT+=1
    )
)

echo.
echo ================================================
echo    ERROR SUMMARY
echo ================================================
echo.

if %ERROR_COUNT%==0 (
    color 0A
    echo  ===================================
    echo    ALL CLEAR! NO ERRORS FOUND!
    echo  ===================================
    echo.
    echo  Your site meets ALL indexing conditions.
    echo  Google should index your pages without issues.
    echo.
    echo  Status: READY FOR INDEXING
) else (
    color 0C
    echo  ===================================
    echo    %ERROR_COUNT% POTENTIAL ISSUES FOUND
    echo  ===================================
    echo.
    echo  Please review the errors above and fix them.
    echo  These may prevent or delay Google indexing.
    echo.
    echo  Status: NEEDS ATTENTION
)

echo.
echo ================================================
echo    GOOGLE'S CRITICAL E-CONDITIONS
echo ================================================
echo.
echo E-Condition 1: Page Must Be Accessible
echo   - No 404 errors
echo   - No 500 server errors
echo   - No authentication required
echo   Status: [?] Test after upload
echo.
echo E-Condition 2: Not Blocked from Indexing
echo   - No noindex meta tag
echo   - Not blocked by robots.txt
echo   - No X-Robots-Tag: noindex
echo   Status:
findstr /I /C:"noindex" "%SITE_DIR%\index.php" >nul 2>&1
if %errorlevel%==0 (
    echo   [ERROR] BLOCKED - Fix immediately!
) else (
    echo   [OK] Not blocked
)

echo.
echo E-Condition 3: Mobile-Friendly
echo   - Viewport meta tag required
echo   - Responsive design
echo   Status:
findstr /C:"viewport" "%SITE_DIR%\index.php" >nul 2>&1
if %errorlevel%==0 (
    echo   [OK] Mobile-friendly
) else (
    echo   [ERROR] NOT mobile-friendly
)

echo.
echo E-Condition 4: No Duplicate Content
echo   - Canonical tag required
echo   - Unique content
echo   Status:
findstr /C:"canonical" "%SITE_DIR%\index.php" >nul 2>&1
if %errorlevel%==0 (
    echo   [OK] Canonical tag present
) else (
    echo   [ERROR] May have duplicate issues
)

echo.
echo E-Condition 5: Sufficient Quality
echo   - Not thin content
echo   - Valuable to users
echo   - Proper structure
echo   Status:
for %%F in ("%SITE_DIR%\index.php") do set SIZE=%%~zF
if %SIZE% GTR 3000 (
    echo   [OK] Sufficient content
) else (
    echo   [WARNING] May be too thin
)

echo.
echo ================================================
echo    NEXT STEPS
echo ================================================
echo.

if %ERROR_COUNT%==0 (
    echo 1. Upload files to production
    echo 2. Test sitemap: https://yoursite.com/sitemap.php
    echo 3. Submit sitemap in Google Search Console
    echo 4. Request indexing for key pages
    echo 5. Wait 1-2 weeks for Google to index
    echo.
    echo You're ready to go!
) else (
    echo 1. Fix the %ERROR_COUNT% error(s) listed above
    echo 2. Re-run this checker to verify fixes
    echo 3. Then upload to production
    echo 4. Submit sitemap in Google Search Console
    echo.
    echo Don't upload until errors are fixed!
)

echo.
echo ================================================
echo.

pause
