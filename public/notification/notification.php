<?php
require_once __DIR__ . '/../../config/db.php';
ensure_session_started();

$user = current_user();
if (!$user) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();

// Mark notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notifId = intval($_POST['notification_id']);
    $stmt = $pdo->prepare("UPDATE notification SET is_read = 1, read_at = NOW() WHERE notification_id = ? AND user_id = ?");
    $stmt->execute([$notifId, $user['user_id']]);
    header('Location: ' . app_url('public/notification/notification.php'));
    exit;
}

// Mark all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notification SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user['user_id']]);
    header('Location: ' . app_url('public/notification/notification.php'));
    exit;
}

// Delete notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notification'])) {
    $notifId = intval($_POST['notification_id']);
    $stmt = $pdo->prepare("DELETE FROM notification WHERE notification_id = ? AND user_id = ?");
    $stmt->execute([$notifId, $user['user_id']]);
    header('Location: ' . app_url('public/notification/notification.php'));
    exit;
}

// Fetch notifications (paginated)
$page = intval($_GET['page'] ?? 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT n.*, nt.type_name 
    FROM notification n
    LEFT JOIN notification_type nt ON n.type_id = nt.type_id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$user['user_id'], $perPage, $offset]);
$notifications = $stmt->fetchAll();

// Count total
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM notification WHERE user_id = ?");
$totalStmt->execute([$user['user_id']]);
$total = $totalStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Unread count
$unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM notification WHERE user_id = ? AND is_read = 0");
$unreadStmt->execute([$user['user_id']]);
$unreadCount = $unreadStmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="notification.css">
</head>
<body class="bg-light">

    <?php require_once __DIR__ . '/../navbar/navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark"><i class="fas fa-bell me-2"></i> Notifications</h2>
                <p class="text-muted mb-0">You have <?= $unreadCount ?> unread notification<?= $unreadCount != 1 ? 's' : '' ?></p>
            </div>
            <?php if ($unreadCount > 0): ?>
            <form method="POST">
                <button type="submit" name="mark_all_read" class="btn btn-outline-primary">
                    <i class="fas fa-check-double me-2"></i> Mark All as Read
                </button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="card shadow-sm border-0 text-center py-5">
                <div class="card-body">
                    <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No Notifications</h5>
                    <p class="text-muted">You're all caught up!</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow-sm border-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="list-group-item notification-item <?= $notif['is_read'] ? 'read' : 'unread' ?>">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="notification-icon me-3">
                                            <i class="fas fa-info-circle"></i>
                                        </span>
                                        <div>
                                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($notif['title']) ?></h6>
                                            <small class="text-muted">
                                                <i class="far fa-clock me-1"></i>
                                                <?= date('M j, Y • h:i A', strtotime($notif['created_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <p class="mb-2 ms-5"><?= nl2br(htmlspecialchars($notif['message'])) ?></p>
                                </div>
                                    <div class="d-flex align-items-center">
                                        <?php if (!$notif['is_read']): ?>
                                            <form method="POST" class="ms-3">
                                                <input type="hidden" name="notification_id" value="<?= $notif['notification_id'] ?>">
                                                <button type="submit" name="mark_read" class="btn btn-sm btn-outline-success" title="Mark as read">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" class="ms-2" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                            <input type="hidden" name="notification_id" value="<?= $notif['notification_id'] ?>">
                                            <button type="submit" name="delete_notification" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Notification pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="notification.js"></script>
</body>
</html>
