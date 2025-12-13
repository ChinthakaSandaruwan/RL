<?php
require __DIR__ . '/../../../config/db.php';
require __DIR__ . '/../../notification/owner/property_approval_notification/property_approval_notification_auto.php';

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
    $propertyId = intval($_POST['property_id'] ?? 0);

    // Fetch Property & Owner Details
    $stmt = $pdo->prepare("SELECT p.title, p.owner_id, u.email, u.name 
                           FROM property p 
                           JOIN user u ON p.owner_id = u.user_id 
                           WHERE p.property_id = ?");
    $stmt->execute([$propertyId]);
    $propInfo = $stmt->fetch();

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE property SET status_id = 1 WHERE property_id = ?");
        $stmt->execute([$propertyId]);
        if ($propInfo) {
            notify_owner_property_status($propInfo['owner_id'], $propInfo['title'], 'approved');
        }

        $_SESSION['_flash']['success'] = 'Property approved successfully!';
        // Redirect to prevent form resubmission
        header('Location: ' . app_url('admin/property/approval/property_approval.php'));
        exit;
    } elseif ($action === 'reject') {
        if ($propInfo) {
            notify_owner_property_status($propInfo['owner_id'], $propInfo['title'], 'rejected');
            // Refund the quota since it was rejected
            increment_package_quota($propInfo['owner_id'], 'property');
        }
        
        // Delete the property instead of marking as status 3
        $stmt = $pdo->prepare("DELETE FROM property WHERE property_id = ?");
        $stmt->execute([$propertyId]);

        $_SESSION['_flash']['success'] = 'Property rejected.';
        // Redirect to prevent form resubmission
        header('Location: ' . app_url('admin/property/approval/property_approval.php'));
        exit;
    }
}

// Fetch pending properties
$stmt = $pdo->query("SELECT p.*, u.name as owner_name, u.email as owner_email, pt.type_name,
    (SELECT image_path FROM property_image WHERE property_id = p.property_id AND primary_image = 1 LIMIT 1) as primary_image,
    pl.address, pl.postal_code
    FROM property p
    LEFT JOIN user u ON p.owner_id = u.user_id
    LEFT JOIN property_type pt ON p.property_type_id = pt.type_id
    LEFT JOIN property_location pl ON p.property_id = pl.property_id
    WHERE p.status_id = 4
    ORDER BY p.created_at DESC");
$properties = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Property Approval - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="property_approval.css">
</head>
<body>

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="mb-0 fw-bold text-primary">Property Approval Queue</h4>
        </div>
        <div class="card-body p-4">
            
            <?php if (empty($properties)): ?>
                <div class="alert alert-info">No pending properties at this time.</div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($properties as $property): ?>
                        <div class="col-12">
                            <div class="card property-card">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <img src="<?= $property['primary_image'] ? app_url($property['primary_image']) : 'https://via.placeholder.com/400x300?text=No+Image' ?>" 
                                             class="img-fluid property-image" alt="<?= htmlspecialchars($property['title']) ?>">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h5 class="card-title mb-0"><?= htmlspecialchars($property['title']) ?></h5>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            </div>
                                            
                                            <p class="text-muted mb-2">
                                                <strong>Owner:</strong> <?= htmlspecialchars($property['owner_name']) ?> (<?= htmlspecialchars($property['owner_email']) ?>)
                                            </p>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Type:</strong> <?= htmlspecialchars($property['type_name'] ?? 'N/A') ?></p>
                                                    <p class="mb-1"><strong>Price:</strong> LKR <?= number_format($property['price_per_month'], 2) ?>/month</p>
                                                    <p class="mb-1"><strong>Area:</strong> <?= number_format($property['sqft'], 2) ?> sqft</p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Bedrooms:</strong> <?= $property['bedrooms'] ?></p>
                                                    <p class="mb-1"><strong>Bathrooms:</strong> <?= $property['bathrooms'] ?></p>
                                                    <p class="mb-1"><strong>Location:</strong> <?= htmlspecialchars($property['address'] ?? 'N/A') ?></p>
                                                </div>
                                            </div>
                                            
                                            <p class="card-text mb-3"><small class="text-muted"><?= htmlspecialchars(substr($property['description'], 0, 150)) ?>...</small></p>
                                            
                                            <div class="d-flex gap-2">
                                                <a href="<?= app_url('admin/property/view/property_view.php?id=' . $property['property_id']) ?>" class="btn btn-info">
                                                    <i class="bi bi-eye"></i> View Details
                                                </a>

                                                <form method="post" style="display:inline;" class="approve-form">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                    <input type="hidden" name="property_id" value="<?= $property['property_id'] ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-success approve-btn">
                                                        <i class="bi bi-check-circle"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="post" style="display:inline;" class="reject-form">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                    <input type="hidden" name="property_id" value="<?= $property['property_id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-danger reject-btn" onclick="return confirm('Are you sure you want to REJECT this property? This will DELETE the listing and refund the owner\'s package quota. This action cannot be undone.')">
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
<script src="property_approval.js"></script>
</body>
</html>
