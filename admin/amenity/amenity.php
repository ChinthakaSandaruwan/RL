<?php
require_once __DIR__ . '/../../config/db.php';
ensure_session_started();

$user = current_user();
if (!$user || $user['role_id'] != 2) { // Admin check
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();

// Flash Data Retrieval
$message = $_SESSION['_flash']['success'] ?? '';
$error = $_SESSION['_flash']['error'] ?? '';
unset($_SESSION['_flash']);

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['amenity_name'] ?? '');
        $category = $_POST['category'] ?? 'both';
        
        if ($name) {
            try {
                $stmt = $pdo->prepare("INSERT INTO amenity (amenity_name, category) VALUES (?, ?)");
                $stmt->execute([$name, $category]);
                $_SESSION['_flash']['success'] = "Amenity added successfully!";
            } catch (PDOException $e) {
                $_SESSION['_flash']['error'] = "Error adding amenity: " . $e->getMessage();
            }
        } else {
            $_SESSION['_flash']['error'] = "Amenity name is required.";
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['amenity_id']);
        $name = trim($_POST['amenity_name'] ?? '');
        $category = $_POST['category'] ?? 'both';
        
        if ($id && $name) {
            try {
                $stmt = $pdo->prepare("UPDATE amenity SET amenity_name = ?, category = ? WHERE amenity_id = ?");
                $stmt->execute([$name, $category, $id]);
                $_SESSION['_flash']['success'] = "Amenity updated successfully!";
            } catch (PDOException $e) {
                $_SESSION['_flash']['error'] = "Error updating amenity: " . $e->getMessage();
            }
        } else {
            $_SESSION['_flash']['error'] = "Invalid data for update.";
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['amenity_id']);
        if ($id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM amenity WHERE amenity_id = ?");
                $stmt->execute([$id]);
                $_SESSION['_flash']['success'] = "Amenity deleted successfully!";
            } catch (PDOException $e) {
                $_SESSION['_flash']['error'] = "Cannot delete amenity used in properties or rooms.";
            }
        }
    }
    
    // Redirect (PRG)
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Fetch Amenities
$amenities = $pdo->query("SELECT * FROM amenity ORDER BY amenity_name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Amenities - Rental Lanka Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .amenity-card { transition: all 0.2s; }
        .amenity-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
        .action-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; }
    </style>
</head>
<body class="bg-light">

    <?php require_once __DIR__ . '/../../public/navbar/navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark"><i class="fas fa-concierge-bell me-2"></i> Manage Amenities</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAmenityModal">
                <i class="fas fa-plus me-2"></i> Add New Amenity
            </button>
        </div>

        <div class="row g-4">
            <!-- Amenities List -->
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Amenity Name</th>
                                        <th>Category</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($amenities)): ?>
                                        <tr><td colspan="3" class="text-center py-4 text-muted">No amenities found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($amenities as $item): ?>
                                            <tr>
                                                <td class="ps-4 fw-medium"><?= htmlspecialchars($item['amenity_name']) ?></td>
                                                <td>
                                                    <?php 
                                                        $badgeClass = 'bg-secondary';
                                                        if($item['category'] == 'property') $badgeClass = 'bg-success';
                                                        if($item['category'] == 'room') $badgeClass = 'bg-info';
                                                        if($item['category'] == 'both') $badgeClass = 'bg-primary';
                                                    ?>
                                                    <span class="badge <?= $badgeClass ?>"><?= ucfirst(htmlspecialchars($item['category'])) ?></span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <button class="btn btn-sm btn-outline-primary action-btn me-2" 
                                                            onclick="editAmenity(<?= $item['amenity_id'] ?>, '<?= addslashes(htmlspecialchars($item['amenity_name'])) ?>', '<?= htmlspecialchars($item['category']) ?>')" 
                                                            title="Edit">
                                                        <i class="fas fa-edit" style="font-size: 0.8rem;"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger action-btn" 
                                                            onclick="deleteAmenity(<?= $item['amenity_id'] ?>)" 
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
    <div class="modal fade" id="addAmenityModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Amenity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Amenity Name</label>
                        <input type="text" name="amenity_name" class="form-control" required placeholder="e.g. Swimming Pool">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="both">Both</option>
                            <option value="property">Property</option>
                            <option value="room">Room</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Amenity</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editAmenityModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Amenity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="amenity_id" id="edit_amenity_id">
                    <div class="mb-3">
                        <label class="form-label">Amenity Name</label>
                        <input type="text" name="amenity_name" id="edit_amenity_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" id="edit_category" class="form-select">
                            <option value="both">Both</option>
                            <option value="property">Property</option>
                            <option value="room">Room</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Form (Hidden) -->
    <form id="deleteForm" method="POST" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="amenity_id" id="delete_amenity_id">
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
        function editAmenity(id, name, category) {
            document.getElementById('edit_amenity_id').value = id;
            document.getElementById('edit_amenity_name').value = name;
            document.getElementById('edit_category').value = category; // Set dropdown value
            new bootstrap.Modal(document.getElementById('editAmenityModal')).show();
        }

        function deleteAmenity(id) {
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
                    document.getElementById('delete_amenity_id').value = id;
                    document.getElementById('deleteForm').submit();
                }
            });
        }
    </script>
</body>
</html>
