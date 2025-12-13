<?php
require __DIR__ . '/../../../../config/db.php';

ensure_session_started();
$user = current_user();

// Check if user is Owner (Role ID 3)
if (!$user || !in_array($user['role_id'], [3])) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();
$success = $_SESSION['_flash']['success'] ?? null;
$errors = $_SESSION['_flash']['errors'] ?? [];
unset($_SESSION['_flash']);

$csrf_token = generate_csrf_token();

$roomId = $_GET['id'] ?? 0;
if (!$roomId) {
    header('Location: ../manage.php');
    exit;
}

// Fetch Room
$stmt = $pdo->prepare("SELECT * FROM room WHERE room_id = ? AND owner_id = ?");
$stmt->execute([$roomId, $user['user_id']]);
$room = $stmt->fetch();

if (!$room) {
    echo "Room not found or access denied.";
    exit;
}

// Fetch Room Types
$stmt = $pdo->query("SELECT * FROM `room_type` ORDER BY `type_name` ASC");
$roomTypes = $stmt->fetchAll();

// Fetch Provinces
$stmt = $pdo->query("SELECT * FROM `provinces` ORDER BY `name_en` ASC");
$provinces = $stmt->fetchAll();

// Fetch all districts and cities
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

// --- Fetch Existing Data for Pre-filling ---

// Location
$stmt = $pdo->prepare("SELECT * FROM room_location WHERE room_id = ?");
$stmt->execute([$roomId]);
$l = $stmt->fetch() ?: ['address'=>'', 'postal_code'=>'', 'google_map_link'=>'', 'city_id'=>0];

// Determine Province/District from City ID
$currentCityId = $l['city_id'];
$currentDistrictId = 0;
$currentProvinceId = 0;

if ($currentCityId) {
    foreach ($cities as $c) {
        if ($c['id'] == $currentCityId) {
            $currentDistrictId = $c['district_id'];
            break;
        }
    }
    if ($currentDistrictId) {
        foreach ($districts as $d) {
            if ($d['id'] == $currentDistrictId) {
                $currentProvinceId = $d['province_id'];
                break;
            }
        }
    }
}

// Images
$stmt = $pdo->prepare("SELECT * FROM room_image WHERE room_id = ?");
$stmt->execute([$roomId]);
$images = $stmt->fetchAll();

