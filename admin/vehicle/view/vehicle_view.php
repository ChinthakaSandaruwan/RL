<?php
require __DIR__ . '/../../../config/db.php';

ensure_session_started();
$user = current_user();

// Check if user is Admin (role_id = 2)
// Check if user is Admin (2) or Super Admin (1)
if (!$user || !in_array($user['role_id'], [1, 2])) {
    header('Location: ' . app_url('index.php'));
    exit;
}
$csrf_token = generate_csrf_token();

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$pdo = get_pdo();
$vehicleId = intval($_GET['id'] ?? 0);

if (!$vehicleId) {
    header('Location: ' . app_url('admin/index/index.php'));
    exit;
}

// Fetch vehicle details
$stmt = $pdo->prepare("SELECT v.*, u.name as owner_name, u.email as owner_email, u.mobile_number as owner_phone, u.profile_image as owner_image,
    vt.type_name, ls.status_name,
    vb.brand_name as make, vm.model_name as model,
    ft.type_name as fuel_type,
    vl.address, vl.postal_code, vl.google_map_link
    FROM vehicle v
    LEFT JOIN user u ON v.owner_id = u.user_id
    LEFT JOIN vehicle_type vt ON v.vehicle_type_id = vt.type_id
    LEFT JOIN listing_status ls ON v.status_id = ls.status_id
    LEFT JOIN vehicle_location vl ON v.vehicle_id = vl.vehicle_id
    LEFT JOIN vehicle_model vm ON v.model_id = vm.model_id
    LEFT JOIN vehicle_brand vb ON vm.brand_id = vb.brand_id
    LEFT JOIN fuel_type ft ON v.fuel_type_id = ft.type_id
    WHERE v.vehicle_id = ?");
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    header('Location: ' . app_url('admin/index/index.php'));
    exit;
}

