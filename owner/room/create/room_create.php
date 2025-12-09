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

// Check if owner has an active package with available room slots
$packageCheck = check_owner_package_quota($user['user_id'], 'room');
if (!$packageCheck['success']) {
    // Redirect to package purchase page
    $_SESSION['package_required_message'] = $packageCheck['message'];
    header('Location: ' . $packageCheck['redirect_url']);
    exit;
}

// Fetch Room Types
$stmt = $pdo->query("SELECT * FROM `room_type` ORDER BY `type_name` ASC");
$roomTypes = $stmt->fetchAll();

// Fetch Provinces
$stmt = $pdo->query("SELECT * FROM `provinces` ORDER BY `name_en` ASC");
$provinces = $stmt->fetchAll();

// Fetch all districts and cities (for JavaScript cascading)
$stmt = $pdo->query("SELECT * FROM `districts` ORDER BY `name_en` ASC");
$districts = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM `cities` ORDER BY `name_en` ASC");
$cities = $stmt->fetchAll();

// Fetch Amenities dynamically
$stmt = $pdo->query("SELECT * FROM `amenity` WHERE `category` IN ('room', 'both') ORDER BY `amenity_name` ASC");
$available_amenities = $stmt->fetchAll();

// Fetch Meal Types
$stmt = $pdo->query("SELECT * FROM `meal_type` ORDER BY `type_name` ASC");
$meal_types = $stmt->fetchAll();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF Token');
    }

    // Inputs
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $typeId = intval($_POST['type_id'] ?? 0);
    $beds = intval($_POST['beds'] ?? 1);
    $bathrooms = intval($_POST['bathrooms'] ?? 1);
    $maxGuests = intval($_POST['maximum_guests'] ?? 1);
    $address = trim($_POST['address'] ?? '');
    $postalCode = trim($_POST['postal_code'] ?? '');
    $googleMapLink = trim($_POST['google_map_link'] ?? '');
    
    // Location dropdowns
    $provinceId = intval($_POST['province_id'] ?? 0);
    $districtId = intval($_POST['district_id'] ?? 0);
    $cityId = intval($_POST['city_id'] ?? 0);

    // Selected Amenities & Meals
    $selected_amenities = $_POST['amenities'] ?? [];
    $selected_meals = $_POST['meals'] ?? []; // Array of meal_type_ids
    $meal_prices = $_POST['meal_prices'] ?? []; // Array mapped by meal_type_id key

    // Validation
    if (!$title || !$price || !$typeId || !$address) {
        $errors[] = 'Title, Price, Room Type, and Address are required.';
    }

    // Image Upload - Primary and Gallery
    $uploadedImages = [];
    
    if (!empty($_FILES['room_images']['name'][0])) {
        $imageCount = count($_FILES['room_images']['name']);
        
        // Validate image count (min 3, max 15)
        if ($imageCount < 3) {
            $errors[] = 'Please upload at least 3 images.';
        } elseif ($imageCount > 15) {
            $errors[] = 'Maximum 15 images allowed.';
        }
        
        if (!$errors) {
            $uploadDir = __DIR__ . '/../../../public/uploads/rooms/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $validTypes = ['jpg', 'jpeg', 'png', 'webp'];
            
            foreach ($_FILES['room_images']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['room_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileName = $_FILES['room_images']['name'][$key];
                    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    
                    if (!in_array($fileType, $validTypes)) {
                        $errors[] = "Invalid image type for {$fileName}. JPG, PNG, WEBP only.";
                        break;
                    }
                    
                    if ($_FILES['room_images']['size'][$key] > 5 * 1024 * 1024) {
                        $errors[] = "Image {$fileName} is too large (Max 5MB).";
                        break;
                    }
                    
                    $newFileName = 'room_' . uniqid() . '_' . time() . '.' . $fileType;
                    $destination = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($tmpName, $destination)) {
                        $uploadedImages[] = 'public/uploads/rooms/' . $newFileName;
                    } else {
                        $errors[] = 'Failed to upload image.';
                        break;
                    }
                }
            }
        }
    } else {
        $errors[] = 'At least 3 room images are required.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $roomCode = 'ROOM-' . strtoupper(uniqid());
            $stmt = $pdo->prepare("INSERT INTO `room` (
                `room_code`, `owner_id`, `title`, `description`, `price_per_day`, 
                `beds`, `bathrooms`, `maximum_guests`, `room_type_id`, `status_id`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 4)"); // Status 4 = Pending
            
            $stmt->execute([
                $roomCode, $user['user_id'], $title, $description, $price,
                $beds, $bathrooms, $maxGuests, $typeId
            ]);
            $roomId = $pdo->lastInsertId();

            // 2. Insert Location (Only City ID)
            $stmt = $pdo->prepare("INSERT INTO `room_location` (
                `room_id`, `city_id`, `address`, `postal_code`, `google_map_link`
            ) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$roomId, $cityId, $address, $postalCode, $googleMapLink]);

            // 3. Insert Images (first image is primary)
            if (!empty($uploadedImages)) {
                $primaryImage = intval($_POST['primary_image'] ?? 0); // Index of primary image
                foreach ($uploadedImages as $index => $imagePath) {
                    $isPrimary = ($index === $primaryImage) ? 1 : 0;
                    $stmt = $pdo->prepare("INSERT INTO `room_image` (`room_id`, `image_path`, `primary_image`) VALUES (?, ?, ?)");
                    $stmt->execute([$roomId, $imagePath, $isPrimary]);
                }
            }

            // 4. Insert Amenities
            if (!empty($selected_amenities)) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO `room_amenity` (`room_id`, `amenity_id`) VALUES (?, ?)");
                foreach ($selected_amenities as $amenityId) {
                    $stmt->execute([$roomId, intval($amenityId)]);
                }
            }

            // 5. Insert Meals
            $mealOption = $_POST['meal_option'] ?? 'none';
            if ($mealOption === 'available' && !empty($selected_meals)) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO `room_meal` (`room_id`, `meal_type_id`, `price`) VALUES (?, ?, ?)");
                foreach ($selected_meals as $mealId) {
                    $price = floatval($meal_prices[$mealId] ?? 0);
                    $stmt->execute([$roomId, intval($mealId), $price]);
                }
            }

            // 6. Decrement package quota
            decrement_package_quota($packageCheck['package_id'], 'room');

            $pdo->commit();
            $success = "Room listed successfully! It is pending approval. You have " . ($packageCheck['remaining'] - 1) . " room slot(s) remaining.";
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
    <title>List New Room - Rental Lanka</title>
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
            <h2 class="mb-4 fw-bold text-dark">List Your Room</h2>

            <?php if (isset($packageCheck) && $packageCheck['success']): ?>
                <div class="alert alert-info shadow-sm d-flex align-items-center mb-4">
                    <i class="bi bi-briefcase-fill me-3 fs-3 text-primary"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Active Package: <?= htmlspecialchars($packageCheck['package_name']) ?></h6>
                        <p class="mb-0 small">You have <strong><?= $packageCheck['remaining'] ?></strong> room listing(s) remaining in this package.</p>
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
                                <label class="form-label">Room Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Cozy Single Room in Colombo" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Room Type <span class="text-danger">*</span></label>
                                <select name="type_id" class="form-select" required>
                                    <option value="" selected disabled>Select Type</option>
                                    <?php foreach ($roomTypes as $type): ?>
                                        <option value="<?= $type['type_id'] ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Daily Price (LKR) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="price" class="form-control" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Describe the room..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold" style="color: var(--hunter-green);">Room Details & Amenities</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Beds</label>
                                <input type="number" name="beds" class="form-control" min="1" value="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Bathrooms</label>
                                <input type="number" name="bathrooms" class="form-control" min="1" value="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Maximum Guests</label>
                                <input type="number" name="maximum_guests" class="form-control" min="1" value="1">
                            </div>
                        </div>
                        
                        <label class="form-label mb-3 d-block">Amenities</label>
                        <div class="row g-3 mb-4">
                            <?php foreach ($available_amenities as $amenity): ?>
                            <div class="col-6 col-md-3">
                                <div class="form-check feature-checkbox-card">
                                    <input class="form-check-input" type="checkbox" name="amenities[]" value="<?= $amenity['amenity_id'] ?>" id="check_<?= $amenity['amenity_id'] ?>">
                                    <label class="form-check-label w-100" for="check_<?= $amenity['amenity_id'] ?>">
                                        <?= htmlspecialchars($amenity['amenity_name']) ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <label class="form-label mb-3 d-block">Meal Plan Options</label>
                        
                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="meal_option" id="mealsNone" value="none" checked>
                                <label class="form-check-label" for="mealsNone">No Meals Provided</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="meal_option" id="mealsAvailable" value="available">
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
                                            <input class="form-check-input meal-check" type="checkbox" name="meals[]" value="<?= $meal['type_id'] ?>" id="meal_<?= $meal['type_id'] ?>">
                                            <label class="form-check-label fw-medium" for="meal_<?= $meal['type_id'] ?>"><?= htmlspecialchars($meal['type_name']) ?></label>
                                        </div>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">LKR</span>
                                        <input type="number" step="0.01" class="form-control meal-price" name="meal_prices[<?= $meal['type_id'] ?>]" id="price_<?= $meal['type_id'] ?>" placeholder="Price" disabled>
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
                            <label class="form-label">Room Address <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control" placeholder="Street Address" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Google Map Link</label>
                            <input type="url" name="google_map_link" class="form-control" placeholder="https://maps.google.com/...">
                            <div class="form-text">Paste the Google Maps link for the room location</div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="postal_code" class="form-control" placeholder="Postal Code">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Room Images <span class="text-danger">*</span></label>
                            <input type="file" name="room_images[]" id="roomImages" class="form-control" accept="image/*" multiple required>
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
                    <button type="submit" class="btn btn-primary px-5 btn-lg shadow-sm" style="background-color: var(--fern); border-color: var(--fern);">Submit Room</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<link rel="stylesheet" href="room_create.css">
