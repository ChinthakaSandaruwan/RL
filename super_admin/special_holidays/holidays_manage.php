<?php
require_once __DIR__ . '/../../config/db.php';
ensure_session_started();
$user = current_user();

if (!$user || $user['role_id'] != 1) {
    header('Location: ' . app_url('index.php'));
    exit;
}

$holidays = [
    'christmas' => ['name' => 'Christmas', 'icon' => 'bi-snow2', 'color' => 'success'],
    'new_year' => ['name' => 'New Year', 'icon' => 'bi-stars', 'color' => 'warning'],
    'divali' => ['name' => 'Divali', 'icon' => 'bi-brightness-high', 'color' => 'danger']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $holiday = $_POST['holiday'] ?? '';
    $action = $_POST['action'] ?? '';
    
    if (array_key_exists($holiday, $holidays)) {
        $statusFile = __DIR__ . "/{$holiday}/status.json";
        $newState = ($action === 'enable');
        
        // Ensure dir exists (sanity check)
        if (!is_dir(dirname($statusFile))) {
            mkdir(dirname($statusFile), 0777, true);
        }
        
        file_put_contents($statusFile, json_encode(['enabled' => $newState]));
        
        // If enabling one, maybe disable others? Or allow multiple? 
        // Let's allow multiple for now, or user might want exclusivity.
        // Assuming exclusivity is better for full-screen animations.
        if ($newState) {
            foreach ($holidays as $key => $info) {
                if ($key !== $holiday) {
                    file_put_contents(__DIR__ . "/{$key}/status.json", json_encode(['enabled' => false]));
                }
            }
        }
        
        $_SESSION['_flash']['success'] = "Holiday '{$holidays[$holiday]['name']}' updated successfully.";
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Read statuses
$statuses = [];
foreach ($holidays as $key => $info) {
    $file = __DIR__ . "/{$key}/status.json";
    $statuses[$key] = false;
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        $statuses[$key] = $data['enabled'] ?? false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Holidays - Super Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../../public/navbar/navbar.php'; ?>

<div class="container mt-5">

    <?php if (isset($_SESSION['_flash']['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4">
            <i class="bi bi-check-circle me-2"></i> <?= $_SESSION['_flash']['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['_flash']['success']); ?>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col">
            <h2 class="fw-bold">Special Holidays Management</h2>
            <p class="text-muted">Enable animations for special occasions. Only one holiday can be active at a time.</p>
        </div>
        <div class="col-auto align-self-center">
            <a href="<?= app_url('super_admin/index/index.php') ?>" class="btn btn-outline-secondary">Back to Dashboard</a>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($holidays as $key => $info): ?>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0 <?= $statuses[$key] ? 'ring-2 ring-' . $info['color'] : '' ?>">
                    <div class="card-body text-center p-5">
                        <div class="mb-3">
                            <i class="bi <?= $info['icon'] ?> display-1 text-<?= $info['color'] ?>"></i>
                        </div>
                        <h3 class="card-title fw-bold mb-3"><?= $info['name'] ?></h3>
                        
                        <div class="mt-4">
                            <?php if ($statuses[$key]): ?>
                                <span class="badge bg-success mb-3">Active</span>
                                <form method="post">
                                    <input type="hidden" name="holiday" value="<?= $key ?>">
                                    <button type="submit" name="action" value="disable" class="btn btn-outline-danger w-100">
                                        Disable
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="badge bg-secondary mb-3">Inactive</span>
                                <form method="post">
                                    <input type="hidden" name="holiday" value="<?= $key ?>">
                                    <button type="submit" name="action" value="enable" class="btn btn-<?= $info['color'] ?> w-100 text-white">
                                        Enable
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
