<?php
require __DIR__ . '/../../../config/db.php';
ensure_session_started();
$user = current_user();

$pdo = get_pdo();

// Get filter parameters
$searchQuery = trim($_GET['search'] ?? '');
$vehicleType = $_GET['type'] ?? '';
$fuelType = $_GET['fuel'] ?? '';
$transmissionType = $_GET['transmission'] ?? '';
$minPrice = floatval($_GET['min_price'] ?? 0);
$maxPrice = floatval($_GET['max_price'] ?? 0);
$provinceId = $_GET['province_id'] ?? '';
$districtId = $_GET['district_id'] ?? '';
$cityId = $_GET['city_id'] ?? '';
$brandId = $_GET['brand'] ?? '';
$minSeats = $_GET['seats'] ?? '';
$driver = $_GET['driver'] ?? '';
$sortBy = $_GET['sort'] ?? 'newest';

// Fetch vehicle types for filter
$vehicleTypes = $pdo->query("SELECT * FROM vehicle_type ORDER BY type_name ASC")->fetchAll();
$fuelTypes = $pdo->query("SELECT * FROM fuel_type ORDER BY type_name ASC")->fetchAll();
$transmissionTypes = $pdo->query("SELECT * FROM transmission_type ORDER BY type_name ASC")->fetchAll();

// Convert vehicle type name to ID if needed (for navbar links)
$vehicleTypeId = null;
if ($vehicleType) {
    if (is_numeric($vehicleType)) {
        // Already an ID
        $vehicleTypeId = intval($vehicleType);
    } else {
        // It's a type name, find the ID
        foreach ($vehicleTypes as $type) {
            if (strcasecmp($type['type_name'], $vehicleType) === 0) {
                $vehicleTypeId = $type['type_id'];
                break;
            }
        }
    }
}

// Build query
$sql = "
    SELECT v.*, vt.type_name, 
           vi.image_path,
           c.name_en as city_name,
           d.name_en as district_name,
           u.name as owner_name,
           vm.model_name,
           vb.brand_name,
           ft.type_name as fuel_name,
           tt.type_name as transmission_name,
           vc.color_name
    FROM vehicle v
    JOIN vehicle_type vt ON v.vehicle_type_id = vt.type_id
    LEFT JOIN vehicle_image vi ON v.vehicle_id = vi.vehicle_id AND vi.primary_image = 1
    LEFT JOIN vehicle_location vl ON v.vehicle_id = vl.vehicle_id
    LEFT JOIN cities c ON vl.city_id = c.id
    LEFT JOIN districts d ON c.district_id = d.id
    LEFT JOIN user u ON v.owner_id = u.user_id
    LEFT JOIN vehicle_model vm ON v.model_id = vm.model_id
    LEFT JOIN vehicle_brand vb ON vm.brand_id = vb.brand_id
    LEFT JOIN fuel_type ft ON v.fuel_type_id = ft.type_id
    LEFT JOIN transmission_type tt ON v.transmission_type_id = tt.type_id
    LEFT JOIN vehicle_color vc ON v.color_id = vc.color_id
    WHERE v.status_id = 1
";

$params = [];

if ($searchQuery) {
    $sql .= " AND (v.title LIKE ? OR v.description LIKE ? OR c.name_en LIKE ? OR vb.brand_name LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($vehicleTypeId) {
    $sql .= " AND v.vehicle_type_id = ?";
    $params[] = $vehicleTypeId;
}

if ($fuelType) {
    $sql .= " AND v.fuel_type_id = ?";
    $params[] = $fuelType;
}

if ($transmissionType) {
    $sql .= " AND v.transmission_type_id = ?";
    $params[] = $transmissionType;
}

if ($minPrice > 0) {
    $sql .= " AND v.price_per_day >= ?";
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $sql .= " AND v.price_per_day <= ?";
    $params[] = $maxPrice;
}

// Location Filters
if ($cityId) {
    $sql .= " AND vl.city_id = ?";
    $params[] = $cityId;
} elseif ($districtId) {
    $sql .= " AND c.district_id = ?";
    $params[] = $districtId;
} elseif ($provinceId) {
    $sql .= " AND d.province_id = ?";
    $params[] = $provinceId;
}

// Additional Filters
if ($brandId) {
    $sql .= " AND vm.brand_id = ?";
    $params[] = $brandId;
}
if ($minSeats) {
    $sql .= " AND v.number_of_seats >= ?";
    $params[] = $minSeats;
}
if ($driver !== '') {
    $sql .= " AND v.is_driver_available = ?";
    $params[] = $driver;
}

