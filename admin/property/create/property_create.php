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
$types = $pdo->query("SELECT * FROM `property_type` ORDER BY `type_name` ASC")->fetchAll();
$provinces = $pdo->query("SELECT * FROM `provinces` ORDER BY `name_en` ASC")->fetchAll();
$districts = $pdo->query("SELECT * FROM `districts` ORDER BY `name_en` ASC")->fetchAll();
$cities = $pdo->query("SELECT * FROM `cities` ORDER BY `name_en` ASC")->fetchAll();
$amenities = $pdo->query("SELECT * FROM `amenity` WHERE `category` IN ('property', 'both') ORDER BY `amenity_name` ASC")->fetchAll();

// Fetch Owners for Dropdown
$owners = $pdo->query("SELECT user_id, name, email FROM `user` WHERE `role_id` = 3 AND `status_id` = 1 ORDER BY `name` ASC")->fetchAll();

// Form Data Holders
$old = [
    'owner_id' => '', // New field for Admin
    'title' => '', 'description' => '', 'price' => '', 'type_id' => '',
    'sqft' => '', 'bedrooms' => '1', 'bathrooms' => '1',
    'province_id' => '', 'district_id' => '', 'city_id' => '',
    'address' => '', 'postal_code' => '', 'google_map_link' => '',
    'amenities' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF Token';
    }

    $old = array_merge($old, $_POST);

    // Input Sanitization
    $ownerId = intval($_POST['owner_id'] ?? 0); // New
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $typeId = intval($_POST['type_id'] ?? 0);
    $sqft = floatval($_POST['sqft'] ?? 0);
    $beds = intval($_POST['bedrooms'] ?? 0);
    $baths = intval($_POST['bathrooms'] ?? 0);
    
    // Location
    $provinceId = intval($_POST['province_id'] ?? 0);
    $districtId = intval($_POST['district_id'] ?? 0);
    $cityId = intval($_POST['city_id'] ?? 0);
    $address = trim($_POST['address'] ?? '');
    $postal = trim($_POST['postal_code'] ?? '');
    $gmap = trim($_POST['google_map_link'] ?? '');
    
    $selectedAmenities = $_POST['amenities'] ?? [];

    // Validation
    if (!$ownerId) {
        $errors[] = "Please select a Property Owner.";
    }
    if (!$title || !$price || !$typeId || !$cityId || !$address) {
        $errors[] = "Please fill in all required fields.";
    }

    // Image Upload
    $uploadedImages = [];
    if (!empty($_FILES['property_images']['name'][0])) {
        $count = count($_FILES['property_images']['name']);
        if ($count < 3) $errors[] = "Please upload at least 3 images.";
        
        if (!$errors) {
            $uploadDir = __DIR__ . '/../../../public/uploads/properties/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $validTypes = ['jpg', 'jpeg', 'png', 'webp'];
            
            foreach ($_FILES['property_images']['tmp_name'] as $k => $tmp) {
                if ($_FILES['property_images']['error'][$k] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['property_images']['name'][$k], PATHINFO_EXTENSION));
                    if (!in_array($ext, $validTypes)) {
                        $errors[] = "Invalid format. JPG, PNG, WEBP only. File: " . $_FILES['property_images']['name'][$k];
                        break; 
                    }
                    
                    $newName = 'prop_' . uniqid() . '_' . time() . '_' . $k . '.' . $ext;
                    if (move_uploaded_file($tmp, $uploadDir . $newName)) {
                        $uploadedImages[] = 'public/uploads/properties/' . $newName;
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
            
            // 1. Insert Property
            $propCode = 'PROP-' . strtoupper(uniqid());
            $stmt = $pdo->prepare("INSERT INTO `property` 
                (`property_code`, `owner_id`, `property_type_id`, `title`, `description`, `price_per_month`, `sqft`, `bedrooms`, `bathrooms`, `status_id`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"); // Admin creates as 1=Available immediately
            $stmt->execute([$propCode, $ownerId, $typeId, $title, $description, $price, $sqft, $beds, $baths]);
            $propId = $pdo->lastInsertId();

            // 2. Insert Location
            $stmt = $pdo->prepare("INSERT INTO `property_location` (`property_id`, `province_id`, `district_id`, `city_id`, `address`, `postal_code`, `google_map_link`) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$propId, $provinceId, $districtId, $cityId, $address, $postal, $gmap]);

            // 3. Insert Images
            $primaryIdx = intval($_POST['primary_image'] ?? 0);
            $stmt = $pdo->prepare("INSERT INTO `property_image` (`property_id`, `image_path`, `primary_image`) VALUES (?, ?, ?)");
            foreach ($uploadedImages as $idx => $path) {
                $isMain = ($idx === $primaryIdx) ? 1 : 0;
                $stmt->execute([$propId, $path, $isMain]);
            }

            // 4. Insert Amenities
            if ($selectedAmenities) {
                $stmt = $pdo->prepare("INSERT INTO `property_amenity` (`property_id`, `amenity_id`) VALUES (?, ?)");
                foreach ($selectedAmenities as $aid) {
                    $stmt->execute([$propId, $aid]);
                }
            }

            // Note: We are ignoring quota decrements for Admin actions, assuming Admin invokes override power.
            
            $pdo->commit();
            $successStr = "Property submitted successfully and is now Live.";
            
            // Reset form
            $old = [
                'owner_id' => '',
                'title' => '', 'description' => '', 'price' => '', 'type_id' => '',
                'sqft' => '', 'bedrooms' => '1', 'bathrooms' => '1',
                'province_id' => '', 'district_id' => '', 'city_id' => '',
                'address' => '', 'postal_code' => '', 'google_map_link' => '',
                'amenities' => []
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
    <title>Admin - Add Property - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="property_create.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h2 class="mb-4 fw-bold text-dark">Create Property (Admin Mode)</h2>

            <!-- SweetAlert Hidden Inputs -->
            <input type="hidden" id="swal-success" value="<?= htmlspecialchars($successStr) ?>">
            <input type="hidden" id="swal-error" value="<?= htmlspecialchars($errorStr) ?>">

            <!-- Location Data for JS -->
            <input type="hidden" id="districtsData" value="<?= htmlspecialchars(json_encode($districts), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" id="citiesData" value="<?= htmlspecialchars(json_encode($cities), ENT_QUOTES, 'UTF-8') ?>">

            <form method="POST" enctype="multipart/form-data" id="propertyForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <!-- Owner Selection (Admin Only) -->
                <div class="card shadow-sm mb-4 border-primary">
                    <div class="card-header bg-primary text-white py-3 fw-bold">Select Owner</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <label class="form-label">Property Owner <span class="text-danger">*</span></label>
                                <select name="owner_id" class="form-select form-select-lg" required>
                                    <option value="">-- Select Owner --</option>
                                    <?php foreach ($owners as $owner): ?>
                                    <option value="<?= $owner['user_id'] ?>" <?= $old['owner_id'] == $owner['user_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($owner['name']) ?> (<?= htmlspecialchars($owner['email']) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Select the registered owner who will own this property listing.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Basic Info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 fw-bold">Basic Information</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Property Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="Modern Apartment in City Center" required value="<?= htmlspecialchars($old['title']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Property Type <span class="text-danger">*</span></label>
                                <select name="type_id" class="form-control" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($types as $t): ?>
                                    <option value="<?= $t['type_id'] ?>" <?= $old['type_id'] == $t['type_id'] ? 'selected' : '' ?>><?= $t['type_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Monthly Price (LKR) <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-control" required value="<?= htmlspecialchars($old['price']) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($old['description']) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 fw-bold">Features & Amenities</div>
                    <div class="card-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Bedrooms</label>
                                <input type="number" name="bedrooms" class="form-control" value="<?= htmlspecialchars($old['bedrooms']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Bathrooms</label>
                                <input type="number" name="bathrooms" class="form-control" value="<?= htmlspecialchars($old['bathrooms']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Area (Sqft)</label>
                                <input type="number" name="sqft" class="form-control" placeholder="e.g. 1200" value="<?= htmlspecialchars($old['sqft']) ?>">
                            </div>
                        </div>
                        <label class="form-label mb-2">Amenities</label>
                        <div class="row g-3">
                            <?php foreach ($amenities as $a): ?>
                            <div class="col-6 col-md-3">
                                <div class="form-check amenity-card">
                                    <input class="form-check-input" type="checkbox" name="amenities[]" value="<?= $a['amenity_id'] ?>" id="am_<?= $a['amenity_id'] ?>"
                                    <?= in_array($a['amenity_id'], $old['amenities']) ? 'checked' : '' ?>>
                                    <label class="form-check-label w-100" for="am_<?= $a['amenity_id'] ?>"><?= $a['amenity_name'] ?></label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 fw-bold">Location</div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Province</label>
                                <select name="province_id" id="province" class="form-control" data-selected="<?= $old['province_id'] ?>">
                                    <option value="">Select</option>
                                    <?php foreach ($provinces as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $old['province_id'] == $p['id'] ? 'selected' : '' ?>><?= $p['name_en'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">District</label>
                                <select name="district_id" id="district" class="form-control" disabled data-selected="<?= $old['district_id'] ?>">
                                    <option value="">Select Province First</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City <span class="text-danger">*</span></label>
                                <select name="city_id" id="city" class="form-control" disabled required data-selected="<?= $old['city_id'] ?>">
                                    <option value="">Select District First</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
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
                            <input type="file" name="property_images[]" id="imgInput" class="form-control" multiple accept="image/*" required>
                        </div>
                        <div id="previewArea" class="d-flex flex-wrap gap-2"></div>
                        <p class="small text-muted mt-2">Click an image to set as Primary Cover.</p>
                        <input type="hidden" name="primary_image" id="primaryIdx" value="0">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3">
                    <a href="<?= app_url('admin/index/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="background: var(--fern); border-color: var(--fern);">Submit Property (Admin)</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="property_create.js"></script>
</body>
</html>
