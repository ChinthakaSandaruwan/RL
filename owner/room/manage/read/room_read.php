<?php
require __DIR__ . '/../../../../config/db.php';
ensure_session_started();
$user = current_user();

if (!$user || $user['role_id'] != 3) { header('Location: ../../../auth/login'); exit; }
$rid = $_GET['id'] ?? 0;
if (!$rid) { header('Location: ../manage.php'); exit; }

$pdo = get_pdo();

// Room Details
$stmt = $pdo->prepare("
    SELECT r.*, rt.type_name, c.name_en as city, d.name_en as district, p.name_en as province,
           rl.address, rl.google_map_link
    FROM room r
    JOIN room_type rt ON r.room_type_id = rt.type_id
    JOIN room_location rl ON r.room_id = rl.room_id
    JOIN cities c ON rl.city_id = c.id
    JOIN districts d ON c.district_id = d.id
    JOIN provinces p ON d.province_id = p.id
    WHERE r.room_id = ? AND r.owner_id = ?
");
$stmt->execute([$rid, $user['user_id']]);
$room = $stmt->fetch();
if (!$room) die("Room not found or access denied.");

// Images
$imgs = $pdo->prepare("SELECT * FROM room_image WHERE room_id = ? ORDER BY primary_image DESC");
$imgs->execute([$rid]); $images = $imgs->fetchAll();

// Amenities
$ams = $pdo->prepare("SELECT a.amenity_name FROM room_amenity ra JOIN amenity a ON ra.amenity_id = a.amenity_id WHERE ra.room_id = ?");
$ams->execute([$rid]); $amenities = $ams->fetchAll();

// Meals
$mls = $pdo->prepare("SELECT m.type_name, rm.price FROM room_meal rm JOIN meal_type m ON rm.meal_type_id = m.type_id WHERE rm.room_id = ?");
$mls->execute([$rid]); $meals = $mls->fetchAll();

$st = [1=>'Active', 2=>'Booked', 3=>'Inactive', 4=>'Pending'];
$stClass = [1=>'success', 2=>'secondary', 3=>'warning', 4=>'info'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title><?= htmlspecialchars($room['title']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="room_read.css">
    <style>.meal-tag { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; padding: 5px 10px; border-radius: 20px; font-size: 0.9em; display: inline-block; margin: 2px; } </style>
</head>
<body>
<?php require __DIR__ . '/../../../../public/navbar/navbar.php'; ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="../manage.php">My Rooms</a></li><li class="breadcrumb-item active">Details</li></ol></nav>
        <a href="../update/room_update.php?id=<?= $rid ?>" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Edit Room</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4 overflow-hidden gallery-card">
                <div class="main-image-container">
                    <?php if ($images): ?>
                    <img src="<?= app_url($images[0]['image_path']) ?>" id="mainImage" class="object-fit-cover w-100 h-100">
                    <?php else: ?><div class="p-5 text-center bg-light">No Images</div><?php endif; ?>
                    <span class="badge bg-<?= $stClass[$room['status_id']] ?> position-absolute top-0 end-0 m-3 p-2"><?= $st[$room['status_id']] ?></span>
                </div>
                <?php if (count($images)>1): ?>
                <div class="d-flex gap-2 p-2 overflow-auto">
                    <?php foreach ($images as $im): ?><img src="<?= app_url($im['image_path']) ?>" class="gallery-thumb cursor-pointer" onclick="document.getElementById('mainImage').src=this.src"><?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="card border-0 shadow-sm mb-4 p-4">
                <h3><?= htmlspecialchars($room['title']) ?></h3>
                <div class="text-muted mb-3"><i class="bi bi-geo-alt"></i> <?= $room['city'] ?>, <?= $room['district'] ?> &bull; <?= $room['type_name'] ?></div>
                <p class="text-secondary"><?= nl2br(htmlspecialchars($room['description'])) ?></p>
                
                <hr>
                <h5>Amenities</h5>
                <div class="row g-2">
                    <?php foreach ($amenities as $a): ?>
                    <div class="col-6 col-md-4"><i class="bi bi-check-circle text-success"></i> <?= $a['amenity_name'] ?></div>
                    <?php endforeach; ?>
                </div>

                <?php if ($meals): ?>
                <hr>
                <h5>Meal Plans</h5>
                <div>
                    <?php foreach ($meals as $m): ?>
                    <span class="meal-tag"><i class="bi bi-egg-fried"></i> <?= $m['type_name'] ?>: LKR <?= number_format($m['price'],2) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-3 p-4">
                <small>Daily Rate</small>
                <h2 class="text-success">LKR <?= number_format($room['price_per_day'], 2) ?></h2>
            </div>
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white fw-bold">Overview</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between"><span>Max Guests</span> <strong><?= $room['maximum_guests'] ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Beds</span> <strong><?= $room['beds'] ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Baths</span> <strong><?= $room['bathrooms'] ?></strong></li>
                </ul>
            </div>
            <div class="card shadow-sm border-0 p-4">
                <h5>Location</h5>
                <p><?= $room['address'] ?></p>
                <?php if ($room['google_map_link']): ?><a href="<?= $room['google_map_link'] ?>" target="_blank" class="btn btn-outline-secondary w-100">View Map</a><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="room_read.js"></script>
</body>
</html>
