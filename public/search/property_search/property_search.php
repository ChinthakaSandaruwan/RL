<?php
require __DIR__ . '/../../../config/db.php';
ensure_session_started();
$user = current_user();

$pdo = get_pdo();

// --- 1. Fetch Filter Data ---

// Property Types
$stmt = $pdo->query("SELECT * FROM `property_type` ORDER BY `type_name` ASC");
$types = $stmt->fetchAll();

// Provinces
$stmt = $pdo->query("SELECT * FROM `provinces` ORDER BY `name_en` ASC");
$provinces = $stmt->fetchAll();

// Districts & Cities (All for JS)
$stmt = $pdo->query("SELECT * FROM `districts` ORDER BY `name_en` ASC");
$districts = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM `cities` ORDER BY `name_en` ASC");
$cities = $stmt->fetchAll();

// Amenities
$stmt = $pdo->query("SELECT * FROM `amenity` WHERE `category` IN ('property', 'both') ORDER BY `amenity_name` ASC");
$amenities = $stmt->fetchAll();


// --- 2. Process Search Params ---
$filters = [
    'keyword' => $_GET['keyword'] ?? '',
    'type_id' => $_GET['type_id'] ?? '',
    'province_id' => $_GET['province_id'] ?? '',
    'district_id' => $_GET['district_id'] ?? '',
    'city_id' => $_GET['city_id'] ?? '',
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
    'bedrooms' => $_GET['bedrooms'] ?? '',
    'bathrooms' => $_GET['bathrooms'] ?? '',
    'sqft_min' => $_GET['sqft_min'] ?? '',
    'sqft_max' => $_GET['sqft_max'] ?? '',
    'amenities' => $_GET['amenities'] ?? [] // Array
];


// --- 3. Build Query ---
$query = "SELECT DISTINCT p.*, pt.type_name, pl.city_id, c.name_en as city_name, d.name_en as district_name, pr.name_en as province_name,
          (SELECT image_path FROM property_image WHERE property_id = p.property_id AND primary_image = 1 LIMIT 1) as primary_image
          FROM property p 
          LEFT JOIN property_type pt ON p.property_type_id = pt.type_id
          LEFT JOIN property_location pl ON p.property_id = pl.property_id
          LEFT JOIN cities c ON pl.city_id = c.id
          LEFT JOIN districts d ON c.district_id = d.id
          LEFT JOIN provinces pr ON d.province_id = pr.id
          LEFT JOIN property_amenity pa ON p.property_id = pa.property_id
          WHERE p.status_id = 1";

$params = [];

// Keyword
if ($filters['keyword']) {
    $query .= " AND (p.title LIKE ? OR p.description LIKE ? OR pl.address LIKE ?)";
    $k = "%" . $filters['keyword'] . "%";
    $params[] = $k; $params[] = $k; $params[] = $k;
}

// Type
if ($filters['type_id']) {
    $query .= " AND p.property_type_id = ?";
    $params[] = $filters['type_id'];
}

// Location
if ($filters['city_id']) {
    $query .= " AND pl.city_id = ?";
    $params[] = $filters['city_id'];
} elseif ($filters['district_id']) {
    $query .= " AND c.district_id = ?";
    $params[] = $filters['district_id'];
} elseif ($filters['province_id']) {
    $query .= " AND d.province_id = ?";
    $params[] = $filters['province_id'];
}

// Price
if ($filters['min_price']) {
    $query .= " AND p.price_per_month >= ?";
    $params[] = $filters['min_price'];
}
if ($filters['max_price']) {
    $query .= " AND p.price_per_month <= ?";
    $params[] = $filters['max_price'];
}

// Features
if ($filters['bedrooms']) {
    $query .= " AND p.bedrooms >= ?";
    $params[] = $filters['bedrooms'];
}
if ($filters['bathrooms']) {
    $query .= " AND p.bathrooms >= ?";
    $params[] = $filters['bathrooms'];
}
if ($filters['sqft_min']) {
    $query .= " AND p.sqft >= ?";
    $params[] = $filters['sqft_min'];
}
if ($filters['sqft_max']) {
    $query .= " AND p.sqft <= ?";
    $params[] = $filters['sqft_max'];
}

