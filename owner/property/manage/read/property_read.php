<?php
require __DIR__ . '/../../../../config/db.php';
ensure_session_started();
$user = current_user();

// Check Role (Owner = 3)
if (!$user || !in_array($user['role_id'], [3])) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$propId = $_GET['id'] ?? null;
if (!$propId) {
    header('Location: ../manage.php');
    exit;
}

$pdo = get_pdo();

// 1. Fetch Property Details
$stmt = $pdo->prepare("
    SELECT p.*, pt.type_name, 
           c.name_en as city_name, d.name_en as district_name, pr.name_en as province_name,
           pl.address, pl.postal_code, pl.google_map_link
    FROM property p
    JOIN property_type pt ON p.property_type_id = pt.type_id
    JOIN property_location pl ON p.property_id = pl.property_id
    JOIN cities c ON pl.city_id = c.id
    JOIN districts d ON c.district_id = d.id
    JOIN provinces pr ON d.province_id = pr.id
    WHERE p.property_id = ? AND p.owner_id = ?
");
$stmt->execute([$propId, $user['user_id']]);
$property = $stmt->fetch();

if (!$property) {
    die("Property not found or access denied.");
}

// 2. Fetch Images
$stmt = $pdo->prepare("SELECT * FROM property_image WHERE property_id = ? ORDER BY primary_image DESC");
$stmt->execute([$propId]);
$images = $stmt->fetchAll();

// 3. Fetch Amenities
$stmt = $pdo->prepare("
    SELECT a.amenity_name 
    FROM property_amenity pa
    JOIN amenity a ON pa.amenity_id = a.amenity_id
    WHERE pa.property_id = ?
");
$stmt->execute([$propId]);
$amenities = $stmt->fetchAll();

// Status Badge Logic
$statusLabels = [
    1 => ['text' => 'Active', 'bg' => 'bg-success'],
    2 => ['text' => 'Rented', 'bg' => 'bg-secondary'],
    3 => ['text' => 'Inactive', 'bg' => 'bg-warning'],
    4 => ['text' => 'Pending Approval', 'bg' => 'bg-info'],
    5 => ['text' => 'Rejected', 'bg' => 'bg-danger']
];
$status = $statusLabels[$property['status_id']] ?? ['text' => 'Unknown', 'bg' => 'bg-secondary'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($property['title']) ?> - Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= app_url('public/profile/profile.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="property_read.css">
</head>
<body>

<?php require __DIR__ . '/../../../../public/navbar/navbar.php'; ?>

<div class="container py-5 profile-container">
    
    <!-- Breadcrumb & Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="../manage.php" class="text-decoration-none text-muted">My Properties</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View Details</li>
                </ol>
            </nav>
            <h2 class="fw-bold text-dark mb-0">Property Details</h2>
        </div>
        <div>
            <a href="../update/property_update.php?id=<?= $propId ?>" class="btn btn-primary px-4 shadow-sm" style="background: var(--fern); border-color: var(--fern);">
                <i class="bi bi-pencil-square me-2"></i>Edit
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Sidebar / Main Info -->
        <div class="col-lg-8">
            <!-- Image Gallery -->
            <div class="card border-0 shadow-sm mb-4 overflow-hidden gallery-card">
                <div class="position-relative main-image-container">
                    <?php if (count($images) > 0): ?>
                        <img src="<?= app_url($images[0]['image_path']) ?>" id="mainImage" class="w-100 h-100 object-fit-cover" alt="Main Property Image">
                    <?php else: ?>
                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light text-muted">No Images Uploaded</div>
                    <?php endif; ?>
                    <div class="status-badge-overlay">
                        <span class="badge <?= $status['bg'] ?> px-3 py-2 rounded-pill shadow-sm"><?= $status['text'] ?></span>
                    </div>
                </div>
                <!-- Thumbnails -->
                <?php if (count($images) > 1): ?>
                <div class="d-flex gap-2 p-3 overflow-auto bg-white border-top">
                    <?php foreach ($images as $img): ?>
                        <img src="<?= app_url($img['image_path']) ?>" class="gallery-thumb rounded cursor-pointer" onclick="changeMainImage(this.src)" alt="Thumbnail">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-3 text-dark"><?= htmlspecialchars($property['title']) ?></h4>
                    <div class="d-flex gap-4 mb-4 text-muted border-bottom pb-4">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-house me-2 fs-5"></i>
                            <span><?= htmlspecialchars($property['type_name']) ?></span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-geo-alt me-2 fs-5"></i>
                            <span><?= htmlspecialchars($property['city_name']) ?></span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar3 me-2 fs-5"></i>
                            <span>Listed <?= date('M d, Y', strtotime($property['created_at'])) ?></span>
                        </div>
                    </div>
                    
                    <h5 class="fw-bold mb-3 text-secondary">About this property</h5>
                    <p class="text-secondary lh-lg mb-0 text-break" style="white-space: pre-wrap;"><?= htmlspecialchars($property['description']) ?></p>
                </div>
            </div>

            <!-- Amenities -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4 text-secondary">Amenities & Features</h5>
                    <?php if (count($amenities) > 0): ?>
                        <div class="row g-3">
                            <?php foreach ($amenities as $am): ?>
                                <div class="col-md-4 col-6">
                                    <div class="d-flex align-items-center gap-2 p-2 rounded bg-light">
                                        <i class="bi text-success bi-check-circle-fill"></i>
                                        <span class="fw-medium text-dark"><?= htmlspecialchars($am['amenity_name']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No specific amenities listed.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Side: Quick Stats & Location -->
        <div class="col-lg-4">
            <!-- Price Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <small class="text-uppercase text-muted fw-bold ls-1">Price</small>
                    <h2 class="text-primary fw-bold mb-0" style="color: var(--hunter-green) !important;">
                        LKR <?= number_format($property['price_per_month'], 2) ?>
                        <span class="fs-6 text-muted fw-normal">/ month</span>
                    </h2>
                </div>
            </div>

            <!-- Key Features -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold py-3">Property Overview</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between py-3 px-4">
                            <span class="text-muted"><i class="bi bi-rulers me-2"></i>Area</span>
                            <span class="fw-semibold"><?= $property['sqft'] ?> sqft</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between py-3 px-4">
                            <span class="text-muted"><i class="bi bi-door-closed me-2"></i>Bedrooms</span>
                            <span class="fw-semibold"><?= $property['bedrooms'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between py-3 px-4">
                            <span class="text-muted"><i class="bi bi-droplet me-2"></i>Bathrooms</span>
                            <span class="fw-semibold"><?= $property['bathrooms'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between py-3 px-4">
                            <span class="text-muted"><i class="bi bi-upc-scan me-2"></i>Reference ID</span>
                            <span class="fw-semibold text-monospace small bg-light px-2 rounded"><?= $property['property_code'] ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Location Map Link -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold py-3">Location</div>
                <div class="card-body p-4">
                    <p class="mb-3">
                        <strong class="d-block text-dark"><?= htmlspecialchars($property['city_name']) ?>, <?= htmlspecialchars($property['district_name']) ?></strong>
                        <span class="text-muted small"><?= htmlspecialchars($property['address']) ?></span>
                        <br>
                        <span class="text-muted small">Postal: <?= htmlspecialchars($property['postal_code']) ?></span>
                    </p>
                    
                    <?php if ($property['google_map_link']): ?>
                        <a href="<?= htmlspecialchars($property['google_map_link']) ?>" target="_blank" class="btn btn-outline-primary w-100">
                            <i class="bi bi-map-fill me-2"></i>View on Google Maps
                        </a>
                    <?php else: ?>
                        <button class="btn btn-light w-100 text-muted" disabled>Map Link Not Available</button>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="property_read.js"></script>
</body>
</html>
