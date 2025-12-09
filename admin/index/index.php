<?php
require __DIR__ . '/../../config/db.php';

ensure_session_started();
$user = current_user();

// Check if user is Admin (role_id = 2)
if (!$user || $user['role_id'] != 2) {
    header('Location: ' . app_url('index.php'));
    exit;
}

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$pdo = get_pdo();

// Get stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
$totalProperties = $pdo->query("SELECT COUNT(*) FROM property")->fetchColumn();
$totalVehicles = $pdo->query("SELECT COUNT(*) FROM vehicle")->fetchColumn();

// Try to get pending requests (table may not exist yet)
try {
    $pendingRequests = $pdo->query("SELECT COUNT(*) FROM user_type_change_request WHERE status_id = 1")->fetchColumn();
} catch (PDOException $e) {
    $pendingRequests = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= app_url('admin/index/index.css') ?>">
</head>
<body>

<?php require __DIR__ . '/../../public/navbar/navbar.php'; ?>

<div class="admin-header">
    <div class="container">
        <h1 class="display-5 fw-bold">Admin Dashboard</h1>
        <p class="lead mb-0">Welcome, <?= htmlspecialchars($user['name']) ?></p>
    </div>
</div>

<div class="container pb-5">
    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Total Users</h6>
                    <h2 class="fw-bold text-primary"><?= number_format($totalUsers) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Properties</h6>
                    <h2 class="fw-bold text-success"><?= number_format($totalProperties) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Vehicles</h6>
                    <h2 class="fw-bold text-warning"><?= number_format($totalVehicles) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Pending Requests</h6>
                    <h2 class="fw-bold text-danger"><?= number_format($pendingRequests) ?></h2>
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
                <div class="col-md-3">
                    <a href="<?= app_url('admin/property/approval/property_approval.php') ?>" class="btn btn-outline-success w-100 py-3">
                        <i class="bi bi-building-check"></i> Property Approval
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/room/approval/room_approval.php') ?>" class="btn btn-outline-success w-100 py-3">
                        <i class="bi bi-building-check"></i> Room Approval
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/user_type_change/sent_user_type_change_request.php') ?>" class="btn btn-outline-primary w-100 py-3">
                        <i class="bi bi-person-check"></i> User Type Requests
                        <?php if ($pendingRequests > 0): ?>
                            <span class="badge bg-danger ms-2"><?= $pendingRequests ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/users/owner/all_owners.php') ?>" class="btn btn-outline-primary w-100 py-3">
                        <i class="bi bi-person-badge"></i> Manage Owners
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/users/customer/all_customers.php') ?>" class="btn btn-outline-primary w-100 py-3">
                        <i class="bi bi-people"></i> Manage Customers
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/listings/manage_listings.php') ?>" class="btn btn-outline-warning w-100 py-3">
                        <i class="bi bi-list-check"></i> Manage Listings
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/footer/footer_details.php') ?>" class="btn btn-outline-warning w-100 py-3">
                        <i class="bi bi-list-check"></i> Footer Details 
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/amenity/amenity.php') ?>" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-tools"></i> Manage Amenities
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/vehicle/vehicle_type/vehicle_type.php') ?>" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-car-front"></i> Vehicle Types
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/vehicle/vehicle_brand/vehicle_brand.php') ?>" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-copyright"></i> Vehicle Brands
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/available_meals_&_prices/meal_type.php') ?>" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-egg-fried"></i> Available Meals
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/notification/owner/send_to_owner.php') ?>" class="btn btn-outline-warning w-100 py-3">
                        <i class="bi bi-bell"></i> Notify Owners
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/notification/cutomer/send_to_customer.php') ?>" class="btn btn-outline-warning w-100 py-3">
                        <i class="bi bi-bell"></i> Notify Customers
                    </a>
                </div>
                
            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
