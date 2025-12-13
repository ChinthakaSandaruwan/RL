<?php
require_once __DIR__ . '/../../../config/db.php';
ensure_session_started();
$user = current_user();

if (!$user || $user['role_id'] != 3) {
    header('Location: ' . app_url('auth/login.php'));
    exit;
}

$pdo = get_pdo();

// Fetch Rooms
$stmt = $pdo->prepare("
    SELECT r.room_id, r.title, r.room_code, r.created_at, 
           ls.status_name, ls.status_id,
           (SELECT image_path FROM room_image WHERE room_id = r.room_id ORDER BY primary_image DESC LIMIT 1) as image_path
    FROM room r
    JOIN listing_status ls ON r.status_id = ls.status_id
    WHERE r.owner_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$user['user_id']]);
$rooms = $stmt->fetchAll();

$csrf_token = generate_csrf_token();

// Handle AJAX Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF Token']);
        exit;
    }

    ob_clean();
    header('Content-Type: application/json');
    
    $roomId = intval($_POST['id']);
    $newStatus = intval($_POST['status_id']);
    
    $check = $pdo->prepare("SELECT status_id FROM room WHERE room_id = ? AND owner_id = ?");
    $check->execute([$roomId, $user['user_id']]);
    $current = $check->fetch();
    
    if (!$current) {
        echo json_encode(['success' => false, 'message' => 'Room not found']);
        exit;
    }
    
    if ($current['status_id'] == 4) {
        echo json_encode(['success' => false, 'message' => 'Cannot change status of Pending listing.']);
        exit;
    }
    
    $upd = $pdo->prepare("UPDATE room SET status_id = ? WHERE room_id = ?");
    if ($upd->execute([$newStatus, $roomId])) {
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
    <title>Room Status - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $csrf_token ?>">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="status.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<?php require_once __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">My Rooms Status</h2>
        <a href="<?= app_url('owner/index/index.php') ?>" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Room</th>
                            <th>Code</th>
                            <th>Posted Date</th>
                            <th>Current Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rooms)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">No rooms found.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= !empty($room['image_path']) ? app_url($room['image_path']) : 'https://via.placeholder.com/60' ?>" 
                                             class="img-thumb me-3" alt="Room">
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($room['title']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($room['room_code']) ?></span></td>
                                <td><?= date('M d, Y', strtotime($room['created_at'])) ?></td>
                                <td>
                                    <?php 
                                    $sClass = match($room['status_id']) {
                                        1 => 'bg-success',
                                        2 => 'bg-info',
                                        3 => 'bg-secondary',
                                        4 => 'bg-warning text-dark',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge rounded-pill <?= $sClass ?> status-badge"><?= htmlspecialchars($room['status_name']) ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if ($room['status_id'] == 4): ?>
                                        <button class="btn btn-sm btn-secondary" disabled>Pending Approval</button>
                                    <?php else: ?>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                Change Status
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item status-action" href="#" data-id="<?= $room['room_id'] ?>" data-status="1">Mark Available</a></li>
                                                <li><a class="dropdown-item status-action" href="#" data-id="<?= $room['room_id'] ?>" data-status="2">Mark Rented</a></li>
                                                <li><a class="dropdown-item status-action" href="#" data-id="<?= $room['room_id'] ?>" data-status="3">Mark Unavailable</a></li>
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
