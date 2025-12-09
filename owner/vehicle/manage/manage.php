<?php
require __DIR__ . '/../../../config/db.php';
ensure_session_started();
$user = current_user();

if (!$user || !in_array($user['role_id'], [3])) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();

// Fetch Vehicles
$stmt = $pdo->prepare("
    SELECT v.*, vt.type_name, vb.brand_name, c.name_en as city_name,
           (SELECT image_path FROM vehicle_image WHERE vehicle_id = v.vehicle_id AND primary_image = 1 LIMIT 1) as main_image
    FROM vehicle v
    LEFT JOIN vehicle_type vt ON v.vehicle_type_id = vt.type_id
    LEFT JOIN vehicle_model vm ON v.model_id = vm.model_id
    LEFT JOIN vehicle_brand vb ON vm.brand_id = vb.brand_id
    LEFT JOIN vehicle_location vl ON v.vehicle_id = vl.vehicle_id
    LEFT JOIN cities c ON vl.city_id = c.id
    WHERE v.owner_id = ?
    ORDER BY v.created_at DESC
");
$stmt->execute([$user['user_id']]);
$vehicles = $stmt->fetchAll();

$statusMap = [
    1 => ['label' => 'Available', 'class' => 'bg-success'],
    2 => ['label' => 'Rented', 'class' => 'bg-secondary'],
    3 => ['label' => 'Inactive', 'class' => 'bg-warning'],
    4 => ['label' => 'Pending', 'class' => 'bg-info']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Vehicles - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= app_url('public/profile/profile.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .vehicle-img-thumb { width: 80px; height: 60px; object-fit: cover; border-radius: 6px; }
        .action-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; transition: all 0.2s; }
        .action-btn:hover { transform: translateY(-2px); }
    </style>
</head>
<body>

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5 profile-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">My Vehicles</h2>
        <a href="create/vehicle_create.php" class="btn btn-primary shadow-sm" style="background-color: var(--fern); border-color: var(--fern);">
            <i class="bi bi-plus-lg me-2"></i>Add New Vehicle
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $_SESSION['success'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $_SESSION['error'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3">Vehicle</th>
                            <th>Type</th>
                            <th>Price / Day</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vehicles)): ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">No vehicles found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <?php 
                                    $imgSrc = $vehicle['main_image'] ? app_url($vehicle['main_image']) : app_url('public/assets/images/no-image-placeholder.jpg');
                                    $viewLink = "read/vehicle_read.php?id=" . $vehicle['vehicle_id'];
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <a href="<?= $viewLink ?>"><img src="<?= $imgSrc ?>" alt="Vehicle" class="vehicle-img-thumb border me-3"></a>
                                            <div>
                                                <h6 class="mb-0 fw-semibold text-truncate" style="max-width: 200px;">
                                                    <a href="<?= $viewLink ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($vehicle['title']) ?></a>
                                                </h6>
                                                <small class="text-muted"><?= htmlspecialchars($vehicle['brand_name'] ?? 'N/A') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($vehicle['type_name'] ?? 'N/A') ?></span></td>
                                    <td class="fw-medium"><?= number_format($vehicle['price_per_day'], 2) ?></td>
                                    <td><?= htmlspecialchars($vehicle['city_name'] ?? 'Unknown') ?></td>
                                    <td>
                                        <span class="badge <?= $statusMap[$vehicle['status_id']]['class'] ?? 'bg-secondary' ?> rounded-pill px-3">
                                            <?= $statusMap[$vehicle['status_id']]['label'] ?? 'Unknown' ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted"><?= date('M d, Y', strtotime($vehicle['created_at'])) ?></td>
                                    <td class="text-end pe-4">
                                        <a href="<?= $viewLink ?>" class="btn btn-sm btn-outline-info action-btn me-1"><i class="bi bi-eye-fill"></i></a>
                                        <a href="update/vehicle_update.php?id=<?= $vehicle['vehicle_id'] ?>" class="btn btn-sm btn-outline-primary action-btn me-1"><i class="bi bi-pencil-fill"></i></a>
                                        <a href="delete/vehicle_delete.php?id=<?= $vehicle['vehicle_id'] ?>" class="btn btn-sm btn-outline-danger action-btn" onclick="return confirm('Delete this vehicle?')"><i class="bi bi-trash-fill"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
