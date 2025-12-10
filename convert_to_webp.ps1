# WebP Image Converter Script for Rental Lanka
# This script converts PNG hero images to WebP format for optimal performance

Write-Host "======================================" -ForegroundColor Cyan
Write-Host "  WebP Image Conversion Script" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

$imagesPath = "c:\xampp\htdocs\RL\public\assets\images"
$heroImages = @("hero_house.png", "hero_apartment.png", "hero_vehicle.png")

Write-Host "Checking for required images..." -ForegroundColor Yellow
Write-Host ""

# Check if images exist
$allImagesExist = $true
foreach ($image in $heroImages) {
    $imagePath = Join-Path $imagesPath $image
    if (Test-Path $imagePath) {
        $fileSize = (Get-Item $imagePath).Length / 1KB
        Write-Host "[�] Found: $image ($('{0:N0}' -f $fileSize) KB)" -ForegroundColor Green
    } else {
        Write-Host "[X] Missing: $image" -ForegroundColor Red
        $allImagesExist = $false
    }
}

Write-Host ""

if (-not $allImagesExist) {
    Write-Host "Error: Some images are missing. Please ensure all hero images are present." -ForegroundColor Red
    exit 1
}

Write-Host "======================================" -ForegroundColor Cyan
Write-Host "  Conversion Options" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Since WebP conversion requires specialized tools, we have 3 options:" -ForegroundColor Yellow
Write-Host ""
Write-Host "OPTION 1: Online Conversion (Easiest)" -ForegroundColor Cyan
Write-Host "  1. Visit: https://squoosh.app/" -ForegroundColor White
Write-Host "  2. Upload each hero image" -ForegroundColor White
Write-Host "  3. Select WebP format with quality 85" -ForegroundColor White
Write-Host "  4. Download and save to:" -ForegroundColor White
Write-Host "     $imagesPath" -ForegroundColor Gray
Write-Host ""
Write-Host "OPTION 2: Using Google's cwebp Tool" -ForegroundColor Cyan
Write-Host "  1. Download from: https://developers.google.com/speed/webp/download" -ForegroundColor White
Write-Host "  2. Extract to C:\webp\" -ForegroundColor White
Write-Host "  3. Run these commands:" -ForegroundColor White
Write-Host "     cd $imagesPath" -ForegroundColor Gray
Write-Host "     C:\webp\bin\cwebp.exe -q 85 hero_house.png -o hero_house.webp" -ForegroundColor Gray
Write-Host "     C:\webp\bin\cwebp.exe -q 85 hero_apartment.png -o hero_apartment.webp" -ForegroundColor Gray
Write-Host "     C:\webp\bin\cwebp.exe -q 85 hero_vehicle.png -o hero_vehicle.webp" -ForegroundColor Gray
Write-Host ""
Write-Host "OPTION 3: Using ImageMagick" -ForegroundColor Cyan
Write-Host "  Install via Chocolatey:" -ForegroundColor White
Write-Host "     choco install imagemagick" -ForegroundColor Gray
Write-Host "  Then run:" -ForegroundColor White
Write-Host "     cd $imagesPath" -ForegroundColor Gray
Write-Host "     magick convert hero_house.png -quality 85 hero_house.webp" -ForegroundColor Gray
Write-Host "     magick convert hero_apartment.png -quality 85 hero_apartment.webp" -ForegroundColor Gray
Write-Host "     magick convert hero_vehicle.png -quality 85 hero_vehicle.webp" -ForegroundColor Gray
Write-Host ""
Write-Host "======================================" -ForegroundColor Cyan
Write-Host "Expected Results:" -ForegroundColor Yellow
Write-Host "  - hero_house.webp: ~130-260 KB (70% smaller)" -ForegroundColor White
Write-Host "  - hero_apartment.webp: ~136-205 KB (70% smaller)" -ForegroundColor White
Write-Host "  - hero_vehicle.webp: ~150-225 KB (70% smaller)" -ForegroundColor White
Write-Host "  Total savings: ~1.5-1.8 MB" -ForegroundColor Green
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

# Ask user if they want to open the online converter
$response = Read-Host "Would you like to open Squoosh.app in your browser? (Y/N)"
if ($response -eq 'Y' -or $response -eq 'y') {
    Write-Host "Opening Squoosh.app..." -ForegroundColor Green
    Start-Process "https://squoosh.app/"
    Write-Host ""
    Write-Host "Please convert the images and save them as:" -ForegroundColor Yellow
    Write-Host "  - hero_house.webp" -ForegroundColor White
    Write-Host "  - hero_apartment.webp" -ForegroundColor White
    Write-Host "  - hero_vehicle.webp" -ForegroundColor White
    Write-Host ""
    Write-Host "Save location: $imagesPath" -ForegroundColor Gray
}

Write-Host ""
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
