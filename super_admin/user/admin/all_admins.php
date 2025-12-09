<?php
require_once __DIR__ . '/../../../config/db.php';
ensure_session_started();

$user = current_user();
if (!$user || $user['role_id'] != 1) { // Super Admin check
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();
$message = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        // Validation
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $status_id = intval($_POST['status_id'] ?? 1);
        
        if ($name && $email && $password && $mobile) {
            try {
                // Check email existence
                $stmt = $pdo->prepare("SELECT user_id FROM user WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    throw new Exception("Email already exists.");
                }

                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO user (name, email, mobile_number, password_hash, role_id, status_id) VALUES (?, ?, ?, ?, 2, ?)");
                $stmt->execute([$name, $email, $mobile, $passwordHash, $status_id]);
                $message = "Admin added successfully!";
            } catch (Exception $e) {
                $error = "Error adding admin: " . $e->getMessage();
            }
        } else {
            $error = "All fields are required.";
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['user_id']);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $status_id = intval($_POST['status_id'] ?? 1);
        
        if ($id && $name && $email && $mobile) {
            try {
                 // Check email existence (excluding self)
                 $stmt = $pdo->prepare("SELECT user_id FROM user WHERE email = ? AND user_id != ?");
                 $stmt->execute([$email, $id]);
                 if ($stmt->rowCount() > 0) {
                     throw new Exception("Email already exists.");
                 }

                $stmt = $pdo->prepare("UPDATE user SET name = ?, email = ?, mobile_number = ?, status_id = ? WHERE user_id = ? AND role_id = 2");
                $stmt->execute([$name, $email, $mobile, $status_id, $id]);
                $message = "Admin updated successfully!";
            } catch (Exception $e) {
                $error = "Error updating admin: " . $e->getMessage();
            }
        } else {
            $error = "Invalid data.";
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['user_id']);
        if ($id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM user WHERE user_id = ? AND role_id = 2");
                $stmt->execute([$id]);
                $message = "Admin deleted successfully!";
            } catch (Exception $e) {
                $error = "Error deleting admin: " . $e->getMessage();
            }
        }
    }
}

// Fetch Admins
$admins = $pdo->query("SELECT u.*, s.status_name 
                       FROM user u 
                       LEFT JOIN user_status s ON u.status_id = s.status_id 
                       WHERE u.role_id = 2 
                       ORDER BY u.created_at DESC")->fetchAll();

// Fetch Statuses
try {
    $statuses = $pdo->query("SELECT * FROM user_status")->fetchAll();
} catch (PDOException $e) {
    $statuses = [
        ['status_id' => 1, 'status_name' => 'Active'],
        ['status_id' => 2, 'status_name' => 'Inactive']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Admins - Rental Lanka Super Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="all_admins.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

    <?php require_once __DIR__ . '/../../../public/navbar/navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark"><i class="fas fa-user-shield me-2"></i> Manage Admins</h2>
                <p class="text-muted mb-0">Manage administrator accounts and permissions.</p>
            </div>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                <i class="fas fa-plus me-2"></i> Add New Admin
            </button>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Admin</th>
                                <th>Contact Info</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($admins)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No admins found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle me-3">
                                                    <?= strtoupper(substr($admin['name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?= htmlspecialchars($admin['name']) ?></div>
                                                    <div class="text-muted small">ID: <?= $admin['user_id'] ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small"><i class="fas fa-envelope text-muted me-2"></i><?= htmlspecialchars($admin['email']) ?></div>
                                            <div class="small"><i class="fas fa-phone text-muted me-2"></i><?= htmlspecialchars($admin['mobile_number']) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge status-badge bg-<?= $admin['status_id'] == 1 ? 'success' : 'secondary' ?>">
                                                <?= htmlspecialchars($admin['status_name'] ?? 'Unknown') ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small">
                                            <?= date('M j, Y', strtotime($admin['created_at'])) ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-outline-primary action-btn me-2" 
                                                    onclick='editAdmin(<?= json_encode($admin) ?>)' 
                                                    title="Edit">
                                                <i class="fas fa-edit" style="font-size: 0.8rem;"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger action-btn" 
                                                    onclick="deleteAdmin(<?= $admin['user_id'] ?>)" 
                                                    title="Delete">
                                                <i class="fas fa-trash-alt" style="font-size: 0.8rem;"></i>
                                            </button>
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

    <!-- Add Modal -->
    <div class="modal fade" id="addAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Add New Admin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile" class="form-control" required placeholder="07XXXXXXXX">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status_id" class="form-select">
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= $status['status_id'] ?>"><?= htmlspecialchars($status['status_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Create Admin Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Edit Admin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile" id="edit_mobile" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status_id" id="edit_status_id" class="form-select">
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= $status['status_id'] ?>"><?= htmlspecialchars($status['status_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Form -->
    <form id="deleteForm" method="POST" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="delete_user_id">
    </form>

    <script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="all_admins.js"></script>
    <script>
        // Flash Messages
        const flashMessage = <?php echo json_encode(['success' => $message, 'error' => $error]); ?>;
        
        if (flashMessage.success) {
            Swal.fire({ icon: 'success', title: 'Success', text: flashMessage.success, timer: 2000, showConfirmButton: false });
        }
        if (flashMessage.error) {
            Swal.fire({ icon: 'error', title: 'Error', text: flashMessage.error });
        }
    </script>
</body>
</html>
