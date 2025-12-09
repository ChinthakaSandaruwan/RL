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

$success = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die("Invalid Token");

    // Update Basic Info
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $sqft = floatval($_POST['sqft']);
    $beds = intval($_POST['bedrooms']);
    $baths = intval($_POST['bathrooms']);
    $ptype = intval($_POST['type_id']);
    
    // Update Location
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

        $stmt = $pdo->prepare("UPDATE property_location SET city_id=?, address=?, postal_code=?, google_map_link=? WHERE property_id=?");
        $stmt->execute([$cityId, $addr, $postal, $gmap, $propId]);

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
                    if (file_exists(__DIR__ . '/../../../../' . $path)) unlink(__DIR__ . '/../../../../' . $path);
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
                        move_uploaded_file($tmp, $uploadDir . $fname);
                        $stmt->execute([$propId, 'public/uploads/properties/' . $fname]);
                    }
                }
            }
        }

        $pdo->commit();
        $success = "Property updated successfully.";
        // Refresh Data
        header("Refresh:0");
    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = "Error: " . $e->getMessage();
    }
}
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Property - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= app_url('public/profile/profile.css') ?>">
    <style>
        .img-edit-card { position: relative; width: 150px; border:1px solid #ddd; padding:5px; border-radius:5px; }
        .img-edit-card img { width: 100%; height: 100px; object-fit: cover; }
        .del-check { position: absolute; top:5px; right:5px; z-index:10; }
        .primary-radio { position: absolute; bottom: 5px; left: 5px; }
    </style>
</head>
<body>
<?php require __DIR__ . '/../../../../public/navbar/navbar.php'; ?>

<div class="container py-5 profile-container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h2 class="mb-4 text-dark fw-bold">Edit Property</h2>
            
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($errors): ?><div class="alert alert-danger"><?= implode('<br>', $errors) ?></div><?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold">Details</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label>Title</label>
                                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($property['title']) ?>" required>
                            </div>
                            <!-- Type, Price, Desc... similar to create -->
                             <div class="col-md-6">
                                <label>Type</label>
                                <select name="type_id" class="form-control">
                                    <?php foreach ($types as $t): ?>
                                    <option value="<?= $t['type_id'] ?>" <?= $t['type_id'] == $property['property_type_id'] ? 'selected' : '' ?>>
                                        <?= $t['type_name'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Price (LKR)</label>
                                <input type="number" name="price" class="form-control" value="<?= htmlspecialchars($property['price_per_month']) ?>" required>
                            </div>
                            <div class="col-md-4"><label>Sqft</label><input type="number" name="sqft" class="form-control" value="<?= $property['sqft'] ?>"></div>
                            <div class="col-md-4"><label>Beds</label><input type="number" name="bedrooms" class="form-control" value="<?= $property['bedrooms'] ?>"></div>
                            <div class="col-md-4"><label>Baths</label><input type="number" name="bathrooms" class="form-control" value="<?= $property['bathrooms'] ?>"></div>
                            <div class="col-12"><label>Description</label><textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($property['description']) ?></textarea></div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold">Location</div>
                    <div class="card-body">
                         <div class="row g-3">
                            <div class="col-md-4">
                                <label>Province</label>
                                <select id="province" class="form-control">
                                    <?php foreach($provinces as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $p['id'] == $currentProvinceId ? 'selected' : '' ?>><?= $p['name_en'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>District</label>
                                <select id="district" class="form-control">
                                     <!-- Populated by JS on load -->
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>City</label>
                                <select name="city_id" id="city" class="form-control">
                                     <!-- Populated by JS on load -->
                                </select>
                            </div>
                            <div class="col-12"><label>Address</label><input type="text" name="address" class="form-control" value="<?= htmlspecialchars($location['address']) ?>"></div>
                             <div class="col-6"><label>Postal</label><input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($location['postal_code']) ?>"></div>
                             <div class="col-6"><label>Map Link</label><input type="text" name="google_map_link" class="form-control" value="<?= htmlspecialchars($location['google_map_link']) ?>"></div>
                         </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold">Images</div>
                    <div class="card-body">
                        <label class="mb-3">Existing Images (Check to Delete, Select Radio for Primary)</label>
                        <div class="d-flex flex-wrap gap-3 mb-3">
                            <?php foreach ($images as $img): ?>
                            <div class="img-edit-card">
                                <img src="<?= app_url($img['image_path']) ?>">
                                <div class="form-check del-check bg-white p-1 rounded border">
                                    <input type="checkbox" name="delete_images[]" value="<?= $img['image_id'] ?>" class="form-check-input bg-danger border-danger">
                                </div>
                                <div class="primary-radio bg-white p-1 rounded border">
                                    <input type="radio" name="set_primary" value="<?= $img['image_id'] ?>" <?= $img['primary_image'] ? 'checked' : '' ?>> Set Main
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <label>Add New Images</label>
                        <input type="file" name="new_images[]" class="form-control" multiple accept="image/*">
                    </div>
                </div>

                 <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold">Amenities</div>
                    <div class="card-body">
                        <div class="row g-2">
                             <?php foreach ($amenities as $a): ?>
                            <div class="col-6 col-md-3">
                                <div class="form-check">
                                    <input type="checkbox" name="amenities[]" value="<?= $a['amenity_id'] ?>" class="form-check-input" <?= in_array($a['amenity_id'], $currentAmenities) ? 'checked' : '' ?>>
                                    <label class="form-check-label"><?= $a['amenity_name'] ?></label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                 </div>

                <div class="d-flex justify-content-end gap-2">
                     <a href="../manage.php" class="btn btn-secondary">Cancel</a>
                     <button type="submit" class="btn btn-primary">Update Changes</button>
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
