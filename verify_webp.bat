@echo off
:: Quick verification script for WebP images
title WebP Image Verification

echo.
echo ========================================
echo    WebP Image Verification
echo ========================================
echo.

set IMAGE_DIR=c:\xampp\htdocs\RL\public\assets\images

echo Checking WebP images...
echo.

:: Check hero_house.webp
if exist "%IMAGE_DIR%\hero_house.webp" (
    echo [OK] hero_house.webp found
    for %%A in ("%IMAGE_DIR%\hero_house.webp") do echo     Size: %%~zA bytes
) else (
    echo [X] hero_house.webp NOT FOUND
)

echo.

:: Check hero_apartment.webp
if exist "%IMAGE_DIR%\hero_apartment.webp" (
    echo [OK] hero_apartment.webp found
    for %%A in ("%IMAGE_DIR%\hero_apartment.webp") do echo     Size: %%~zA bytes
) else (
    echo [X] hero_apartment.webp NOT FOUND
)

echo.

:: Check hero_vehicle.webp
if exist "%IMAGE_DIR%\hero_vehicle.webp" (
    echo [OK] hero_vehicle.webp found
    for %%A in ("%IMAGE_DIR%\hero_vehicle.webp") do echo     Size: %%~zA bytes
) else (
    echo [X] hero_vehicle.webp NOT FOUND
)

echo.
echo ========================================
echo.

:: Count how many are present
set COUNT=0
if exist "%IMAGE_DIR%\hero_house.webp" set /a COUNT+=1
if exist "%IMAGE_DIR%\hero_apartment.webp" set /a COUNT+=1
if exist "%IMAGE_DIR%\hero_vehicle.webp" set /a COUNT+=1

if %COUNT%==3 (
    echo Status: ALL IMAGES READY! ^(3/3^)
    echo You can now test your site.
    echo.
    echo Next steps:
    echo 1. Start XAMPP Apache server
    echo 2. Visit http://localhost/RL/
    echo 3. Check DevTools Network tab
    echo.
) else (
    echo Status: INCOMPLETE ^(%COUNT%/3 images found^)
    echo.
    echo Please convert the missing images using:
    echo - Squoosh.app ^(https://squoosh.app/^)
    echo - Or run: convert_to_webp.ps1
    echo.
)

echo ========================================
echo.
pause