// Amenities (IDs only)
$stmt = $pdo->prepare("SELECT amenity_id FROM room_amenity WHERE room_id = ?");
$stmt->execute([$roomId]);
$currentAmenities = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Meals (Map meal_type_id => price)
$stmt = $pdo->prepare("SELECT meal_type_id, price FROM room_meal WHERE room_id = ?");
$stmt->execute([$roomId]);
$currentMeals = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [type_id => price]

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentErrors = [];

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
    $cityId = intval($_POST['city_id'] ?? 0);

    // Selected Amenities & Meals
    $selected_amenities = $_POST['amenities'] ?? [];
    $selected_meals = $_POST['meals'] ?? []; 
    $meal_prices = $_POST['meal_prices'] ?? []; 

    if (!$title || !$price || !$typeId || !$address) {
        $currentErrors[] = 'Title, Price, Room Type, and Address are required.';
    }

    if (!$currentErrors) {
        try {
            $pdo->beginTransaction();

            // Update Room
            $stmt = $pdo->prepare("UPDATE room SET 
                title=?, description=?, price_per_day=?, beds=?, bathrooms=?, maximum_guests=?, room_type_id=?
                WHERE room_id=?");
            $stmt->execute([$title, $description, $price, $beds, $bathrooms, $maxGuests, $typeId, $roomId]);

            // Update Location
            // Check if location exists, else insert (handling edge case)
            if ($l['city_id']) {
                $stmt = $pdo->prepare("UPDATE room_location SET city_id=?, address=?, postal_code=?, google_map_link=? WHERE room_id=?");
                $stmt->execute([$cityId, $address, $postalCode, $googleMapLink, $roomId]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO room_location (room_id, city_id, address, postal_code, google_map_link) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$roomId, $cityId, $address, $postalCode, $googleMapLink]);
            }

            // Update Amenities
            $pdo->prepare("DELETE FROM room_amenity WHERE room_id=?")->execute([$roomId]);
            if (!empty($selected_amenities)) {
                $stmt = $pdo->prepare("INSERT INTO room_amenity (room_id, amenity_id) VALUES (?, ?)");
                foreach ($selected_amenities as $aid) {
                    $stmt->execute([$roomId, intval($aid)]);
                }
            }

            // Update Meals
            $pdo->prepare("DELETE FROM room_meal WHERE room_id=?")->execute([$roomId]);
            $mealOption = $_POST['meal_option'] ?? 'none';
            if ($mealOption === 'available' && !empty($selected_meals)) {
                $stmt = $pdo->prepare("INSERT INTO room_meal (room_id, meal_type_id, price) VALUES (?, ?, ?)");
                foreach ($selected_meals as $mealId) {
                    $mPrice = floatval($meal_prices[$mealId] ?? 0);
                    $stmt->execute([$roomId, intval($mealId), $mPrice]);
                }
            }

            // Handle Image Deletion
            if (isset($_POST['del_img'])) {
                $delStmt = $pdo->prepare("DELETE FROM room_image WHERE image_id = ? AND room_id = ?");
                $getPath = $pdo->prepare("SELECT image_path FROM room_image WHERE image_id = ?");
                foreach ($_POST['del_img'] as $delId) {
                    $getPath->execute([$delId]);
                    $path = $getPath->fetchColumn();
                    if ($path && file_exists(__DIR__ . '/../../../../' . $path)) {
                        unlink(__DIR__ . '/../../../../' . $path);
                    }
                    $delStmt->execute([$delId, $roomId]);
                }
            }

            // Set Primary Image
            if (isset($_POST['set_primary'])) {
                $primId = $_POST['set_primary'];
                $pdo->prepare("UPDATE room_image SET primary_image = 0 WHERE room_id = ?")->execute([$roomId]);
                $pdo->prepare("UPDATE room_image SET primary_image = 1 WHERE image_id = ? AND room_id = ?")->execute([$primId, $roomId]);
            }

            // Upload New Images
             if (!empty($_FILES['new_imgs']['name'][0])) {
                $uploadDir = __DIR__ . '/../../../../public/uploads/rooms/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                
                $validTypes = ['jpg', 'jpeg', 'png', 'webp'];
                $stmtIns = $pdo->prepare("INSERT INTO room_image (room_id, image_path, primary_image) VALUES (?, ?, 0)");

                foreach ($_FILES['new_imgs']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['new_imgs']['error'][$key] === UPLOAD_ERR_OK) {
                        $fileName = $_FILES['new_imgs']['name'][$key];
                        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        
                        if (in_array($fileType, $validTypes)) {
                            $newFileName = 'room_' . uniqid() . '_' . time() . '.' . $fileType;
                            if (move_uploaded_file($tmpName, $uploadDir . $newFileName)) {
                                $stmtIns->execute([$roomId, 'public/uploads/rooms/' . $newFileName]);
                            }
                        }
                    }
                }
            }

            $pdo->commit();
            $_SESSION['_flash']['success'] = "Room updated successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['_flash']['errors'][] = "Database Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['_flash']['errors'] = $currentErrors;
    }
    
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Room - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= app_url('public/profile/profile.css') ?>">
    <link rel="stylesheet" href="../create/room_create.css">
    <style>
        .img-manage-card {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #dee2e6;
            margin-bottom: 10px;
        }
        .img-manage-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }
        .img-actions {
            padding: 8px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<?php require __DIR__ . '/../../../../public/navbar/navbar.php'; ?>

<div class="container py-5 profile-container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark mb-0">Edit Room</h2>
                <a href="<?= app_url('public/room/view/room_view.php?id='.$roomId) ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                    <i class="bi bi-eye"></i> View Live
                </a>
            </div>

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
                                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($room['title']) ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Room Type <span class="text-danger">*</span></label>
                                <select name="type_id" class="form-select" required>
                                    <option value="" disabled>Select Type</option>
                                    <?php foreach ($roomTypes as $type): ?>
                                        <option value="<?= $type['type_id'] ?>" <?= $type['type_id'] == $room['room_type_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type['type_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Daily Price (LKR) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="price" class="form-control" value="<?= $room['price_per_day'] ?>" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($room['description']) ?></textarea>
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
                                <input type="number" name="beds" class="form-control" min="1" value="<?= $room['beds'] ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Bathrooms</label>
                                <input type="number" name="bathrooms" class="form-control" min="1" value="<?= $room['bathrooms'] ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Maximum Guests</label>
                                <input type="number" name="maximum_guests" class="form-control" min="1" value="<?= $room['maximum_guests'] ?>">
                            </div>
                        </div>
                        
                        <label class="form-label mb-3 d-block">Amenities</label>
                        <div class="row g-3 mb-4">
                            <?php foreach ($available_amenities as $amenity): ?>
                            <div class="col-6 col-md-3">
                                <div class="form-check feature-checkbox-card">
                                    <input class="form-check-input" type="checkbox" name="amenities[]" value="<?= $amenity['amenity_id'] ?>" id="check_<?= $amenity['amenity_id'] ?>"
                                        <?= in_array($amenity['amenity_id'], $currentAmenities) ? 'checked' : '' ?>>
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
                                <input class="form-check-input" type="radio" name="meal_option" id="mealsNone" value="none" <?= empty($currentMeals) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="mealsNone">No Meals Provided</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="meal_option" id="mealsAvailable" value="available" <?= !empty($currentMeals) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="mealsAvailable">Meals Available</label>
                            </div>
                        </div>

                        <div id="mealSelectionContainer" class="row g-3" style="display:<?= !empty($currentMeals) ? 'flex' : 'none' ?>;">
                            <div class="col-12"><small class="text-muted">Select available meals and set their price. Check "Free" if included in rent.</small></div>
                            <?php foreach ($meal_types as $meal): 
                                $isChecked = isset($currentMeals[$meal['type_id']]);
                                $price = $isChecked ? $currentMeals[$meal['type_id']] : '';
                                $isFree = ($isChecked && $price == 0);
                            ?>
                            <div class="col-12 col-md-6">
                                <div class="card p-2 border-primary-subtle">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="form-check me-auto">
                                            <input class="form-check-input meal-check" type="checkbox" name="meals[]" value="<?= $meal['type_id'] ?>" id="meal_<?= $meal['type_id'] ?>" <?= $isChecked ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-medium" for="meal_<?= $meal['type_id'] ?>"><?= htmlspecialchars($meal['type_name']) ?></label>
                                        </div>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">LKR</span>
                                        <input type="number" step="0.01" class="form-control meal-price" name="meal_prices[<?= $meal['type_id'] ?>]" id="price_<?= $meal['type_id'] ?>" placeholder="Price" value="<?= $price ?>" <?= $isChecked && !$isFree ? '' : 'disabled' ?> <?= $isFree ? 'readOnly' : '' ?>>
                                        <div class="input-group-text bg-white">
                                            <input class="form-check-input mt-0 me-1 meal-free-check" type="checkbox" value="1" id="free_<?= $meal['type_id'] ?>" <?= $isFree ? 'checked' : '' ?> <?= !$isChecked ? 'disabled' : '' ?>>
                                            <label for="free_<?= $meal['type_id'] ?>" class="small mb-0">Free</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Location & Media -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold" style="color: var(--hunter-green);">Location & Media</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Province <span class="text-danger">*</span></label>
                                <select name="province_id" id="province" class="form-select" required>
                                    <option value="" disabled>Select Province</option>
                                    <?php foreach ($provinces as $province): ?>
                                        <option value="<?= $province['id'] ?>" <?= $province['id'] == $currentProvinceId ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($province['name_en']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">District <span class="text-danger">*</span></label>
                                <select name="district_id" id="district" class="form-select" required></select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City <span class="text-danger">*</span></label>
                                <select name="city_id" id="city" class="form-select" required></select>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($l['address']) ?>" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($l['postal_code']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Google Map Link</label>
                                <input type="url" name="google_map_link" class="form-control" value="<?= htmlspecialchars($l['google_map_link']) ?>">
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="fw-bold mb-3">Existing Images</h6>
                        <div class="row g-3 mb-4">
                            <?php foreach($images as $im): ?>
                            <div class="col-6 col-md-3">
                                <div class="img-manage-card">
                                    <img src="<?= app_url($im['image_path']) ?>" alt="Room Image">
                                    <div class="img-actions">
                                        <div class="form-check mb-1">
                                            <input class="form-check-input" type="checkbox" name="del_img[]" value="<?= $im['image_id'] ?>" id="del_<?= $im['image_id'] ?>">
                                            <label class="form-check-label text-danger small" for="del_<?= $im['image_id'] ?>">Delete</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="set_primary" value="<?= $im['image_id'] ?>" id="prim_<?= $im['image_id'] ?>" <?= $im['primary_image'] ? 'checked' : '' ?>>
                                            <label class="form-check-label small" for="prim_<?= $im['image_id'] ?>">Set Primary</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Add New Images</label>
                            <input type="file" name="new_imgs[]" class="form-control" multiple accept="image/*">
                            <div class="form-text">Upload new images to add to the gallery. (Max 5MB each)</div>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?= app_url('index.php') ?>" class="btn btn-outline-secondary px-5">Cancel</a>
                    <button type="submit" class="btn btn-primary px-5 btn-lg shadow-sm" style="background-color: var(--fern); border-color: var(--fern);">
                        Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script>
// Data
const districts = <?= json_encode($districts) ?>;
const cities = <?= json_encode($cities) ?>;
const currDistId = <?= $currentDistrictId ?>;
const currCityId = <?= $currentCityId ?>;

const elProv = document.getElementById('province');
const elDist = document.getElementById('district');
const elCity = document.getElementById('city');

function loadDistricts(provId, selId = null) {
    elDist.innerHTML = '<option value="" disabled selected>Select District</option>';
    if (!provId) return;
    districts.filter(x => x.province_id == provId).forEach(x => {
        let opt = new Option(x.name_en, x.id);
        if (selId && x.id == selId) opt.selected = true;
        elDist.add(opt);
    });
}

function loadCities(distId, selId = null) {
    elCity.innerHTML = '<option value="" disabled selected>Select City</option>';
    if (!distId) return;
    cities.filter(x => x.district_id == distId).forEach(x => {
        let opt = new Option(x.name_en, x.id);
        if (selId && x.id == selId) opt.selected = true;
        elCity.add(opt);
    });
}

// Initial Load
if (elProv.value) loadDistricts(elProv.value, currDistId);
if (currDistId) loadCities(currDistId, currCityId);

// Events
elProv.addEventListener('change', function() {
    loadDistricts(this.value);
    elCity.innerHTML = '<option value="" disabled selected>Select District First</option>';
});
elDist.addEventListener('change', function() {
    loadCities(this.value);
});

// Meal Logic (Copied from create, adapted for PHP pre-fill)
// The HTML logic above handles disabled states on load via PHP. We just need the togglers.
document.addEventListener('DOMContentLoaded', function() {
    const radioNone = document.getElementById('mealsNone');
    const radioAvailable = document.getElementById('mealsAvailable');
    const container = document.getElementById('mealSelectionContainer');
    
    function toggleMeals() {
        container.style.display = radioAvailable.checked ? 'flex' : 'none';
    }
    radioNone.addEventListener('change', toggleMeals);
    radioAvailable.addEventListener('change', toggleMeals);

    // Checkboxes
    const mealChecks = document.querySelectorAll('.meal-check');
    mealChecks.forEach(check => {
        check.addEventListener('change', function() {
            const id = this.value;
            const priceInput = document.getElementById('price_' + id);
            const freeCheck = document.getElementById('free_' + id);
            
            if (this.checked) {
                // If it was checked on load, freeCheck state is preserved. 
                // Wait, if I uncheck then recheck, I should reset?
                priceInput.disabled = freeCheck.checked;
                freeCheck.disabled = false;
            } else {
                priceInput.disabled = true;
                freeCheck.disabled = true;
                priceInput.value = '';
                freeCheck.checked = false;
                priceInput.readOnly = false;
            }
        });
    });

    // Free Checks
    const freeChecks = document.querySelectorAll('.meal-free-check');
    freeChecks.forEach(check => {
        check.addEventListener('change', function() {
            const id = this.id.split('_')[1];
            const priceInput = document.getElementById('price_' + id);
            if (this.checked) {
                priceInput.value = 0;
                priceInput.readOnly = true;
                priceInput.disabled = false; 
            } else {
                priceInput.readOnly = false;
                priceInput.value = '';
            }
        });
    });
});
</script>
</body>
</html>
