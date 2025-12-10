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
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold" style="color: var(--hunter-green);">
                <i class="bi bi-check2-square me-2"></i>Approvals & Requests
            </h5>
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
                        <i class="bi bi-door-closed-fill"></i> Room Approval
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/vehicle/approval/vehicle_approval.php') ?>" class="btn btn-outline-success w-100 py-3">
                        <i class="bi bi-car-front-fill"></i> Vehicle Approval
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
                    <a href="<?= app_url('admin/bought_ads_package/approve/package_approval.php') ?>" class="btn btn-outline-warning w-100 py-3">
                        <i class="bi bi-box-seam"></i> Ads Package Approval
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- User Management -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold" style="color: var(--hunter-green);">
                <i class="bi bi-people me-2"></i>User Management
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
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
                    <a href="<?= app_url('admin/users/customer/customer_stauts/status_change.php') ?>" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-person-gear"></i> Customer Status
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/users/owner/owner_stauts/status_change.php') ?>" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-person-gear"></i> Owner Status
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Package & Payment Management -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold" style="color: var(--hunter-green);">
                <i class="bi bi-box-seam me-2"></i>Package & Payment Management
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="<?= app_url('admin/ads_package/manage/manage.php') ?>" class="btn btn-outline-primary w-100 py-3">
                        <i class="bi bi-boxes"></i> Manage Packages
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/ads_package/create/create_package.php') ?>" class="btn btn-outline-success w-100 py-3">
                        <i class="bi bi-plus-circle"></i> Create Package
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/bank_details/bank_details.php') ?>" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-bank"></i> Bank Details
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- System Configuration -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold" style="color: var(--hunter-green);">
                <i class="bi bi-gear me-2"></i>System Configuration
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="<?= app_url('admin/amenity/amenity.php') ?>" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-stars"></i> Manage Amenities
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/property/property_type/property_type.php') ?>" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-house-gear"></i> Property Types
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/room/room_type/room_type.php') ?>" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-door-open"></i> Room Types
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/vehicle/vehicle_type/vehicle_type.php') ?>" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-car-front"></i> Vehicle Types
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/brand_&_model/brand_&_model.php') ?>" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-tags"></i> Brands & Models
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/meals_&_prices/meal_type.php') ?>" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-egg-fried"></i> Meal Types
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/footer/footer_details.php') ?>" class="btn btn-outline-secondary w-100 py-3">
                        <i class="bi bi-layout-text-window"></i> Footer Details
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Communication -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold" style="color: var(--hunter-green);">
                <i class="bi bi-bell me-2"></i>Communication
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="<?= app_url('admin/notification/owner/send_to_owner.php') ?>" class="btn btn-outline-warning w-100 py-3">
                        <i class="bi bi-send"></i> Notify Owners
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('admin/notification/customer/send_to_customer.php') ?>" class="btn btn-outline-warning w-100 py-3">
                        <i class="bi bi-send"></i> Notify Customers
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
