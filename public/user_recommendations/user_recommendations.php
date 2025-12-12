<?php
// Determine user preference or default to random high-rated/newest items
// Since we don't have complex analytics, we'll fetch a mix of latest items

$pdo = get_pdo();

// Fetch 3 latest properties
$stmtProp = $pdo->query("
    SELECT p.*, pi.image_path, l.city_id, c.name_en as city_name 
    FROM property p 
    LEFT JOIN property_image pi ON p.property_id = pi.property_id AND pi.primary_image = 1 
    LEFT JOIN property_location l ON p.property_id = l.property_id 
    LEFT JOIN cities c ON l.city_id = c.id
    WHERE p.status_id = 1 
    ORDER BY p.created_at DESC 
    LIMIT 3
");
$recProperties = $stmtProp->fetchAll();

// Fetch 3 latest rooms
$stmtRoom = $pdo->query("
    SELECT r.*, ri.image_path, l.city_id, c.name_en as city_name 
    FROM room r 
    LEFT JOIN room_image ri ON r.room_id = ri.room_id AND ri.primary_image = 1 
    LEFT JOIN room_location l ON r.room_id = l.room_id 
    LEFT JOIN cities c ON l.city_id = c.id
    WHERE r.status_id = 1 
    ORDER BY r.created_at DESC 
    LIMIT 3
");
$recRooms = $stmtRoom->fetchAll();

// Fetch 3 latest vehicles
$stmtVeh = $pdo->query("
    SELECT v.*, vi.image_path, vl.city_id, c.name_en as city_name 
    FROM vehicle v 
    LEFT JOIN vehicle_image vi ON v.vehicle_id = vi.vehicle_id AND vi.primary_image = 1 
    LEFT JOIN vehicle_location vl ON v.vehicle_id = vl.vehicle_id 
    LEFT JOIN cities c ON vl.city_id = c.id
    WHERE v.status_id = 1 
    ORDER BY v.created_at DESC 
    LIMIT 3
");
$recVehicles = $stmtVeh->fetchAll();

// Check if we have any recommendations
if (empty($recProperties) && empty($recRooms) && empty($recVehicles)) {
    return; // Nothing to show
}
?>

<link rel="stylesheet" href="<?= app_url('public/user_recommendations/user_recommendations.css') ?>">

<section class="recommendation-section">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="display-6 fw-bold mb-3">Recommended For You</h2>
                <p class="text-muted lead">Handpicked selections just for you.</p>
                <div class="d-flex justify-content-center gap-2">
                    <span class="badge bg-primary rounded-pill px-3 py-2">Properties</span>
                    <span class="badge bg-success rounded-pill px-3 py-2">Rooms</span>
                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2">Vehicles</span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Properties -->
            <?php foreach ($recProperties as $item): ?>
                <div class="col-md-4">
                    <div class="card recommendation-card">
                        <div class="recommendation-img-wrapper">
                            <span class="recommendation-badge badge-property">Property</span>
                            <img src="<?= !empty($item['image_path']) ? app_url($item['image_path']) : app_url('public/assets/images/placeholder_property.jpg') ?>" 
                                 class="recommendation-img" alt="<?= htmlspecialchars($item['title']) ?>">
                        </div>
                        <div class="recommendation-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="recommendation-title mb-0"><?= htmlspecialchars($item['title']) ?></h5>
                            </div>
                            <div class="text-muted small mb-3">
                                <i class="bi bi-geo-alt-fill me-1"></i> <?= htmlspecialchars($item['city_name'] ?? 'Unknown Location') ?>
                            </div>
                            <div class="recommendation-features">
                                <span class="recommendation-feature"><i class="bi bi-x-diamond"></i> <?= $item['bedrooms'] ?> Beds</span>
                                <span class="recommendation-feature"><i class="bi bi-droplet"></i> <?= $item['bathrooms'] ?> Baths</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="recommendation-price">Rs. <?= number_format($item['price_per_month']) ?>/mo</span>
                                <a href="<?= app_url('public/property/view/property_view.php?id=' . $item['property_id']) ?>" class="btn btn-outline-primary btn-sm stretched-link">View</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Rooms -->
            <?php foreach ($recRooms as $item): ?>
                <div class="col-md-4">
                    <div class="card recommendation-card">
                        <div class="recommendation-img-wrapper">
                            <span class="recommendation-badge badge-room">Room</span>
                            <img src="<?= !empty($item['image_path']) ? app_url($item['image_path']) : app_url('public/assets/images/placeholder_room.jpg') ?>" 
                                 class="recommendation-img" alt="<?= htmlspecialchars($item['title']) ?>">
                        </div>
                        <div class="recommendation-body">
                            <h5 class="recommendation-title"><?= htmlspecialchars($item['title']) ?></h5>
                            <div class="text-muted small mb-3">
                                <i class="bi bi-geo-alt-fill me-1"></i> <?= htmlspecialchars($item['city_name'] ?? 'Unknown Location') ?>
                            </div>
                            <div class="recommendation-features">
                                <span class="recommendation-feature"><i class="bi bi-person"></i> <?= $item['maximum_guests'] ?> Guests</span>
                                <span class="recommendation-feature"><i class="bi bi-moon"></i> <?= $item['beds'] ?> Beds</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="recommendation-price text-success">Rs. <?= number_format($item['price_per_day']) ?>/day</span>
                                <a href="<?= app_url('public/room/view/room_view.php?id=' . $item['room_id']) ?>" class="btn btn-outline-success btn-sm stretched-link">View</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Vehicles -->
            <?php foreach ($recVehicles as $item): ?>
                <div class="col-md-4">
                    <div class="card recommendation-card">
                        <div class="recommendation-img-wrapper">
                            <span class="recommendation-badge badge-vehicle">Vehicle</span>
                            <img src="<?= !empty($item['image_path']) ? app_url($item['image_path']) : app_url('public/assets/images/placeholder_vehicle.jpg') ?>" 
                                 class="recommendation-img" alt="<?= htmlspecialchars($item['title']) ?>">
                        </div>
                        <div class="recommendation-body">
                            <h5 class="recommendation-title"><?= htmlspecialchars($item['title']) ?></h5>
                            <div class="text-muted small mb-3">
                                <i class="bi bi-geo-alt-fill me-1"></i> <?= htmlspecialchars($item['city_name'] ?? 'Unknown Location') ?>
                            </div>
                            <div class="recommendation-features">
                                <span class="recommendation-feature"><i class="bi bi-speedometer2"></i> <?= $item['year'] ?></span>
                                <span class="recommendation-feature"><i class="bi bi-fuel-pump"></i> <?= $item['transmission_type_id'] == 1 ? 'Auto' : 'Manual' ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="recommendation-price text-warning text-dark">Rs. <?= number_format($item['price_per_day']) ?>/day</span>
                                <a href="<?= app_url('public/vehicle/view/vehicle_view.php?id=' . $item['vehicle_id']) ?>" class="btn btn-outline-warning btn-sm stretched-link">View</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script src="<?= app_url('public/user_recommendations/user_recommendations.js') ?>"></script>
