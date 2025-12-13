<?php
require __DIR__ . '/../../../config/db.php';
ensure_session_started();
$user = current_user();

$pdo = get_pdo();

// Get filter parameters
$searchQuery = trim($_GET['search'] ?? '');
$propertyType = $_GET['type'] ?? '';
$minBedrooms = intval($_GET['min_bedrooms'] ?? 0);
$minBathrooms = intval($_GET['min_bathrooms'] ?? 0);
$minPrice = floatval($_GET['min_price'] ?? 0);
$maxPrice = floatval($_GET['max_price'] ?? 0);
$provinceId = $_GET['province_id'] ?? '';
$districtId = $_GET['district_id'] ?? '';
$cityId = $_GET['city_id'] ?? '';
$minSqft = $_GET['sqft_min'] ?? '';
$maxSqft = $_GET['sqft_max'] ?? '';
$amenities = $_GET['amenities'] ?? [];
$sortBy = $_GET['sort'] ?? 'newest';

// Fetch property types for filter
$propertyTypes = $pdo->query("SELECT * FROM property_type ORDER BY type_name ASC")->fetchAll();

// Convert property type name to ID if needed (for navbar links)
$propertyTypeId = null;
if ($propertyType) {
    if (is_numeric($propertyType)) {
        // Already an ID
        $propertyTypeId = intval($propertyType);
    } else {
        // It's a type name, find the ID
        foreach ($propertyTypes as $type) {
            if (strcasecmp($type['type_name'], $propertyType) === 0) {
                $propertyTypeId = $type['type_id'];
                break;
            }
        }
    }
}

// Build query (compatible with ONLY_FULL_GROUP_BY)
$sql = "
    SELECT 
        p.*, 
        pt.type_name, 
        pi.image_path,
        c.name_en AS city_name,
        d.name_en AS district_name,
        u.name AS owner_name,
        (
            SELECT COUNT(DISTINCT pa.amenity_id)
            FROM property_amenity pa
            WHERE pa.property_id = p.property_id
        ) AS amenity_count
    FROM property p
    JOIN property_type pt ON p.property_type_id = pt.type_id
    LEFT JOIN property_image pi ON p.property_id = pi.property_id AND pi.primary_image = 1
    LEFT JOIN property_location pl ON p.property_id = pl.property_id
    LEFT JOIN cities c ON pl.city_id = c.id
    LEFT JOIN districts d ON c.district_id = d.id
    LEFT JOIN user u ON p.owner_id = u.user_id
    WHERE p.status_id = 1
";

$params = [];

