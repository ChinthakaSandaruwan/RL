<?php
require __DIR__ . '/../../config/db.php';
ensure_session_started();
$user = current_user();

// Require any logged-in user (any role)
if (!$user) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();

// Fetch All Wishlist Items
$properties = $pdo->prepare("
    SELECT p.*, pt.type_name, c.name_en as city_name,
           (SELECT image_path FROM property_image WHERE property_id = p.property_id AND primary_image = 1 LIMIT 1) as main_image,
           pw.created_at as added_at
    FROM property_wishlist pw
    JOIN property p ON pw.property_id = p.property_id
    LEFT JOIN property_type pt ON p.property_type_id = pt.type_id
    LEFT JOIN property_location pl ON p.property_id = pl.property_id
    LEFT JOIN cities c ON pl.city_id = c.id
    WHERE pw.customer_id = ?
    ORDER BY pw.created_at DESC
");
$properties->execute([$user['user_id']]);
$propertyItems = $properties->fetchAll();

$rooms = $pdo->prepare("
    SELECT r.*, rt.type_name, c.name_en as city_name,
           (SELECT image_path FROM room_image WHERE room_id = r.room_id AND primary_image = 1 LIMIT 1) as main_image,
           rw.created_at as added_at
    FROM room_wishlist rw
    JOIN room r ON rw.room_id = r.room_id
    LEFT JOIN room_type rt ON r.room_type_id = rt.type_id
    LEFT JOIN room_location rl ON r.room_id = rl.room_id
    LEFT JOIN cities c ON rl.city_id = c.id
    WHERE rw.customer_id = ?
    ORDER BY rw.created_at DESC
");
$rooms->execute([$user['user_id']]);
$roomItems = $rooms->fetchAll();

$vehicles = $pdo->prepare("
    SELECT v.*, vt.type_name, vb.brand_name, vm.model_name, c.name_en as city_name,
           (SELECT image_path FROM vehicle_image WHERE vehicle_id = v.vehicle_id AND primary_image = 1 LIMIT 1) as main_image,
           vw.created_at as added_at
    FROM vehicle_wishlist vw
    JOIN vehicle v ON vw.vehicle_id = v.vehicle_id
    LEFT JOIN vehicle_type vt ON v.vehicle_type_id = vt.type_id
    LEFT JOIN vehicle_model vm ON v.model_id = vm.model_id
    LEFT JOIN vehicle_brand vb ON vm.brand_id = vb.brand_id
    LEFT JOIN vehicle_location vl ON v.vehicle_id = vl.vehicle_id
    LEFT JOIN cities c ON vl.city_id = c.id
    WHERE vw.customer_id = ?
    ORDER BY vw.created_at DESC
");
$vehicles->execute([$user['user_id']]);
$vehicleItems = $vehicles->fetchAll();

$totalItems = count($propertyItems) + count($roomItems) + count($vehicleItems);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Wishlist - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= app_url('public/profile/profile.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="wishlist.css">
</head>
<body>

<?php require __DIR__ . '/../navbar/navbar.php'; ?>

<div class="container py-5 profile-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">My Wishlist</h2>
            <p class="text-muted mb-0"><?= $totalItems ?> saved item(s)</p>
        </div>
    </div>

    <?php if ($totalItems === 0): ?>
        <div class="text-center py-5">
            <i class="bi bi-heart fs-1 text-muted"></i>
            <h4 class="mt-3 text-muted">Your wishlist is empty</h4>
            <p class="text-muted">Start adding your favorite properties, rooms, and vehicles!</p>
            <a href="<?= app_url('index.php') ?>" class="btn btn-primary">Browse Listings</a>
        </div>
    <?php else: ?>

    <!-- Properties -->
    <?php if (!empty($propertyItems)): ?>
    <div class="mb-5">
        <h4 class="mb-3"><i class="bi bi-house-door me-2"></i>Properties (<?= count($propertyItems) ?>)</h4>
        <div class="row g-3">
            <?php foreach ($propertyItems as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card wishlist-card border-0 shadow-sm h-100">
                    <div class="position-relative">
                        <img src="<?= app_url($item['main_image'] ?? 'public/assets/images/no-image-placeholder.jpg') ?>" class="card-img-top" alt="Property">
                        <button class="btn btn-sm btn-danger wishlist-remove-btn" onclick="removeFromWishlist('property', <?= $item['property_id'] ?>, this)" title="Remove">
                            <i class="bi bi-heart-fill"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title mb-2"><?= htmlspecialchars($item['title']) ?></h6>
                        <p class="text-muted small mb-2"><i class="bi bi-geo-alt"></i> <?= $item['city_name'] ?? 'N/A' ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-success fw-bold">LKR <?= number_format($item['price_per_month'], 0) ?>/mo</span>
                            <a href="<?= app_url('public/property/view/property_view.php?id='.$item['property_id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                        </div>
                        <small class="text-muted">Added <?= date('M d, Y', strtotime($item['added_at'])) ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Rooms -->
    <?php if (!empty($roomItems)): ?>
    <div class="mb-5">
        <h4 class="mb-3"><i class="bi bi-door-closed me-2"></i>Rooms (<?= count($roomItems) ?>)</h4>
        <div class="row g-3">
            <?php foreach ($roomItems as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card wishlist-card border-0 shadow-sm h-100">
                    <div class="position-relative">
                        <img src="<?= app_url($item['main_image'] ?? 'public/assets/images/no-image-placeholder.jpg') ?>" class="card-img-top" alt="Room">
                        <button class="btn btn-sm btn-danger wishlist-remove-btn" onclick="removeFromWishlist('room', <?= $item['room_id'] ?>, this)" title="Remove">
                            <i class="bi bi-heart-fill"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title mb-2"><?= htmlspecialchars($item['title']) ?></h6>
                        <p class="text-muted small mb-2"><i class="bi bi-geo-alt"></i> <?= $item['city_name'] ?? 'N/A' ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-success fw-bold">LKR <?= number_format($item['price_per_day'], 0) ?>/day</span>
                            <a href="<?= app_url('public/room/view/room_view.php?id='.$item['room_id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                        </div>
                        <small class="text-muted">Added <?= date('M d, Y', strtotime($item['added_at'])) ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Vehicles -->
    <?php if (!empty($vehicleItems)): ?>
    <div class="mb-5">
        <h4 class="mb-3"><i class="bi bi-car-front me-2"></i>Vehicles (<?= count($vehicleItems) ?>)</h4>
        <div class="row g-3">
            <?php foreach ($vehicleItems as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card wishlist-card border-0 shadow-sm h-100">
                    <div class="position-relative">
                        <img src="<?= app_url($item['main_image'] ?? 'public/assets/images/no-image-placeholder.jpg') ?>" class="card-img-top" alt="Vehicle">
                        <button class="btn btn-sm btn-danger wishlist-remove-btn" onclick="removeFromWishlist('vehicle', <?= $item['vehicle_id'] ?>, this)" title="Remove">
                            <i class="bi bi-heart-fill"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title mb-2"><?= htmlspecialchars($item['title']) ?></h6>
                        <p class="text-muted small mb-2"><?= $item['brand_name'] ?? '' ?> <?= $item['model_name'] ?? '' ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-success fw-bold">LKR <?= number_format($item['price_per_day'], 0) ?>/day</span>
                            <a href="<?= app_url('public/vehicle/view/vehicle_view.php?id='.$item['vehicle_id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                        </div>
                        <small class="text-muted">Added <?= date('M d, Y', strtotime($item['added_at'])) ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="wishlist.js"></script>
</body>
</html>
