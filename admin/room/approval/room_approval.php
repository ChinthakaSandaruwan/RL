<?php
require __DIR__ . '/../../../config/db.php';
require __DIR__ . '/../../notification/owner/room_approval_notification/room_approval_notification_auto.php';

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
$errors = [];
$success = null;
$csrf_token = generate_csrf_token();

// Handle Approve/Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF Token');
    }

    $action = $_POST['action'] ?? '';
    $roomId = intval($_POST['room_id'] ?? 0);

    // Fetch Room & Owner Details
    $stmt = $pdo->prepare("SELECT r.title, r.owner_id, u.email, u.name 
                           FROM room r 
                           JOIN user u ON r.owner_id = u.user_id 
                           WHERE r.room_id = ?");
    $stmt->execute([$roomId]);
    $roomInfo = $stmt->fetch();

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE room SET status_id = 1 WHERE room_id = ?");
        $stmt->execute([$roomId]);
        
        if ($roomInfo) {
            notify_owner_room_status($roomInfo['owner_id'], $roomInfo['title'], 'approved');
        }
        
        // Redirect to prevent form resubmission
        header('Location: ' . app_url('admin/room/approval/room_approval.php?success=approved'));
        exit;
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE room SET status_id = 3 WHERE room_id = ?");
        $stmt->execute([$roomId]);
        
        if ($roomInfo) {
            notify_owner_room_status($roomInfo['owner_id'], $roomInfo['title'], 'rejected');
            // Refund the quota
            increment_package_quota($roomInfo['owner_id'], 'room');
        }
        
        // Redirect to prevent form resubmission
        header('Location: ' . app_url('admin/room/approval/room_approval.php?success=rejected'));
        exit;
    }
}

// Handle success messages from GET parameters
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'approved') {
        $success = 'Room approved successfully!';
    } elseif ($_GET['success'] === 'rejected') {
        $success = 'Room rejected.';
    }
}


// Fetch pending rooms
$stmt = $pdo->query("SELECT r.*, u.name as owner_name, u.email as owner_email, rt.type_name,
    rl.address, rl.postal_code,
    (SELECT image_path FROM room_image WHERE room_id = r.room_id AND primary_image = 1 LIMIT 1) as primary_image
    FROM room r
    LEFT JOIN user u ON r.owner_id = u.user_id
    LEFT JOIN room_type rt ON r.room_type_id = rt.type_id
    LEFT JOIN room_location rl ON r.room_id = rl.room_id
    WHERE r.status_id = 4
    ORDER BY r.created_at DESC");
$rooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Approval - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="room_approval.css">
</head>
<body>

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="mb-0 fw-bold text-primary">Room Approval Queue</h4>
        </div>
        <div class="card-body p-4">
            
            <?php if (empty($rooms)): ?>
                <div class="alert alert-info">No pending rooms at this time.</div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($rooms as $room): ?>
                        <div class="col-12">
                            <div class="card room-card">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <img src="<?= $room['primary_image'] ? app_url($room['primary_image']) : 'https://via.placeholder.com/400x300?text=No+Image' ?>" 
                                             class="img-fluid room-image" alt="<?= htmlspecialchars($room['title']) ?>">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h5 class="card-title mb-0"><?= htmlspecialchars($room['title']) ?></h5>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            </div>
                                            
                                            <p class="text-muted mb-2">
                                                <strong>Owner:</strong> <?= htmlspecialchars($room['owner_name']) ?> (<?= htmlspecialchars($room['owner_email']) ?>)
                                            </p>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Type:</strong> <?= htmlspecialchars($room['type_name'] ?? 'N/A') ?></p>
                                                    <p class="mb-1"><strong>Price:</strong> LKR <?= number_format($room['price_per_day'], 2) ?>/day</p>
                                                    <p class="mb-1"><strong>Beds:</strong> <?= $room['beds'] ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Bathrooms:</strong> <?= $room['bathrooms'] ?></p>
                                                    <p class="mb-1"><strong>Max Guests:</strong> <?= $room['maximum_guests'] ?? 'N/A' ?></p>
                                                    <p class="mb-1"><strong>Location:</strong> <?= htmlspecialchars($room['address'] ?? 'N/A') ?></p>
                                                </div>
                                            </div>
                                            
                                            <p class="card-text mb-3"><small class="text-muted"><?= htmlspecialchars(substr($room['description'], 0, 150)) ?>...</small></p>
                                            
                                            <div class="d-flex gap-2">
                                                <a href="<?= app_url('admin/room/view/room_view.php?id=' . $room['room_id']) ?>" class="btn btn-info">
                                                    <i class="bi bi-eye"></i> View Details
                                                </a>

                                                <form method="post" style="display:inline;" class="approve-form">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                    <input type="hidden" name="room_id" value="<?= $room['room_id'] ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="button" class="btn btn-success approve-btn">
                                                        <i class="bi bi-check-circle"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="post" style="display:inline;" class="reject-form">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                    <input type="hidden" name="room_id" value="<?= $room['room_id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="button" class="btn btn-danger reject-btn">
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
<script src="room_approval.js"></script>
</body>
</html>
