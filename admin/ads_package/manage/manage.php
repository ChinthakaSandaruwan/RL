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

// Display session message if any
$message = $_SESSION['admin_message'] ?? null;
unset($_SESSION['admin_message']);

// Fetch all packages with statistics
$stmt = $pdo->query("
    SELECT p.*, pt.type_name,
           COUNT(DISTINCT bp.bought_package_id) as total_sales,
           SUM(CASE WHEN bp.status_id = 1 THEN 1 ELSE 0 END) as active_subscriptions
    FROM package p
    LEFT JOIN package_type pt ON p.package_type_id = pt.type_id
    LEFT JOIN bought_package bp ON p.package_id = bp.package_id
    GROUP BY p.package_id
    ORDER BY p.created_at DESC
");
$packages = $stmt->fetchAll();

// Get overall statistics
$totalPackages = count($packages);
$activePackages = count(array_filter($packages, fn($p) => $p['status_id'] == 1));
$totalRevenue = $pdo->query("
    SELECT SUM(p.price) as revenue
    FROM bought_package bp
    JOIN package p ON bp.package_id = p.package_id
    WHERE bp.payment_status_id IN (2, 3)
")->fetch()['revenue'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Packages - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --fern: #588157ff;
            --hunter-green: #3a5a40ff;
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .package-card {
            transition: all 0.2s;
            border-left: 4px solid var(--fern);
        }
        .package-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><i class="bi bi-box-seam me-2"></i>Package Management</h2>
            <p class="text-muted mb-0">Manage all advertising packages</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= app_url('admin/ads_package/create/create_package.php') ?>" 
               class="btn" style="background-color: var(--fern); border-color: var(--fern); color: white;">
                <i class="bi bi-plus-circle me-1"></i> Create New Package
            </a>
            <a href="<?= app_url('admin/index/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total Packages</p>
                            <h3 class="mb-0 fw-bold"><?= $totalPackages ?></h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-box-seam fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Active Packages</p>
                            <h3 class="mb-0 fw-bold text-success"><?= $activePackages ?></h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-check-circle fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total Revenue</p>
                            <h3 class="mb-0 fw-bold" style="color: var(--fern);">LKR <?= number_format($totalRevenue, 0) ?></h3>
                        </div>
                        <div style="background-color: rgba(88, 129, 87, 0.1);" class="p-3 rounded-circle">
                            <i class="bi bi-currency-dollar fs-4" style="color: var(--fern);"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Packages List -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0" style="color: var(--hunter-green);">
                <i class="bi bi-list-ul me-2"></i>All Packages
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($packages)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">No packages created yet.</p>
                    <a href="<?= app_url('admin/ads_package/create/create_package.php') ?>" 
                       class="btn" style="background-color: var(--fern); border-color: var(--fern); color: white;">
                        <i class="bi bi-plus-circle me-1"></i> Create First Package
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Package Name</th>
                                <th>Type</th>
                                <th>Price</th>
                                <th>Duration</th>
                                <th>Limits (P/R/V)</th>
                                <th>Sales</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $package): ?>
                            <tr class="package-row">
                                <td class="ps-4">
                                    <div class="fw-medium"><?= htmlspecialchars($package['package_name']) ?></div>
                                    <small class="text-muted">ID: <?= $package['package_id'] ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= htmlspecialchars($package['type_name']) ?></span>
                                </td>
                                <td class="fw-bold" style="color: var(--fern);">
                                    LKR <?= number_format($package['price'], 0) ?>
                                </td>
                                <td>
                                    <?= $package['duration_days'] ? $package['duration_days'] . 'd' : '∞' ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= $package['max_properties'] ?>/<?= $package['max_rooms'] ?>/<?= $package['max_vehicles'] ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= $package['total_sales'] ?> sold
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $package['status_id'] == 1 ? 'success' : 'secondary' ?>">
                                        <?= $package['status_id'] == 1 ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= app_url('admin/ads_package/read/read_package.php?id=' . $package['package_id']) ?>" 
                                           class="btn btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= app_url('admin/ads_package/update/update_package.php?id=' . $package['package_id']) ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= app_url('admin/ads_package/delete/delete_package.php?id=' . $package['package_id']) ?>" 
                                           class="btn btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
