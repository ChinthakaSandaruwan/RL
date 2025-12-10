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

    $propertyId = intval($_POST['property_id'] ?? 0);
    
    if ($propertyId > 0) {
        $pdo = get_pdo();
        try {
            $stmt = $pdo->prepare("SELECT owner_id FROM property WHERE property_id = ?");
            $stmt->execute([$propertyId]);
            $ownerId = $stmt->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM property WHERE property_id = ?");
            $stmt->execute([$propertyId]);
            
            if ($stmt->rowCount() > 0) {
                if ($ownerId) {
                    increment_package_quota($ownerId, 'property');
                }
                header('Location: delete_property.php?owner_id=' . $ownerId . '&success=Property deleted successfully');
                exit;
            } else {
                 header('Location: delete_property.php?owner_id=' . $ownerId . '&error=Property not found');
                 exit;
            }
        } catch (Exception $e) {
             header('Location: delete_property.php?owner_id=' . $ownerId . '&error=Error: ' . urlencode($e->getMessage()));
             exit;
        }
    }
}

$pdo = get_pdo();
$owners = $pdo->query("SELECT user_id, name, email FROM user WHERE role_id = 3 ORDER BY name ASC")->fetchAll();
$properties = [];

$selectedOwnerId = isset($_GET['owner_id']) ? intval($_GET['owner_id']) : 0;

if ($selectedOwnerId) {
    $stmt = $pdo->prepare("SELECT p.property_id, p.title, p.created_at, pt.type_name, pl.address,
                           (SELECT image_path FROM property_image WHERE property_id = p.property_id AND primary_image = 1 LIMIT 1) as primary_image
                           FROM property p
                           LEFT JOIN property_type pt ON p.property_type_id = pt.type_id
                           LEFT JOIN property_location pl ON p.property_id = pl.property_id
                           WHERE p.owner_id = ?
                           ORDER BY p.created_at DESC");
    $stmt->execute([$selectedOwnerId]);
    $properties = $stmt->fetchAll();
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Property - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="delete_property.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <?php require_once __DIR__ . '/../../../public/navbar/navbar.php'; ?>

    <div class="container py-5">
        <h2 class="fw-bold mb-4">Delete Properties</h2>
        
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
            <?php if (empty($properties)): ?>
                <div class="alert alert-info">No properties found for this owner.</div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($properties as $property): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm">
                                <img src="<?= $property['primary_image'] ? app_url($property['primary_image']) : 'https://via.placeholder.com/400x250?text=Property' ?>" class="card-img-top" alt="<?= htmlspecialchars($property['title']) ?>" style="height: 200px; object-fit: cover;">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($property['title']) ?></h5>
                                    <p class="mb-1 text-muted small"><?= htmlspecialchars($property['type_name'] ?? 'Unknown Type') ?></p>
                                    <p class="mb-2 small"><i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($property['address'] ?? 'No Address') ?></p>
                                    <button class="btn btn-danger w-100 mt-3" onclick="confirmDelete(<?= $property['property_id'] ?>)">Delete Property</button>
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
        <input type="hidden" name="property_id" id="deleteId">
    </form>

    <script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="delete_property.js"></script>
</body>
</html>
