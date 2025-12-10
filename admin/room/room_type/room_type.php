<?php
require __DIR__ . '/../../../config/db.php';
require __DIR__ . '/../../../services/email.php';

ensure_session_started();
$currentUser = current_user();
$user = $currentUser; // For navbar compatibility

// Check if user is Admin (Role ID 2 or Super Admin 1)
if (!$currentUser || !in_array($currentUser['role_id'], [1, 2])) {
    header('Location: ' . app_url('auth/login.php'));
    exit;
}

$pdo = get_pdo();
$success = '';
$error = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $action = $_POST['action'] ?? '';
        $typeName = trim($_POST['type_name'] ?? '');
        $typeId = intval($_POST['type_id'] ?? 0);

        if ($action === 'add') {
            if (empty($typeName)) {
                $error = "Type name is required.";
            } else {
                // Check if exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM room_type WHERE type_name = ?");
                $stmt->execute([$typeName]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "Room type already exists.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO room_type (type_name) VALUES (?)");
                    if ($stmt->execute([$typeName])) {
                        $success = "Room type added successfully.";
                    } else {
                        $error = "Failed to add room type.";
                    }
                }
            }
        } elseif ($action === 'update') {
            if (empty($typeName) || $typeId <= 0) {
                $error = "Invalid data provided.";
            } else {
                // Check if name is taken by another ID
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM room_type WHERE type_name = ? AND type_id != ?");
                $stmt->execute([$typeName, $typeId]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "Room type name already exists.";
                } else {
                    $stmt = $pdo->prepare("UPDATE room_type SET type_name = ? WHERE type_id = ?");
                    if ($stmt->execute([$typeName, $typeId])) {
                        $success = "Room type updated successfully.";
                    } else {
                        $error = "Failed to update room type.";
                    }
                }
            }
        } elseif ($action === 'delete') {
            if ($typeId <= 0) {
                $error = "Invalid type ID.";
            } else {
                // Check usage
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM room WHERE room_type_id = ?");
                $stmt->execute([$typeId]);
                $count = $stmt->fetchColumn();

                if ($count > 0) {
                    $error = "Cannot delete this type. It is currently assigned to $count room(s).";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM room_type WHERE type_id = ?");
                    if ($stmt->execute([$typeId])) {
                        $success = "Room type deleted successfully.";
                    } else {
                        $error = "Failed to delete room type.";
                    }
                }
            }
        }
    }
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Fetch all types with usage count
$query = "
    SELECT rt.*, COUNT(r.room_id) as usage_count 
    FROM room_type rt 
    LEFT JOIN room r ON rt.type_id = r.room_type_id 
    GROUP BY rt.type_id 
    ORDER BY rt.type_name ASC
";
$types = $pdo->query($query)->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Types - Admin</title>
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../css/admin.css">
    <link rel="stylesheet" href="room_type.css">
</head>
<body>

    <?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?= app_url('admin/index/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Room Types</li>
                    </ol>
                </nav>
                <h2 class="fw-bold mb-0 text-dark">Manage Room Types</h2>
            </div>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="bi bi-plus-lg me-2"></i>Add New Type
            </button>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4" style="width: 80px;">ID</th>
                                <th>Type Name</th>
                                <th>Usage Count</th>
                                <th class="text-end pe-4" style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($types)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        No room types found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($types as $type): ?>
                                    <tr>
                                        <td class="ps-4 text-muted">#<?= $type['type_id'] ?></td>
                                        <td class="fw-medium text-dark"><?= htmlspecialchars($type['type_name']) ?></td>
                                        <td>
                                            <?php if ($type['usage_count'] > 0): ?>
                                                <span class="badge bg-info text-dark rounded-pill">
                                                    <?= $type['usage_count'] ?> Rooms
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border rounded-pill">Unused</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="openEditModal(<?= $type['type_id'] ?>, '<?= addslashes(htmlspecialchars($type['type_name'])) ?>')">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmDelete(<?= $type['type_id'] ?>, <?= $type['usage_count'] ?>)">
                                                <i class="bi bi-trash-fill"></i>
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

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="typeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalTitle">Add Room Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="typeForm" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="type_id" id="typeId">
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small text-uppercase fw-bold">Type Name</label>
                            <input type="text" name="type_name" id="typeName" class="form-control form-control-lg" placeholder="e.g. Single Room, Deluxe" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Delete Form -->
    <form id="deleteForm" method="POST" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="type_id" id="deleteId">
    </form>

    <script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="room_type.js"></script>
</body>
</html>
