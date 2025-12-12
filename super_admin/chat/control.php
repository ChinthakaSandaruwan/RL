<?php
require_once __DIR__ . '/../../config/db.php';

ensure_session_started();
$user = current_user();

if (!$user || $user['role_id'] != 1) {
    header('Location: ' . app_url('index.php'));
    exit;
}

$chatMessage = null;
$chatStatus = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_chat'])) {
    $action = $_POST['toggle_chat'];
    if ($action === 'enable') {
        set_chat_enabled(true);
        $chatMessage = 'Live chat has been enabled for the site.';
        $chatStatus = 'success';
    } elseif ($action === 'disable') {
        set_chat_enabled(false);
        $chatMessage = 'Live chat has been disabled for all users.';
        $chatStatus = 'warning';
    }
}

$chatEnabled = is_chat_enabled();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Control - Rental Lanka</title>
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../../public/navbar/navbar.php'; ?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-chat-dots me-2 text-primary"></i>Live Chat Control
                    </h4>
                </div>
                <div class="card-body p-5">

                    <?php if ($chatMessage): ?>
                        <div class="alert alert-<?= $chatStatus ?> mb-4"><?= htmlspecialchars($chatMessage) ?></div>
                    <?php endif; ?>

                    <div class="text-center mb-4">
                        <?php if ($chatEnabled): ?>
                            <div class="display-1 text-success mb-3"><i class="bi bi-toggle-on"></i></div>
                            <h3 class="text-success fw-bold">Chat is Enabled</h3>
                            <p class="text-muted mb-0">Customers can see the chat widget and admins can use the chat panel.</p>
                        <?php else: ?>
                            <div class="display-1 text-danger mb-3"><i class="bi bi-toggle-off"></i></div>
                            <h3 class="text-danger fw-bold">Chat is Disabled</h3>
                            <p class="text-muted mb-0">Chat widget is hidden from the public side and chat actions are blocked.</p>
                        <?php endif; ?>
                    </div>

                    <form method="post" action="">
                        <div class="d-grid gap-2 col-md-8 mx-auto">
                            <?php if ($chatEnabled): ?>
                                <button type="submit" name="toggle_chat" value="disable" class="btn btn-danger btn-lg rounded-pill shadow-sm">
                                    <i class="bi bi-pause-circle-fill me-2"></i> Disable Live Chat
                                </button>
                            <?php else: ?>
                                <button type="submit" name="toggle_chat" value="enable" class="btn btn-success btn-lg rounded-pill shadow-sm">
                                    <i class="bi bi-play-circle-fill me-2"></i> Enable Live Chat
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="mt-4 text-center">
                        <a href="<?= app_url('super_admin/index/index.php') ?>" class="text-decoration-none text-secondary">Back to Dashboard</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
