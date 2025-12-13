<?php
require_once __DIR__ . '/../../../config/db.php';

ensure_session_started();
$currentUser = current_user();

// 1. Check if user is logged in and is a Super Admin (Role ID 1)
if (!$currentUser || $currentUser['role_id'] != 1) {
    header('Location: ' . app_url('index.php'));
    exit;
}

$pdo = get_pdo();
$error = '';
$success = '';

// 2. Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- Create New Super Admin ---
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $mobile = trim($_POST['mobile']); 
        $nic = trim($_POST['nic']); 
        // No password input needed for OTP-based login systems unless we auto-generate one for fallback?
        // But schema likely requires NOT NULL password if not changed. 
        // However, user request says "im not use passwrod only otp use for login".
        // If the 'password' column is NOT NULL in DB, we must provide a dummy value (or random).
        // Let's generate a random password to satisfy DB constraint, but user won't know it.


        if (empty($name) || empty($email) || empty($mobile)) {
            $error = "Name, Email, and Mobile are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            // Check if email or mobile already exists
            $stmt = $pdo->prepare("SELECT user_id FROM user WHERE email = ? OR mobile_number = ?");
            $stmt->execute([$email, $mobile]);
            if ($stmt->fetch()) {
                $error = "Email or Mobile Number already registered.";
            } else {
                try {
                    // role_id = 1 (Super Admin), status_id = 1 (Active)
                    // Generate random secure password for DB constraint (User uses OTP)
                    $dummyPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("INSERT INTO user (name, email, mobile_number, nic, password, role_id, status_id, created_at) VALUES (?, ?, ?, ?, ?, 1, 1, NOW())");
                    if ($stmt->execute([$name, $email, $mobile, $nic, $dummyPass])) {
                        $success = "New Super Admin added successfully. They can login via OTP.";
                    } else {
                        $error = "Failed to create user.";
                    }
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
    }

    // --- Delete Super Admin ---
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $delete_id = (int)$_POST['user_id'];
        
        if ($delete_id == $currentUser['user_id']) {
            $error = "You cannot delete your own account.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT role_id FROM user WHERE user_id = ?");
                $stmt->execute([$delete_id]);
                $target = $stmt->fetch();

                if ($target && $target['role_id'] == 1) {
                    $stmt = $pdo->prepare("DELETE FROM user WHERE user_id = ?");
                    $stmt->execute([$delete_id]);
                    $success = "Super Admin removed successfully.";
                } else {
                    $error = "Invalid user target.";
                }
            } catch (PDOException $e) {
                $error = "Could not delete user. " . $e->getMessage();
            }
        }
    }
}

// 3. Fetch All Super Admins
$stmt = $pdo->query("SELECT user_id, name, email, mobile_number, nic, created_at FROM user WHERE role_id = 1 ORDER BY created_at DESC");
$superAdmins = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Super Admins - Rental Lanka</title>
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="super_admin.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container mt-5 mb-5">
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-hunter-green mb-1">Super Administrators</h2>
            <p class="text-muted">Manage system administrators (OTP Login Only).</p>
        </div>
        <button class="btn btn-primary btn-theme shadow-sm" data-bs-toggle="modal" data-bs-target="#addAdminModal">
            <i class="bi bi-person-plus-fill me-2"></i>Add New Super Admin
        </button>
    </div>

    <!-- Alerts -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3 text-secondary border-0">Name</th>
                            <th class="px-4 py-3 text-secondary border-0">Contact Info</th>
                            <th class="px-4 py-3 text-secondary border-0">NIC</th>
                            <th class="px-4 py-3 text-secondary border-0">Joined Date</th>
                            <th class="px-4 py-3 text-secondary border-0 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($superAdmins as $admin): ?>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-3">
                                            <?= strtoupper(substr($admin['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($admin['name']) ?></div>
                                            <?php if ($admin['user_id'] == $currentUser['user_id']): ?>
                                                <span class="badge bg-soft-success text-success rounded-pill" style="font-size: 0.7rem;">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-dark small mb-1"><i class="bi bi-envelope me-1 text-muted"></i> <?= htmlspecialchars($admin['email']) ?></div>
                                    <div class="text-dark small"><i class="bi bi-phone me-1 text-muted"></i> <?= htmlspecialchars($admin['mobile_number']) ?></div>
                                </td>
                                <td class="px-4 py-3 text-muted small">
                                    <?= htmlspecialchars($admin['nic'] ?: '-') ?>
                                </td>
                                <td class="px-4 py-3 text-muted small">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?= date('M d, Y', strtotime($admin['created_at'])) ?>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <?php if ($admin['user_id'] != $currentUser['user_id']): ?>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this Super Admin? This action cannot be undone.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?= $admin['user_id'] ?>">
                                            <button type="submit" class="btn btn-icon btn-outline-danger btn-sm" title="Remove Admin">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-icon btn-light btn-sm disabled" title="Current User">
                                            <i class="bi bi-lock"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($superAdmins)): ?>
                <div class="text-center py-5">
                    <p class="text-muted">No records found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="addAdminModalLabel">Add New Super Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form method="post" id="addAdminForm">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-lg bg-light border-0" required placeholder="e.g. John Doe">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control form-control-lg bg-light border-0" required placeholder="name@example.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" name="mobile" class="form-control form-control-lg bg-light border-0" required placeholder="07xxxxxxxx">
                            <div class="form-text">Used for OTP Login</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">NIC Number <span class="text-muted font-weight-normal">(Optional)</span></label>
                        <input type="text" name="nic" class="form-control form-control-lg bg-light border-0" placeholder="National Identity Card">
                    </div>
                    
                    <div class="alert alert-info py-2 small">
                        <i class="bi bi-info-circle me-1"></i> A secure random password will be generated internally. Login is handled via OTP only.
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-theme btn-lg">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="super_admin.js"></script>
</body>
</html>
