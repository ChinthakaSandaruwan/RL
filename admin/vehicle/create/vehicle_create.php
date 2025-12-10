<?php
require __DIR__ . '/../../../config/db.php';

ensure_session_started();
$user = current_user();

// Check if user is Admin or Super Admin
if (!$user || !in_array($user['role_id'], [1, 2])) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();
$errors = [];
$successStr = '';
$errorStr = '';
$csrf_token = generate_csrf_token();

// Fetch Master Data
$vehicleTypes = $pdo->query("SELECT * FROM `vehicle_type` ORDER BY `type_name` ASC")->fetchAll();
$fuelTypes = $pdo->query("SELECT * FROM `fuel_type` ORDER BY `type_name` ASC")->fetchAll();
$transmissionTypes = $pdo->query("SELECT * FROM `transmission_type` ORDER BY `type_name` ASC")->fetchAll();
$brands = $pdo->query("SELECT * FROM `vehicle_brand` ORDER BY `brand_name` ASC")->fetchAll();
$models = $pdo->query("SELECT * FROM `vehicle_model` ORDER BY `model_name` ASC")->fetchAll(); // For JS
$colors = $pdo->query("SELECT * FROM `vehicle_color` ORDER BY `color_name` ASC")->fetchAll(); // For Datalist

$provinces = $pdo->query("SELECT * FROM `provinces` ORDER BY `name_en` ASC")->fetchAll();
$districts = $pdo->query("SELECT * FROM `districts` ORDER BY `name_en` ASC")->fetchAll();
$cities = $pdo->query("SELECT * FROM `cities` ORDER BY `name_en` ASC")->fetchAll();

// Fetch Owners
$owners = $pdo->query("SELECT user_id, name, email FROM `user` WHERE `role_id` = 3 AND `status_id` = 1 ORDER BY `name` ASC")->fetchAll();

