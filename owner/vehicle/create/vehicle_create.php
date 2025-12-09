<?php
require __DIR__ . '/../../../config/db.php';

ensure_session_started();
$user = current_user();

// Check if user is Owner (Role ID 3)
if (!$user || !in_array($user['role_id'], [3])) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$pdo = get_pdo();
$errors = [];
$success = null;
$csrf_token = generate_csrf_token();

// Check if owner has an active package with available vehicle slots
$packageCheck = check_owner_package_quota($user['user_id'], 'vehicle');
if (!$packageCheck['success']) {
    // Redirect to package purchase page
    $_SESSION['package_required_message'] = $packageCheck['message'];
    header('Location: ' . $packageCheck['redirect_url']);
    exit;
}

// Fetch Vehicle Types
$stmt = $pdo->query("SELECT * FROM `vehicle_type` ORDER BY `type_name` ASC");
$vehicleTypes = $stmt->fetchAll();

// Fetch Fuel Types
$stmt = $pdo->query("SELECT * FROM `fuel_type` ORDER BY `type_name` ASC");
$fuelTypes = $stmt->fetchAll();

// Fetch Transmission Types
$stmt = $pdo->query("SELECT * FROM `transmission_type` ORDER BY `type_name` ASC");
$transmissionTypes = $stmt->fetchAll();

// Fetch Pricing Types
$stmt = $pdo->query("SELECT * FROM `pricing_type` ORDER BY `type_name` ASC");
$pricingTypes = $stmt->fetchAll();

// Fetch Brands
$stmt = $pdo->query("SELECT * FROM `vehicle_brand` ORDER BY `brand_name` ASC");
$brands = $stmt->fetchAll();

// Fetch Models (for JS cascading)
$stmt = $pdo->query("SELECT * FROM `vehicle_model` ORDER BY `model_name` ASC");
$models = $stmt->fetchAll();

// Fetch Colors
$stmt = $pdo->query("SELECT * FROM `vehicle_color` ORDER BY `color_name` ASC");
$colors = $stmt->fetchAll();

// Fetch Provinces
$stmt = $pdo->query("SELECT * FROM `provinces` ORDER BY `name_en` ASC");
$provinces = $stmt->fetchAll();