<script src="room_create.js"></script>
<script>
// Location data from PHP
const districts = <?= json_encode($districts) ?>;
const cities = <?= json_encode($cities) ?>;

// Province change handler
document.getElementById('province').addEventListener('change', function() {
    const provinceId = parseInt(this.value);
    const districtSelect = document.getElementById('district');
    const citySelect = document.getElementById('city');
    
    // Clear and disable district and city
    districtSelect.innerHTML = '<option value="" selected>Select District</option>';
    citySelect.innerHTML = '<option value="" selected>Select Province first</option>';
    citySelect.disabled = true;
    
    // Filter districts by province
    const filteredDistricts = districts.filter(d => d.province_id == provinceId);
    
    if (filteredDistricts.length > 0) {
        filteredDistricts.forEach(district => {
            const option = document.createElement('option');
            option.value = district.id;
            option.textContent = district.name_en;
            districtSelect.appendChild(option);
        });
        districtSelect.disabled = false;
    } else {
        districtSelect.disabled = true;
    }
});

// District change handler
document.getElementById('district').addEventListener('change', function() {
    const districtId = parseInt(this.value);
    const citySelect = document.getElementById('city');
    
    // Clear city
    citySelect.innerHTML = '<option value="" selected>Select City</option>';
    
    // Filter cities by district
    const filteredCities = cities.filter(c => c.district_id == districtId);
    
    if (filteredCities.length > 0) {
        filteredCities.forEach(city => {
            const option = document.createElement('option');
            option.value = city.id;
            option.textContent = city.name_en;
            citySelect.appendChild(option);
        });
        citySelect.disabled = false;
    } else {
        citySelect.disabled = true;
    }
});

