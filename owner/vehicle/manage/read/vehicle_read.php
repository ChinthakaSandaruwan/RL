<?php
require __DIR__ . '/../../../../config/db.php';
ensure_session_started();
$user = current_user();

if (!$user || $user['role_id'] != 3) { header('Location: ../../../auth/login'); exit; }
$vid = $_GET['id'] ?? 0;
if (!$vid) { header('Location: ../manage.php'); exit; }

$pdo = get_pdo();

// Fetch Vehicle Details
$stmt = $pdo->prepare("
    SELECT v.*, vt.type_name, ft.type_name as fuel_type, tt.type_name as transmission, 
           vc.color_name, vm.model_name, vb.brand_name, pt.type_name as pricing_type_name,
           c.name_en as city, d.name_en as district, pr.name_en as province,
           vl.address, vl.google_map_link
    FROM vehicle v
    JOIN vehicle_type vt ON v.vehicle_type_id = vt.type_id
    JOIN fuel_type ft ON v.fuel_type_id = ft.type_id
    JOIN transmission_type tt ON v.transmission_type_id = tt.type_id
    JOIN vehicle_color vc ON v.color_id = vc.color_id
    JOIN vehicle_model vm ON v.model_id = vm.model_id
    JOIN vehicle_brand vb ON vm.brand_id = vb.brand_id
    JOIN pricing_type pt ON v.pricing_type_id = pt.type_id
    JOIN vehicle_location vl ON v.vehicle_id = vl.vehicle_id
    JOIN cities c ON vl.city_id = c.id
    JOIN districts d ON c.district_id = d.id
    JOIN provinces pr ON d.province_id = pr.id
    WHERE v.vehicle_id = ? AND v.owner_id = ?
");
$stmt->execute([$vid, $user['user_id']]);
$vehicle = $stmt->fetch();
if (!$vehicle) die("Vehicle not found or access denied.");

// Images
$imgs = $pdo->prepare("SELECT * FROM vehicle_image WHERE vehicle_id = ? ORDER BY primary_image DESC");
$imgs->execute([$vid]); $images = $imgs->fetchAll();

$st = [1=>'Available', 2=>'Rented', 3=>'Inactive', 4=>'Pending'];
$stClass = [1=>'success', 2=>'secondary', 3=>'warning', 4=>'info'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title><?= htmlspecialchars($vehicle['title']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="vehicle_read.css">
</head>
<body>
<?php require __DIR__ . '/../../../../public/navbar/navbar.php'; ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="../manage.php">My Vehicles</a></li><li class="breadcrumb-item active">Details</li></ol></nav>
        <a href="../update/vehicle_update.php?id=<?= $vid ?>" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Edit Vehicle</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4 overflow-hidden gallery-card">
                <div class="main-image-container">
                    <?php if ($images): ?>
                    <img src="<?= app_url($images[0]['image_path']) ?>" id="mainImage" class="object-fit-cover w-100 h-100">
                    <?php else: ?><div class="p-5 text-center bg-light">No Images</div><?php endif; ?>
                    <span class="badge bg-<?= $stClass[$vehicle['status_id']] ?> position-absolute top-0 end-0 m-3 p-2"><?= $st[$vehicle['status_id']] ?></span>
                </div>
                <?php if (count($images)>1): ?>
                <div class="d-flex gap-2 p-2 overflow-auto">
                    <?php foreach ($images as $im): ?><img src="<?= app_url($im['image_path']) ?>" class="gallery-thumb cursor-pointer" onclick="document.getElementById('mainImage').src=this.src"><?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="card border-0 shadow-sm mb-4 p-4">
                <h3><?= htmlspecialchars($vehicle['title']) ?></h3>
                <div class="text-muted mb-3">
                    <i class="bi bi-geo-alt"></i> <?= $vehicle['city'] ?>, <?= $vehicle['district'] ?> &bull; 
                    <?= $vehicle['brand_name'] ?> <?= $vehicle['model_name'] ?> (<?= $vehicle['year'] ?>)
                </div>
                <p class="text-secondary"><?= nl2br(htmlspecialchars($vehicle['description'])) ?></p>
                
                <hr>
                <h5>Vehicle Specifications</h5>
                <div class="row g-3">
                    <div class="col-6 col-md-4"><i class="bi bi-speedometer2 text-primary"></i> <strong>Type:</strong> <?= $vehicle['type_name'] ?></div>
                    <div class="col-6 col-md-4"><i class="bi bi-fuel-pump text-primary"></i> <strong>Fuel:</strong> <?= $vehicle['fuel_type'] ?></div>
                    <div class="col-6 col-md-4"><i class="bi bi-gear text-primary"></i> <strong>Transmission:</strong> <?= $vehicle['transmission'] ?></div>
                    <div class="col-6 col-md-4"><i class="bi bi-palette text-primary"></i> <strong>Color:</strong> <?= $vehicle['color_name'] ?></div>
                    <div class="col-6 col-md-4"><i class="bi bi-people text-primary"></i> <strong>Seats:</strong> <?= $vehicle['number_of_seats'] ?></div>
                    <div class="col-6 col-md-4"><i class="bi bi-speedometer text-primary"></i> <strong>Mileage:</strong> <?= $vehicle['mileage'] ?? 'N/A' ?> km/L</div>
                    <?php if ($vehicle['license_plate']): ?>
                    <div class="col-12"><i class="bi bi-123 text-primary"></i> <strong>License Plate:</strong> <span class="badge bg-dark"><?= htmlspecialchars($vehicle['license_plate']) ?></span></div>
                    <?php endif; ?>
                </div>

                <?php if ($vehicle['is_driver_available']): ?>
                <hr>
                <div class="alert alert-info">
                    <i class="bi bi-person-check-fill"></i> <strong>Driver Available:</strong> LKR <?= number_format($vehicle['driver_cost_per_day'], 2) ?> per day
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-3 p-4">
                <small class="text-uppercase fw-bold">Pricing (<?= $vehicle['pricing_type_name'] ?>)</small>
                <h2 class="text-success mb-2">LKR <?= number_format($vehicle['price_per_day'], 2) ?></h2>
                <small class="text-muted">Per Day</small>
                <?php if ($vehicle['price_per_km'] > 0): ?>
                <hr>
                <small class="text-muted">Per Km: <strong>LKR <?= number_format($vehicle['price_per_km'], 2) ?></strong></small>
                <?php endif; ?>
                <?php if ($vehicle['security_deposit'] > 0): ?>
                <hr>
                <small class="text-muted">Security Deposit: <strong>LKR <?= number_format($vehicle['security_deposit'], 2) ?></strong></small>
                <?php endif; ?>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white fw-bold">Vehicle Info</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between"><span>Brand</span> <strong><?= $vehicle['brand_name'] ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Model</span> <strong><?= $vehicle['model_name'] ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Year</span> <strong><?= $vehicle['year'] ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Code</span> <small class="badge bg-light text-dark"><?= $vehicle['vehicle_code'] ?></small></li>
                </ul>
            </div>

            <div class="card shadow-sm border-0 p-4">
                <h5>Location</h5>
                <p><?= htmlspecialchars($vehicle['address']) ?><br><?= $vehicle['city'] ?>, <?= $vehicle['district'] ?></p>
                <?php if ($vehicle['google_map_link']): ?><a href="<?= $vehicle['google_map_link'] ?>" target="_blank" class="btn btn-outline-secondary w-100">View Map</a><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="vehicle_read.js"></script>
</body>
</html>