// Fetch all districts and cities (for JavaScript cascading)
$stmt = $pdo->query("SELECT * FROM `districts` ORDER BY `name_en` ASC");
$districts = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM `cities` ORDER BY `name_en` ASC");
$cities = $stmt->fetchAll();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF Token');
    }

    // Inputs
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    // Vehicle Details
    $typeId = intval($_POST['type_id'] ?? 0); // Added missing type_id
    $modelId = intval($_POST['model_id'] ?? 0);
    
    // Handle Color Input (Text -> ID)
    $colorName = trim($_POST['color'] ?? '');
    $colorId = 0;
    if ($colorName) {
        // Check if color exists
        $stmt = $pdo->prepare("SELECT color_id FROM vehicle_color WHERE color_name = ?");
        $stmt->execute([$colorName]);
        $existingId = $stmt->fetchColumn();
        
        if ($existingId) {
            $colorId = $existingId;
        } else {
            // Create new color
            $stmt = $pdo->prepare("INSERT INTO vehicle_color (color_name) VALUES (?)");
            $stmt->execute([$colorName]);
            $colorId = $pdo->lastInsertId();
        }
    }
    $year = intval($_POST['year'] ?? 0);
    $fuelTypeId = intval($_POST['fuel_type_id'] ?? 0);
    $transmissionTypeId = intval($_POST['transmission_type_id'] ?? 0);
    $seats = intval($_POST['seats'] ?? 0);
    $pricingTypeId = intval($_POST['pricing_type_id'] ?? 0);
    $pricePerDay = floatval($_POST['price_per_day'] ?? 0);
    $pricePerKm = floatval($_POST['price_per_km'] ?? 0); // Added per km
    $licensePlate = trim($_POST['license_plate'] ?? '');
    
    // Driver Options
    $isDriverAvailable = isset($_POST['is_driver_available']) ? 1 : 0;
    $driverCost = floatval($_POST['driver_cost_per_day'] ?? 0);
    
    // address/postal/link...
    $address = trim($_POST['address'] ?? '');
    $postalCode = trim($_POST['postal_code'] ?? '');
    $googleMapLink = trim($_POST['google_map_link'] ?? '');
    
    // Location dropdowns
    $provinceId = intval($_POST['province_id'] ?? 0);
    $districtId = intval($_POST['district_id'] ?? 0);
    $cityId = intval($_POST['city_id'] ?? 0);

    // Features
    $ac = isset($_POST['ac']) ? 1 : 0;
    $gps = isset($_POST['gps']) ? 1 : 0;
    $bluetooth = isset($_POST['bluetooth']) ? 1 : 0;
    $childSeat = isset($_POST['child_seat']) ? 1 : 0;
    $usbCharger = isset($_POST['usb_charger']) ? 1 : 0;

    // Validation
    // Validation
    if (!$title || !$typeId || !$modelId || !$colorId || !$address) {
        $errors[] = 'Title, Type, Model, Color, and Address are required.';
    }
    if ($pricingTypeId == 1 && $pricePerDay <= 0) {
        $errors[] = 'Daily Price is required.';
    }
    if ($pricingTypeId == 2 && $pricePerKm <= 0) {
        $errors[] = 'Price Per KM is required.';
    }
    if ($year < 1900 || $year > 2100) {
        $errors[] = 'Please enter a valid vehicle year.';
    }

    // Image Upload - Primary and Gallery
    $uploadedImages = [];
    
    if (!empty($_FILES['vehicle_images']['name'][0])) {
        $imageCount = count($_FILES['vehicle_images']['name']);
        
        // Validate image count (min 3, max 15)
        if ($imageCount < 3) {
            $errors[] = 'Please upload at least 3 images.';
        } elseif ($imageCount > 15) {
            $errors[] = 'Maximum 15 images allowed.';
        }
        
        if (!$errors) {
            $uploadDir = __DIR__ . '/../../../public/uploads/vehicles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $validTypes = ['jpg', 'jpeg', 'png', 'webp'];
            
            foreach ($_FILES['vehicle_images']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['vehicle_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileName = $_FILES['vehicle_images']['name'][$key];
                    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    
                    if (!in_array($fileType, $validTypes)) {
                        $errors[] = "Invalid image type for {$fileName}. JPG, PNG, WEBP only.";
                        break;
                    }
                    
                    if ($_FILES['vehicle_images']['size'][$key] > 5 * 1024 * 1024) {
                        $errors[] = "Image {$fileName} is too large (Max 5MB).";
                        break;
                    }
                    
                    $newFileName = 'vehicle_' . uniqid() . '_' . time() . '.' . $fileType;
                    $destination = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($tmpName, $destination)) {
                        $uploadedImages[] = 'public/uploads/vehicles/' . $newFileName;
                    } else {
                        $errors[] = 'Failed to upload image.';
                        break;
                    }
                }
            }
        }
    } else {
        $errors[] = 'At least 3 vehicle images are required.';
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $vehicleCode = 'VEH-' . strtoupper(uniqid());
            $stmt = $pdo->prepare("INSERT INTO `vehicle` (
                `vehicle_code`, `owner_id`, `title`, `description`, `vehicle_type_id`,
                `model_id`, `year`, `fuel_type_id`, `transmission_type_id`,
                `number_of_seats`, `pricing_type_id`, `price_per_day`, `price_per_km`, `license_plate`, `color_id`,
                `is_driver_available`, `driver_cost_per_day`, `status_id`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 4)"); // Status 4 = Pending
            
            $stmt->execute([
                $vehicleCode, $user['user_id'], $title, $description, $typeId,
                $modelId, $year, $fuelTypeId, $transmissionTypeId,
                $seats, $pricingTypeId, $pricePerDay, $pricePerKm, $licensePlate, $colorId,
                $isDriverAvailable, $driverCost
            ]);
            $vehicleId = $pdo->lastInsertId();

            // 2. Insert Location
            $stmt = $pdo->prepare("INSERT INTO `vehicle_location` (
                `vehicle_id`, `city_id`, `address`, `postal_code`, `google_map_link`
            ) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$vehicleId, $cityId, $address, $postalCode, $googleMapLink]);

            // 3. Insert Images (first image is primary)
            if (!empty($uploadedImages)) {
                $primaryImage = intval($_POST['primary_image'] ?? 0); //  Index of primary image
                foreach ($uploadedImages as $index => $imagePath) {
                    $isPrimary = ($index === $primaryImage) ? 1 : 0;
                    $stmt = $pdo->prepare("INSERT INTO `vehicle_image` (`vehicle_id`, `image_path`, `primary_image`) VALUES (?, ?, ?)");
                    $stmt->execute([$vehicleId, $imagePath, $isPrimary]);
                }
            }

            // 4. Decrement package quota
            decrement_package_quota($packageCheck['package_id'], 'vehicle');

            $pdo->commit();
            $success = "Vehicle listed successfully! It is pending approval. You have " . ($packageCheck['remaining'] - 1) . " vehicle slot(s) remaining.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>List New Vehicle - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= app_url('public/profile/profile.css') ?>">
    
    <style>
        .feature-checkbox-card {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            height: 100%;
            transition: all 0.2s;
        }
        .feature-checkbox-card:hover {
            border-color: var(--fern);
            background-color: #f8fcf8;
        }
        .form-check-input:checked {
            background-color: var(--fern);
            border-color: var(--fern);
        }
    </style>
