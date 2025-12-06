<?php
require __DIR__ . '/../../../config/db.php';

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
$vehicleId = intval($_GET['id'] ?? 0);

if (!$vehicleId) {
    header('Location: ' . app_url('admin/index/index.php'));
    exit;
}

// Fetch vehicle details
$stmt = $pdo->prepare("SELECT v.*, u.name as owner_name, u.email as owner_email, u.mobile_number as owner_phone,
    vt.type_name, ls.status_name,
    vl.address, vl.postal_code, vl.google_map_link
    FROM vehicle v
    LEFT JOIN user u ON v.owner_id = u.user_id
    LEFT JOIN vehicle_type vt ON v.vehicle_type_id = vt.type_id
    LEFT JOIN listing_status ls ON v.status_id = ls.status_id
    LEFT JOIN vehicle_location vl ON v.vehicle_id = vl.vehicle_id
    WHERE v.vehicle_id = ?");
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    header('Location: ' . app_url('admin/index/index.php'));
    exit;
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
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="card-title mb-2"><?= htmlspecialchars($vehicle['title']) ?></h2>
                    <p class="text-muted mb-2">Vehicle Code: <strong><?= htmlspecialchars($vehicle['vehicle_code']) ?></strong></p>
                </div>
                <span class="badge bg-<?= $vehicle['status_id'] == 1 ? 'success' : ($vehicle['status_id'] == 4 ? 'warning' : 'danger') ?> fs-6">
                    <?= htmlspecialchars($vehicle['status_name']) ?>
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Vehicle Image Placeholder -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Vehicle Image</h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-5">
                        <img src="https://via.placeholder.com/600x400?text=Vehicle+Image" class="img-fluid vehicle-main-image" alt="Vehicle image">
                    </div>
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
                            'driver_available' => 'Driver Available'
                        ];
                        foreach ($features as $key => $label):
                            if ($vehicle[$key]):
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
                <div class="card-body">
                    <p class="mb-2"><strong>Name:</strong> <?= htmlspecialchars($vehicle['owner_name']) ?></p>
                    <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($vehicle['owner_email']) ?></p>
                    <p class="mb-0"><strong>Phone:</strong> <?= htmlspecialchars($vehicle['owner_phone']) ?></p>
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
