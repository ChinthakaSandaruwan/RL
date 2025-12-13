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

$success=null; $errors=[];

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
                    if(file_exists(__DIR__.'/../../../../'.$path)) unlink(__DIR__.'/../../../../'.$path);
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
                     $nm = 'vehicle_'.uniqid().time().'.jpg';
                     move_uploaded_file($tmp, $dir.$nm);
                     $stmt->execute([$vid, 'public/uploads/vehicles/'.$nm]);
                }
            }
        }

        $pdo->commit();
        $success="Vehicle updated!";
        header("Refresh:0");
    } catch(Exception $e) { $pdo->rollBack(); $errors[]=$e->getMessage(); }
}
$csrf = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Update Vehicle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="vehicle_update.css">
</head>
<body>
<?php require __DIR__ . '/../../../../public/navbar/navbar.php'; ?>
<div class="container py-5">
    <h2>Edit Vehicle</h2>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        
        <!-- Basic -->
        <div class="card p-4 mb-3">
            <h5>Basic Details</h5>
            <div class="row g-3">
                <div class="col-12"><label>Title</label><input type="text" name="title" class="form-control" value="<?= htmlspecialchars($vehicle['title']) ?>"></div>
                <div class="col-md-4"><label>Brand</label><select id="brandSel" class="form-control"><option>Select</option>
                    <?php foreach($brands as $b): $curModel = $models[array_search($vehicle['model_id'], array_column($models, 'model_id'))]; $curBrand = $curModel['brand_id'] ?? 0; ?>
                        <option value="<?= $b['brand_id'] ?>" <?= $b['brand_id']==$curBrand?'selected':'' ?>><?= $b['brand_name'] ?></option>
                    <?php endforeach; ?>
                </select></div>
                <div class="col-md-4"><label>Model</label><select name="model_id" id="modelSel" class="form-control"></select></div>
                <div class="col-md-4"><label>Year</label><input type="number" name="year" class="form-control" value="<?= $vehicle['year'] ?>"></div>
                <div class="col-12"><label>Description</label><textarea name="description" class="form-control"><?= $vehicle['description'] ?></textarea></div>
            </div>
        </div>

        <!-- Specs -->
        <div class="card p-4 mb-3">
            <h5>Specifications</h5>
            <div class="row g-3">
                <div class="col-md-3"><label>Type</label><select name="type_id" class="form-control">
                    <?php foreach($types as $t) echo "<option value='{$t['type_id']}' ".($t['type_id']==$vehicle['vehicle_type_id']?'selected':'').">{$t['type_name']}</option>"; ?>
                </select></div>
                <div class="col-md-3"><label>Fuel</label><select name="fuel_id" class="form-control">
                    <?php foreach($fuels as $f) echo "<option value='{$f['type_id']}' ".($f['type_id']==$vehicle['fuel_type_id']?'selected':'').">{$f['type_name']}</option>"; ?>
                </select></div>
                <div class="col-md-3"><label>Transmission</label><select name="trans_id" class="form-control">
                    <?php foreach($transmissions as $tr) echo "<option value='{$tr['type_id']}' ".($tr['type_id']==$vehicle['transmission_type_id']?'selected':'').">{$tr['type_name']}</option>"; ?>
                </select></div>
                <div class="col-md-3"><label>Color</label><select name="color_id" class="form-control">
                    <?php foreach($colors as $c) echo "<option value='{$c['color_id']}' ".($c['color_id']==$vehicle['color_id']?'selected':'').">{$c['color_name']}</option>"; ?>
                </select></div>
                <div class="col-md-4"><label>Seats</label><input type="number" name="seats" class="form-control" value="<?= $vehicle['number_of_seats'] ?>"></div>
                <div class="col-md-4"><label>Mileage (km/L)</label><input type="number" step="0.01" name="mileage" class="form-control" value="<?= $vehicle['mileage'] ?>"></div>
                <div class="col-md-4"><label>License Plate</label><input type="text" name="plate" class="form-control" value="<?= $vehicle['license_plate'] ?>"></div>
            </div>
        </div>

        <!-- Pricing -->
        <div class="card p-4 mb-3">
            <h5>Pricing</h5>
            <div class="row g-3">
                <div class="col-md-3"><label>Type</label><select name="pricing_type_id" class="form-control">
                    <?php foreach($pricingTypes as $pt) echo "<option value='{$pt['type_id']}' ".($pt['type_id']==$vehicle['pricing_type_id']?'selected':'').">{$pt['type_name']}</option>"; ?>
                </select></div>
                <div class="col-md-3"><label>Per Day</label><input type="number" step="0.01" name="price_day" class="form-control" value="<?= $vehicle['price_per_day'] ?>"></div>
                <div class="col-md-3"><label>Per Km</label><input type="number" step="0.01" name="price_km" class="form-control" value="<?= $vehicle['price_per_km'] ?>"></div>
                <div class="col-md-3"><label>Deposit</label><input type="number" step="0.01" name="deposit" class="form-control" value="<?= $vehicle['security_deposit'] ?>"></div>
                <div class="col-12">
                    <div class="form-check"><input type="checkbox" name="driver_available" id="driverChk" class="form-check-input" <?= $vehicle['is_driver_available']?'checked':'' ?>><label for="driverChk">Driver Available</label></div>
                </div>
                <div class="col-md-4"><label>Driver Cost/Day</label><input type="number" step="0.01" name="driver_cost" id="driverCost" class="form-control" value="<?= $vehicle['driver_cost_per_day'] ?>" <?= !$vehicle['is_driver_available']?'disabled':'' ?>></div>
            </div>
        </div>

        <!-- Location -->
        <div class="card p-4 mb-3">
            <h5>Location</h5>
            <div class="row g-3">
                <div class="col-md-4"><label>Province</label><select name="province_id" id="prov" class="form-control">
                    <?php foreach($provinces as $p) echo "<option value='{$p['id']}' ".($p['id']==$pid?'selected':'').">{$p['name_en']}</option>"; ?>
                </select></div>
                <div class="col-md-4"><label>District</label><select name="district_id" id="dist" class="form-control"></select></div>
                <div class="col-md-4"><label>City</label><select name="city_id" id="city" class="form-control"></select></div>
                <div class="col-12"><label>Address</label><input type="text" name="address" class="form-control" value="<?= $l['address'] ?>"></div>
                <div class="col-md-6"><label>Postal</label><input type="text" name="postal" class="form-control" value="<?= $l['postal_code'] ?>"></div>
                <div class="col-md-6"><label>Map</label><input type="text" name="gmap" class="form-control" value="<?= $l['google_map_link'] ?>"></div>

            </div>
        </div>

        <!-- Images -->
        <div class="card p-4 mb-3">
            <h5>Images</h5>
            <div class="mb-3">
                <?php foreach($images as $im): ?>
                <div class="img-card">
                    <img src="<?= app_url($im['image_path']) ?>">
                    <input type="checkbox" name="del_img[]" value="<?= $im['image_id'] ?>" class="del-chk">
                    <div class="primary-radio-label"><input type="radio" name="set_primary" value="<?= $im['image_id'] ?>" <?= $im['primary_image']?'checked':'' ?>> Main</div>
                </div>
                <?php endforeach; ?>
            </div>
            <label>Add New</label>
            <input type="file" name="new_imgs[]" multiple class="form-control">
        </div>

        <button class="btn btn-primary btn-lg">Save Changes</button>
    </form>
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
});
</script>
<script src="vehicle_update.js"></script>
</body>
</html>
