<?php
require_once __DIR__ . '/../../../../../config/db.php';
ensure_session_started();

$user = current_user();
if (!$user || !in_array($user['role_id'], [1, 2])) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();
$error = $_SESSION['_flash']['error'] ?? '';
unset($_SESSION['_flash']);
$targetUser = null;

if (isset($_GET['id'])) {
    $targetId = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM user WHERE user_id = ? AND role_id = 3");
    $stmt->execute([$targetId]);
    $targetUser = $stmt->fetch();
}

if (!$targetUser) {
    $_SESSION['_flash']['error'] = 'Owner not found';
    header('Location: ../read/read_owner.php');
    exit;
}

$statuses = $pdo->query("SELECT * FROM user_status")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
         $_SESSION['_flash']['error'] = "Invalid CSRF Token";
         header("Location: " . $_SERVER['REQUEST_URI']);
         exit;
    } else {
        $newStatus = intval($_POST['status_id']);
        try {
            $stmt = $pdo->prepare("UPDATE user SET status_id = ? WHERE user_id = ?");
            $stmt->execute([$newStatus, $targetUser['user_id']]);
            
            $_SESSION['_flash']['success'] = 'Status updated successfully';
            header('Location: ../read/read_owner.php');
            exit;
        } catch (Exception $e) {
             $_SESSION['_flash']['error'] = "Update failed: " . $e->getMessage();
             header("Location: " . $_SERVER['REQUEST_URI']);
             exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Owner Status - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
</head>
<body class="bg-light">
    <?php require_once __DIR__ . '/../../../../../public/navbar/navbar.php'; ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Change Owner Status</h5></div>
                    <div class="card-body p-4">
                        <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                        <div class="d-flex align-items-center mb-4">
                            <div class="me-3">
                                <?php if($targetUser['profile_image']): ?>
                                    <img src="<?= app_url($targetUser['profile_image']) ?>" class="rounded-circle" width="60" height="60" style="object-fit:cover;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:60px; height:60px; font-size:1.5rem;"><?= strtoupper(substr($targetUser['name'], 0, 1)) ?></div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($targetUser['name']) ?></h6>
                                <p class="mb-0 text-muted small"><?= htmlspecialchars($targetUser['email']) ?></p>
                            </div>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <div class="mb-4"><label class="form-label">Select New Status</label>
                                <select name="status_id" class="form-select">
                                    <?php foreach($statuses as $st): ?><option value="<?= $st['status_id'] ?>" <?= $targetUser['status_id'] == $st['status_id'] ? 'selected' : '' ?>><?= htmlspecialchars($st['status_name']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="d-flex gap-2"><a href="../read/read_owner.php" class="btn btn-outline-secondary flex-grow-1">Cancel</a><button type="submit" class="btn btn-primary flex-grow-1">Update Status</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
