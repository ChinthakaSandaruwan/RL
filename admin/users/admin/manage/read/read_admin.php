<?php
require_once __DIR__ . '/../../../../../config/db.php';
ensure_session_started();

$currentUser = current_user();
// Only Super Admin (1) or Admin (2) can view
if (!$currentUser || !in_array($currentUser['role_id'], [1, 2])) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();
$success = $_SESSION['_flash']['success'] ?? '';
$error = $_SESSION['_flash']['error'] ?? '';
unset($_SESSION['_flash']);
$csrf_token = generate_csrf_token();

// Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['_flash']['error'] = "Invalid CSRF Token";
    } else {
        $adminIdToDelete = intval($_POST['admin_id']);
        
        // Prevent deleting self
        if ($adminIdToDelete === $currentUser['user_id']) {
            $_SESSION['_flash']['error'] = "You cannot delete your own account.";
        } else {
            // Check if target is Super Admin (Role 1) - Only Super Admin can delete Super Admin
            $stmt = $pdo->prepare("SELECT role_id FROM user WHERE user_id = ?");
            $stmt->execute([$adminIdToDelete]);
            $targetRole = $stmt->fetchColumn();
            
            if ($targetRole == 1 && $currentUser['role_id'] != 1) {
                $_SESSION['_flash']['error'] = "You do not have permission to delete a Super Admin.";
            } else {
                try {
                    $stmt = $pdo->prepare("DELETE FROM user WHERE user_id = ?");
                    $stmt->execute([$adminIdToDelete]);
                    $_SESSION['_flash']['success'] = "Admin removed successfully.";
                } catch (Exception $e) {
                    $_SESSION['_flash']['error'] = "Error: " . $e->getMessage();
                }
            }
        }
    }
    
    // Redirect (PRG)
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Fetch Admins (Role 1 & 2)
$stmt = $pdo->prepare("
    SELECT u.*, r.role_name, s.status_name 
    FROM user u
    LEFT JOIN user_role r ON u.role_id = r.role_id
    LEFT JOIN user_status s ON u.status_id = s.status_id
    WHERE u.role_id IN (1, 2)
    ORDER BY u.role_id ASC, u.name ASC
");
$stmt->execute();
$admins = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Admins - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="read_admin.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <?php require_once __DIR__ . '/../../../../../public/navbar/navbar.php'; ?>
    
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark"><i class="fas fa-user-shield me-2"></i>Admin Users</h2>
                <p class="text-muted mb-0">View and manage administrative accounts.</p>
            </div>
            <!-- Link to Create Admin (Generic button for now, path might need creating) -->
            <a href="../manage.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back
            </a>
        </div>

        <?php if($success): ?>
            <div class="alert alert-success shadow-sm"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Search Bar -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="adminSearch" class="form-control border-start-0" placeholder="Search admins by name or email...">
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">User</th>
                                <th>Role</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($admins)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">No admins found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($admins as $admin): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle me-3">
                                                    <?php if($admin['profile_image']): ?>
                                                        <img src="<?= app_url($admin['profile_image']) ?>" alt="Avatar">
                                                    <?php else: ?>
                                                        <?= strtoupper(substr($admin['name'], 0, 1)) ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold admin-name"><?= htmlspecialchars($admin['name']) ?></div>
                                                    <div class="small text-muted">Joined <?= date('M Y', strtotime($admin['created_at'])) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($admin['role_id'] == 1): ?>
                                                <span class="badge bg-danger">Super Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">Admin</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="small admin-email"><i class="fas fa-envelope text-muted me-2"></i><?= htmlspecialchars($admin['email']) ?></span>
                                                <span class="small"><i class="fas fa-phone text-muted me-2"></i><?= htmlspecialchars($admin['mobile_number']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge status-badge bg-<?= $admin['status_id'] == 1 ? 'success' : 'secondary' ?>">
                                                <?= htmlspecialchars($admin['status_name']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light round-btn" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v text-muted"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                    <!-- Edit (Placeholder) -->
                                                    <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2 text-primary"></i> Edit Details</a></li>
                                                    
                                                    <?php if($admin['user_id'] != $currentUser['user_id']): ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item text-danger" onclick="deleteAdmin(<?= $admin['user_id'] ?>)">
                                                                <i class="fas fa-trash-alt me-2"></i> Delete
                                                            </button>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="read_admin.js"></script>
</body>
</html>
