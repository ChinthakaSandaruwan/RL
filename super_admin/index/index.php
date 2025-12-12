<?php
require __DIR__ . '/../../config/db.php';

ensure_session_started();
$user = current_user();

// Check if user is Super Admin (role_id = 1)
if (!$user || $user['role_id'] != 1) {
    header('Location: ' . app_url('index.php'));
    exit;
}

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$pdo = get_pdo();



// Get stats
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM user WHERE role_id = 2")->fetchColumn();
$totalOwners = $pdo->query("SELECT COUNT(*) FROM user WHERE role_id = 3")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM user WHERE role_id = 4")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
$totalProperties = $pdo->query("SELECT COUNT(*) FROM property")->fetchColumn();
$totalRooms = $pdo->query("SELECT COUNT(*) FROM room")->fetchColumn();
$totalVehicles = $pdo->query("SELECT COUNT(*) FROM vehicle")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= app_url('public/favicon/android-chrome-192x192.png') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= app_url('public/favicon/android-chrome-512x512.png') ?>">
    <link rel="shortcut icon" href="<?= app_url('public/favicon/favicon.ico') ?>">
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">

    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= app_url('super_admin/index/index.css') ?>">
</head>
<body>

<?php require __DIR__ . '/../../public/navbar/navbar.php'; ?>

<div class="super-admin-header">
    <div class="container">
        <h1 class="display-5 fw-bold text-white">Super Admin Dashboard</h1>
        <p class="lead mb-0 text-white-50">Welcome, <?= htmlspecialchars($user['name']) ?></p>
    </div>
</div>

<div class="container pb-5">
    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card stat-card shadow-sm h-100 border-success">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Total Admins</h6>
                    <h2 class="fw-bold text-success"><?= number_format($totalAdmins) ?></h2>
                    <small class="text-muted">System Administrators</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm h-100 border-primary">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Total Owners</h6>
                    <h2 class="fw-bold text-primary"><?= number_format($totalOwners) ?></h2>
                    <small class="text-muted">Property/Vehicle Owners</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm h-100 border-success">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Total Customers</h6>
                    <h2 class="fw-bold text-success"><?= number_format($totalCustomers) ?></h2>
                    <small class="text-muted">Registered Customers</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card shadow-sm h-100 border-warning">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Total Users</h6>
                    <h2 class="fw-bold text-warning"><?= number_format($totalUsers) ?></h2>
                    <small class="text-muted">All User Accounts</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Listing Statistics -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Properties</h6>
                    <h2 class="fw-bold text-info"><?= number_format($totalProperties) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Rooms</h6>
                    <h2 class="fw-bold text-info"><?= number_format($totalRooms) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Vehicles</h6>
                    <h2 class="fw-bold text-info"><?= number_format($totalVehicles) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold">Quick Actions</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="<?= app_url('super_admin/user/super_admin/super_admin.php') ?>" class="btn btn-outline-dark w-100 py-3">
                        <i class="bi bi-person-workspace"></i> Manage Super Admins
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('super_admin/user/admin/all_admins.php') ?>" class="btn btn-outline-success w-100 py-3">
                        <i class="bi bi-shield-lock"></i> Manage Admins
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('super_admin/promotion/role_change/role_change.php') ?>" class="btn btn-outline-primary w-100 py-3">
                        <i class="bi bi-person-gear"></i> Change User Role
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- System Management -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold">System Management</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
 <div class="col-md-3">
                    <a href="<?= app_url('super_admin/backup/database_backup/database_backup.php') ?>" class="btn btn-outline-dark w-100 py-3">
                        <i class="bi bi-database-down"></i> Database Backup
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('super_admin/backup/upload_backup/upload_backup.php') ?>" class="btn btn-outline-dark w-100 py-3">
                        <i class="bi bi-database-up"></i> Upload Backup
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('super_admin/maintain/control.php') ?>" class="btn btn-outline-warning text-dark w-100 py-3">
                        <i class="bi bi-cone-striped"></i> Maintenance Mode
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= app_url('super_admin/chat/control.php') ?>" class="btn btn-outline-primary w-100 py-3">
                        <i class="bi bi-chat-dots"></i> Chat Control
                    </a>
                </div>
                <!-- special holidays -->
                <div class="col-md-3">
                    <a href="<?= app_url('super_admin/special_holidays/holidays_manage.php') ?>" class="btn btn-outline-dark w-100 py-3">
                        <i class="bi bi-snow"></i> Special Holidays
                    </a>
                </div>
             

            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= app_url('super_admin/index/index.js') ?>"></script>

</body>
</html>