// Sorting
switch ($sortBy) {
    case 'price_low':
        $sql .= " ORDER BY v.price_per_day ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY v.price_per_day DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY v.created_at ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY v.created_at DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

$totalVehicles = count($vehicles);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Vehicles - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="view_all.css">
</head>
<body>

<?php require __DIR__ . '/../../navbar/navbar.php'; ?>

<div class="hero-section">
    <div class="container">
        <h1 class="display-4 fw-bold text-white">Explore Our Vehicle Fleet</h1>
        <p class="lead text-white-50">Find the perfect vehicle for your journey across Sri Lanka</p>
    </div>
</div>

<div class="container py-5">
    <!-- Search & Filter Bar -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" placeholder="Search vehicles, brands, models..." 
                               value="<?= htmlspecialchars($searchQuery) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="type" class="form-select">
                            <option value="">All Vehicle Types</option>
                            <?php foreach ($vehicleTypes as $type): ?>
                            <option value="<?= $type['type_id'] ?>" <?= $vehicleTypeId == $type['type_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['type_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="fuel" class="form-select">
                            <option value="">All Fuel Types</option>
                            <?php foreach ($fuelTypes as $fuel): ?>
                            <option value="<?= $fuel['type_id'] ?>" <?= $fuelType == $fuel['type_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($fuel['type_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <select name="transmission" class="form-select">
                            <option value="">All Transmissions</option>
                            <?php foreach ($transmissionTypes as $trans): ?>
                            <option value="<?= $trans['type_id'] ?>" <?= $transmissionType == $trans['type_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($trans['type_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="min_price" class="form-control" placeholder="Min Price" 
                               value="<?= $minPrice > 0 ? $minPrice : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="max_price" class="form-control" placeholder="Max Price" 
                               value="<?= $maxPrice > 0 ? $maxPrice : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn w-100" style="background-color: var(--fern); border-color: var(--fern); color: white;">
                            <i class="bi bi-search me-1"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0"><?= $totalVehicles ?> Vehicle<?= $totalVehicles != 1 ? 's' : '' ?> Found</h5>
        <div class="d-flex gap-2 align-items-center">
            <label class="small text-muted mb-0">Sort by:</label>
            <select class="form-select form-select-sm" style="width: auto;" onchange="updateSort(this.value)">
                <option value="newest" <?= $sortBy == 'newest' ? 'selected' : '' ?>>Newest First</option>
                <option value="oldest" <?= $sortBy == 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                <option value="price_low" <?= $sortBy == 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_high" <?= $sortBy == 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
            </select>
        </div>
    </div>

    <!-- Vehicles Grid -->
    <?php if (empty($vehicles)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <p class="lead mt-3">No vehicles found matching your criteria.</p>
            <a href="view_all.php" class="btn" style="background-color: var(--fern); border-color: var(--fern); color: white;">Clear Filters</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($vehicles as $vehicle): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card vehicle-card h-100 shadow-sm">
                    <div class="position-relative">
                        <?php if ($vehicle['image_path']): ?>
                        <img src="<?= app_url($vehicle['image_path']) ?>" class="card-img-top vehicle-image" 
                             alt="<?= htmlspecialchars($vehicle['title']) ?>">
                        <?php else: ?>
                        <div class="card-img-top vehicle-image bg-secondary d-flex align-items-center justify-content-center">
                            <i class="bi bi-car-front text-white" style="font-size: 3rem;"></i>
                        </div>
                        <?php endif; ?>
                        <div class="vehicle-badge">
                            <span class="badge" style="background-color: var(--fern);"><?= htmlspecialchars($vehicle['type_name']) ?></span>
                        </div>
                        <?php if ($vehicle['is_driver_available']): ?>
                        <div class="driver-badge">
                            <span class="badge bg-info"><i class="bi bi-person-check"></i> Driver Available</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= htmlspecialchars($vehicle['title']) ?></h5>
                        <p class="text-muted small mb-2">
                            <i class="bi bi-geo-alt"></i> 
                            <?= htmlspecialchars($vehicle['city_name'] ?? 'Location not specified') ?>, 
                            <?= htmlspecialchars($vehicle['district_name'] ?? '') ?>
                        </p>
                        <div class="vehicle-specs mb-3">
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-fuel-pump"></i> <?= htmlspecialchars($vehicle['fuel_name'] ?? 'N/A') ?>
                            </span>
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-gear"></i> <?= htmlspecialchars($vehicle['transmission_name'] ?? 'N/A') ?>
                            </span>
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-people"></i> <?= $vehicle['number_of_seats'] ?> Seats
                            </span>
                            <?php if ($vehicle['year']): ?>
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-calendar"></i> <?= $vehicle['year'] ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <div>
                                <?php if ($vehicle['pricing_type_id'] == 2): ?>
                                    <span class="h4 mb-0 fw-bold" style="color: var(--fern);">LKR <?= number_format($vehicle['price_per_km'], 2) ?></span>
                                    <small class="text-muted">/km</small>
                                <?php else: ?>
                                    <span class="h4 mb-0 fw-bold" style="color: var(--fern);">LKR <?= number_format($vehicle['price_per_day'], 2) ?></span>
                                    <small class="text-muted">/day</small>
                                <?php endif; ?>
                            </div>
                            <a href="<?= app_url('public/vehicle/view/vehicle_view.php?id=' . $vehicle['vehicle_id']) ?>" 
                               class="btn btn-sm" style="background-color: var(--fern); border-color: var(--fern); color: white;">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../footer/footer.php'; ?>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="view_all.js"></script>
</body>
</html>