// Form Data Holders
$old = [
    'owner_id' => '',
    'title' => '', 'description' => '', 
    'type_id' => '', 'brand_id' => '', 'model_id' => '',
    'year' => '', 'fuel_type_id' => '', 'transmission_type_id' => '',
    'seats' => '', 'color' => '', 'license_plate' => '',
    'pricing_type_id' => '1', 'price_per_day' => '', 'price_per_km' => '',
    'is_driver_available' => '', 'driver_cost_per_day' => '',
    'province_id' => '', 'district_id' => '', 'city_id' => '',
    'address' => '', 'postal_code' => '', 'google_map_link' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF Token';
    }

    $old = array_merge($old, $_POST);

    // Inputs
    $ownerId = intval($_POST['owner_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    $typeId = intval($_POST['type_id'] ?? 0);
    $modelId = intval($_POST['model_id'] ?? 0);
    
    // Color Handling
    $colorName = trim($_POST['color'] ?? '');
    $colorId = 0;
    if ($colorName) {
        $stmt = $pdo->prepare("SELECT color_id FROM vehicle_color WHERE color_name = ?");
        $stmt->execute([$colorName]);
        $existingId = $stmt->fetchColumn();
        if ($existingId) {
            $colorId = $existingId;
        } else {
            $stmt = $pdo->prepare("INSERT INTO vehicle_color (color_name) VALUES (?)");
            $stmt->execute([$colorName]);
            $colorId = $pdo->lastInsertId();
        }
    }

    $year = intval($_POST['year'] ?? 0);
    $fuelTypeId = intval($_POST['fuel_type_id'] ?? 0);
    $transmissionTypeId = intval($_POST['transmission_type_id'] ?? 0);
    $seats = intval($_POST['seats'] ?? 0);
    $pricingTypeId = intval($_POST['pricing_type_id'] ?? 1);
    $pricePerDay = floatval($_POST['price_per_day'] ?? 0);
    $pricePerKm = floatval($_POST['price_per_km'] ?? 0);
    $licensePlate = trim($_POST['license_plate'] ?? '');

    $isDriverAvailable = isset($_POST['is_driver_available']) ? 1 : 0;
    $driverCost = floatval($_POST['driver_cost_per_day'] ?? 0);

    // Location
    $provinceId = intval($_POST['province_id'] ?? 0);
    $districtId = intval($_POST['district_id'] ?? 0);
    $cityId = intval($_POST['city_id'] ?? 0);
    $address = trim($_POST['address'] ?? '');
    $postal = trim($_POST['postal_code'] ?? '');
    $gmap = trim($_POST['google_map_link'] ?? '');

    // Validation
    if (!$ownerId) $errors[] = "Please select a Vehicle Owner.";
    if (!$title || !$typeId || !$modelId || !$colorId || !$cityId || !$address) {
        $errors[] = "Please fill in all required fields (Title, Type, Model, Color, City, Address).";
    }
    if ($pricingTypeId == 1 && $pricePerDay <= 0) $errors[] = "Daily Price is required.";
    if ($pricingTypeId == 2 && $pricePerKm <= 0) $errors[] = "Price Per KM is required.";

    // Image Upload
    $uploadedImages = [];
    if (!empty($_FILES['vehicle_images']['name'][0])) {
        $count = count($_FILES['vehicle_images']['name']);
        if ($count < 3) $errors[] = "Please upload at least 3 images.";
        
        if (!$errors) {
            $uploadDir = __DIR__ . '/../../../public/uploads/vehicles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $validTypes = ['jpg', 'jpeg', 'png', 'webp'];
            
            foreach ($_FILES['vehicle_images']['tmp_name'] as $k => $tmp) {
                if ($_FILES['vehicle_images']['error'][$k] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['vehicle_images']['name'][$k], PATHINFO_EXTENSION));
                    if (!in_array($ext, $validTypes)) {
                        $errors[] = "Invalid format. JPG, PNG, WEBP only. File: " . $_FILES['vehicle_images']['name'][$k];
                        break; 
                    }
                    if ($_FILES['vehicle_images']['size'][$k] > 5 * 1024 * 1024) {
                        $errors[] = "Image too large (Max 5MB). File: " . $_FILES['vehicle_images']['name'][$k];
                        break;
                    }
                    $newName = 'vehi_' . uniqid() . '_' . time() . '_' . $k . '.' . $ext;
                    if (move_uploaded_file($tmp, $uploadDir . $newName)) {
                        $uploadedImages[] = 'public/uploads/vehicles/' . $newName;
                    }
                }
            }
        }
    } else {
        $errors[] = "Please upload images.";
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            
            // 1. Insert Vehicle
            $vehicleCode = 'VEH-' . strtoupper(uniqid());
            $stmt = $pdo->prepare("INSERT INTO `vehicle` 
                (`vehicle_code`, `owner_id`, `title`, `description`, `vehicle_type_id`, `model_id`, `year`, `fuel_type_id`, `transmission_type_id`, `number_of_seats`, `pricing_type_id`, `price_per_day`, `price_per_km`, `license_plate`, `color_id`, `is_driver_available`, `driver_cost_per_day`, `status_id`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"); // Status 1 = Active
            
            $stmt->execute([
                $vehicleCode, $ownerId, $title, $description, $typeId, $modelId, $year, $fuelTypeId, $transmissionTypeId, 
                $seats, $pricingTypeId, $pricePerDay, $pricePerKm, $licensePlate, $colorId, 
                $isDriverAvailable, $driverCost
            ]);
            $vehicleId = $pdo->lastInsertId();

            // 2. Insert Location
            $stmt = $pdo->prepare("INSERT INTO `vehicle_location` (`vehicle_id`, `city_id`, `address`, `postal_code`, `google_map_link`) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$vehicleId, $cityId, $address, $postal, $gmap]);

            // 3. Insert Images
            $primaryIdx = intval($_POST['primary_image'] ?? 0);
            $stmt = $pdo->prepare("INSERT INTO `vehicle_image` (`vehicle_id`, `image_path`, `primary_image`) VALUES (?, ?, ?)");
            foreach ($uploadedImages as $idx => $path) {
                $isMain = ($idx === $primaryIdx) ? 1 : 0;
                $stmt->execute([$vehicleId, $path, $isMain]);
            }

            // Quota decrement ignored for Admin

            $pdo->commit();
            $successStr = "Vehicle submitted successfully and is now Live.";
            
            // Reset
            $old = [
                'owner_id' => '', 'title' => '', 'description' => '', 
                'type_id' => '', 'brand_id' => '', 'model_id' => '',
                'year' => '', 'fuel_type_id' => '', 'transmission_type_id' => '',
                'seats' => '', 'color' => '', 'license_plate' => '',
                'pricing_type_id' => '1', 'price_per_day' => '', 'price_per_km' => '',
                'is_driver_available' => '', 'driver_cost_per_day' => '',
                'province_id' => '', 'district_id' => '', 'city_id' => '',
                'address' => '', 'postal_code' => '', 'google_map_link' => ''
            ];

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "System Error: " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $errorStr = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Add Vehicle - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="vehicle_create.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h2 class="mb-4 fw-bold text-dark">Create Vehicle (Admin Mode)</h2>

            <input type="hidden" id="swal-success" value="<?= htmlspecialchars($successStr) ?>">
            <input type="hidden" id="swal-error" value="<?= htmlspecialchars($errorStr) ?>">

            <!-- Data for JS -->
            <input type="hidden" id="districtsData" value="<?= htmlspecialchars(json_encode($districts), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" id="citiesData" value="<?= htmlspecialchars(json_encode($cities), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" id="modelsData" value="<?= htmlspecialchars(json_encode($models), ENT_QUOTES, 'UTF-8') ?>">

            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <!-- Owner Selection -->
                <div class="card shadow-sm mb-4 border-primary">
                    <div class="card-header bg-primary text-white py-3 fw-bold">Select Owner</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <label class="form-label">Vehicle Owner <span class="text-danger">*</span></label>
                                <select name="owner_id" class="form-select form-select-lg" required>
                                    <option value="">-- Select Owner --</option>
                                    <?php foreach ($owners as $owner): ?>
                                    <option value="<?= $owner['user_id'] ?>" <?= $old['owner_id'] == $owner['user_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($owner['name']) ?> (<?= htmlspecialchars($owner['email']) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Basic Info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 fw-bold">Vehicle Details</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Vehicle Title/Name <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Toyota Prius 2018" required value="<?= htmlspecialchars($old['title']) ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select name="type_id" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($vehicleTypes as $t): ?>
                                    <option value="<?= $t['type_id'] ?>" <?= $old['type_id'] == $t['type_id'] ? 'selected' : '' ?>><?= $t['type_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Pricing Model <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="pricing_type_id" id="priceOption1" value="1" <?= $old['pricing_type_id'] == 1 ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-success" for="priceOption1">Per Day</label>

                                    <input type="radio" class="btn-check" name="pricing_type_id" id="priceOption2" value="2" <?= $old['pricing_type_id'] == 2 ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-success" for="priceOption2">Per KM</label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div id="dailyPriceContainer">
                                    <label class="form-label">Daily Price (LKR) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="price_per_day" id="inputDailyPrice" class="form-control" value="<?= htmlspecialchars($old['price_per_day']) ?>">
                                </div>
                                <div id="kmPriceContainer" style="display:none;">
                                    <label class="form-label">Price Per KM (LKR) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="price_per_km" id="inputKmPrice" class="form-control" value="<?= htmlspecialchars($old['price_per_km']) ?>">
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($old['description']) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Specs -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 fw-bold">Specifications</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Brand <span class="text-danger">*</span></label>
                                <select name="brand_id" id="brand" class="form-select" required data-selected="<?= $old['brand_id'] ?>">
                                    <option value="">Select Brand</option>
                                    <?php foreach ($brands as $b): ?>
                                    <option value="<?= $b['brand_id'] ?>" <?= $old['brand_id'] == $b['brand_id'] ? 'selected' : '' ?>><?= $b['brand_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Model <span class="text-danger">*</span></label>
                                <select name="model_id" id="model" class="form-select" disabled required data-selected="<?= $old['model_id'] ?>">
                                    <option value="">Select Brand First</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Year</label>
                                <input type="number" name="year" class="form-control" value="<?= htmlspecialchars($old['year']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fuel</label>
                                <select name="fuel_type_id" class="form-select">
                                    <option value="">Select</option>
                                    <?php foreach ($fuelTypes as $f): ?>
                                    <option value="<?= $f['type_id'] ?>" <?= $old['fuel_type_id'] == $f['type_id'] ? 'selected' : '' ?>><?= $f['type_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Transmission</label>
                                <select name="transmission_type_id" class="form-select">
                                    <option value="">Select</option>
                                    <?php foreach ($transmissionTypes as $t): ?>
                                    <option value="<?= $t['type_id'] ?>" <?= $old['transmission_type_id'] == $t['type_id'] ? 'selected' : '' ?>><?= $t['type_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                             <div class="col-md-4">
                                <label class="form-label">Seats</label>
                                <input type="number" name="seats" class="form-control" value="<?= htmlspecialchars($old['seats']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">License Plate</label>
                                <input type="text" name="license_plate" class="form-control" value="<?= htmlspecialchars($old['license_plate']) ?>">
                            </div>
                             <div class="col-md-4">
                                <label class="form-label">Color <span class="text-danger">*</span></label>
                                <input type="text" name="color" class="form-control" list="colorList" required value="<?= htmlspecialchars($old['color']) ?>">
                                <datalist id="colorList">
                                    <?php foreach ($colors as $c): ?>
                                    <option value="<?= htmlspecialchars($c['color_name']) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="form-check feature-checkbox-card d-inline-block pe-4">
                                <input class="form-check-input" type="checkbox" name="is_driver_available" id="driverCheck" <?= $old['is_driver_available'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="driverCheck">Driver Available</label>
                            </div>
                            <div class="d-inline-block" style="width: 200px; vertical-align: middle;">
                                <input type="number" step="0.01" name="driver_cost_per_day" class="form-control" id="driverCost" placeholder="Driver Cost (LKR)" disabled value="<?= htmlspecialchars($old['driver_cost_per_day']) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 fw-bold">Location</div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Province <span class="text-danger">*</span></label>
                                <select name="province_id" id="province" class="form-select" required data-selected="<?= $old['province_id'] ?>">
                                    <option value="">Select</option>
                                    <?php foreach ($provinces as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $old['province_id'] == $p['id'] ? 'selected' : '' ?>><?= $p['name_en'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">District <span class="text-danger">*</span></label>
                                <select name="district_id" id="district" class="form-select" disabled data-selected="<?= $old['district_id'] ?>">
                                    <option value="">Select Province First</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City <span class="text-danger">*</span></label>
                                <select name="city_id" id="city" class="form-select" disabled required data-selected="<?= $old['city_id'] ?>">
                                    <option value="">Select District First</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control" required placeholder="Full Address" value="<?= htmlspecialchars($old['address']) ?>">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($old['postal_code']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Google Maps Link</label>
                                <input type="text" name="google_map_link" class="form-control" placeholder="Share Link from Google Maps" value="<?= htmlspecialchars($old['google_map_link']) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Images -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 fw-bold">Photos</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Upload Images (Min 3) <span class="text-danger">*</span></label>
                            <input type="file" name="vehicle_images[]" id="vehicleImages" class="form-control" multiple accept="image/*" required>
                        </div>
                        <div id="imagePreviewContainer" class="d-flex flex-wrap gap-2"></div>
                        <input type="hidden" name="primary_image" id="primaryImageIndex" value="0">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3">
                    <a href="<?= app_url('admin/index/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="background: var(--fern); border-color: var(--fern);">Submit Vehicle (Admin)</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="vehicle_create.js"></script>
</body>
</html>
