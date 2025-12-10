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

    $roomId = intval($_POST['room_id'] ?? 0);
    
    if ($roomId > 0) {
        $pdo = get_pdo();
        try {
            $stmt = $pdo->prepare("SELECT owner_id FROM room WHERE room_id = ?");
            $stmt->execute([$roomId]);
            $ownerId = $stmt->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM room WHERE room_id = ?");
            $stmt->execute([$roomId]);
            
            if ($stmt->rowCount() > 0) {
                if ($ownerId) {
                    increment_package_quota($ownerId, 'room');
                }
                header('Location: delete_room.php?owner_id=' . $ownerId . '&success=Room deleted successfully');
                exit;
            } else {
                 header('Location: delete_room.php?owner_id=' . $ownerId . '&error=Room not found');
                 exit;
            }
        } catch (Exception $e) {
             header('Location: delete_room.php?owner_id=' . $ownerId . '&error=Error: ' . urlencode($e->getMessage()));
             exit;
        }
    }
}

$pdo = get_pdo();
$owners = $pdo->query("SELECT user_id, name, email FROM user WHERE role_id = 3 ORDER BY name ASC")->fetchAll();
$rooms = [];

$selectedOwnerId = isset($_GET['owner_id']) ? intval($_GET['owner_id']) : 0;

if ($selectedOwnerId) {
    $stmt = $pdo->prepare("SELECT r.room_id, r.title, r.created_at, rt.type_name, rl.address,
                           (SELECT image_path FROM room_image WHERE room_id = r.room_id AND primary_image = 1 LIMIT 1) as primary_image
                           FROM room r 
                           LEFT JOIN room_type rt ON r.room_type_id = rt.type_id
                           LEFT JOIN room_location rl ON r.room_id = rl.room_id
                           WHERE r.owner_id = ? 
                           ORDER BY r.created_at DESC");
    $stmt->execute([$selectedOwnerId]);
    $rooms = $stmt->fetchAll();
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Room - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="delete_room.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <?php require_once __DIR__ . '/../../../public/navbar/navbar.php'; ?>

    <div class="container py-5">
        <h2 class="fw-bold mb-4">Delete Rooms</h2>
        
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
            <?php if (empty($rooms)): ?>
                <div class="alert alert-info">No rooms found for this owner.</div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($rooms as $room): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm">
                                <img src="<?= $room['primary_image'] ? app_url($room['primary_image']) : 'https://via.placeholder.com/400x250?text=Room' ?>" class="card-img-top" alt="<?= htmlspecialchars($room['title']) ?>" style="height: 200px; object-fit: cover;">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($room['title']) ?></h5>
                                    <p class="mb-1 text-muted small"><?= htmlspecialchars($room['type_name'] ?? 'Unknown Type') ?></p>
                                    <p class="mb-2 small"><i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($room['address'] ?? 'No Address') ?></p>
                                    <button class="btn btn-danger w-100 mt-3" onclick="confirmDelete(<?= $room['room_id'] ?>)">Delete Room</button>
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
        <input type="hidden" name="room_id" id="deleteId">
    </form>

    <script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="delete_room.js"></script>
</body>
</html>
