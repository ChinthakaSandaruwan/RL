<?php
require_once __DIR__ . '/../../config/db.php';
ensure_session_started();

$user = current_user();
if (!$user) {
    header("Location: " . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();

// Fetch user's property rentals
$stmt_property = $pdo->prepare("
    SELECT 
        pr.*, 
        p.title as property_title,
        p.property_code,
        pt.type_name,
        pl.address,
        c.name_en as city_name,
        rs.status_name,
        (SELECT image_path FROM property_image WHERE property_id = p.property_id AND primary_image = 1 LIMIT 1) as image_path,
        u.name as owner_name,
        u.mobile_number as owner_phone
    FROM property_rent pr
    JOIN property p ON pr.property_id = p.property_id
    LEFT JOIN property_type pt ON p.property_type_id = pt.type_id
    LEFT JOIN property_location pl ON p.property_id = pl.property_id
    LEFT JOIN cities c ON pl.city_id = c.id
    LEFT JOIN rent_status rs ON pr.status_id = rs.status_id
    LEFT JOIN user u ON p.owner_id = u.user_id
    WHERE pr.customer_id = ?
    ORDER BY pr.created_at DESC
");
$stmt_property->execute([$user['user_id']]);
$property_rentals = $stmt_property->fetchAll();

// Fetch user's room rentals
$stmt_room = $pdo->prepare("
    SELECT 
        rr.*, 
        r.title as room_title,
        r.room_code,
        rt.type_name,
        rl.address,
        c.name_en as city_name,
        rs.status_name,
        (SELECT image_path FROM room_image WHERE room_id = r.room_id AND primary_image = 1 LIMIT 1) as image_path,
        u.name as owner_name,
        u.mobile_number as owner_phone
    FROM room_rent rr
    JOIN room r ON rr.room_id = r.room_id
    LEFT JOIN room_type rt ON r.room_type_id = rt.type_id
    LEFT JOIN room_location rl ON r.room_id = rl.room_id
    LEFT JOIN cities c ON rl.city_id = c.id
    LEFT JOIN rent_status rs ON rr.status_id = rs.status_id
    LEFT JOIN user u ON r.owner_id = u.user_id
    WHERE rr.customer_id = ?
    ORDER BY rr.created_at DESC
");
$stmt_room->execute([$user['user_id']]);
$room_rentals = $stmt_room->fetchAll();

// Fetch user's vehicle rentals
$stmt_vehicle = $pdo->prepare("
    SELECT 
        vr.*, 
        v.title as vehicle_title,
        v.vehicle_code,
        vt.type_name,
        vb.brand_name,
        vm.model_name,
        rs.status_name,
        (SELECT image_path FROM vehicle_image WHERE vehicle_id = v.vehicle_id AND primary_image = 1 LIMIT 1) as image_path,
        u.name as owner_name,
        u.mobile_number as owner_phone
    FROM vehicle_rent vr
    JOIN vehicle v ON vr.vehicle_id = v.vehicle_id
    LEFT JOIN vehicle_type vt ON v.vehicle_type_id = vt.type_id
    LEFT JOIN vehicle_model vm ON v.model_id = vm.model_id
    LEFT JOIN vehicle_brand vb ON vm.brand_id = vb.brand_id
    LEFT JOIN rent_status rs ON vr.status_id = rs.status_id
    LEFT JOIN user u ON v.owner_id = u.user_id
    WHERE vr.customer_id = ?
    ORDER BY vr.created_at DESC
");
$stmt_vehicle->execute([$user['user_id']]);
$vehicle_rentals = $stmt_vehicle->fetchAll();

$total_rentals = count($property_rentals) + count($room_rentals) + count($vehicle_rentals);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Rentals - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">
    
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="my_rent.css">
</head>
<body class="bg-light">

<?php require_once __DIR__ . '/../navbar/navbar.php'; ?>

<div class="container py-5">
    <!-- Page Header -->
    <div class="mb-4">
        <h1 class="h2 fw-bold" style="color: var(--hunter-green);">
            <i class="bi bi-calendar-check me-2"></i>My Rentals
        </h1>
        <p class="text-muted">View and manage all your rental bookings</p>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                <i class="bi bi-house-door text-primary" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0 fw-bold"><?= count($property_rentals) ?></h3>
                            <p class="text-muted mb-0 small">Property Rentals</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded p-3">
                                <i class="bi bi-door-open text-info" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0 fw-bold"><?= count($room_rentals) ?></h3>
                            <p class="text-muted mb-0 small">Room Rentals</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded p-3">
                                <i class="bi bi-car-front text-warning" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0 fw-bold"><?= count($vehicle_rentals) ?></h3>
                            <p class="text-muted mb-0 small">Vehicle Rentals</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($total_rentals == 0): ?>
        <!-- Empty State -->
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                <h4 class="mt-3">No Rentals Yet</h4>
                <p class="text-muted mb-4">You haven't made any rental bookings yet. Start exploring!</p>
                <a href="<?= app_url() ?>" class="btn btn-primary">
                    <i class="bi bi-search me-2"></i>Browse Listings
                </a>
            </div>
        </div>
    <?php else: ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="rentalTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button">
                    All (<?= $total_rentals ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="properties-tab" data-bs-toggle="tab" data-bs-target="#properties" type="button">
                    Properties (<?= count($property_rentals) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rooms-tab" data-bs-toggle="tab" data-bs-target="#rooms" type="button">
                    Rooms (<?= count($room_rentals) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="vehicles-tab" data-bs-toggle="tab" data-bs-target="#vehicles" type="button">
                    Vehicles (<?= count($vehicle_rentals) ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="rentalTabsContent">
            <!-- All Tab -->
            <div class="tab-pane fade show active" id="all">
                <?php
                $all_rentals = array_merge(
                    array_map(function($r) { $r['type'] = 'property'; return $r; }, $property_rentals),
                    array_map(function($r) { $r['type'] = 'room'; return $r; }, $room_rentals),
                    array_map(function($r) { $r['type'] = 'vehicle'; return $r; }, $vehicle_rentals)
                );
                usort($all_rentals, function($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
                
                foreach ($all_rentals as $rental):
                    include __DIR__ . '/../includes/rental_card.php';
                endforeach;
                ?>
            </div>

            <!-- Properties Tab -->
            <div class="tab-pane fade" id="properties">
                <?php if (empty($property_rentals)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-house text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">No property rentals found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($property_rentals as $rental): 
                        $rental['type'] = 'property';
                        include __DIR__ . '/../includes/rental_card.php';
                    endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Rooms Tab -->
            <div class="tab-pane fade" id="rooms">
                <?php if (empty($room_rentals)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-door-open text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">No room rentals found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($room_rentals as $rental): 
                        $rental['type'] = 'room';
                        include __DIR__ . '/../includes/rental_card.php';
                    endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Vehicles Tab -->
            <div class="tab-pane fade" id="vehicles">
                <?php if (empty($vehicle_rentals)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-car-front text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">No vehicle rentals found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($vehicle_rentals as $rental): 
                        $rental['type'] = 'vehicle';
                        include __DIR__ . '/../includes/rental_card.php';
                    endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="my_rent.js"></script>
</body>
</html>
