<?php
require_once __DIR__ . '/../../../config/db.php';
ensure_session_started();

$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($property_id <= 0) {
    header("Location: " . app_url());
    exit;
}

$pdo = get_pdo();

// Fetch property details
$stmt = $pdo->prepare("
    SELECT 
        p.*, 
        pt.type_name,
        pl.address, pl.google_map_link, pl.postal_code,
        c.name_en as city_name, d.name_en as district_name, pr.name_en as province_name,
        u.name as owner_name, u.email as owner_email, u.mobile_number as owner_phone, u.profile_image as owner_image
    FROM property p
    LEFT JOIN property_type pt ON p.property_type_id = pt.type_id
    LEFT JOIN property_location pl ON p.property_id = pl.property_id
    LEFT JOIN cities c ON pl.city_id = c.id
    LEFT JOIN districts d ON c.district_id = d.id
    LEFT JOIN provinces pr ON d.province_id = pr.id
    LEFT JOIN user u ON p.owner_id = u.user_id
    WHERE p.property_id = ?
");
$stmt->execute([$property_id]);
$property = $stmt->fetch();

if (!$property) {
    header("Location: " . app_url());
    exit;
}

// Fetch images
$stmt_img = $pdo->prepare("SELECT * FROM property_image WHERE property_id = ? ORDER BY primary_image DESC");
$stmt_img->execute([$property_id]);
$images = $stmt_img->fetchAll();

// Fetch amenities
$stmt_am = $pdo->prepare("
    SELECT a.amenity_name 
    FROM property_amenity pa 
    JOIN amenity a ON pa.amenity_id = a.amenity_id 
    WHERE pa.property_id = ?
");
$stmt_am->execute([$property_id]);
$property_amenities = $stmt_am->fetchAll(PDO::FETCH_COLUMN);

// Fallback image if no images exist
if (empty($images)) {
    $images[] = ['image_path' => 'public/assets/images/placeholder-property.jpg', 'primary_image' => 1];
}

$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($property['title']) ?> - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= app_url('public/favicon/android-chrome-192x192.png') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= app_url('public/favicon/android-chrome-512x512.png') ?>">
    <link rel="shortcut icon" href="<?= app_url('public/favicon/favicon.ico') ?>">
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="property_view.css">
</head>
<body class="bg-light">

<?php require_once __DIR__ . '/../../navbar/navbar.php'; ?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= app_url() ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= app_url('public/property/view_all.php') ?>">Properties</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($property['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8">
            
            <!-- Image Gallery -->
            <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                <div id="propertyCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($images as $index => $img): ?>
                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                <img src="<?= app_url($img['image_path']) ?>" class="d-block w-100 main-img" alt="Property Image" onerror="this.src='https://via.placeholder.com/800x500?text=No+Image'">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($images) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#propertyCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#propertyCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Thumbnails -->
                <div class="d-flex gap-2 p-2 overflow-auto bg-white">
                    <?php foreach ($images as $index => $img): ?>
                        <img src="<?= app_url($img['image_path']) ?>" 
                             class="img-thumbnail thumb-img property-thumb <?= $index === 0 ? 'active-thumb' : '' ?>" 
                             onclick="showSlide(<?= $index ?>)"
                             alt="Thumbnail"
                             onerror="this.src='https://via.placeholder.com/100x80?text=No+Image'">
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Property Overview -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                        <div>
                            <span class="badge badge-theme mb-2"><?= htmlspecialchars($property['type_name']) ?></span>
                            <h1 class="h3 fw-bold text-dark mb-1"><?= htmlspecialchars($property['title']) ?></h1>
                            <p class="text-muted mb-0">
                                <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                <?= htmlspecialchars(implode(', ', array_filter([$property['address'], $property['city_name'], $property['district_name']]))) ?>
                            </p>
                        </div>
                        <div class="text-end">
                            <h2 class="h3 fw-bold text-theme mb-0">LKR <?= number_format($property['price_per_month'], 2) ?></h2>
                            <small class="text-muted">/ Month</small>
                        </div>
                    </div>

                    <hr>

                    <!-- Key Features -->
                    <div class="row g-3 text-center mb-4">
                        <div class="col-6 col-sm-3">
                            <div class="feature-box">
                                <i class="bi bi-houses feature-icon"></i>
                                <div class="fw-bold"><?= $property['sqft'] ?></div>
                                <small class="text-muted">Sqft</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="feature-box">
                                <i class="bi bi-door-open feature-icon"></i>
                                <div class="fw-bold"><?= $property['bedrooms'] ?></div>
                                <small class="text-muted">Bedrooms</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="feature-box">
                                <i class="bi bi-droplet feature-icon"></i>
                                <div class="fw-bold"><?= $property['bathrooms'] ?></div>
                                <small class="text-muted">Bathrooms</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="feature-box">
                                <i class="bi bi-tv feature-icon"></i>
                                <div class="fw-bold"><?= $property['living_rooms'] ?></div>
                                <small class="text-muted">Living Rooms</small>
                            </div>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3">Description</h5>
                    <p class="text-secondary lh-lg mb-4">
                        <?= nl2br(htmlspecialchars($property['description'])) ?>
                    </p>

                    <h5 class="fw-bold mb-3">Amenities & Features</h5>
                    <div class="row g-3 mb-4">

                        <?php if (empty($property_amenities)): ?>
                            <div class="col-12"><p class="text-muted">No specific amenities listed.</p></div>
                        <?php else: ?>
                            <?php foreach ($property_amenities as $amenity): ?>
                                <div class="col-6 col-md-4">
                                    <div class="d-flex align-items-center text-dark">
                                        <i class="bi bi-check-circle-fill text-theme me-2"></i>
                                        <span><?= htmlspecialchars($amenity) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($property['google_map_link'])): ?>
                        <h5 class="fw-bold mb-3">Location</h5>
                        <div class="ratio ratio-21x9 rounded overflow-hidden shadow-sm">
                             <!-- Using iframe generator logic or simple link if not embeddable. 
                                  If it's a direct link, we might want to show a button instead. 
                                  Assuming user puts a valid link, we can try to embed if it's embed format, 
                                  or just a button. For safety, let's provide a button to open map. -->
                            <div class="bg-light d-flex align-items-center justify-content-center flex-column">
                                <i class="bi bi-map fs-1 text-muted mb-2"></i>
                                <a href="<?= htmlspecialchars($property['google_map_link']) ?>" target="_blank" class="btn btn-outline-theme">
                                    <i class="bi bi-geo-alt me-2"></i>View on Google Maps
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            
            <!-- Owner Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Contact Owner</h5>
                    <div class="d-flex align-items-center mb-4">
                        <img src="<?= $property['owner_image'] ? app_url($property['owner_image']) : 'https://ui-avatars.com/api/?name='.urlencode($property['owner_name']).'&background=random' ?>" 
                             class="rounded-circle me-3" width="60" height="60" style="object-fit: cover;">
                        <div>
                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($property['owner_name']) ?></h6>
                            <small class="text-muted">Property Owner</small>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <?php if (isset($user) && $user): ?>
                        <a href="<?= app_url('public/rent/rent_property/rent_property.php?id=' . $property_id) ?>" class="btn btn-theme btn-lg mb-2">
                            <i class="bi bi-house-check me-2"></i> Rent Now
                        </a>
                        <?php else: ?>
                        <a href="<?= app_url('auth/login/index.php') ?>" class="btn btn-theme btn-lg mb-2">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Login to Rent
                        </a>
                        <?php endif; ?>

                        <a href="tel:<?= htmlspecialchars($property['owner_phone']) ?>" class="btn btn-outline-theme">
                            <i class="bi bi-telephone-fill me-2"></i> Call Now
                        </a>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $property['owner_phone']) ?>" target="_blank" class="btn btn-outline-theme">
                            <i class="bi bi-whatsapp me-2"></i> WhatsApp
                        </a>
                    </div>
                </div>
            </div>

            <!-- Safety Tips -->
            <div class="card border-0 shadow-sm bg-info bg-opacity-10 text-dark">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-shield-check me-2"></i>Safety Tips</h6>
                    <ul class="list-unstyled small mb-0 lh-sm gap-2 d-flex flex-column">
                        <li><i class="bi bi-dot"></i> Meet seller in a public place.</li>
                        <li><i class="bi bi-dot"></i> Don't pay before inspection.</li>
                        <li><i class="bi bi-dot"></i> Check property documents.</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="property_view.js"></script>

</body>
</html>
