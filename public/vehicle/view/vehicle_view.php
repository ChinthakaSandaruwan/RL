<?php
require_once __DIR__ . '/../../../config/db.php';
ensure_session_started();

$vehicle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($vehicle_id <= 0) {
    header("Location: " . app_url());
    exit;
}

$pdo = get_pdo();

// Fetch details
$stmt = $pdo->prepare("
    SELECT 
        v.*, 
        vt.type_name,
        vb.brand_name, vm.model_name, vc.color_name,
        ft.type_name as fuel_type, tt.type_name as transmission,
        vl.address, vl.google_map_link, vl.postal_code,
        c.name_en as city_name, d.name_en as district_name, pr.name_en as province_name,
        u.name as owner_name, u.email as owner_email, u.mobile_number as owner_phone, u.profile_image as owner_image
    FROM vehicle v
    LEFT JOIN vehicle_type vt ON v.vehicle_type_id = vt.type_id
    LEFT JOIN vehicle_model vm ON v.model_id = vm.model_id
    LEFT JOIN vehicle_brand vb ON vm.brand_id = vb.brand_id
    LEFT JOIN vehicle_color vc ON v.color_id = vc.color_id
    LEFT JOIN fuel_type ft ON v.fuel_type_id = ft.type_id
    LEFT JOIN transmission_type tt ON v.transmission_type_id = tt.type_id
    LEFT JOIN vehicle_location vl ON v.vehicle_id = vl.vehicle_id
    LEFT JOIN cities c ON vl.city_id = c.id
    LEFT JOIN districts d ON c.district_id = d.id
    LEFT JOIN provinces pr ON d.province_id = pr.id
    LEFT JOIN user u ON v.owner_id = u.user_id
    WHERE v.vehicle_id = ?
");
$stmt->execute([$vehicle_id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    header("Location: " . app_url());
    exit;
}

// Fetch images
$stmt_img = $pdo->prepare("SELECT * FROM vehicle_image WHERE vehicle_id = ? ORDER BY primary_image DESC");
$stmt_img->execute([$vehicle_id]);
$images = $stmt_img->fetchAll();

// Fallback image
if (empty($images)) {
    $images[] = ['image_path' => 'public/assets/images/placeholder-vehicle.jpg', 'primary_image' => 1];
}

$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($vehicle['model_name'] ? $vehicle['brand_name'] . ' ' . $vehicle['model_name'] : $vehicle['title']) ?> - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= app_url('public/favicon/android-chrome-192x192.png') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= app_url('public/favicon/android-chrome-512x512.png') ?>">
    <link rel="shortcut icon" href="<?= app_url('public/favicon/favicon.ico') ?>">
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">
    
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="vehicle_view.css">
</head>
<body class="bg-light">

<?php require_once __DIR__ . '/../../navbar/navbar.php'; ?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= app_url() ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= app_url('public/vehicle/view_all.php') ?>">Vehicles</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($vehicle['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-8">
            
            <!-- Image Gallery -->
            <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                <div id="vehicleCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($images as $index => $img): ?>
                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                            <img src="<?= app_url($img['image_path']) ?>" class="d-block w-100 main-img" alt="Vehicle Image" onerror="this.src='https://via.placeholder.com/800x500?text=No+Image'">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($images) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#vehicleCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#vehicleCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex gap-2 p-2 overflow-auto bg-white">
                    <?php foreach ($images as $index => $img): ?>
                        <img src="<?= app_url($img['image_path']) ?>" 
                             class="img-thumbnail thumb-img vehicle-thumb <?= $index === 0 ? 'active-thumb' : '' ?>" 
                             onclick="showSlide(<?= $index ?>)"
                             alt="Thumbnail"
                             onerror="this.src='https://via.placeholder.com/100x80?text=No+Image'">
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Overview -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                        <div>
                            <span class="badge badge-theme mb-2"><?= htmlspecialchars($vehicle['type_name']) ?></span>
                            <h1 class="h3 fw-bold text-dark mb-1"><?= htmlspecialchars($vehicle['title']) ?></h1>
                            <p class="text-muted mb-0">
                                <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                <?= htmlspecialchars(implode(', ', array_filter([$vehicle['address'], $vehicle['city_name'], $vehicle['district_name']]))) ?>
                            </p>
                            <div class="mt-2 text-primary fw-bold">
                                <?= htmlspecialchars($vehicle['brand_name'] ?? '') ?> <?= htmlspecialchars($vehicle['model_name'] ?? '') ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <?php if ($vehicle['pricing_type_id'] == 2): ?>
                                <h2 class="h3 fw-bold text-theme mb-0">LKR <?= number_format($vehicle['price_per_km'], 2) ?>/km</h2>
                            <?php else: ?>
                                <h2 class="h3 fw-bold text-theme mb-0">LKR <?= number_format($vehicle['price_per_day'], 2) ?>/day</h2>
                            <?php endif; ?>
                            <?php if($vehicle['is_driver_available']): ?>
                                <div class="badge bg-info mt-1 d-block">+ Driver Available</div>
                                <div class="badge bg-secondary mt-1 d-block">Driver Cost Per Day: LKR <?= number_format($vehicle['driver_cost_per_day']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr>

                    <!-- Features -->
                    <div class="row g-3 text-center mb-4">
                        <div class="col-6 col-sm-3">
                            <div class="feature-box">
                                <i class="bi bi-gear feature-icon"></i>
                                <div class="fw-bold"><?= $vehicle['transmission'] ?></div>
                                <small class="text-muted">Transmission</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="feature-box">
                                <i class="bi bi-fuel-pump feature-icon"></i>
                                <div class="fw-bold"><?= $vehicle['fuel_type'] ?></div>
                                <small class="text-muted">Fuel</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="feature-box">
                                <i class="bi bi-people feature-icon"></i>
                                <div class="fw-bold"><?= $vehicle['number_of_seats'] ?></div>
                                <small class="text-muted">Seats</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="feature-box">
                                <i class="bi bi-palette feature-icon"></i>
                                <div class="fw-bold"><?= htmlspecialchars($vehicle['color_name']) ?></div>
                                <small class="text-muted">Color</small>
                            </div>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3">Description</h5>
                    <p class="text-secondary lh-lg mb-4">
                        <?= nl2br(htmlspecialchars($vehicle['description'])) ?>
                    </p>

                    <h5 class="fw-bold mb-3">Vehicle Details</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Mileage</span>
                                    <span class="fw-bold"><?= number_format($vehicle['mileage']) ?> km</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Color</span>
                                    <span class="fw-bold"><?= htmlspecialchars($vehicle['color_name']) ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <?php if (!empty($vehicle['google_map_link'])): ?>
                        <h5 class="fw-bold mb-3">Location</h5>
                        <div class="bg-light p-4 rounded text-center">
                            <a href="<?= htmlspecialchars($vehicle['google_map_link']) ?>" target="_blank" class="btn btn-outline-theme">
                                <i class="bi bi-geo-alt me-2"></i>View on Google Maps
                            </a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Owner Card (Status Hidden) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Interested?</h5>
                    <div class="d-flex align-items-center mb-4">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-person-fill text-muted fs-3"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Verified Owner</h6>
                            <small class="text-muted">Contact via platform</small>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <?php if (isset($user) && $user): ?>
                            <a href="<?= app_url('public/rent/rent_vehicle/rent_vehicle.php?id=' . $vehicle['vehicle_id']) ?>" class="btn btn-theme btn-lg">
                                <i class="bi bi-calendar-check me-2"></i> Rent Request
                            </a>
                        <?php else: ?>
                            <a href="<?= app_url('auth/login/index.php') ?>" class="btn btn-theme btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Login to Rent
                            </a>
                        <?php endif; ?>

                         <div class="alert alert-info small mb-0 mt-2">
                            <i class="bi bi-info-circle me-1"></i> Owner details will be shared after rental approval.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="vehicle_view.js"></script>
</body>
</html>
