<?php
require __DIR__ . '/../../config/db.php';

ensure_session_started();
$user = current_user();

if (!$user) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$pdo = get_pdo();
$errors = [];
$success = null;
$csrf_token = generate_csrf_token();

// Check if user already has a pending request
$stmt = $pdo->prepare("SELECT * FROM user_type_change_request WHERE user_id = ? AND status_id = 1");
$stmt->execute([$user['user_id']]);
$pendingRequest = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pendingRequest) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF Token');
    }

    $reason = trim($_POST['reason'] ?? '');
    
    if (strlen($reason) < 20) {
        $errors[] = 'Please provide a detailed reason (minimum 20 characters).';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO user_type_change_request (user_id, current_role_id, requested_role_id, reason, status_id) VALUES (?, ?, 3, ?, 1)");
        $stmt->execute([$user['user_id'], $user['role_id'], $reason]);
        $success = 'Your request has been submitted successfully. An admin will review it shortly.';
        
        // Reload to show pending state
        $stmt = $pdo->prepare("SELECT * FROM user_type_change_request WHERE user_id = ? AND status_id = 1");
        $stmt->execute([$user['user_id']]);
        $pendingRequest = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Owner Account - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="send_user_type_change_request.css">
</head>
<body>

<?php require __DIR__ . '/../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0 fw-bold text-success">Request Owner Account</h4>
                </div>
                <div class="card-body p-4">
                    
                    <?php if ($pendingRequest): ?>
                        <div class="alert alert-info">
                            <h5 class="alert-heading"><i class="bi bi-clock"></i> Request Pending</h5>
                            <p class="mb-0">You have already submitted a request to become an Owner. Please wait for admin approval.</p>
                            <hr>
                            <p class="mb-0"><strong>Submitted:</strong> <?= date('F j, Y g:i A', strtotime($pendingRequest['created_at'])) ?></p>
                        </div>
                    <?php else: ?>
                        
                        <div class="mb-4">
                            <h5>Current Role: <span class="badge bg-secondary"><?= htmlspecialchars($user['role_name'] ?? 'Customer') ?></span></h5>
                            <p class="text-muted">Upgrade to <span class="badge bg-success">Owner</span> to list properties, rooms, and vehicles.</p>
                        </div>

                        <form method="post" id="requestForm">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Why do you want to become an Owner? <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control" rows="5" placeholder="Please explain why you want to list properties/vehicles on Rental Lanka..." required></textarea>
                                <div class="form-text">Minimum 20 characters</div>
                            </div>
                            
                            <div class="alert alert-warning">
                                <strong>Note:</strong> Once approved, you will be able to create property, room, and vehicle listings. This action requires admin approval.
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?= app_url('index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-success px-4">Submit Request</button>
                            </div>
                        </form>
                        
                    <?php endif; ?>
                </div>
            </div>
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
<script src="send_user_type_change_request.js"></script>
</body>
</html>
