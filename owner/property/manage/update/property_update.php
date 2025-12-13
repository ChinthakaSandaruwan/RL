<?php
require __DIR__ . '/../../../../config/db.php';
ensure_session_started();
$user = current_user();

if (!$user || $user['role_id'] != 3) { header('Location: ' . app_url('auth/login')); exit; }

$propId = $_GET['id'] ?? null;
if (!$propId) { header('Location: ../manage.php'); exit; }

$pdo = get_pdo();

// Fetch Property & Verify Owner
$stmt = $pdo->prepare("SELECT * FROM property WHERE property_id = ? AND owner_id = ?");
$stmt->execute([$propId, $user['user_id']]);
$property = $stmt->fetch();

if (!$property) { header('Location: ../manage.php'); exit; }

// Fetch Related Data
$loc = $pdo->prepare("SELECT * FROM property_location WHERE property_id = ?");
$loc->execute([$propId]);
$location = $loc->fetch();

$imgs = $pdo->prepare("SELECT * FROM property_image WHERE property_id = ?");
$imgs->execute([$propId]);
$images = $imgs->fetchAll();

$ams = $pdo->prepare("SELECT amenity_id FROM property_amenity WHERE property_id = ?");
$ams->execute([$propId]);
$currentAmenities = $ams->fetchAll(PDO::FETCH_COLUMN);

// Master Data
$types = $pdo->query("SELECT * FROM property_type ORDER BY type_name")->fetchAll();
$provinces = $pdo->query("SELECT * FROM provinces ORDER BY name_en")->fetchAll();
$districts = $pdo->query("SELECT * FROM districts ORDER BY name_en")->fetchAll();
$cities = $pdo->query("SELECT * FROM cities ORDER BY name_en")->fetchAll();
$amenities = $pdo->query("SELECT * FROM amenity WHERE category IN ('property','both') ORDER BY amenity_name")->fetchAll();

// Determine Province/District from City ID
$currentCityId = $location['city_id'];
$currentDistrictId = 0;
$currentProvinceId = 0;

foreach ($cities as $c) {
    if ($c['id'] == $currentCityId) {
        $currentDistrictId = $c['district_id'];
        break;
    }
}
foreach ($districts as $d) {
    if ($d['id'] == $currentDistrictId) {
        $currentProvinceId = $d['province_id'];
        break;
    }
}