</head>
<body>

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5 profile-container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h2 class="mb-4 fw-bold text-dark">List Your Vehicle</h2>

            <?php if (isset($packageCheck) && $packageCheck['success']): ?>
                <div class="alert alert-info shadow-sm d-flex align-items-center mb-4">
                    <i class="bi bi-briefcase-fill me-3 fs-3 text-primary"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Active Package: <?= htmlspecialchars($packageCheck['package_name']) ?></h6>
                        <p class="mb-0 small">You have <strong><?= $packageCheck['remaining'] ?></strong> vehicle listing(s) remaining in this package.</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success shadow-sm"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if ($errors): ?>
                <div class="alert alert-danger shadow-sm">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?><li><?= $err ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <!-- Basic Info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold" style="color: var(--hunter-green);">Basic Details</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="form-label">Vehicle Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. 2020 Toyota Prius" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                                <select name="type_id" class="form-select" required>
                                    <option value="" selected disabled>Select Type</option>
                                    <?php foreach ($vehicleTypes as $type): ?>
                                        <option value="<?= $type['type_id'] ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Pricing Type <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="pricing_type_id" id="priceOption1" value="1" autocomplete="off" checked>
                                    <label class="btn btn-outline-success" for="priceOption1">Daily (Per Day)</label>

                                    <input type="radio" class="btn-check" name="pricing_type_id" id="priceOption2" value="2" autocomplete="off">
                                    <label class="btn btn-outline-success" for="priceOption2">Price Per KM</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div id="dailyPriceContainer">
                                    <label class="form-label">Daily Price (LKR) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="price_per_day" class="form-control" id="inputDailyPrice">
                                </div>
                                <div id="kmPriceContainer" style="display:none;">
                                    <label class="form-label">Price Per KM (LKR) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="price_per_km" class="form-control" id="inputKmPrice">
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Describe the vehicle..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Specifications -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold" style="color: var(--hunter-green);">Vehicle Specifications</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Brand (Make) <span class="text-danger">*</span></label>
                                <select name="brand_id" id="brand" class="form-select" required>
                                    <option value="" selected disabled>Select Brand</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?= $brand['brand_id'] ?>"><?= htmlspecialchars($brand['brand_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Model <span class="text-danger">*</span></label>
                                <select name="model_id" id="model" class="form-select" required disabled>
                                    <option value="" selected>Select Brand first</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Year <span class="text-danger">*</span></label>
                                <input type="number" name="year" class="form-control" min="1900" max="2099" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fuel Type <span class="text-danger">*</span></label>
                                <select name="fuel_type_id" class="form-select" required>
                                    <option value="" selected disabled>Select Fuel Type</option>
                                    <?php foreach ($fuelTypes as $type): ?>
                                        <option value="<?= $type['type_id'] ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Transmission <span class="text-danger">*</span></label>
                                <select name="transmission_type_id" class="form-select" required>
                                    <option value="" selected disabled>Select Transmission</option>
                                    <?php foreach ($transmissionTypes as $type): ?>
                                        <option value="<?= $type['type_id'] ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Number of Seats</label>
                                <input type="number" name="seats" class="form-control" min="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">License Plate</label>
                                <input type="text" name="license_plate" class="form-control" placeholder="ABC-1234">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Color <span class="text-danger">*</span></label>
                                <input type="text" name="color" class="form-control" placeholder="e.g. Red, Metallic Blue" required list="colorList">
                                <datalist id="colorList">
                                    <?php foreach ($colors as $color): ?>
                                        <option value="<?= htmlspecialchars($color['color_name']) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>

                        <label class="form-label mb-3 d-block">Additional Options</label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check feature-checkbox-card">
                                    <input class="form-check-input" type="checkbox" name="is_driver_available" id="driverCheck">
                                    <label class="form-check-label" for="driverCheck">Driver Available</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Driver Cost Per Day (LKR)</label>
                                <input type="number" step="0.01" name="driver_cost_per_day" class="form-control" id="driverCost" disabled>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location & Image -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold" style="color: var(--hunter-green);">Location & Media</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Province <span class="text-danger">*</span></label>
                                <select name="province_id" id="province" class="form-select" required>
                                    <option value="" selected disabled>Select Province</option>
                                    <?php foreach ($provinces as $province): ?>
                                        <option value="<?= $province['id'] ?>"><?= htmlspecialchars($province['name_en']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">District <span class="text-danger">*</span></label>
                                <select name="district_id" id="district" class="form-select" required disabled>
                                    <option value="" selected>Select Province first</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City <span class="text-danger">*</span></label>
                                <select name="city_id" id="city" class="form-select" required disabled>
                                    <option value="" selected>Select District first</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Pickup Address <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control" placeholder="Street Address" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Google Map Link</label>
                            <input type="url" name="google_map_link" class="form-control" placeholder="https://maps.google.com/...">
                            <div class="form-text">Paste the Google Maps link for the pickup location</div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="postal_code" class="form-control" placeholder="Postal Code">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Vehicle Images <span class="text-danger">*</span></label>
                            <input type="file" name="vehicle_images[]" id="vehicleImages" class="form-control" accept="image/*" multiple required>
                            <div class="form-text">Upload 3-15 images (JPG, PNG, WEBP - Max 5MB each)</div>
                        </div>

                        <!-- Image Preview Container -->
                        <div id="imagePreviewContainer" class="row g-2 mb-3" style="display:none;">
                            <!-- Previews will be inserted here by JavaScript -->
                        </div>

                        <input type="hidden" name="primary_image" id="primaryImageIndex" value="0">
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?= app_url('index.php') ?>" class="btn btn-outline-secondary px-5">Cancel</a>
                    <button type="submit" class="btn btn-primary px-5 btn-lg shadow-sm" style="background-color: var(--fern); border-color: var(--fern);">Submit Vehicle</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<link rel="stylesheet" href="vehicle_create.css">
<script src="vehicle_create.js"></script>
<script>
// Pass PHP data to JavaScript
window.vehicleData = {
    districts: <?= json_encode($districts) ?>,
    cities: <?= json_encode($cities) ?>,
    models: <?= json_encode($models) ?>
};
</script>
</body>
</html>
