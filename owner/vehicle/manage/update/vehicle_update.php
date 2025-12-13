<?php
require __DIR__ . '/../../../../config/db.php';
ensure_session_started();
$user = current_user();

if (!$user || $user['role_id'] != 3) { header('Location: ../../../auth/login'); exit; }
$vid = $_GET['id'] ?? 0;
if (!$vid) { header('Location: ../manage.php'); exit; }

$pdo = get_pdo();

// Fetch Vehicle Data
$v = $pdo->prepare("SELECT * FROM vehicle WHERE vehicle_id = ? AND owner_id = ?");
$v->execute([$vid, $user['user_id']]); $vehicle = $v->fetch();
if (!$vehicle) die("Access Denied");

$loc = $pdo->prepare("SELECT * FROM vehicle_location WHERE vehicle_id = ?"); $loc->execute([$vid]); $l = $loc->fetch();
$imgs = $pdo->prepare("SELECT * FROM vehicle_image WHERE vehicle_id = ?"); $imgs->execute([$vid]); $images = $imgs->fetchAll();

// Master Data
$types = $pdo->query("SELECT * FROM vehicle_type")->fetchAll();
$brands = $pdo->query("SELECT * FROM vehicle_brand")->fetchAll();
$models = $pdo->query("SELECT * FROM vehicle_model")->fetchAll();
$fuels = $pdo->query("SELECT * FROM fuel_type")->fetchAll();
$transmissions = $pdo->query("SELECT * FROM transmission_type")->fetchAll();
$colors = $pdo->query("SELECT * FROM vehicle_color")->fetchAll();
$pricingTypes = $pdo->query("SELECT * FROM pricing_type")->fetchAll();
$provinces = $pdo->query("SELECT * FROM provinces")->fetchAll();
$districts = $pdo->query("SELECT * FROM districts")->fetchAll();
$cities = $pdo->query("SELECT * FROM cities")->fetchAll();

// Location logic
if ($l) {
    $cid = $l['city_id'] ?? 0;
    $did = $l['district_id'] ?? 0;
    $pid = $l['province_id'] ?? 0;
} else {
    $cid = 0; $did = 0; $pid = 0;
    $l = ['address'=>'', 'postal_code'=>'', 'google_map_link'=>''];
}