$success = $_SESSION['_flash']['success'] ?? null;
$errors = $_SESSION['_flash']['errors'] ?? [];
unset($_SESSION['_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentErrors = [];

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
         die("Invalid Token");
    }

    // Update Basic Info
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $sqft = floatval($_POST['sqft']);
    $beds = intval($_POST['bedrooms']);
    $baths = intval($_POST['bathrooms']);
    $ptype = intval($_POST['type_id']);
    
    // Update Location
    $provinceId = intval($_POST['province_id'] ?? 0);
    $districtId = intval($_POST['district_id'] ?? 0);
    $cityId = intval($_POST['city_id']);
    $addr = trim($_POST['address']);
    $postal = trim($_POST['postal_code']);
    $gmap = trim($_POST['google_map_link']);

    // Amenities
    $selAms = $_POST['amenities'] ?? [];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE property SET title=?, description=?, price_per_month=?, sqft=?, bedrooms=?, bathrooms=?, property_type_id=? WHERE property_id=?");
        $stmt->execute([$title, $desc, $price, $sqft, $beds, $baths, $ptype, $propId]);

        $stmt = $pdo->prepare("UPDATE property_location SET province_id=?, district_id=?, city_id=?, address=?, postal_code=?, google_map_link=? WHERE property_id=?");
        $stmt->execute([$provinceId, $districtId, $cityId, $addr, $postal, $gmap, $propId]);

        // Update Amenities
        $pdo->prepare("DELETE FROM property_amenity WHERE property_id=?")->execute([$propId]);
        if ($selAms) {
            $stmt = $pdo->prepare("INSERT INTO property_amenity (property_id, amenity_id) VALUES (?, ?)");
            foreach ($selAms as $aid) $stmt->execute([$propId, $aid]);
        }

        // Handle Image Deletion
        $delImgs = $_POST['delete_images'] ?? [];
        if ($delImgs) {
            $stmt = $pdo->prepare("SELECT image_path FROM property_image WHERE image_id = ? AND property_id = ?");
            foreach ($delImgs as $imgId) {
                // Verify image belongs to property
                $stmt->execute([$imgId, $propId]);
                $path = $stmt->fetchColumn();
                if ($path) {
                    $pdo->prepare("DELETE FROM property_image WHERE image_id=?")->execute([$imgId]);
                    // Security: Use basename
                    $safePath = 'public/uploads/properties/' . basename($path);
                    $fullPath = __DIR__ . '/../../../../' . $safePath;
                    if (file_exists($fullPath)) unlink($fullPath);
                }
            }
        }

        // Handle Primary Image Change
        $newPrimary = $_POST['set_primary'] ?? null;
        if ($newPrimary) {
            $pdo->prepare("UPDATE property_image SET primary_image=0 WHERE property_id=?")->execute([$propId]);
            $pdo->prepare("UPDATE property_image SET primary_image=1 WHERE image_id=? AND property_id=?")->execute([$newPrimary, $propId]);
        }

        // Handle New Images
        if (!empty($_FILES['new_images']['name'][0])) {
            $uploadDir = __DIR__ . '/../../../../public/uploads/properties/';
            $valid = ['jpg', 'jpeg', 'png', 'webp'];
            $stmt = $pdo->prepare("INSERT INTO property_image (property_id, image_path, primary_image) VALUES (?, ?, 0)");
            
            foreach ($_FILES['new_images']['tmp_name'] as $k => $tmp) {
                if ($_FILES['new_images']['error'][$k] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['new_images']['name'][$k], PATHINFO_EXTENSION));
                    if (in_array($ext, $valid)) {
                        $fname = 'prop_' . uniqid() . '_' . time() . '.' . $ext;
                        
                        // Security Check
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mime = $finfo->file($tmp);
                        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

                        if (in_array($mime, $allowed)) {
                            move_uploaded_file($tmp, $uploadDir . $fname);
                            $stmt->execute([$propId, 'public/uploads/properties/' . $fname]);
                        } else {
                            $currentErrors[] = "Invalid file type detected.";
                        }
                    }
                }
            }
        }

        if (empty($currentErrors)) {
            $pdo->commit();
            $_SESSION['_flash']['success'] = "Property updated successfully.";
        } else {
            $pdo->rollBack();
            $_SESSION['_flash']['errors'] = $currentErrors;
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['_flash']['errors'][] = "Error: " . $e->getMessage();
    }
    
    // Redirect (PRG)
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Property - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= app_url('public/profile/profile.css') ?>">
    <link rel="stylesheet" href="../create/property_create.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .img-manage-card {
            position: relative;
            border-radius: 0.5rem;
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
                <h2 class="fw-bold text-dark mb-0">Edit Property</h2>
               <a href="<?= app_url('public/property/view/property_view.php?id='.$propId) ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                    <i class="bi bi-eye"></i> View Live
                </a>
            </div>
            
            <?php if ($success): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: '<?= addslashes($success) ?>',
                        confirmButtonColor: 'var(--fern)',
                        timer: 2000
                    });
                });
            </script>
            <?php endif; ?>
            <?php if ($errors): ?><div class="alert alert-danger shadow-sm"><?= implode('<br>', $errors) ?></div><?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <!-- Basic Info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 fw-bold">Basic Information</div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Property Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($property['title']) ?>" required>
                            </div>
                             <div class="col-md-6">
                                <label class="form-label">Property Type <span class="text-danger">*</span></label>
                                <select name="type_id" class="form-select" required>
                                    <?php foreach ($types as $t): ?>
                                    <option value="<?= $t['type_id'] ?>" <?= $t['type_id'] == $property['property_type_id'] ? 'selected' : '' ?>>
                                        <?= $t['type_name'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Monthly Price (LKR) <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-control" value="<?= htmlspecialchars($property['price_per_month']) ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($property['description']) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 fw-bold">Features & Amenities</div>
                    <div class="card-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-md-4"><label class="form-label">Bedrooms</label><input type="number" name="bedrooms" class="form-control" value="<?= $property['bedrooms'] ?>"></div>
                            <div class="col-md-4"><label class="form-label">Bathrooms</label><input type="number" name="bathrooms" class="form-control" value="<?= $property['bathrooms'] ?>"></div>
                            <div class="col-md-4"><label class="form-label">Area (Sqft)</label><input type="number" name="sqft" class="form-control" value="<?= $property['sqft'] ?>"></div>
                        </div>
                        <label class="form-label mb-2">Amenities</label>
                        <div class="row g-3">
                             <?php foreach ($amenities as $a): ?>
                            <div class="col-6 col-md-3">
                                <div class="form-check amenity-card">
                                    <input type="checkbox" name="amenities[]" value="<?= $a['amenity_id'] ?>" id="am_<?= $a['amenity_id'] ?>" class="form-check-input" <?= in_array($a['amenity_id'], $currentAmenities) ? 'checked' : '' ?>>
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
                    <div class="card-body p-4">
                         <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Province</label>
                                <select name="province_id" id="province" class="form-select">
                                    <?php foreach($provinces as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $p['id'] == $currentProvinceId ? 'selected' : '' ?>><?= $p['name_en'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">District</label>
                                <select name="district_id" id="district" class="form-select"></select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City <span class="text-danger">*</span></label>
                                <select name="city_id" id="city" class="form-select" required></select>
                            </div>
                        </div>
                         <div class="mb-3">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($location['address']) ?>" required>
                        </div>
                        <div class="row g-3">
                             <div class="col-md-6"><label class="form-label">Postal Code</label><input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($location['postal_code']) ?>"></div>
                             <div class="col-md-6"><label class="form-label">Google Maps Link</label><input type="text" name="google_map_link" class="form-control" value="<?= htmlspecialchars($location['google_map_link']) ?>"></div>
                         </div>
                    </div>
                </div>

                <!-- Images -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 fw-bold">Photos</div>
                    <div class="card-body p-4">
                        <label class="mb-3 fw-bold">Existing Images</label>
                        <div class="row g-3 mb-4">
                            <?php foreach ($images as $img): ?>
                            <div class="col-6 col-md-3">
                                <div class="img-manage-card">
                                    <img src="<?= app_url($img['image_path']) ?>">
                                    <div class="img-actions">
                                        <div class="form-check mb-1">
                                            <input type="checkbox" name="delete_images[]" value="<?= $img['image_id'] ?>" id="del_<?= $img['image_id'] ?>" class="form-check-input">
                                            <label class="form-check-label text-danger small" for="del_<?= $img['image_id'] ?>">Delete</label>
                                        </div>
                                        <div class="form-check">
                                            <input type="radio" name="set_primary" value="<?= $img['image_id'] ?>" id="prim_<?= $img['image_id'] ?>" <?= $img['primary_image'] ? 'checked' : '' ?> class="form-check-input">
                                            <label class="form-check-label small" for="prim_<?= $img['image_id'] ?>">Set Cover</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <label class="form-label">Add New Images</label>
                        <input type="file" name="new_images[]" class="form-control" multiple accept="image/*">
                        <div class="form-text">Upload new images to add to gallery.</div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                     <a href="../manage.php" class="btn btn-outline-secondary px-5">Cancel</a>
                     <button type="submit" class="btn btn-primary px-5 btn-lg shadow-sm" style="background-color: var(--fern); border-color: var(--fern);">Update Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
const districts = <?= json_encode($districts) ?>;
const cities = <?= json_encode($cities) ?>;
const provSel = document.getElementById('province');
const distSel = document.getElementById('district');
const citySel = document.getElementById('city');

const currDist = <?= $currentDistrictId ?>;
const currCity = <?= $currentCityId ?>;

function popDist(pid, selId = null) {
    distSel.innerHTML = '<option value="">Select District</option>';
    if(!pid) return;
    districts.filter(d => d.province_id == pid).forEach(d => {
        let opt = new Option(d.name_en, d.id);
        if(selId && d.id == selId) opt.selected = true;
        distSel.add(opt);
    });
}

function popCity(did, selId = null) {
    citySel.innerHTML = '<option value="">Select City</option>';
    if(!did) return;
    cities.filter(c => c.district_id == did).forEach(c => {
        let opt = new Option(c.name_en, c.id);
        if(selId && c.id == selId) opt.selected = true;
        citySel.add(opt);
    });
}

// Initial Load
popDist(provSel.value, currDist);
popCity(currDist, currCity);

provSel.addEventListener('change', function() { popDist(this.value); citySel.innerHTML='<option>Select</option>'; });
distSel.addEventListener('change', function() { popCity(this.value); });

</script>
<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