// Fetch Images
$stmt = $pdo->prepare("SELECT * FROM vehicle_image WHERE vehicle_id = ? ORDER BY primary_image DESC");
$stmt->execute([$vehicleId]);
$images = $stmt->fetchAll();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die("Invalid CSRF");
    
    $action = $_POST['action'] ?? '';
    require_once __DIR__ . '/../../notification/owner/vehicle_approval_notification/vehicle_approval_notification_auto.php';

    if ($action === 'approve') {
        $pdo->prepare("UPDATE vehicle SET status_id = 1 WHERE vehicle_id = ?")->execute([$vehicleId]);
        notify_owner_vehicle_status($vehicle['owner_id'], $vehicle['title'], 'approved');
        header("Location: ".app_url("admin/vehicle/view/vehicle_view.php?id=$vehicleId"));
        exit;
    } elseif ($action === 'reject') {
        notify_owner_vehicle_status($vehicle['owner_id'], $vehicle['title'], 'rejected');
        increment_package_quota($vehicle['owner_id'], 'vehicle');
        $pdo->prepare("DELETE FROM vehicle WHERE vehicle_id = ?")->execute([$vehicleId]);
        header("Location: ".app_url("admin/vehicle/approval/vehicle_approval.php?success=rejected"));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Vehicle - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="vehicle_view.css">
</head>
<body>

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="mb-4">
        <a href="<?= app_url('admin/vehicle/approval/vehicle_approval.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Approvals
        </a>
    </div>

    <!-- Vehicle Header -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="card-title mb-1"><?= htmlspecialchars($vehicle['title']) ?></h2>
                    <p class="text-muted mb-0">Vehicle Code: <strong><?= htmlspecialchars($vehicle['vehicle_code']) ?></strong></p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($vehicle['status_id'] == 4): // Pending ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="action" value="approve">
                            <button class="btn btn-success"><i class="bi bi-check-circle"></i> Approve</button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="action" value="reject">
                            <button class="btn btn-danger" onclick="return confirm('Are you sure you want to REJECT this listing? It will be DELETED.')"><i class="bi bi-x-circle"></i> Reject</button>
                        </form>
                    <?php endif; ?>
                    <span class="badge bg-<?= $vehicle['status_id'] == 1 ? 'success' : ($vehicle['status_id'] == 4 ? 'warning' : 'danger') ?> fs-6">
                        <?= htmlspecialchars($vehicle['status_name']) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Vehicle Images -->
            <div class="card shadow-sm mb-4 overflow-hidden">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Vehicle Images</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($images)): ?>
                        <div id="vehicleCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach ($images as $k => $img): ?>
                                    <div class="carousel-item <?= $k === 0 ? 'active' : '' ?>">
                                        <img src="<?= app_url($img['image_path']) ?>" class="d-block w-100" alt="Vehicle Image" style="height: 400px; object-fit: cover;">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($images) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#vehicleCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#vehicleCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <img src="https://via.placeholder.com/600x400?text=No+Image+Available" class="img-fluid" alt="No Image">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Description -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Description</h5>
                </div>
                <div class="card-body">
                    <p><?= nl2br(htmlspecialchars($vehicle['description'] ?? 'No description provided.')) ?></p>
                </div>
            </div>

            <!-- Features -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Features</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php
                        $features = [
                            'air_conditioning' => 'Air Conditioning',
                            'gps' => 'GPS Navigation',
                            'bluetooth' => 'Bluetooth',
                            'child_seat' => 'Child Seat Available',
                            'usb_charger' => 'USB Charger',
                            'spare_tyre' => 'Spare Tyre',
                            'insurance' => 'Insurance Included',
                            'is_driver_available' => 'Driver Available'
                        ];
                        foreach ($features as $key => $label):
                            if (!empty($vehicle[$key])):
                        ?>
                            <div class="col-6 col-md-4">
                                <div class="feature-badge">
                                    <i class="bi bi-check-circle-fill text-success"></i> <?= $label ?>
                                </div>
                            </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Sidebar -->
        <div class="col-lg-4">
            <!-- Owner Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Owner Information</h5>
                </div>
                <div class="card-body text-center">
                    <?php 
                        $ownerImgSrc = !empty($vehicle['owner_image']) ? app_url($vehicle['owner_image']) : 'https://ui-avatars.com/api/?name='.urlencode($vehicle['owner_name']).'&background=random&color=ffffff&size=150';
                    ?>
                    <img src="<?= $ownerImgSrc ?>" class="rounded-circle mb-3" alt="Owner" style="width: 100px; height: 100px; object-fit: cover;">
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($vehicle['owner_name']) ?></h5>
                    <p class="text-muted small mb-3">Vehicle Owner</p>
                    
                    <div class="d-grid gap-2">
                        <a href="mailto:<?= htmlspecialchars($vehicle['owner_email']) ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-envelope"></i> Email
                        </a>
                        <a href="tel:<?= htmlspecialchars($vehicle['owner_phone']) ?>" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-telephone"></i> Call
                        </a>
                    </div>
                </div>
            </div>

            <!-- Vehicle Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Vehicle Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Type:</strong></td>
                            <td><?= htmlspecialchars($vehicle['type_name'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Price:</strong></td>
                            <td>LKR <?= number_format($vehicle['price_per_day'], 2) ?>/day</td>
                        </tr>
                        <tr>
                            <td><strong>Make:</strong></td>
                            <td><?= htmlspecialchars($vehicle['make'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Model:</strong></td>
                            <td><?= htmlspecialchars($vehicle['model'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Year:</strong></td>
                            <td><?= $vehicle['year'] ?? 'N/A' ?></td>
                        </tr>
                        <tr>
                            <td><strong>Seats:</strong></td>
                            <td><?= $vehicle['number_of_seats'] ?? 'N/A' ?></td>
                        </tr>
                        <tr>
                            <td><strong>Fuel Type:</strong></td>
                            <td><?= htmlspecialchars($vehicle['fuel_type'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Listed:</strong></td>
                            <td><?= date('M j, Y', strtotime($vehicle['created_at'])) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Location -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Location</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Address:</strong><br><?= htmlspecialchars($vehicle['address'] ?? 'N/A') ?></p>
                    <p class="mb-0"><strong>Postal Code:</strong> <?= htmlspecialchars($vehicle['postal_code'] ?? 'N/A') ?></p>
                    <?php if ($vehicle['google_map_link']): ?>
                        <hr>
                        <a href="<?= htmlspecialchars($vehicle['google_map_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-geo-alt"></i> View on Map
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="vehicle_view.js"></script>
</body>
</html>