// Meal Options Handler
document.addEventListener('DOMContentLoaded', function() {
    const radioNone = document.getElementById('mealsNone');
    const radioAvailable = document.getElementById('mealsAvailable');
    const container = document.getElementById('mealSelectionContainer');
    
    function toggleMeals() {
        if (radioAvailable.checked) {
            container.style.display = 'flex';
        } else {
            container.style.display = 'none';
            // Optional: Uncheck all if hidden? prefer to keep state
        }
    }
    
    radioNone.addEventListener('change', toggleMeals);
    radioAvailable.addEventListener('change', toggleMeals);
    
    // Per Meal Logic
    const mealChecks = document.querySelectorAll('.meal-check');
    mealChecks.forEach(check => {
        check.addEventListener('change', function() {
            const id = this.value;
            const priceInput = document.getElementById('price_' + id);
            const freeCheck = document.getElementById('free_' + id);
            
            if (this.checked) {
                priceInput.disabled = freeCheck.checked; // If Free is checked, keep disabled
                freeCheck.disabled = false;
            } else {
                priceInput.disabled = true;
                freeCheck.disabled = true;
                priceInput.value = '';
                freeCheck.checked = false;
            }
        });
    });

    const freeChecks = document.querySelectorAll('.meal-free-check');
    freeChecks.forEach(check => {
        check.addEventListener('change', function() {
            // Find sibling price input (traversing DOM or using ID logic)
            // ID format: free_X -> price_X
            const id = this.id.split('_')[1];
            const priceInput = document.getElementById('price_' + id);
            
            if (this.checked) {
                priceInput.value = 0;
                priceInput.disabled = true;
                // Add hidden input to force sending 0 if disabled inputs aren't sent?
                // Disabled inputs are NOT sent in POST.
                // We handle this: if checkbox checked but price missing, defaulting to 0 in PHP?
                // No, PHP reads 'price'. If disabled, 'price' key is missing.
                // We should rely on JS to Enable it before submit OR change logic.
                // Better: Set Readonly? Readonly inputs ARE sent.
                priceInput.readOnly = true;
                priceInput.disabled = false; // Enable but readonly
            } else {
                priceInput.readOnly = false;
                priceInput.disabled = false;
                priceInput.value = '';
            }
        });
    });
});

</script>
</body>
</html>