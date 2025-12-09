<?php
require __DIR__ . '/../../../config/db.php';
ensure_session_started();
$user = current_user();

// Check if user is admin (role_id = 2)
if (!$user || $user['role_id'] != 2) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();
$errors = [];
$success = null;

// Get package ID
$packageId = intval($_GET['id'] ?? 0);

if (!$packageId) {
    header('Location: ' . app_url('admin/index/index.php'));
    exit;
}

// Fetch package data
$stmt = $pdo->prepare("SELECT * FROM package WHERE package_id = ?");
$stmt->execute([$packageId]);
$package = $stmt->fetch();

if (!$package) {
    header('Location: ' . app_url('admin/index/index.php'));
    exit;
}

// Check if package is being used
$stmt = $pdo->prepare("
    SELECT COUNT(*) as usage_count 
    FROM bought_package 
    WHERE package_id = ?
");
$stmt->execute([$packageId]);
$usageData = $stmt->fetch();
$usageCount = $usageData['usage_count'];

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF Token');
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'soft_delete') {
            // Soft delete - just set status to inactive
            $stmt = $pdo->prepare("UPDATE package SET status_id = 2 WHERE package_id = ?");
            $stmt->execute([$packageId]);
            
            $_SESSION['admin_message'] = "Package deactivated successfully!";
            header('Location: ' . app_url('admin/index/index.php'));
            exit;
            
        } elseif ($action === 'hard_delete') {
            if ($usageCount > 0) {
                $errors[] = "Cannot delete package that has been purchased by owners. Please deactivate instead.";
            } else {
                // Hard delete - completely remove from database
                $stmt = $pdo->prepare("DELETE FROM package WHERE package_id = ?");
                $stmt->execute([$packageId]);
                
                $_SESSION['admin_message'] = "Package deleted permanently!";
                header('Location: ' . app_url('admin/index/index.php'));
                exit;
            }
        }
    } catch (Exception $e) {
        $errors[] = "Error: " . $e->getMessage();
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Package - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="delete_package.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-danger"><i class="bi bi-trash me-2"></i>Delete Package</h2>
                    <p class="text-muted mb-0">Remove or deactivate this package</p>
                </div>
                <a href="<?= app_url('admin/index/index.php') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Package Info Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Package Details</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Package Name:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($package['package_name']) ?></dd>

                        <dt class="col-sm-4">Price:</dt>
                        <dd class="col-sm-8">LKR <?= number_format($package['price'], 2) ?></dd>

                        <dt class="col-sm-4">Duration:</dt>
                        <dd class="col-sm-8"><?= $package['duration_days'] ? $package['duration_days'] . ' days' : 'Lifetime' ?></dd>

                        <dt class="col-sm-4">Properties:</dt>
                        <dd class="col-sm-8"><?= $package['max_properties'] ?></dd>

                        <dt class="col-sm-4">Rooms:</dt>
                        <dd class="col-sm-8"><?= $package['max_rooms'] ?></dd>

                        <dt class="col-sm-4">Vehicles:</dt>
                        <dd class="col-sm-8"><?= $package['max_vehicles'] ?></dd>

                        <dt class="col-sm-4">Status:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-<?= $package['status_id'] == 1 ? 'success' : 'secondary' ?>">
                                <?= $package['status_id'] == 1 ? 'Active' : 'Inactive' ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4">Created:</dt>
                        <dd class="col-sm-8"><?= date('M d, Y', strtotime($package['created_at'])) ?></dd>
                    </dl>
                </div>
            </div>

            <!-- Usage Warning -->
            <?php if ($usageCount > 0): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Warning:</strong> This package has been purchased by <strong><?= $usageCount ?></strong> owner(s).
                    Hard deletion is disabled for safety.
                </div>
            <?php endif; ?>

            <!-- Action Cards -->
            <div class="row g-3">
                <!-- Soft Delete -->
                <div class="col-md-6">
                    <div class="card h-100 border-warning">
                        <div class="card-body text-center">
                            <i class="bi bi-pause-circle text-warning" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">Deactivate</h5>
                            <p class="text-muted small">Hide package from owners but keep data</p>
                            <form method="POST" class="delete-form" data-type="soft">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="action" value="soft_delete">
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="bi bi-pause-circle me-1"></i> Deactivate Package
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Hard Delete -->
                <div class="col-md-6">
                    <div class="card h-100 border-danger">
                        <div class="card-body text-center">
                            <i class="bi bi-trash text-danger" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">Delete Permanently</h5>
                            <p class="text-muted small">Remove package completely from database</p>
                            <form method="POST" class="delete-form" data-type="hard">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="action" value="hard_delete">
                                <button type="submit" class="btn btn-danger w-100" 
                                        <?= $usageCount > 0 ? 'disabled' : '' ?>>
                                    <i class="bi bi-trash me-1"></i> Delete Forever
                                </button>
                            </form>
                            <?php if ($usageCount > 0): ?>
                                <small class="text-muted d-block mt-2">Disabled due to existing purchases</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="<?= app_url('admin/index/index.php') ?>" class="btn btn-outline-secondary px-5">
                    <i class="bi bi-x-circle me-1"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="delete_package.js"></script>
</body>
</html>
