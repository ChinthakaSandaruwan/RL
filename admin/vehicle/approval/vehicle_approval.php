<?php
require __DIR__ . '/../../../config/db.php';
require __DIR__ . '/../../notification/owner/vehicle_approval_notification/vehicle_approval_notification_auto.php';

ensure_session_started();
$user = current_user();

// Check if user is Admin (role_id = 2)
// Check if user is Admin (2) or Super Admin (1)
if (!$user || !in_array($user['role_id'], [1, 2])) {
    header('Location: ' . app_url('index.php'));
    exit;
}

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$pdo = get_pdo();
$errors = [];
$success = $_SESSION['_flash']['success'] ?? null;
unset($_SESSION['_flash']);
$csrf_token = generate_csrf_token();

// Handle Approve/Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF Token');
    }

    $action = $_POST['action'] ?? '';
    $vehicleId = intval($_POST['vehicle_id'] ?? 0);

    // Fetch Vehicle & Owner Details
    $stmt = $pdo->prepare("SELECT v.title, v.owner_id, u.email, u.name 
                           FROM vehicle v 
                           JOIN user u ON v.owner_id = u.user_id 
                           WHERE v.vehicle_id = ?");
    $stmt->execute([$vehicleId]);
    $vehicleInfo = $stmt->fetch();

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE vehicle SET status_id = 1 WHERE vehicle_id = ?");
        $stmt->execute([$vehicleId]);
        
        if ($vehicleInfo) {
            notify_owner_vehicle_status($vehicleInfo['owner_id'], $vehicleInfo['title'], 'approved');
        }
        
        $_SESSION['_flash']['success'] = 'Vehicle approved successfully!';
        // Redirect to prevent form resubmission
        header('Location: ' . app_url('admin/vehicle/approval/vehicle_approval.php'));
        exit;
    } elseif ($action === 'reject') {
        if ($vehicleInfo) {
            notify_owner_vehicle_status($vehicleInfo['owner_id'], $vehicleInfo['title'], 'rejected');
            // Refund the quota
            increment_package_quota($vehicleInfo['owner_id'], 'vehicle');
        }
        
        // Delete the vehicle instead of marking as status 3
        $stmt = $pdo->prepare("DELETE FROM vehicle WHERE vehicle_id = ?");
        $stmt->execute([$vehicleId]);
        
        $_SESSION['_flash']['success'] = 'Vehicle rejected.';
        // Redirect to prevent form resubmission
        header('Location: ' . app_url('admin/vehicle/approval/vehicle_approval.php'));
        exit;
    }
}

    // Fetch pending vehicles
    $stmt = $pdo->query("SELECT v.*, u.name as owner_name, u.email as owner_email, vt.type_name,
        vl.address, vl.postal_code,
        vb.brand_name as make, vm.model_name as model,
        (SELECT image_path FROM vehicle_image WHERE vehicle_id = v.vehicle_id ORDER BY primary_image DESC LIMIT 1) as image_path
        FROM vehicle v
        LEFT JOIN user u ON v.owner_id = u.user_id
        LEFT JOIN vehicle_type vt ON v.vehicle_type_id = vt.type_id
        LEFT JOIN vehicle_location vl ON v.vehicle_id = vl.vehicle_id
        LEFT JOIN vehicle_model vm ON v.model_id = vm.model_id
        LEFT JOIN vehicle_brand vb ON vm.brand_id = vb.brand_id
        WHERE v.status_id = 4
        ORDER BY v.created_at DESC");
    $vehicles = $stmt->fetchAll();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Vehicle Approval - Admin</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
        <link rel="stylesheet" href="vehicle_approval.css">
    </head>
    <body>
    
    <?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>
    
    <div class="container py-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h4 class="mb-0 fw-bold text-primary">Vehicle Approval Queue</h4>
            </div>
            <div class="card-body p-4">
                
                <?php if (empty($vehicles)): ?>
                    <div class="alert alert-info">No pending vehicles at this time.</div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($vehicles as $vehicle): ?>
                            <div class="col-12">
                                <div class="card vehicle-card">
                                    <div class="row g-0">
                                        <div class="col-md-4">
                                            <?php 
                                            // Determine image source
                                            $imgSrc = !empty($vehicle['image_path']) ? app_url($vehicle['image_path']) : 'https://via.placeholder.com/400x300?text=No+Image';
                                            ?>
                                            <img src="<?= $imgSrc ?>" 
                                                 class="img-fluid vehicle-image" alt="<?= htmlspecialchars($vehicle['title']) ?>"
                                                 style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                        <div class="col-md-8">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h5 class="card-title mb-0"><?= htmlspecialchars($vehicle['title']) ?></h5>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                </div>
                                                
                                                <p class="text-muted mb-2">
                                                    <strong>Owner:</strong> <?= htmlspecialchars($vehicle['owner_name']) ?> (<?= htmlspecialchars($vehicle['owner_email']) ?>)
                                                </p>
                                                
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p class="mb-1"><strong>Type:</strong> <?= htmlspecialchars($vehicle['type_name'] ?? 'N/A') ?></p>
                                                        <p class="mb-1"><strong>Price:</strong> LKR <?= number_format($vehicle['price_per_day'], 2) ?>/day</p>
                                                        <p class="mb-1"><strong>Make:</strong> <?= htmlspecialchars($vehicle['make'] ?? 'N/A') ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p class="mb-1"><strong>Model:</strong> <?= htmlspecialchars($vehicle['model'] ?? 'N/A') ?></p>
                                                        <p class="mb-1"><strong>Year:</strong> <?= $vehicle['year'] ?? 'N/A' ?></p>
                                                        <p class="mb-1"><strong>Location:</strong> <?= htmlspecialchars($vehicle['address'] ?? 'N/A') ?></p>
                                                    </div>
                                                </div>
                                            
                                            <p class="card-text mb-3"><small class="text-muted"><?= htmlspecialchars(substr($vehicle['description'], 0, 150)) ?>...</small></p>
                                            
                                            <div class="d-flex gap-2">
                                                <a href="<?= app_url('admin/vehicle/view/vehicle_view.php?id=' . $vehicle['vehicle_id']) ?>" class="btn btn-info">
                                                    <i class="bi bi-eye"></i> View Details
                                                </a>

                                                <form method="post" style="display:inline;" class="approve-form">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                    <input type="hidden" name="vehicle_id" value="<?= $vehicle['vehicle_id'] ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-success approve-btn">
                                                        <i class="bi bi-check-circle"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="post" style="display:inline;" class="reject-form">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                    <input type="hidden" name="vehicle_id" value="<?= $vehicle['vehicle_id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-danger reject-btn" onclick="return confirm('Are you sure you want to REJECT this vehicle? This will DELETE the listing and refund the owner\'s package quota. This action cannot be undone.')">
                                                        <i class="bi bi-x-circle"></i> Reject
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.serverMessages = {
        success: <?= json_encode($success) ?>,
        errors: <?= json_encode($errors) ?>
    };
</script>
<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="vehicle_approval.js"></script>
</body>
</html>