if ($searchQuery) {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ? OR c.name_en LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($propertyTypeId) {
    $sql .= " AND p.property_type_id = ?";
    $params[] = $propertyTypeId;
}

if ($minBedrooms > 0) {
    $sql .= " AND p.bedrooms >= ?";
    $params[] = $minBedrooms;
}

if ($minBathrooms > 0) {
    $sql .= " AND p.bathrooms >= ?";
    $params[] = $minBathrooms;
}

if ($minPrice > 0) {
    $sql .= " AND p.rent_per_month >= ?";
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $sql .= " AND p.rent_per_month <= ?";
    $params[] = $maxPrice;
}

// Location Filters
if ($cityId) {
    $sql .= " AND pl.city_id = ?";
    $params[] = $cityId;
} elseif ($districtId) {
    $sql .= " AND c.district_id = ?";
    $params[] = $districtId;
} elseif ($provinceId) {
    $sql .= " AND d.province_id = ?";
    $params[] = $provinceId;
}

// Area Filters
if ($minSqft) {
    $sql .= " AND p.square_feet >= ?";
    $params[] = $minSqft;
}
if ($maxSqft) {
    $sql .= " AND p.square_feet <= ?";
    $params[] = $maxSqft;
}

// Amenities Filter
if (!empty($amenities) && is_array($amenities)) {
    foreach ($amenities as $amenityId) {
        $sql .= " AND EXISTS (SELECT 1 FROM property_amenity pa_filter WHERE pa_filter.property_id = p.property_id AND pa_filter.amenity_id = ?)";
        $params[] = $amenityId;
    }
}

// Sort logic
switch ($sortBy) {
    case 'price_low':
        $sql .= " ORDER BY p.rent_per_month ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY p.rent_per_month DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY p.created_at ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY p.created_at DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();

$totalProperties = count($properties);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Properties - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="view_all.css">
</head>
<body>

<?php require __DIR__ . '/../../navbar/navbar.php'; ?>

<div class="hero-section">
    <div class="container">
        <h1 class="display-4 fw-bold text-white">Discover Your Dream Property</h1>
        <p class="lead text-white-50">Find the perfect home for rent across Sri Lanka</p>
    </div>
</div>

<div class="container py-5">
    <!-- Search & Filter Bar -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" placeholder="Search properties..." 
                               value="<?= htmlspecialchars($searchQuery) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="type" class="form-select">
                            <option value="">All Property Types</option>
                            <?php foreach ($propertyTypes as $type): ?>
                            <option value="<?= $type['type_id'] ?>" <?= $propertyTypeId == $type['type_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['type_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="min_bedrooms" class="form-select">
                            <option value="">Any Bedrooms</option>
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= $minBedrooms == $i ? 'selected' : '' ?>><?= $i ?>+ Bedroom<?= $i > 1 ? 's' : '' ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <select name="min_bathrooms" class="form-select">
                            <option value="">Any Bathrooms</option>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?= $i ?>" <?= $minBathrooms == $i ? 'selected' : '' ?>><?= $i ?>+ Bathroom<?= $i > 1 ? 's' : '' ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="min_price" class="form-control" placeholder="Min Monthly Rent" 
                               value="<?= $minPrice > 0 ? $minPrice : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="max_price" class="form-control" placeholder="Max Monthly Rent" 
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
        <h5 class="mb-0"><?= $totalProperties ?> Propert<?= $totalProperties != 1 ? 'ies' : 'y' ?> Found</h5>
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

    <!-- Properties Grid -->
    <?php if (empty($properties)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <p class="lead mt-3">No properties found matching your criteria.</p>
            <a href="view_all.php" class="btn" style="background-color: var(--fern); border-color: var(--fern); color: white;">Clear Filters</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($properties as $property): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card property-card h-100 shadow-sm">
                    <div class="position-relative">
                        <?php if ($property['image_path']): ?>
                        <img src="<?= app_url($property['image_path']) ?>" class="card-img-top property-image" 
                             alt="<?= htmlspecialchars($property['title']) ?>">
                        <?php else: ?>
                        <div class="card-img-top property-image bg-secondary d-flex align-items-center justify-content-center">
                            <i class="bi bi-house text-white" style="font-size: 3rem;"></i>
                        </div>
                        <?php endif; ?>
                        <div class="property-badge">
                            <span class="badge" style="background-color: var(--fern);"><?= htmlspecialchars($property['type_name']) ?></span>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= htmlspecialchars($property['title']) ?></h5>
                        <p class="text-muted small mb-2">
                            <i class="bi bi-geo-alt"></i> 
                            <?= htmlspecialchars($property['city_name'] ?? 'Location not specified') ?>, 
                            <?= htmlspecialchars($property['district_name'] ?? '') ?>
                        </p>
                        <p class="card-text text-muted small flex-grow-1">
                            <?= htmlspecialchars(substr($property['description'], 0, 100)) ?>...
                        </p>
                        <div class="property-features mb-3">
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-door-closed"></i> <?= $property['bedrooms'] ?> Bed<?= $property['bedrooms'] > 1 ? 's' : '' ?>
                            </span>
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-droplet"></i> <?= $property['bathrooms'] ?> Bath<?= $property['bathrooms'] > 1 ? 's' : '' ?>
                            </span>
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-arrows-fullscreen"></i> <?= number_format($property['square_feet']) ?> sqft
                            </span>
                            <?php if ($property['amenity_count'] > 0): ?>
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-star"></i> <?= $property['amenity_count'] ?> Amenities
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="h4 mb-0 fw-bold" style="color: var(--fern);">LKR <?= number_format($property['rent_per_month'], 2) ?></span>
                                <small class="text-muted">/month</small>
                            </div>
                            <a href="<?= app_url('public/property/view/property_view.php?id=' . $property['property_id']) ?>" 
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
