<?php
require_once __DIR__ . '/../../../config/db.php';
ensure_session_started();
$user = current_user();

// Check Role
if (!$user || $user['role_id'] != 3) {
    header('Location: ' . app_url('auth/login.php'));
    exit;
}

$pdo = get_pdo();

// Fetch Properties
$stmt = $pdo->prepare("
    SELECT p.property_id, p.title, p.property_code, p.created_at, 
           ls.status_name, ls.status_id,
           (SELECT image_path FROM property_image WHERE property_id = p.property_id ORDER BY primary_image DESC LIMIT 1) as image_path
    FROM property p
    JOIN listing_status ls ON p.status_id = ls.status_id
    WHERE p.owner_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$user['user_id']]);
$properties = $stmt->fetchAll();

$csrf_token = generate_csrf_token();

// Handle AJAX Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF Token']);
        exit;
    }
    
    // Basic API response logic inside PHP file for simplicity or use separate file. 
    // Usually separate, but for single file structure:
    ob_clean();
    header('Content-Type: application/json');
    
    $propId = intval($_POST['id']);
    $newStatus = intval($_POST['status_id']);
    
    // Validate ownership
    $check = $pdo->prepare("SELECT status_id FROM property WHERE property_id = ? AND owner_id = ?");
    $check->execute([$propId, $user['user_id']]);
    $current = $check->fetch();
    
    if (!$current) {
        echo json_encode(['success' => false, 'message' => 'Property not found']);
        exit;
    }
    
    // Rules: Cannot change Pending (4) or Suspended/Banned without Admin
    if ($current['status_id'] == 4) {
        echo json_encode(['success' => false, 'message' => 'Cannot change status of Pending property.']);
        exit;
    }
    
    $upd = $pdo->prepare("UPDATE property SET status_id = ? WHERE property_id = ?");
    if ($upd->execute([$newStatus, $propId])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Property Status - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $csrf_token ?>">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="status.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<?php require_once __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">My Properties Status</h2>
        <a href="<?= app_url('owner/index/index.php') ?>" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Property</th>
                            <th>Code</th>
                            <th>Posted Date</th>
                            <th>Current Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($properties)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">No properties found.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($properties as $prop): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= !empty($prop['image_path']) ? app_url($prop['image_path']) : 'https://via.placeholder.com/60' ?>" 
                                             class="img-thumb me-3" alt="Prop">
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($prop['title']) ?></div>
                                            <!-- <small class="text-muted">ID: <?= $prop['property_id'] ?></small> -->
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($prop['property_code']) ?></span></td>
                                <td><?= date('M d, Y', strtotime($prop['created_at'])) ?></td>
                                <td>
                                    <?php 
                                    $sClass = match($prop['status_id']) {
                                        1 => 'bg-success',
                                        2 => 'bg-info',
                                        3 => 'bg-secondary',
                                        4 => 'bg-warning text-dark',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge rounded-pill <?= $sClass ?> status-badge"><?= htmlspecialchars($prop['status_name']) ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if ($prop['status_id'] == 4): ?>
                                        <button class="btn btn-sm btn-secondary" disabled>Pending Approval</button>
                                    <?php else: ?>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                Change Status
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item status-action" href="#" data-id="<?= $prop['property_id'] ?>" data-status="1">Mark Available</a></li>
                                                <li><a class="dropdown-item status-action" href="#" data-id="<?= $prop['property_id'] ?>" data-status="2">Mark Rented</a></li>
                                                <li><a class="dropdown-item status-action" href="#" data-id="<?= $prop['property_id'] ?>" data-status="3">Mark Unavailable</a></li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
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
<script src="status.js"></script>
</body>
</html>
