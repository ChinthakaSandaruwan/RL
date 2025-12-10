<?php
require_once __DIR__ . '/../../../config/db.php';
ensure_session_started();

$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($room_id <= 0) {
    header("Location: " . app_url());
    exit;
}

$pdo = get_pdo();

// Fetch room details
$stmt = $pdo->prepare("
    SELECT 
        r.*, 
        rt.type_name,
        rl.address, rl.google_map_link, rl.postal_code,
        c.name_en as city_name, d.name_en as district_name, pr.name_en as province_name,
        u.name as owner_name, u.email as owner_email, u.mobile_number as owner_phone, u.profile_image as owner_image
    FROM room r
    LEFT JOIN room_type rt ON r.room_type_id = rt.type_id
    LEFT JOIN room_location rl ON r.room_id = rl.room_id
    LEFT JOIN cities c ON rl.city_id = c.id
    LEFT JOIN districts d ON c.district_id = d.id
    LEFT JOIN provinces pr ON d.province_id = pr.id
    LEFT JOIN user u ON r.owner_id = u.user_id
    WHERE r.room_id = ?
");
$stmt->execute([$room_id]);
$room = $stmt->fetch();

if (!$room) {
    header("Location: " . app_url());
    exit;
}

// Fetch images
$stmt_img = $pdo->prepare("SELECT * FROM room_image WHERE room_id = ? ORDER BY primary_image DESC");
$stmt_img->execute([$room_id]);
$images = $stmt_img->fetchAll();

// Fetch amenities
$stmt_am = $pdo->prepare("
    SELECT a.amenity_name 
    FROM room_amenity ra 
    JOIN amenity a ON ra.amenity_id = a.amenity_id 
    WHERE ra.room_id = ?
");
$stmt_am->execute([$room_id]);
$room_amenities = $stmt_am->fetchAll(PDO::FETCH_COLUMN);

// Fetch Meals
$stmt_meal = $pdo->prepare("
    SELECT m.type_name, rm.price
    FROM room_meal rm
    JOIN meal_type m ON rm.meal_type_id = m.type_id
    WHERE rm.room_id = ?
");
$stmt_meal->execute([$room_id]);
$room_meals = $stmt_meal->fetchAll();

// Fallback image
if (empty($images)) {
    $images[] = ['image_path' => 'public/assets/images/placeholder-room.jpg', 'primary_image' => 1];
}

$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($room['title']) ?> - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">
    
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="room_view.css">
</head>
<body class="bg-light">

<?php require_once __DIR__ . '/../../navbar/navbar.php'; ?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= app_url() ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= app_url('public/room/view_all.php') ?>">Rooms</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($room['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-8">
            
            <!-- Image Gallery -->
            <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                <div id="roomCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($images as $index => $img): ?>
                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                            <img src="<?= app_url($img['image_path']) ?>" class="d-block w-100 main-img" alt="Room Image" onerror="this.src='https://via.placeholder.com/800x500?text=No+Image'">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($images) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#roomCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#roomCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex gap-2 p-2 overflow-auto bg-white">
                    <?php foreach ($images as $index => $img): ?>
                        <img src="<?= app_url($img['image_path']) ?>" 
                             class="img-thumbnail thumb-img room-thumb <?= $index === 0 ? 'active-thumb' : '' ?>" 
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
                            <span class="badge badge-theme mb-2"><?= htmlspecialchars($room['type_name']) ?></span>
                            <h1 class="h3 fw-bold text-dark mb-1"><?= htmlspecialchars($room['title']) ?></h1>
                            <p class="text-muted mb-0">
                                <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                <?= htmlspecialchars(implode(', ', array_filter([$room['address'], $room['city_name'], $room['district_name']]))) ?>
                            </p>
                        </div>
                        <div class="text-end">
                            <h2 class="h3 fw-bold text-theme mb-0">LKR <?= number_format($room['price_per_day'], 2) ?></h2>
                            <small class="text-muted">/ Day</small>
                        </div>
                    </div>

                    <hr>

                    <!-- Features -->
                    <div class="row g-3 text-center mb-4">
                        <div class="col-6 col-sm-3">
                            <div class="feature-box">
                                <i class="bi bi-droplet feature-icon"></i>
                                <div class="fw-bold"><?= $room['bathrooms'] ?></div>
                                <small class="text-muted">Bathrooms</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="feature-box">
                                <i class="bi bi-door-closed feature-icon"></i>
                                <div class="fw-bold"><?= $room['beds'] ?></div>
                                <small class="text-muted">Beds</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="feature-box">
                                <i class="bi bi-people feature-icon"></i>
                                <div class="fw-bold"><?= $room['maximum_guests'] ?? '2' ?></div>
                                <small class="text-muted">Max Guests</small>
                            </div>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3">Description</h5>
                    <p class="text-secondary lh-lg mb-4">
                        <?= nl2br(htmlspecialchars($room['description'])) ?>
                    </p>

                    <h5 class="fw-bold mb-3">Amenities</h5>
                    <div class="row g-3 mb-4">
                        <?php if (empty($room_amenities)): ?>
                            <div class="col-12"><p class="text-muted">No specific amenities listed.</p></div>
                        <?php else: ?>
                            <?php foreach ($room_amenities as $amenity): ?>
                                <div class="col-6 col-md-4">
                                    <div class="d-flex align-items-center text-dark">
                                        <i class="bi bi-check-circle-fill text-theme me-2"></i>
                                        <span><?= htmlspecialchars($amenity) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($room_meals)): ?>
                    <h5 class="fw-bold mb-3">Meal Plans Available</h5>
                    <div class="row g-3 mb-4">
                        <?php foreach ($room_meals as $meal): ?>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center border rounded p-2">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-egg-fried text-warning me-2"></i>
                                        <span class="fw-bold"><?= htmlspecialchars($meal['type_name']) ?></span>
                                    </div>
                                    <span class="badge bg-secondary">LKR <?= number_format($meal['price']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($room['google_map_link'])): ?>
                        <h5 class="fw-bold mb-3">Location</h5>
                        <div class="bg-light p-4 rounded text-center">
                            <a href="<?= htmlspecialchars($room['google_map_link']) ?>" target="_blank" class="btn btn-outline-theme">
                                <i class="bi bi-geo-alt me-2"></i>View on Google Maps
                            </a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Contact Owner</h5>
                    <div class="d-flex align-items-center mb-4">
                         <img src="<?= $room['owner_image'] ? app_url($room['owner_image']) : 'https://ui-avatars.com/api/?name='.urlencode($room['owner_name']).'&background=random' ?>" 
                             class="rounded-circle me-3" width="60" height="60" style="object-fit: cover;">
                        <div>
                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($room['owner_name']) ?></h6>
                            <small class="text-muted">Room Owner</small>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="<?= app_url('public/rent/rent_room/rent_room.php?room_id=' . $room_id) ?>" class="btn btn-theme btn-lg">
                            <i class="bi bi-house-check-fill me-2"></i> Rent Now
                        </a>
                        <a href="tel:<?= htmlspecialchars($room['owner_phone']) ?>" class="btn btn-outline-theme">
                            <i class="bi bi-telephone-fill me-2"></i> Call Now
                        </a>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $room['owner_phone']) ?>" target="_blank" class="btn btn-outline-theme">
                            <i class="bi bi-whatsapp me-2"></i> WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="room_view.js"></script>
</body>
</html>