// Amenities (Array of IDs)
if (!empty($filters['amenities']) && is_array($filters['amenities'])) {
    // Logic: Retrieve properties that have ALL selected amenities? Or ANY?
    // "AND p.property_id IN (SELECT property_id FROM property_amenity WHERE amenity_id IN (...))" -> This is ANY.
    // For specific multiple amenities search usually implies "Must have X AND Y".
    // We'll implementing "Must have ALL selected":
    foreach ($filters['amenities'] as $amenityId) {
        $query .= " AND EXISTS (SELECT 1 FROM property_amenity pa_sub WHERE pa_sub.property_id = p.property_id AND pa_sub.amenity_id = ?)";
        $params[] = $amenityId;
    }
}

$query .= " ORDER BY p.created_at DESC";

// Pagination Logic (could act as limit)
// $limit = 12;
// $offset = ... 
// keeping simple for now
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$properties = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Find Properties - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= app_url('public/footer/footer.css') ?>">
    <link rel="stylesheet" href="property_search.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<!-- Header / Hero Mini -->
<div class="search-header bg-dark text-white py-5 mb-5" style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('<?= app_url('public/assets/images/property_bg.jpg') ?>') center/cover;">
    <div class="container text-center">
        <h1 class="fw-bold display-5">Find Your Dream Property</h1>
        <p class="lead text-white-50">Browse thousands of properties for rent across Sri Lanka</p>
    </div>
</div>

