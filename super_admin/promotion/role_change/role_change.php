<?php
require_once __DIR__ . '/../../../config/db.php';
ensure_session_started();
$user = current_user();

// Check if user is Super Admin
if (!$user || $user['role_id'] != 1) {
    header('Location: ' . app_url('index.php'));
    exit;
}

$pdo = get_pdo();
$message = '';
$error = '';
$searchQuery = $_GET['search'] ?? '';


// Handle Role Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['new_role'])) {
    $userId = (int)$_POST['user_id'];
    $newRole = (int)$_POST['new_role'];
    
    // Prevent changing own role or other Super Admins
    if ($userId == $user['user_id']) {
        $error = "You cannot change your own role.";
    } else {
        // Check if target user is super admin
        $stmt = $pdo->prepare("SELECT role_id FROM user WHERE user_id = ?");
        $stmt->execute([$userId]);
        $targetUserRole = $stmt->fetchColumn();
        
        if ($targetUserRole == 1) {
            $error = "You cannot change the role of another Super Admin.";
        } else {
            $stmt = $pdo->prepare("UPDATE user SET role_id = ? WHERE user_id = ?");
            if ($stmt->execute([$newRole, $userId])) {
                $message = "User role updated successfully.";
            } else {
                $error = "Failed to update user role.";
            }
        }
    }
}

// Fetch Users based on search
$users = [];
if (!empty($searchQuery)) {
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.name, u.email, u.mobile_number, u.role_id, ur.role_name 
        FROM user u 
        JOIN user_role ur ON u.role_id = ur.role_id
        WHERE (u.name LIKE ? OR u.email LIKE ?) AND u.role_id != 1
        LIMIT 20
    ");
    $searchTerm = "%$searchQuery%";
    $stmt->execute([$searchTerm, $searchTerm]);
    $users = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Role Management - Super Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= app_url('super_admin/promotion/role_change/role_change.css') ?>">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">User Role Management</h2>
            <p class="text-muted">Search for a user to change their role.</p>
        </div>
        <a href="<?= app_url('super_admin/index/index.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <form method="get" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search by name or email..." value="<?= htmlspecialchars($searchQuery) ?>" autofocus>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-lg w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($searchQuery)): ?>
        <?php if (count($users) > 0): ?>
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Search Results</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">User Details</th>
                                <th>Current Role</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?= htmlspecialchars($u['name']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($u['email']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($u['mobile_number']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($u['role_name']) ?></span>
                                    </td>
                                    <td>
                                        <form method="post" class="d-flex gap-2">
                                            <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                            <select name="new_role" class="form-select form-select-sm w-auto" required>
                                                <option value="" disabled selected>Select Role</option>
                                                <option value="4" <?= $u['role_id'] == 4 ? 'disabled' : '' ?>>Customer</option>
                                                <option value="3" <?= $u['role_id'] == 3 ? 'disabled' : '' ?>>Owner</option>
                                                <option value="2" <?= $u['role_id'] == 2 ? 'disabled' : '' ?>>Admin</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Are you sure you want to change this user\'s role?')">Update</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-search display-1 text-muted opacity-25"></i>
                <p class="mt-3 text-muted">No users found matching "<?= htmlspecialchars($searchQuery) ?>"</p>
            </div>
        <?php endif; ?>
    <?php elseif (isset($_GET['search'])): ?>
         <div class="text-center py-5">
            <i class="bi bi-search display-1 text-muted opacity-25"></i>
            <p class="mt-3 text-muted">Please enter a search term.</p>
        </div>
    <?php endif; ?>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= app_url('super_admin/promotion/role_change/role_change.js') ?>"></script>
</body>
</html>
