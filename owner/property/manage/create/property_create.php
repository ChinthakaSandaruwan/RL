<?php
require __DIR__ . '/../../../../config/db.php';

ensure_session_started();
$user = current_user();

// Check if user is Owner
if (!$user || !in_array($user['role_id'], [3])) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();
$errors = [];
$success = null;
$csrf_token = generate_csrf_token();

// Check Quota
$packageCheck = check_owner_package_quota($user['user_id'], 'property');
if (!$packageCheck['success']) {
    $_SESSION['package_required_message'] = $packageCheck['message'];
    header('Location: ' . $packageCheck['redirect_url']);
    exit;
}

// Fetch Master Data
$types = $pdo->query("SELECT * FROM `property_type` ORDER BY `type_name` ASC")->fetchAll();
$provinces = $pdo->query("SELECT * FROM `provinces` ORDER BY `name_en` ASC")->fetchAll();
$districts = $pdo->query("SELECT * FROM `districts` ORDER BY `name_en` ASC")->fetchAll();
$cities = $pdo->query("SELECT * FROM `cities` ORDER BY `name_en` ASC")->fetchAll();
$amenities = $pdo->query("SELECT * FROM `amenity` WHERE `category` IN ('property', 'both') ORDER BY `amenity_name` ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF Token');
    }

    // Input Sanitization
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $typeId = intval($_POST['type_id'] ?? 0);
    $sqft = floatval($_POST['sqft'] ?? 0);
    $beds = intval($_POST['bedrooms'] ?? 0);
    $baths = intval($_POST['bathrooms'] ?? 0);
    
    // Location
    $cityId = intval($_POST['city_id'] ?? 0);
    $address = trim($_POST['address'] ?? '');
    $postal = trim($_POST['postal_code'] ?? '');
    $gmap = trim($_POST['google_map_link'] ?? '');
    
    // Checkboxes
    $selectedAmenities = $_POST['amenities'] ?? [];

    // Validation
    if (!$title || !$price || !$typeId || !$cityId || !$address) {
        $errors[] = "Please fill in all required fields.";
    }

    // Image Upload
    $uploadedImages = [];
    if (!empty($_FILES['property_images']['name'][0])) {
        $count = count($_FILES['property_images']['name']);
        if ($count < 3) $errors[] = "Please upload at least 3 images.";
        
        if (!$errors) {
            $uploadDir = __DIR__ . '/../../../../public/uploads/properties/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $validTypes = ['jpg', 'jpeg', 'png', 'webp'];
            
            foreach ($_FILES['property_images']['tmp_name'] as $k => $tmp) {
                if ($_FILES['property_images']['error'][$k] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['property_images']['name'][$k], PATHINFO_EXTENSION));
                    if (!in_array($ext, $validTypes)) {
                        $errors[] = "Invalid format. JPG, PNG, WEBP only.";
                        break; 
                    }
                    
                    $newName = 'prop_' . uniqid() . '_' . time() . '.' . $ext;
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
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 4)"); // 4=Pending
            $stmt->execute([$propCode, $user['user_id'], $typeId, $title, $description, $price, $sqft, $beds, $baths]);
            $propId = $pdo->lastInsertId();

            // 2. Insert Location
            $stmt = $pdo->prepare("INSERT INTO `property_location` (`property_id`, `city_id`, `address`, `postal_code`, `google_map_link`) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$propId, $cityId, $address, $postal, $gmap]);

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

            // 5. Decrement Quota
            decrement_package_quota($packageCheck['package_id'], 'property');

            $pdo->commit();
            $success = "Property submitted successfully and is pending approval.";
            
            // Optional: Redirect to manage
            // header("Location: ../manage.php"); exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "System Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Property - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= app_url('public/profile/profile.css') ?>"> 
    <!-- Inline minimal CSS for specific form elements -->
    <style>
        .amenity-card {
            position: relative;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 10px;
            cursor: pointer;
            transition: 0.2s;
        }
        .amenity-card:hover { background: #f9f9f9; border-color: #ddd; }
        .form-check-input:checked + label { color: var(--fern); font-weight: bold; }
        .img-preview { width: 100px; height: 100px; object-fit: cover; border-radius: 4px; margin-right: 10px; cursor: pointer; border: 2px solid transparent;}
        .img-preview.selected-main { border-color: var(--fern); }
    </style>
</head>
<body>
<?php require __DIR__ . '/../../../../public/navbar/navbar.php'; ?>

<div class="container py-5 profile-container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h2 class="mb-4 fw-bold text-dark">Add New Property</h2>

            <?php if (isset($packageCheck) && $packageCheck['success']): ?>
                <div class="alert alert-info shadow-sm d-flex align-items-center mb-4">
                    <i class="bi bi-briefcase-fill me-3 fs-3 text-primary"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Active Package: <?= htmlspecialchars($packageCheck['package_name']) ?></h6>
                        <p class="mb-0 small">You have <strong><?= $packageCheck['remaining'] ?></strong> property listing(s) remaining.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?> <a href="../manage.php" class="fw-bold">Go to Dashboard</a></div>
            <?php endif; ?>
            
            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="propertyForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <!-- Basic Info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 fw-bold">Basic Information</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Property Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="Modern Apartment in City Center" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Property Type <span class="text-danger">*</span></label>
                                <select name="type_id" class="form-control" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($types as $t): ?>
                                    <option value="<?= $t['type_id'] ?>"><?= $t['type_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Monthly Price (LKR) <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"></textarea>
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
                                <input type="number" name="bedrooms" class="form-control" value="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Bathrooms</label>
                                <input type="number" name="bathrooms" class="form-control" value="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Area (Sqft)</label>
                                <input type="number" name="sqft" class="form-control" placeholder="e.g. 1200">
                            </div>
                        </div>
                        <label class="form-label mb-2">Amenities</label>
                        <div class="row g-3">
                            <?php foreach ($amenities as $a): ?>
                            <div class="col-6 col-md-3">
                                <div class="form-check amenity-card">
                                    <input class="form-check-input" type="checkbox" name="amenities[]" value="<?= $a['amenity_id'] ?>" id="am_<?= $a['amenity_id'] ?>">
                                    <label class="form-check-label w-100 stretched-link" for="am_<?= $a['amenity_id'] ?>"><?= $a['amenity_name'] ?></label>
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
                                <select id="province" class="form-control">
                                    <option value="">Select</option>
                                    <?php foreach ($provinces as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= $p['name_en'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">District</label>
                                <select id="district" class="form-control" disabled><option>Select Province First</option></select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City <span class="text-danger">*</span></label>
                                <select name="city_id" id="city" class="form-control" disabled required><option>Select District First</option></select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" required placeholder="Full Address">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal_code" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Google Maps Link</label>
                                <input type="text" name="google_map_link" class="form-control" placeholder="Share Link from Google Maps">
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
                    <a href="../manage.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="background: var(--fern); border-color: var(--fern);">Submit Property</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Data passing
    const districts = <?= json_encode($districts) ?>;
    const cities = <?= json_encode($cities) ?>;

    // Location Logic
    const provSel = document.getElementById('province');
    const distSel = document.getElementById('district');
    const citySel = document.getElementById('city');

    provSel.addEventListener('change', function() {
        const pid = this.value;
        distSel.innerHTML = '<option value="">Select District</option>';
        citySel.innerHTML = '<option value="">Select District First</option>';
        citySel.disabled = true;
        
        if (pid) {
            const fil = districts.filter(d => d.province_id == pid);
            fil.forEach(d => distSel.add(new Option(d.name_en, d.id)));
            distSel.disabled = false;
        } else {
            distSel.disabled = true;
        }
    });

    distSel.addEventListener('change', function() {
        const did = this.value;
        citySel.innerHTML = '<option value="">Select City</option>';
        if (did) {
            const fil = cities.filter(c => c.district_id == did);
            fil.forEach(c => citySel.add(new Option(c.name_en, c.id)));
            citySel.disabled = false;
        } else {
            citySel.disabled = true;
        }
    });

    // Image Preview Logic
    const imgInput = document.getElementById('imgInput');
    const previewArea = document.getElementById('previewArea');
    const primaryInput = document.getElementById('primaryIdx');

    imgInput.addEventListener('change', function(e) {
        previewArea.innerHTML = '';
        if (this.files) {
            Array.from(this.files).forEach((file, idx) => {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    const img = document.createElement('img');
                    img.src = ev.target.result;
                    img.className = 'img-preview' + (idx === 0 ? ' selected-main' : '');
                    img.onclick = () => {
                        document.querySelectorAll('.img-preview').forEach(el => el.classList.remove('selected-main'));
                        img.classList.add('selected-main');
                        primaryInput.value = idx;
                    };
                    previewArea.appendChild(img);
                }
                reader.readAsDataURL(file);
            });
        }
    });
</script>
<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
