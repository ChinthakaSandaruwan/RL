<?php
require_once __DIR__ . '/../../../config/db.php';
ensure_session_started();

$user = current_user();
if (!$user || !in_array($user['role_id'], [1, 2])) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF Token');
    }

    $vehicleId = intval($_POST['vehicle_id'] ?? 0);
    
    if ($vehicleId > 0) {
        $pdo = get_pdo();
        try {
            $stmt = $pdo->prepare("SELECT owner_id FROM vehicle WHERE vehicle_id = ?");
            $stmt->execute([$vehicleId]);
            $ownerId = $stmt->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM vehicle WHERE vehicle_id = ?");
            $stmt->execute([$vehicleId]);
            
            if ($stmt->rowCount() > 0) {
                if ($ownerId) {
                    increment_package_quota($ownerId, 'vehicle');
                }
                header('Location: delete_vehicle.php?owner_id=' . $ownerId . '&success=Vehicle deleted successfully');
                exit;
            } else {
                 header('Location: delete_vehicle.php?owner_id=' . $ownerId . '&error=Vehicle not found');
                 exit;
            }
        } catch (Exception $e) {
             header('Location: delete_vehicle.php?owner_id=' . $ownerId . '&error=Error: ' . urlencode($e->getMessage()));
             exit;
        }
    }
}

$pdo = get_pdo();
$owners = $pdo->query("SELECT user_id, name, email FROM user WHERE role_id = 3 ORDER BY name ASC")->fetchAll();
$vehicles = [];

$selectedOwnerId = isset($_GET['owner_id']) ? intval($_GET['owner_id']) : 0;

if ($selectedOwnerId) {
    $stmt = $pdo->prepare("SELECT v.vehicle_id, v.title, v.created_at, vt.type_name, vl.address,
                           (SELECT image_path FROM vehicle_image WHERE vehicle_id = v.vehicle_id AND primary_image = 1 LIMIT 1) as primary_image
                           FROM vehicle v 
                           LEFT JOIN vehicle_type vt ON v.vehicle_type_id = vt.type_id
                           LEFT JOIN vehicle_location vl ON v.vehicle_id = vl.vehicle_id
                           WHERE v.owner_id = ? 
                           ORDER BY v.created_at DESC");
    $stmt->execute([$selectedOwnerId]);
    $vehicles = $stmt->fetchAll();
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Vehicle - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="delete_vehicle.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <?php require_once __DIR__ . '/../../../public/navbar/navbar.php'; ?>

    <div class="container py-5">
        <h2 class="fw-bold mb-4">Delete Vehicles</h2>
        
        <?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label">Select Owner</label>
                        <select name="owner_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Select an Owner --</option>
                            <?php foreach ($owners as $owner): ?>
                                <option value="<?= $owner['user_id'] ?>" <?= $selectedOwnerId == $owner['user_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($owner['name']) ?> (<?= htmlspecialchars($owner['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($selectedOwnerId): ?>
            <?php if (empty($vehicles)): ?>
                <div class="alert alert-info">No vehicles found for this owner.</div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($vehicles as $vehicle): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm">
                                <img src="<?= $vehicle['primary_image'] ? app_url($vehicle['primary_image']) : 'https://via.placeholder.com/400x250?text=Vehicle' ?>" class="card-img-top" alt="<?= htmlspecialchars($vehicle['title']) ?>" style="height: 200px; object-fit: cover;">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($vehicle['title']) ?></h5>
                                    <p class="mb-1 text-muted small"><?= htmlspecialchars($vehicle['type_name'] ?? 'Unknown Type') ?></p>
                                    <p class="mb-2 small"><i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($vehicle['address'] ?? 'No Address') ?></p>
                                    <button class="btn btn-danger w-100 mt-3" onclick="confirmDelete(<?= $vehicle['vehicle_id'] ?>)">Delete Vehicle</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <form id="deleteForm" method="POST" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        <input type="hidden" name="vehicle_id" id="deleteId">
    </form>

    <script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="delete_vehicle.js"></script>
</body>
</html>
