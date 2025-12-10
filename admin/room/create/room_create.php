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
$roomTypes = $pdo->query("SELECT * FROM `room_type` ORDER BY `type_name` ASC")->fetchAll();
$provinces = $pdo->query("SELECT * FROM `provinces` ORDER BY `name_en` ASC")->fetchAll();
$districts = $pdo->query("SELECT * FROM `districts` ORDER BY `name_en` ASC")->fetchAll();
$cities = $pdo->query("SELECT * FROM `cities` ORDER BY `name_en` ASC")->fetchAll();
$amenities = $pdo->query("SELECT * FROM `amenity` WHERE `category` IN ('room', 'both') ORDER BY `amenity_name` ASC")->fetchAll();
$meal_types = $pdo->query("SELECT * FROM `meal_type` ORDER BY `type_name` ASC")->fetchAll();

// Fetch Owners for Dropdown (Role 3, Active)
$owners = $pdo->query("SELECT user_id, name, email FROM `user` WHERE `role_id` = 3 AND `status_id` = 1 ORDER BY `name` ASC")->fetchAll();

// Form Data Holders
$old = [
    'owner_id' => '', 
    'title' => '', 'description' => '', 'price' => '', 'type_id' => '',
    'beds' => '1', 'bathrooms' => '1', 'maximum_guests' => '1',
    'province_id' => '', 'district_id' => '', 'city_id' => '',
    'address' => '', 'postal_code' => '', 'google_map_link' => '',
    'amenities' => [],
    'meals' => [], 'meal_prices' => [], 'meal_option' => 'none'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF Token';
    }

    $old = array_merge($old, $_POST);

    // Input Sanitization
    $ownerId = intval($_POST['owner_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $typeId = intval($_POST['type_id'] ?? 0);
    
    $beds = intval($_POST['beds'] ?? 1);
    $bathrooms = intval($_POST['bathrooms'] ?? 1);
    $maxGuests = intval($_POST['maximum_guests'] ?? 1);

    // Location
    $provinceId = intval($_POST['province_id'] ?? 0);
    $districtId = intval($_POST['district_id'] ?? 0);
    $cityId = intval($_POST['city_id'] ?? 0);
    $address = trim($_POST['address'] ?? '');
    $postal = trim($_POST['postal_code'] ?? '');
    $gmap = trim($_POST['google_map_link'] ?? '');
    
    $selectedAmenities = $_POST['amenities'] ?? [];
    $selectedMeals = $_POST['meals'] ?? [];
    $mealPrices = $_POST['meal_prices'] ?? [];
    $mealOption = $_POST['meal_option'] ?? 'none';

    // Validation
    if (!$ownerId) $errors[] = "Please select a Property Owner.";
    if (!$title || !$price || !$typeId || !$cityId || !$address) {
        $errors[] = "Please fill in all required fields (Title, Price, Type, City, Address).";
    }

    // Image Upload
    $uploadedImages = [];
    if (!empty($_FILES['room_images']['name'][0])) {
        $count = count($_FILES['room_images']['name']);
        if ($count < 3) $errors[] = "Please upload at least 3 images.";
        
        if (!$errors) {
            $uploadDir = __DIR__ . '/../../../public/uploads/rooms/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $validTypes = ['jpg', 'jpeg', 'png', 'webp'];
            
            foreach ($_FILES['room_images']['tmp_name'] as $k => $tmp) {
                if ($_FILES['room_images']['error'][$k] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['room_images']['name'][$k], PATHINFO_EXTENSION));
                    if (!in_array($ext, $validTypes)) {
                        $errors[] = "Invalid format. JPG, PNG, WEBP only. File: " . $_FILES['room_images']['name'][$k];
                        break; 
                    }
                    if ($_FILES['room_images']['size'][$k] > 5 * 1024 * 1024) {
                        $errors[] = "Image too large (Max 5MB). File: " . $_FILES['room_images']['name'][$k];
                        break;
                    }

                    $newName = 'room_' . uniqid() . '_' . time() . '_' . $k . '.' . $ext;
                    if (move_uploaded_file($tmp, $uploadDir . $newName)) {
                        $uploadedImages[] = 'public/uploads/rooms/' . $newName;
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
            
            // 1. Insert Room
            $roomCode = 'ROOM-' . strtoupper(uniqid());
            $stmt = $pdo->prepare("INSERT INTO `room` 
                (`room_code`, `owner_id`, `title`, `description`, `price_per_day`, `beds`, `bathrooms`, `maximum_guests`, `room_type_id`, `status_id`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"); // Admin creates as 1=Active
            $stmt->execute([$roomCode, $ownerId, $title, $description, $price, $beds, $bathrooms, $maxGuests, $typeId]);
            $roomId = $pdo->lastInsertId();

            // 2. Insert Location (City only required by schema usually, but we store address)
            // Note: Schema for room_location might differ from property_location. 
            // Checking owner room create: INSERT INTO `room_location` (`room_id`, `city_id`, `address`, `postal_code`, `google_map_link`)
            $stmt = $pdo->prepare("INSERT INTO `room_location` (`room_id`, `city_id`, `address`, `postal_code`, `google_map_link`) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$roomId, $cityId, $address, $postal, $gmap]);

            // 3. Insert Images
            $primaryIdx = intval($_POST['primary_image'] ?? 0);
            $stmt = $pdo->prepare("INSERT INTO `room_image` (`room_id`, `image_path`, `primary_image`) VALUES (?, ?, ?)");
            foreach ($uploadedImages as $idx => $path) {
                $isMain = ($idx === $primaryIdx) ? 1 : 0;
                $stmt->execute([$roomId, $path, $isMain]);
            }

            // 4. Insert Amenities
            if ($selectedAmenities) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO `room_amenity` (`room_id`, `amenity_id`) VALUES (?, ?)");
                foreach ($selectedAmenities as $aid) {
                    $stmt->execute([$roomId, $aid]);
                }
            }
            
            // 5. Insert Meals
            if ($mealOption === 'available' && !empty($selectedMeals)) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO `room_meal` (`room_id`, `meal_type_id`, `price`) VALUES (?, ?, ?)");
                foreach ($selectedMeals as $mid) {
                    $mPrice = floatval($mealPrices[$mid] ?? 0);
                    $stmt->execute([$roomId, $mid, $mPrice]);
                }
            }

            // Note: Quota decrement ignored for admin.

            $pdo->commit();
            $successStr = "Room submitted successfully and is now Live.";
            
            // Reset form
            $old = [
                'owner_id' => '', 
                'title' => '', 'description' => '', 'price' => '', 'type_id' => '',
                'beds' => '1', 'bathrooms' => '1', 'maximum_guests' => '1',
                'province_id' => '', 'district_id' => '', 'city_id' => '',
                'address' => '', 'postal_code' => '', 'google_map_link' => '',
                'amenities' => [],
                'meals' => [], 'meal_prices' => [], 'meal_option' => 'none'
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
    <title>Admin - Add Room - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="room_create.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h2 class="mb-4 fw-bold text-dark">Create Room (Admin Mode)</h2>

            <!-- SweetAlert Hidden Inputs -->
            <input type="hidden" id="swal-success" value="<?= htmlspecialchars($successStr) ?>">
            <input type="hidden" id="swal-error" value="<?= htmlspecialchars($errorStr) ?>">

            <!-- Location Data for JS -->
            <input type="hidden" id="districtsData" value="<?= htmlspecialchars(json_encode($districts), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" id="citiesData" value="<?= htmlspecialchars(json_encode($cities), ENT_QUOTES, 'UTF-8') ?>">

            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <!-- Owner Selection (Admin Only) -->
                <div class="card shadow-sm mb-4 border-primary">
                    <div class="card-header bg-primary text-white py-3 fw-bold">Select Owner</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <label class="form-label">Property/Room Owner <span class="text-danger">*</span></label>
                                <select name="owner_id" class="form-select form-select-lg" required>
                                    <option value="">-- Select Owner --</option>
                                    <?php foreach ($owners as $owner): ?>
                                    <option value="<?= $owner['user_id'] ?>" <?= $old['owner_id'] == $owner['user_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($owner['name']) ?> (<?= htmlspecialchars($owner['email']) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Select the registered owner who will own this room listing.</div>
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
                                <label class="form-label">Room Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Cozy Single Room" required value="<?= htmlspecialchars($old['title']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Room Type <span class="text-danger">*</span></label>
                                <select name="type_id" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($roomTypes as $t): ?>
                                    <option value="<?= $t['type_id'] ?>" <?= $old['type_id'] == $t['type_id'] ? 'selected' : '' ?>><?= $t['type_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Daily Price (LKR) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="price" class="form-control" required value="<?= htmlspecialchars($old['price']) ?>">
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
                    <div class="card-header bg-white py-3 fw-bold">Room Details & Amenities</div>
                    <div class="card-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Beds</label>
                                <input type="number" name="beds" class="form-control" min="1" value="<?= htmlspecialchars($old['beds']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Bathrooms</label>
                                <input type="number" name="bathrooms" class="form-control" min="1" value="<?= htmlspecialchars($old['bathrooms']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Max Guests</label>
                                <input type="number" name="maximum_guests" class="form-control" min="1" value="<?= htmlspecialchars($old['maximum_guests']) ?>">
                            </div>
                        </div>
                        
                        <label class="form-label mb-2 fw-bold">Amenities</label>
                        <div class="row g-3 mb-4">
                            <?php foreach ($amenities as $a): ?>
                            <div class="col-6 col-md-3">
                                <div class="form-check feature-checkbox-card">
                                    <input class="form-check-input" type="checkbox" name="amenities[]" value="<?= $a['amenity_id'] ?>" id="am_<?= $a['amenity_id'] ?>"
                                    <?= in_array($a['amenity_id'], $old['amenities']) ? 'checked' : '' ?>>
                                    <label class="form-check-label w-100 stretched-link" for="am_<?= $a['amenity_id'] ?>"><?= $a['amenity_name'] ?></label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Meals -->
                        <div class="border-top pt-4">
                            <label class="form-label mb-3 fw-bold d-block">Meal Plan Options</label>
                            <div class="mb-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="meal_option" id="mealsNone" value="none" <?= $old['meal_option'] === 'none' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="mealsNone">No Meals Provided</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="meal_option" id="mealsAvailable" value="available" <?= $old['meal_option'] === 'available' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="mealsAvailable">Meals Available</label>
                                </div>
                            </div>

                            <div id="mealSelectionContainer" class="row g-3" style="display:none;">
                                <div class="col-12"><small class="text-muted">Select available meals and set their price. Check "Free" if included in rent.</small></div>
                                <?php foreach ($meal_types as $meal): ?>
                                <div class="col-12 col-md-6">
                                    <div class="card p-2 border-primary-subtle">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="form-check me-auto">
                                                <input class="form-check-input meal-check" type="checkbox" name="meals[]" value="<?= $meal['type_id'] ?>" id="meal_<?= $meal['type_id'] ?>"
                                                <?= in_array($meal['type_id'], $old['meals']) ? 'checked' : '' ?>>
                                                <label class="form-check-label fw-medium" for="meal_<?= $meal['type_id'] ?>"><?= htmlspecialchars($meal['type_name']) ?></label>
                                            </div>
                                        </div>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">LKR</span>
                                            <input type="number" step="0.01" class="form-control meal-price" name="meal_prices[<?= $meal['type_id'] ?>]" id="price_<?= $meal['type_id'] ?>" placeholder="Price" disabled
                                            value="<?= htmlspecialchars($old['meal_prices'][$meal['type_id']] ?? '') ?>">
                                            <div class="input-group-text bg-white">
                                                <input class="form-check-input mt-0 me-1 meal-free-check" type="checkbox" value="1" id="free_<?= $meal['type_id'] ?>" disabled>
                                                <label for="free_<?= $meal['type_id'] ?>" class="small mb-0">Free</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
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
                            <input type="file" name="room_images[]" id="roomImages" class="form-control" multiple accept="image/*" required>
                        </div>
                        <div id="imagePreviewContainer" class="d-flex flex-wrap gap-2"></div>
                        <p class="small text-muted mt-2">Click an image to set as Primary Cover.</p>
                        <input type="hidden" name="primary_image" id="primaryImageIndex" value="0">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3">
                    <a href="<?= app_url('admin/index/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="background: var(--fern); border-color: var(--fern);">Submit Room (Admin)</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="room_create.js"></script>
</body>
</html>