<div class="container-fluid px-lg-5 mb-5">
    <div class="row g-4">
        
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="filter-card card border-0 shadow-sm sticky-top" style="top: 100px; z-index: 900;">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-sliders me-2"></i>Filters</h5>
                        <a href="property_search.php" class="small text-danger text-decoration-none">Clear All</a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="property_search.php" method="GET" id="filterForm">
                        
                        <!-- Search Keyword -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">Keyword</label>
                            <input type="text" name="keyword" class="form-control" placeholder="Search by title, address..." value="<?= htmlspecialchars($filters['keyword']) ?>">
                        </div>

                        <!-- Location -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">Location</label>
                            <div class="d-flex flex-column gap-2">
                                <select name="province_id" id="filter_province" class="form-select form-select-sm">
                                    <option value="">All Provinces</option>
                                    <?php foreach ($provinces as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= $filters['province_id'] == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name_en']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="district_id" id="filter_district" class="form-select form-select-sm" <?= empty($filters['province_id']) ? 'disabled' : '' ?>>
                                    <option value="">All Districts</option>
                                    <!-- JS Populates -->
                                </select>
                                <select name="city_id" id="filter_city" class="form-select form-select-sm" <?= empty($filters['district_id']) ? 'disabled' : '' ?>>
                                    <option value="">All Cities</option>
                                    <!-- JS Populates -->
                                </select>
                            </div>
                        </div>

                        <!-- Property Type -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">Property Type</label>
                            <select name="type_id" class="form-select">
                                <option value="">All Types</option>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?= $t['type_id'] ?>" <?= $filters['type_id'] == $t['type_id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['type_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Price Range -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">Price Range (LKR)</label>
                            <div class="input-group input-group-sm mb-2">
                                <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?= htmlspecialchars($filters['min_price']) ?>">
                                <span class="input-group-text">-</span>
                                <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?= htmlspecialchars($filters['max_price']) ?>">
                            </div>
                        </div>

                        <!-- Rooms & Area -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">Specifications</label>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="small text-muted">Bedrooms</label>
                                    <select name="bedrooms" class="form-select form-select-sm">
                                        <option value="">Any</option>
                                        <?php for($i=1; $i<=10; $i++): ?>
                                            <option value="<?= $i ?>" <?= $filters['bedrooms'] == $i ? 'selected' : '' ?>><?= $i ?>+</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted">Bathrooms</label>
                                    <select name="bathrooms" class="form-select form-select-sm">
                                        <option value="">Any</option>
                                        <?php for($i=1; $i<=10; $i++): ?>
                                            <option value="<?= $i ?>" <?= $filters['bathrooms'] == $i ? 'selected' : '' ?>><?= $i ?>+</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="small text-muted">Area (Sqft)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="sqft_min" class="form-control" placeholder="Min" value="<?= htmlspecialchars($filters['sqft_min']) ?>">
                                        <span class="input-group-text">-</span>
                                        <input type="number" name="sqft_max" class="form-control" placeholder="Max" value="<?= htmlspecialchars($filters['sqft_max']) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Amenities -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted mb-2">Amenities</label>
                            <div class="amenities-scroll-box border rounded p-2 bg-light" style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($amenities as $am): ?>
                                    <div class="form-check form-check-sm">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="<?= $am['amenity_id'] ?>" id="am_<?= $am['amenity_id'] ?>" 
                                            <?= in_array($am['amenity_id'], $filters['amenities']) ? 'checked' : '' ?>>
                                        <label class="form-check-label small" for="am_<?= $am['amenity_id'] ?>">
                                            <?= htmlspecialchars($am['amenity_name']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold">Apply Filters</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Search Results -->
        <div class="col-lg-9">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0 text-dark">
                    <span class="text-primary"><?= count($properties) ?></span> Properties Found
                </h4>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm w-auto" form="filterForm" name="sort">
                        <option value="latest">Newest First</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                    </select>
                </div>
            </div>

            <?php if (empty($properties)): ?>
                <div class="text-center py-5">
                    <img src="<?= app_url('public/assets/images/no_results.svg') ?>" alt="No Results" class="mb-3" style="max-width: 200px; opacity: 0.5;"> <!-- Placeholder check -->
                    <h4 class="text-muted">No properties found matching your criteria.</h4>
                    <p class="text-muted">Try adjusting your filters or search terms.</p>
                    <a href="property_search.php" class="btn btn-outline-primary mt-2">Clear Filters</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($properties as $property): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm property-card">
                                <div class="position-relative card-image-wrapper">
                                    <span class="badge bg-success position-absolute top-0 start-0 m-3 z-1"><?= htmlspecialchars($property['type_name']) ?></span>
                                    <img src="<?= $property['primary_image'] ? app_url($property['primary_image']) : 'https://via.placeholder.com/400x250?text=Property' ?>" 
                                         class="card-img-top h-100 object-fit-cover" alt="<?= htmlspecialchars($property['title']) ?>">
                                    <div class="card-overlay">
                                        <a href="<?= app_url('public/property/view/property_view.php?id=' . $property['property_id']) ?>" class="btn btn-light btn-sm fw-bold stretched-link">View Details</a>
                                    </div>
                                </div>
                                <div class="card-body p-3">
                                    <h5 class="card-title text-truncate fw-bold mb-1" title="<?= htmlspecialchars($property['title']) ?>">
                                        <?= htmlspecialchars($property['title']) ?>
                                    </h5>
                                    <p class="small text-muted mb-2 text-truncate">
                                        <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                        <?= htmlspecialchars($property['city_name'] . ', ' . $property['district_name']) ?>
                                    </p>
                                    
                                    <div class="d-flex justify-content-between border-top pt-2 mt-2 features-row">
                                        <span class="small text-muted"><i class="bi bi-door-open me-1"></i> <?= $property['bedrooms'] ?> Bed</span>
                                        <span class="small text-muted"><i class="bi bi-droplet me-1"></i> <?= $property['bathrooms'] ?> Bath</span>
                                        <span class="small text-muted"><i class="bi bi-bounding-box me-1"></i> <?= $property['sqft'] ?> sqft</span>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-top-0 p-3 pt-0 d-flex justify-content-between align-items-center">
                                    <h5 class="text-success fw-bold mb-0">LKR <?= number_format($property['price_per_month']) ?></h5>
                                    <small class="text-muted">/ Month</small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require __DIR__ . '/../../../public/footer/footer.php'; ?>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script>
    // Pass PHP data to JS
    const locationData = {
        districts: <?= json_encode($districts) ?>,
        cities: <?= json_encode($cities) ?>,
        selected: {
            province: '<?= $filters['province_id'] ?>',
            district: '<?= $filters['district_id'] ?>',
            city: '<?= $filters['city_id'] ?>'
        }
    };
</script>
<script src="property_search.js"></script>

</body>
</html>
