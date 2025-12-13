<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../services/email.php';

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
$errors = $_SESSION['_flash']['errors'] ?? [];
$success = $_SESSION['_flash']['success'] ?? null;
unset($_SESSION['_flash']);
$csrf_token = generate_csrf_token();

// Handle Approve/Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['_flash']['errors'][] = 'Invalid CSRF Token';
    } else {
        $action = $_POST['action'] ?? '';
        $requestId = intval($_POST['request_id'] ?? 0);

        if ($action === 'approve') {
            // Get request details with user info
            $stmt = $pdo->prepare("
                SELECT r.*, u.name, u.email, ur.role_name as requested_role_name 
                FROM user_type_change_request r
                JOIN user u ON r.user_id = u.user_id
                LEFT JOIN user_role ur ON r.requested_role_id = ur.role_id
                WHERE r.request_id = ? AND r.status_id = 1
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if ($request) {
                $pdo->beginTransaction();
                try {
                    // Update user role
                    $stmt = $pdo->prepare("UPDATE user SET role_id = ? WHERE user_id = ?");
                    $stmt->execute([$request['requested_role_id'], $request['user_id']]);

                    // Update request status to Approved (2)
                    $stmt = $pdo->prepare("UPDATE user_type_change_request SET status_id = 2, processed_at = NOW(), processed_by = ? WHERE request_id = ?");
                    $stmt->execute([$user['user_id'], $requestId]);

                    // Send approval email
                    send_user_type_approved_email(
                        $request['email'], 
                        $request['name'], 
                        $request['requested_role_name']
                    );

                    $pdo->commit();
                    $_SESSION['_flash']['success'] = 'Request approved successfully and notification email sent.';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['_flash']['errors'][] = 'Failed to approve request: ' . $e->getMessage();
                }
            } else {
                 $_SESSION['_flash']['errors'][] = 'Request not found or already processed.';
            }
        } elseif ($action === 'reject') {
            // Get request details with user info for email
            $stmt = $pdo->prepare("
                SELECT r.*, u.name, u.email, ur.role_name as requested_role_name 
                FROM user_type_change_request r
                JOIN user u ON r.user_id = u.user_id
                LEFT JOIN user_role ur ON r.requested_role_id = ur.role_id
                WHERE r.request_id = ?
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if ($request) {
                // Update request status to Rejected (3)
                $stmt = $pdo->prepare("UPDATE user_type_change_request SET status_id = 3, processed_at = NOW(), processed_by = ? WHERE request_id = ?");
                $stmt->execute([$user['user_id'], $requestId]);

                // Send rejection email
                send_user_type_rejected_email(
                    $request['email'], 
                    $request['name'], 
                    $request['requested_role_name']
                );

                $_SESSION['_flash']['success'] = 'Request rejected and notification email sent.';
            }
        }
    }
    
    // Redirect (PRG)
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Fetch pending requests
$stmt = $pdo->query("SELECT r.*, u.name, u.email, u.mobile_number, ur1.role_name as `current_role`, ur2.role_name as `requested_role`
    FROM user_type_change_request r
    JOIN user u ON r.user_id = u.user_id
    LEFT JOIN user_role ur1 ON r.current_role_id = ur1.role_id
    LEFT JOIN user_role ur2 ON r.requested_role_id = ur2.role_id
    WHERE r.status_id = 1
    ORDER BY r.created_at DESC");
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Type Change Requests - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="sent_user_type_change_request.css">
</head>
<body>

<?php require __DIR__ . '/../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="mb-0 fw-bold text-primary">User Type Change Requests</h4>
        </div>
        <div class="card-body p-4">
            
            <?php if (empty($requests)): ?>
                <div class="alert alert-info">No pending requests at this time.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Current Role</th>
                                <th>Requested Role</th>
                                <th>Reason</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?= htmlspecialchars($request['name']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($request['email']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($request['mobile_number']) ?></small>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($request['current_role']) ?></span></td>
                                    <td><span class="badge bg-success"><?= htmlspecialchars($request['requested_role']) ?></span></td>
                                    <td><small><?= htmlspecialchars(substr($request['reason'], 0, 100)) ?>...</small></td>
                                    <td><small><?= date('M j, Y', strtotime($request['created_at'])) ?></small></td>
                                    <td>
                                        <form method="post" style="display:inline;" class="approve-form" data-request-id="<?= $request['request_id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="button" class="btn btn-sm btn-success approve-btn">Approve</button>
                                        </form>
                                        <form method="post" style="display:inline;" class="reject-form" data-request-id="<?= $request['request_id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="button" class="btn btn-sm btn-danger reject-btn">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
<script src="sent_user_type_change_request.js"></script>
</body>
</html>
