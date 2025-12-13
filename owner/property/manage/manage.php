<?php
require __DIR__ . '/../../../config/db.php';
ensure_session_started();
$user = current_user();

// Check Role (Owner = 3)
if (!$user || !in_array($user['role_id'], [3])) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();

// Fetch Properties
$stmt = $pdo->prepare("
    SELECT p.*, pt.type_name, pl.city_id, c.name_en as city_name, 
           (SELECT image_path FROM property_image WHERE property_id = p.property_id AND primary_image = 1 LIMIT 1) as main_image
    FROM property p
    LEFT JOIN property_type pt ON p.property_type_id = pt.type_id
    LEFT JOIN property_location pl ON p.property_id = pl.property_id
    LEFT JOIN cities c ON pl.city_id = c.id
    WHERE p.owner_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$user['user_id']]);
$properties = $stmt->fetchAll();

// Status Mapping
$statusMap = [
    1 => ['label' => 'Active', 'class' => 'bg-success'],
    2 => ['label' => 'Rented', 'class' => 'bg-secondary'],
    3 => ['label' => 'Inactive', 'class' => 'bg-warning'],
    4 => ['label' => 'Pending', 'class' => 'bg-info']
];
// Flash Data
$flashSuccess = $_SESSION['_flash']['success'] ?? $_SESSION['success'] ?? null;
$flashError = $_SESSION['_flash']['error'] ?? $_SESSION['error'] ?? null;
unset($_SESSION['_flash'], $_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Properties - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= app_url('public/profile/profile.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .property-img-thumb {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }
        .action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }
        .action-btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5 profile-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">My Properties</h2>
        <a href="create/property_create.php" class="btn btn-primary shadow-sm" style="background-color: var(--fern); border-color: var(--fern);">
            <i class="bi bi-plus-lg me-2"></i>Add New Property
        </a>
    </div>

    <!-- Feedback Messages -->
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <?= $flashSuccess ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <?= $flashError ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3">Property</th>
                            <th>Type</th>
                            <th>Price (LKR)</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($properties)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-house-door fs-1 d-block mb-3 opacity-25"></i>
                                    No properties found. Start by listing your first property!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($properties as $prop): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            // Handle Image Path (could be full URL or relative)
                                            $imgSrc = $prop['main_image'] ? app_url($prop['main_image']) : app_url('public/assets/images/no-image-placeholder.jpg');
                                            $viewLink = "read/property_read.php?id=" . $prop['property_id'];
                                            ?>
                                            <a href="<?= $viewLink ?>">
                                                <img src="<?= $imgSrc ?>" alt="Property" class="property-img-thumb border me-3">
                                            </a>
                                            <div>
                                                <h6 class="mb-0 fw-semibold text-truncate" style="max-width: 200px;">
                                                    <a href="<?= $viewLink ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($prop['title']) ?></a>
                                                </h6>
                                                <small class="text-muted"><?= $prop['sqft'] ?> sqft</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($prop['type_name'] ?? 'N/A') ?></span></td>
                                    <td class="fw-medium"><?= number_format($prop['price_per_month'], 2) ?></td>
                                    <td><?= htmlspecialchars($prop['city_name'] ?? 'Unknown') ?></td>
                                    <td>
                                        <?php 
                                            $status = $statusMap[$prop['status_id']] ?? ['label' => 'Unknown', 'class' => 'bg-secondary'];
                                        ?>
                                        <span class="badge <?= $status['class'] ?> rounded-pill px-3"><?= $status['label'] ?></span>
                                    </td>
                                    <td class="small text-muted"><?= date('M d, Y', strtotime($prop['created_at'])) ?></td>
                                    <td class="text-end pe-4">
                                        <a href="<?= $viewLink ?>" class="btn btn-sm btn-outline-info action-btn me-1" title="View Details">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                        <a href="update/property_update.php?id=<?= $prop['property_id'] ?>" class="btn btn-sm btn-outline-primary action-btn me-1" title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>

                                        <a href="delete/property_delete.php?id=<?= $prop['property_id'] ?>" 
                                           class="btn btn-sm btn-outline-danger action-btn" 
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this property? This action cannot be undone.');">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
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