$success = $_SESSION['_flash']['success'] ?? null;
$errors = $_SESSION['_flash']['errors'] ?? [];
unset($_SESSION['_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die("CSRF");

    $title = trim($_POST['title']); $desc = $_POST['description']; $year = $_POST['year'];
    $modelId = $_POST['model_id']; $typeId = $_POST['type_id']; $fuelId = $_POST['fuel_id'];
    $transId = $_POST['trans_id']; $colorId = $_POST['color_id']; $seats = $_POST['seats'];
    $mileage = $_POST['mileage']; $pricingTypeId = $_POST['pricing_type_id'];
    $priceDay = $_POST['price_day']; $priceKm = $_POST['price_km']; $deposit = $_POST['deposit'];
    $plate = trim($_POST['plate']); $driverAvail = isset($_POST['driver_available']) ? 1 : 0;
    $driverCost = $_POST['driver_cost'] ?? 0;
    
    $addr = $_POST['address']; $postal = $_POST['postal']; $gmap = $_POST['gmap'];
    $provinceId = $_POST['province_id'] ?? 0;
    $districtId = $_POST['district_id'] ?? 0;
    $cityId = $_POST['city_id'] ?? 0;

    try {
        $pdo->beginTransaction();

        $pdo->prepare("UPDATE vehicle SET title=?, description=?, model_id=?, year=?, vehicle_type_id=?, fuel_type_id=?, transmission_type_id=?, color_id=?, number_of_seats=?, mileage=?, pricing_type_id=?, price_per_day=?, price_per_km=?, security_deposit=?, license_plate=?, is_driver_available=?, driver_cost_per_day=? WHERE vehicle_id=?")
            ->execute([$title, $desc, $modelId, $year, $typeId, $fuelId, $transId, $colorId, $seats, $mileage, $pricingTypeId, $priceDay, $priceKm, $deposit, $plate, $driverAvail, $driverCost, $vid]);
        
        $pdo->prepare("UPDATE vehicle_location SET province_id=?, district_id=?, city_id=?, address=?, postal_code=?, google_map_link=? WHERE vehicle_id=?")
            ->execute([$provinceId, $districtId, $cityId, $addr, $postal, $gmap, $vid]);

        // Image Delete
        if (isset($_POST['del_img'])) {
            $stmt = $pdo->prepare("SELECT image_path FROM vehicle_image WHERE image_id=? AND vehicle_id=?");
            foreach($_POST['del_img'] as $iid) {
                $stmt->execute([$iid, $vid]); $path=$stmt->fetchColumn();
                if($path) {
                    $pdo->prepare("DELETE FROM vehicle_image WHERE image_id=?")->execute([$iid]);
                    // Security: Use basename to prevent directory traversal
                    $safePath = 'public/uploads/vehicles/' . basename($path);
                    $fullPath = __DIR__.'/../../../../' . $safePath;
                    if(file_exists($fullPath)) unlink($fullPath);
                }
            }
        }
        // Primary
        if (isset($_POST['set_primary'])) {
            $pdo->prepare("UPDATE vehicle_image SET primary_image=0 WHERE vehicle_id=?")->execute([$vid]);
            $pdo->prepare("UPDATE vehicle_image SET primary_image=1 WHERE image_id=?")->execute([$_POST['set_primary']]);
        }
        // New Images
        if (!empty($_FILES['new_imgs']['name'][0])) {
            $dir = __DIR__.'/../../../../public/uploads/vehicles/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $stmt=$pdo->prepare("INSERT INTO vehicle_image(vehicle_id,image_path,primary_image) VALUES(?,?,0)");
            foreach($_FILES['new_imgs']['tmp_name'] as $k=>$tmp) {
                if($_FILES['new_imgs']['error'][$k]===0) {
                     // Security Check
                     $finfo = new finfo(FILEINFO_MIME_TYPE);
                     $mime = $finfo->file($tmp);
                     $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                     
                     if(in_array($mime, $allowed)) {
                         $nm = 'vehicle_'.uniqid().time().'.jpg';
                         move_uploaded_file($tmp, $dir.$nm);
                         $stmt->execute([$vid, 'public/uploads/vehicles/'.$nm]);
                     } else {
                         // $errors[] = "Invalid file type detected for one or more files.";
                         // For now, silently skip or we can add to errors, but we need to handle transaction
                     }
                }
            }
        }

        $pdo->commit();
        $_SESSION['_flash']['success'] = "Vehicle updated!";
    } catch(Exception $e) { 
        $pdo->rollBack(); 
        $_SESSION['_flash']['errors'][] = $e->getMessage(); 
    }
    
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
$csrf = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Vehicle - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= app_url('public/profile/profile.css') ?>">
    <link rel="stylesheet" href="../create/vehicle_create.css"> <!-- Re-use create styles -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <h2 class="fw-bold text-dark mb-0">Edit Vehicle</h2>
                <a href="<?= app_url('public/vehicle/view/vehicle_view.php?id='.$vid) ?>" class="btn btn-outline-primary btn-sm" target="_blank">
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
            
            <?php if ($errors): ?>
                <div class="alert alert-danger shadow-sm">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?><li><?= $err ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                
                <!-- Basic Info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold" style="color: var(--hunter-green);">Basic Details</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="form-label">Vehicle Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($vehicle['title']) ?>" required>
                            </div>
                            
                            <!-- Hidden inputs for cascading JS to pick up initial values -->
                            <!-- Note: JS reads PHP vars directly, this structure matches Create for user consistency -->
                            
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($vehicle['description']) ?></textarea>
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
                                <label class="form-label">Brand <span class="text-danger">*</span></label>
                                <select id="brandSel" class="form-select" required>
                                    <option value="">Select Brand</option>
                                    <?php 
                                    // Helper to find brand of current model
                                    $curBrandId = 0;
                                    foreach($models as $m) { if($m['model_id'] == $vehicle['model_id']) { $curBrandId = $m['brand_id']; break; } }
                                    foreach($brands as $b): ?>
                                        <option value="<?= $b['brand_id'] ?>" <?= $b['brand_id'] == $curBrandId ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($b['brand_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Model <span class="text-danger">*</span></label>
                                <select name="model_id" id="modelSel" class="form-select" required></select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Year <span class="text-danger">*</span></label>
                                <input type="number" name="year" class="form-control" value="<?= $vehicle['year'] ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                                <select name="type_id" class="form-select">
                                    <?php foreach($types as $t): ?>
                                        <option value="<?= $t['type_id'] ?>" <?= $t['type_id'] == $vehicle['vehicle_type_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['type_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Fuel Type <span class="text-danger">*</span></label>
                                <select name="fuel_id" class="form-select">
                                    <?php foreach($fuels as $f): ?>
                                        <option value="<?= $f['type_id'] ?>" <?= $f['type_id'] == $vehicle['fuel_type_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($f['type_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Transmission <span class="text-danger">*</span></label>
                                <select name="trans_id" class="form-select">
                                    <?php foreach($transmissions as $tr): ?>
                                        <option value="<?= $tr['type_id'] ?>" <?= $tr['type_id'] == $vehicle['transmission_type_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tr['type_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Color <span class="text-danger">*</span></label>
                                <select name="color_id" class="form-select">
                                    <?php foreach($colors as $c): ?>
                                        <option value="<?= $c['color_id'] ?>" <?= $c['color_id'] == $vehicle['color_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['color_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Number of Seats</label>
                                <input type="number" name="seats" class="form-control" value="<?= $vehicle['number_of_seats'] ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">License Plate</label>
                                <input type="text" name="plate" class="form-control" value="<?= htmlspecialchars($vehicle['license_plate']) ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Mileage (km/L)</label>
                                <input type="number" step="0.01" name="mileage" class="form-control" value="<?= $vehicle['mileage'] ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold" style="color: var(--hunter-green);">Pricing</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pricing Type</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="pricing_type_id" id="priceType1" value="1" <?= $vehicle['pricing_type_id'] == 1 ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-success" for="priceType1">Daily</label>

                                    <input type="radio" class="btn-check" name="pricing_type_id" id="priceType2" value="2" <?= $vehicle['pricing_type_id'] == 2 ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-success" for="priceType2">Per KM</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Security Deposit (LKR)</label>
                                <input type="number" step="0.01" name="deposit" class="form-control" value="<?= $vehicle['security_deposit'] ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <div id="dailyPriceContainer" style="<?= $vehicle['pricing_type_id'] == 1 ? '' : 'display:none;' ?>">
                                    <label class="form-label">Price Per Day (LKR) *</label>
                                    <input type="number" step="0.01" name="price_day" class="form-control" value="<?= $vehicle['price_per_day'] ?>">
                                </div>
                                <div id="kmPriceContainer" style="<?= $vehicle['pricing_type_id'] == 2 ? '' : 'display:none;' ?>">
                                    <label class="form-label">Price Per KM (LKR) *</label>
                                    <input type="number" step="0.01" name="price_km" class="form-control" value="<?= $vehicle['price_per_km'] ?>">
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <div class="form-check p-3 border rounded bg-light">
                                    <input class="form-check-input" type="checkbox" name="driver_available" id="driverChk" <?= $vehicle['is_driver_available'] ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="driverChk">Driver Available</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Driver Cost Per Day (LKR)</label>
                                <input type="number" step="0.01" name="driver_cost" id="driverCost" class="form-control" value="<?= $vehicle['driver_cost_per_day'] ?>" <?= !$vehicle['is_driver_available'] ? 'disabled' : '' ?>>
                            </div>
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
                                <label class="form-label">Province</label>
                                <select name="province_id" id="prov" class="form-select">
                                    <?php foreach($provinces as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= $p['id'] == $pid ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['name_en']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">District</label>
                                <select name="district_id" id="dist" class="form-select"></select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City</label>
                                <select name="city_id" id="city" class="form-select"></select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Pickup Address</label>
                                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($l['address']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal" class="form-control" value="<?= htmlspecialchars($l['postal_code']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Google Map Link</label>
                                <input type="url" name="gmap" class="form-control" value="<?= htmlspecialchars($l['google_map_link']) ?>">
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="fw-bold mb-3">Existing Images</h6>
                        <div class="row g-3 mb-4">
                            <?php foreach($images as $im): ?>
                            <div class="col-6 col-md-3">
                                <div class="img-manage-card">
                                    <img src="<?= app_url($im['image_path']) ?>" alt="Vehicle Image">
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data from PHP
    const models = <?= json_encode($models) ?>;
    const districts = <?= json_encode($districts) ?>;
    const cities = <?= json_encode($cities) ?>;

    // Current Values
    let currentModelId = <?= $vehicle['model_id'] ?>;
    let currentProvinceId = <?= $pid ?>;
    let currentDistrictId = <?= $did ?>;
    let currentCityId = <?= $cid ?>;

    // --- Brand & Model Logic ---
    const brandSel = document.getElementById('brandSel');
    const modelSel = document.getElementById('modelSel');

    function populateModels(brandId, selectedModelId = null) {
        modelSel.innerHTML = '<option value="">Select Model</option>';
        if (brandId) {
            const filteredModels = models.filter(m => m.brand_id == brandId);
            filteredModels.forEach(m => {
                const option = new Option(m.model_name, m.model_id);
                if (m.model_id == selectedModelId) option.selected = true;
                modelSel.add(option);
            });
        }
    }

    brandSel.addEventListener('change', function() {
        populateModels(this.value);
    });

    // Initial Population
    if (brandSel.value) {
        populateModels(brandSel.value, currentModelId);
    }


    // --- Location Logic ---
    const provSel = document.getElementById('prov');
    const distSel = document.getElementById('dist');
    const citySel = document.getElementById('city');

    function populateDistricts(provId, selectedDistId = null) {
        distSel.innerHTML = '<option value="">Select District</option>';
        citySel.innerHTML = '<option value="">Select City</option>'; // Reset city too
        
        if (provId) {
            const filteredDistricts = districts.filter(d => d.province_id == provId);
            filteredDistricts.forEach(d => {
                const option = new Option(d.name_en, d.id);
                if (d.id == selectedDistId) option.selected = true;
                distSel.add(option);
            });
        }
    }

    function populateCities(distId, selectedCityId = null) {
        citySel.innerHTML = '<option value="">Select City</option>';
        if (distId) {
            const filteredCities = cities.filter(c => c.district_id == distId);
            filteredCities.forEach(c => {
                const option = new Option(c.name_en, c.id);
                if (c.id == selectedCityId) option.selected = true;
                citySel.add(option);
            });
        }
    }

    provSel.addEventListener('change', function() {
        populateDistricts(this.value);
    });

    distSel.addEventListener('change', function() {
        populateCities(this.value);
    });

    // Initial Location Population
    if (currentProvinceId) {
        populateDistricts(currentProvinceId, currentDistrictId);
        if (currentDistrictId) {
            populateCities(currentDistrictId, currentCityId);
        }
    }

    // --- Driver Toggle ---
    const driverChk = document.getElementById('driverChk');
    const driverCost = document.getElementById('driverCost');
    if (driverChk) {
        driverChk.addEventListener('change', function() {
            driverCost.disabled = !this.checked;
        });
    }

    // --- Pricing Logic ---
    const p1 = document.getElementById('priceType1');
    const p2 = document.getElementById('priceType2');
    const c1 = document.getElementById('dailyPriceContainer');
    const c2 = document.getElementById('kmPriceContainer');
    const inpDay = document.querySelector('input[name="price_day"]');
    const inpKm = document.querySelector('input[name="price_km"]');

    function togglePrice() {
        if (p1.checked) {
            c1.style.display = 'block';
            c2.style.display = 'none';
            if(inpKm) inpKm.value = ''; // Clean inactive
        } else {
            c1.style.display = 'none';
            c2.style.display = 'block';
            if(inpDay) inpDay.value = ''; // Clean inactive
        }
    }
    if (p1 && p2) {
        p1.addEventListener('change', togglePrice);
        p2.addEventListener('change', togglePrice);
    }
});
</script>
<script src="vehicle_update.js"></script>
</body>
</html>
