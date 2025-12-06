<?php
require __DIR__ . '/../../config/db.php';

ensure_session_started();
$user = current_user();

// Check if user is Owner (role_id = 3)
if (!$user || $user['role_id'] != 3) {
    header('Location: ' . app_url('index.php'));
    exit;
}

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$pdo = get_pdo();

// Get owner's stats
$myProperties = $pdo->prepare("SELECT COUNT(*) FROM property WHERE owner_id = ?");
$myProperties->execute([$user['user_id']]);
$totalProperties = $myProperties->fetchColumn();

$myVehicles = $pdo->prepare("SELECT COUNT(*) FROM vehicle WHERE owner_id = ?");
$myVehicles->execute([$user['user_id']]);
$totalVehicles = $myVehicles->fetchColumn();

$myRooms = $pdo->prepare("SELECT COUNT(*) FROM room WHERE owner_id = ?");
$myRooms->execute([$user['user_id']]);
$totalRooms = $myRooms->fetchColumn();

// Active listings
$activeListings = $pdo->prepare("SELECT 
    (SELECT COUNT(*) FROM property WHERE owner_id = ? AND status_id = 1) +
    (SELECT COUNT(*) FROM vehicle WHERE owner_id = ? AND status_id = 1) +
    (SELECT COUNT(*) FROM room WHERE owner_id = ? AND status_id = 1) as total");
$activeListings->execute([$user['user_id'], $user['user_id'], $user['user_id']]);
$active = $activeListings->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Owner Dashboard - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="index.css">
</head>
<body>

<?php require __DIR__ . '/../../public/navbar/navbar.php'; ?>

<div class="owner-header">
    <div class="container">
        <h1 class="display-5 fw-bold">Owner Dashboard</h1>
        <p class="lead mb-0">Welcome, <?= htmlspecialchars($user['name']) ?></p>
    </div>
</div>

<div class="container pb-5">
    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">My Properties</h6>
                    <h2 class="fw-bold text-success"><?= number_format($totalProperties) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">My Rooms</h6>
                    <h2 class="fw-bold text-info"><?= number_format($totalRooms) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">My Vehicles</h6>
                    <h2 class="fw-bold text-warning"><?= number_format($totalVehicles) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Active Listings</h6>
                    <h2 class="fw-bold text-primary"><?= number_format($active) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold">Quick Actions</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="<?= app_url('owner/property/create/property_create.php') ?>" class="btn btn-outline-success w-100 py-3">
                        <i class="bi bi-plus-circle"></i> Add Property
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?= app_url('owner/room/create/room_create.php') ?>" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-plus-circle"></i> Add Room
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?= app_url('owner/vehicle/create/vehicle_create.php') ?>" class="btn btn-outline-warning w-100 py-3">
                        <i class="bi bi-plus-circle"></i> Add Vehicle
                    </a>
                </div>
            </div>
            <hr class="my-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="<?= app_url('owner/property/manage_properties.php') ?>" class="btn btn-outline-secondary w-100 py-3">
                        <i class="bi bi-list-check"></i> Manage Properties
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?= app_url('owner/room/manage_rooms.php') ?>" class="btn btn-outline-secondary w-100 py-3">
                        <i class="bi bi-list-check"></i> Manage Rooms
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?= app_url('owner/vehicle/manage_vehicles.php') ?>" class="btn btn-outline-secondary w-100 py-3">
                        <i class="bi bi-list-check"></i> Manage Vehicles
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="index.js"></script>
</body>
</html>
