<?php
require_once __DIR__ . '/../../../config/db.php';
ensure_session_started();

$user = current_user();
if (!$user || $user['role_id'] != 2) { // Admin check
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();
$message = $_SESSION['_flash']['success'] ?? '';
$error = $_SESSION['_flash']['error'] ?? '';
unset($_SESSION['_flash']);

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['type_name'] ?? '');
        if ($name) {
            try {
                $stmt = $pdo->prepare("INSERT INTO vehicle_type (type_name) VALUES (?)");
                $stmt->execute([$name]);
                $_SESSION['_flash']['success'] = "Vehicle Type added successfully!";
            } catch (PDOException $e) {
                $_SESSION['_flash']['error'] = "Error adding type: " . $e->getMessage();
            }
        } else {
            $_SESSION['_flash']['error'] = "Type name is required.";
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['type_id']);
        $name = trim($_POST['type_name'] ?? '');
        if ($id && $name) {
            try {
                $stmt = $pdo->prepare("UPDATE vehicle_type SET type_name = ? WHERE type_id = ?");
                $stmt->execute([$name, $id]);
                $_SESSION['_flash']['success'] = "Vehicle Type updated successfully!";
            } catch (PDOException $e) {
                $_SESSION['_flash']['error'] = "Error updating type: " . $e->getMessage();
            }
        } else {
            $_SESSION['_flash']['error'] = "Invalid data for update.";
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['type_id']);
        if ($id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM vehicle_type WHERE type_id = ?");
                $stmt->execute([$id]);
                $_SESSION['_flash']['success'] = "Vehicle Type deleted successfully!";
            } catch (PDOException $e) {
                $_SESSION['_flash']['error'] = "Cannot delete type because it is assigned to vehicles.";
            }
        }
    }
    
    // Redirect (PRG)
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Fetch Types
$types = $pdo->query("SELECT * FROM vehicle_type ORDER BY type_name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Vehicle Types - Rental Lanka Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .type-card { transition: all 0.2s; }
        .type-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
        .action-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; }
    </style>
</head>
<body class="bg-light">

    <?php require_once __DIR__ . '/../../../public/navbar/navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark"><i class="fas fa-car me-2"></i> Manage Vehicle Types</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTypeModal">
                <i class="fas fa-plus me-2"></i> Add New Type
            </button>
        </div>

        <div class="row g-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Type Name</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($types)): ?>
                                        <tr><td colspan="2" class="text-center py-4 text-muted">No vehicle types found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($types as $item): ?>
                                            <tr>
                                                <td class="ps-4 fw-medium"><?= htmlspecialchars($item['type_name']) ?></td>
                                                <td class="text-end pe-4">
                                                    <button class="btn btn-sm btn-outline-primary action-btn me-2" 
                                                            onclick="editType(<?= $item['type_id'] ?>, '<?= addslashes(htmlspecialchars($item['type_name'])) ?>')" 
                                                            title="Edit">
                                                        <i class="fas fa-edit" style="font-size: 0.8rem;"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger action-btn" 
                                                            onclick="deleteType(<?= $item['type_id'] ?>)" 
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
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Vehicle Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Type Name</label>
                        <input type="text" name="type_name" class="form-control" required placeholder="e.g. SUV, Sedan">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Type</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Vehicle Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="type_id" id="edit_type_id">
                    <div class="mb-3">
                        <label class="form-label">Type Name</label>
                        <input type="text" name="type_name" id="edit_type_name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Form -->
    <form id="deleteForm" method="POST" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="type_id" id="delete_type_id">
    </form>

    <script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
        // Flash Messages
        const flashMessage = <?php echo json_encode(['success' => $message, 'error' => $error]); ?>;
        
        if (flashMessage.success) {
            Swal.fire({ icon: 'success', title: 'Success', text: flashMessage.success, timer: 2000, showConfirmButton: false });
        }
        if (flashMessage.error) {
            Swal.fire({ icon: 'error', title: 'Error', text: flashMessage.error });
        }

        // Functions
        function editType(id, name) {
            document.getElementById('edit_type_id').value = id;
            document.getElementById('edit_type_name').value = name;
            new bootstrap.Modal(document.getElementById('editTypeModal')).show();
        }

        function deleteType(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete_type_id').value = id;
                    document.getElementById('deleteForm').submit();
                }
            });
        }
    </script>
</body>
</html>
