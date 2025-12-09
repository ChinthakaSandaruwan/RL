<?php
require __DIR__ . '/../../../config/db.php';
ensure_session_started();
$user = current_user();

// Check if user is admin (role_id = 2)
if (!$user || $user['role_id'] != 2) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();

// Get package ID
$packageId = intval($_GET['id'] ?? 0);

if (!$packageId) {
    header('Location: ' . app_url('admin/ads_package/manage/manage.php'));
    exit;
}

// Fetch package data with type
$stmt = $pdo->prepare("
    SELECT p.*, pt.type_name
    FROM package p
    JOIN package_type pt ON p.package_type_id = pt.type_id
    WHERE p.package_id = ?
");
$stmt->execute([$packageId]);
$package = $stmt->fetch();

if (!$package) {
    header('Location: ' . app_url('admin/ads_package/manage/manage.php'));
    exit;
}

// Get purchase statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_purchases,
        SUM(CASE WHEN status_id = 1 THEN 1 ELSE 0 END) as active_subscriptions,
        SUM(CASE WHEN payment_status_id = 1 THEN 1 ELSE 0 END) as pending_payments
    FROM bought_package
    WHERE package_id = ?
");
$stmt->execute([$packageId]);
$stats = $stmt->fetch();

// Get recent buyers
$stmt = $pdo->prepare("
    SELECT bp.*, u.name, u.email
    FROM bought_package bp
    JOIN user u ON bp.user_id = u.user_id
    WHERE bp.package_id = ?
    ORDER BY bp.bought_date DESC
    LIMIT 10
");
$stmt->execute([$packageId]);
$recentBuyers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Package Details - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="read_package.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold"><i class="bi bi-box-seam me-2"></i>Package Details</h2>
                    <p class="text-muted mb-0">View package information and statistics</p>
                </div>
                <a href="<?= app_url('admin/ads_package/manage/manage.php') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Packages
                </a>
            </div>

            <!-- Package Info Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" style="color: var(--hunter-green);">
                        <i class="bi bi-info-circle me-2"></i><?= htmlspecialchars($package['package_name']) ?>
                    </h5>
                    <span class="badge bg-<?= $package['status_id'] == 1 ? 'success' : 'secondary' ?> px-3 py-2">
                        <?= $package['status_id'] == 1 ? 'Active' : 'Inactive' ?>
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Package Type</label>
                                <div class="info-value"><?= htmlspecialchars($package['type_name']) ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Price</label>
                                <div class="info-value">LKR <?= number_format($package['price'], 2) ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Duration</label>
                                <div class="info-value">
                                    <?= $package['duration_days'] ? $package['duration_days'] . ' days' : 'Lifetime' ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Created Date</label>
                                <div class="info-value"><?= date('M d, Y', strtotime($package['created_at'])) ?></div>
                            </div>
                        </div>
                        <?php if ($package['description']): ?>
                        <div class="col-12">
                            <div class="info-item">
                                <label class="info-label">Description</label>
                                <div class="info-value"><?= nl2br(htmlspecialchars($package['description'])) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Listing Limits -->
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h6 class="mb-0" style="color: var(--hunter-green);">
                                <i class="bi bi-list-stars me-2"></i>Listing Limits
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="limit-item">
                                <i class="bi bi-house-door text-primary fs-4"></i>
                                <div>
                                    <div class="limit-value"><?= $package['max_properties'] ?></div>
                                    <div class="limit-label">Properties</div>
                                </div>
                            </div>
                            <div class="limit-item">
                                <i class="bi bi-door-closed text-success fs-4"></i>
                                <div>
                                    <div class="limit-value"><?= $package['max_rooms'] ?></div>
                                    <div class="limit-label">Rooms</div>
                                </div>
                            </div>
                            <div class="limit-item">
                                <i class="bi bi-car-front text-warning fs-4"></i>
                                <div>
                                    <div class="limit-value"><?= $package['max_vehicles'] ?></div>
                                    <div class="limit-label">Vehicles</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h6 class="mb-0" style="color: var(--hunter-green);">
                                <i class="bi bi-graph-up me-2"></i>Statistics
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="stat-item">
                                <div class="stat-icon bg-primary">
                                    <i class="bi bi-cart-check"></i>
                                </div>
                                <div>
                                    <div class="stat-value"><?= $stats['total_purchases'] ?></div>
                                    <div class="stat-label">Total Purchases</div>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon bg-success">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div>
                                    <div class="stat-value"><?= $stats['active_subscriptions'] ?></div>
                                    <div class="stat-label">Active Subscriptions</div>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon bg-warning">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <div>
                                    <div class="stat-value"><?= $stats['pending_payments'] ?></div>
                                    <div class="stat-label">Pending Payments</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Buyers -->
            <?php if (!empty($recentBuyers)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0" style="color: var(--hunter-green);">
                        <i class="bi bi-people me-2"></i>Recent Buyers
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Owner</th>
                                    <th>Email</th>
                                    <th>Purchased</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBuyers as $buyer): ?>
                                <tr>
                                    <td><?= htmlspecialchars($buyer['name']) ?></td>
                                    <td><?= htmlspecialchars($buyer['email']) ?></td>
                                    <td><?= date('M d, Y', strtotime($buyer['bought_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $buyer['status_id'] == 1 ? 'success' : 'secondary' ?>">
                                            <?= $buyer['status_id'] == 1 ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= app_url('admin/ads_package/update/update_package.php?id=' . $packageId) ?>" 
                   class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
                <a href="<?= app_url('admin/ads_package/delete/delete_package.php?id=' . $packageId) ?>" 
                   class="btn btn-outline-danger">
                    <i class="bi bi-trash me-1"></i> Delete
                </a>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="read_package.js"></script>
</body>
</html>
