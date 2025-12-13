<?php
// maintenance.lock content structure:
// JSON: { "blocked_roles": [3, 4, 0], "message": "Optional custom message" }
// 0 = Guest/Unauthenticated
// 1 = Super Admin (Never blocked)
// 2 = Admin
// 3 = Owner
// 4 = Customer

require_once __DIR__ . '/../../config/db.php';

ensure_session_started();
$user = current_user();

// Check Super Admin
if (!$user || $user['role_id'] != 1) {
    header('Location: ' . app_url('index.php'));
    exit;
}

$lockFile = __DIR__ . '/../../maintenance.lock';

$message = $_SESSION['_flash']['success'] ?? $_SESSION['_flash']['warning'] ?? null;
$status = isset($_SESSION['_flash']['success']) ? 'success' : 'warning';
unset($_SESSION['_flash']['success'], $_SESSION['_flash']['warning']);

// Handle Toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_maintenance'])) {
        $action = $_POST['toggle_maintenance'];
        
        if ($action === 'disable') {
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
            $_SESSION['_flash']['success'] = "Maintenance mode disabled. Site is live.";
        } elseif ($action === 'enable') {
            $blockedRoles = $_POST['blocked_roles'] ?? [];
            
            // To ensure 0 (Guest) is handled correctly as a string '0' from POST
            $data = [
                'blocked_roles' => array_map('intval', $blockedRoles)
            ];
            
            file_put_contents($lockFile, json_encode($data));
            $_SESSION['_flash']['warning'] = "Maintenance mode enabled for selected user types.";
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Read current state
$isMaintenanceMode = file_exists($lockFile);
$currentBlockedRoles = [];

if ($isMaintenanceMode) {
    $content = file_get_contents($lockFile);
    // Legacy support: if file just contains text, assume default block (all non-super-admin)
    // Legacy support: if file just contains text, assume default block (all non-super-admin)
    $data = json_decode($content, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
        $currentBlockedRoles = $data['blocked_roles'] ?? [];
    } else {
        // Fallback or legacy file
        $currentBlockedRoles = [2, 3, 4, 0]; // Block everyone logic check
    }
} else {
    // Default selection for new enable: Block Everyone (Guest, Customer, Owner, Admin)
    $currentBlockedRoles = [0, 4, 3, 2];
}

// Role Definitions for UI
$roles = [
    0 => ['name' => 'Guests (Unregistered)', 'icon' => 'bi-person'],
    4 => ['name' => 'Customers', 'icon' => 'bi-people'],
    3 => ['name' => 'Owners', 'icon' => 'bi-briefcase'],
    2 => ['name' => 'Admins', 'icon' => 'bi-shield-lock']
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Control - Rental Lanka</title>
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .role-check-card {
            border: 2px solid #e9ecef;
            transition: all 0.2s;
            cursor: pointer;
        }
        .role-check-card:hover {
            border-color: #dee2e6;
            background-color: #f8f9fa;
        }
        .role-check-input:checked + .role-check-card {
            border-color: #ffc107;
            background-color: #fff3cd;
        }
        .role-check-input:checked + .role-check-card .bi {
            color: #856404;
        }
    </style>
</head>
<body class="bg-light">

<?php require __DIR__ . '/../../public/navbar/navbar.php'; ?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-cone-striped me-2 text-warning"></i>Maintenance Mode Control</h4>
                </div>
                <div class="card-body p-5">
                    
                    <?php if (isset($message)): ?>
                        <div class="alert alert-<?= $status ?> mb-4"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>

                    <div class="text-center mb-5">
                        <?php if ($isMaintenanceMode): ?>
                            <div class="display-1 text-warning mb-3"><i class="bi bi-toggle-on"></i></div>
                            <h3 class="text-danger fw-bold">System Offline (Restricted)</h3>
                            <p class="text-muted">Users in the selected groups below cannot access the site.</p>
                        <?php else: ?>
                            <div class="display-1 text-success mb-3"><i class="bi bi-toggle-off"></i></div>
                            <h3 class="text-success fw-bold">System Online (Live)</h3>
                            <p class="text-muted">The website is currently accessible to everyone.</p>
                        <?php endif; ?>
                    </div>

                    <form method="post" action="">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-3 d-block text-center">Select who should see the "Under Maintenance" page:</label>
                            <div class="row g-3 justify-content-center">
                                <?php foreach ($roles as $id => $role): ?>
                                    <div class="col-6 col-md-3">
                                        <label class="w-100 h-100">
                                            <input type="checkbox" name="blocked_roles[]" value="<?= $id ?>" 
                                                class="d-none role-check-input" 
                                                <?= in_array($id, $currentBlockedRoles) ? 'checked' : '' ?>
                                                <?= !$isMaintenanceMode ? 'checked' : '' ?> 
                                            >
                                            <div class="card role-check-card h-100 p-3 text-center rounded-3">
                                                <i class="bi <?= $role['icon'] ?> fs-1 mb-2 d-block text-muted"></i>
                                                <span class="fw-semibold small"><?= $role['name'] ?></span>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text text-center mt-2 text-muted">Super Admins (You) will always have access.</div>
                        </div>

                        <div class="d-grid gap-2 col-md-8 mx-auto">
                            <?php if ($isMaintenanceMode): ?>
                                <button type="submit" name="toggle_maintenance" value="disable" class="btn btn-success btn-lg rounded-pill shadow-sm">
                                    <i class="bi bi-play-circle-fill me-2"></i> Disable Maintenance (Go Live)
                                </button>
                                <button type="submit" name="toggle_maintenance" value="enable" class="btn btn-outline-warning mt-2 rounded-pill border-0">
                                    <i class="bi bi-arrow-clockwise me-2"></i> Update Blocked Roles
                                </button>
                            <?php else: ?>
                                <button type="submit" name="toggle_maintenance" value="enable" class="btn btn-warning btn-lg rounded-pill shadow-sm text-dark" onclick="return confirm('Are you sure you want to enable maintenance mode for selected roles?');">
                                    <i class="bi bi-pause-circle-fill me-2"></i> Enable Maintenance
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
