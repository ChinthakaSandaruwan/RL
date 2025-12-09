<?php
require __DIR__ . '/../../config/db.php';

ensure_session_started();
$user = current_user();

// Check if user is Admin (role_id = 2)
if (!$user || $user['role_id'] != 2) {
    header('Location: ' . app_url('index.php'));
    exit;
}

$pdo = get_pdo();
$errors = [];
$success = null;
$activeTab = 'brands'; // Default tab

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // --- BRAND ACTIONS ---
    if ($action === 'add_brand') {
        $brandName = trim($_POST['brand_name'] ?? '');
        $activeTab = 'brands';
        
        if (empty($brandName)) {
            $errors[] = "Brand name is required.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO vehicle_brand (brand_name) VALUES (?)");
                $stmt->execute([$brandName]);
                $success = "Brand added successfully!";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Integrity constraint violation (Duplicate)
                    $errors[] = "Brand '$brandName' already exists.";
                } else {
                    $errors[] = "Error adding brand: " . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'edit_brand') {
        $brandId = intval($_POST['brand_id']);
        $brandName = trim($_POST['brand_name'] ?? '');
        $activeTab = 'brands';

        if (empty($brandName)) {
            $errors[] = "Brand name is required.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE vehicle_brand SET brand_name = ? WHERE brand_id = ?");
                $stmt->execute([$brandName, $brandId]);
                $success = "Brand updated successfully!";
            } catch (PDOException $e) {
                $errors[] = "Error updating brand: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_brand') {
        $brandId = intval($_POST['brand_id']);
        $activeTab = 'brands';
        try {
            $stmt = $pdo->prepare("DELETE FROM vehicle_brand WHERE brand_id = ?");
            $stmt->execute([$brandId]);
            $success = "Brand deleted successfully!";
        } catch (PDOException $e) {
            $errors[] = "Error deleting brand. Ensure no vehicles are using this brand.";
        }
    }

    // --- MODEL ACTIONS ---
    elseif ($action === 'add_model') {
        $modelName = trim($_POST['model_name'] ?? '');
        $brandId = intval($_POST['brand_id'] ?? 0);
        $activeTab = 'models';

        if (empty($modelName) || $brandId <= 0) {
            $errors[] = "Model name and Brand are required.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO vehicle_model (model_name, brand_id) VALUES (?, ?)");
                $stmt->execute([$modelName, $brandId]);
                $success = "Model added successfully!";
            } catch (PDOException $e) {
                 if ($e->getCode() == 23000) {
                    $errors[] = "Model '$modelName' already exists.";
                } else {
                    $errors[] = "Error adding model: " . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'edit_model') {
        $modelId = intval($_POST['model_id']);
        $modelName = trim($_POST['model_name'] ?? '');
        $brandId = intval($_POST['brand_id'] ?? 0);
        $activeTab = 'models';

        if (empty($modelName) || $brandId <= 0) {
            $errors[] = "Model name and Brand are required.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE vehicle_model SET model_name = ?, brand_id = ? WHERE model_id = ?");
                $stmt->execute([$modelName, $brandId, $modelId]);
                $success = "Model updated successfully!";
            } catch (PDOException $e) {
                $errors[] = "Error updating model: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_model') {
        $modelId = intval($_POST['model_id']);
        $activeTab = 'models';
        try {
            $stmt = $pdo->prepare("DELETE FROM vehicle_model WHERE model_id = ?");
            $stmt->execute([$modelId]);
            $success = "Model deleted successfully!";
        } catch (PDOException $e) {
            $errors[] = "Error deleting model.";
        }
    }
}

// Fetch Data
$brands = $pdo->query("SELECT * FROM vehicle_brand ORDER BY brand_name ASC")->fetchAll();
$models = $pdo->query("SELECT m.*, b.brand_name 
                       FROM vehicle_model m 
                       JOIN vehicle_brand b ON m.brand_id = b.brand_id 
                       ORDER BY b.brand_name ASC, m.model_name ASC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Brand & Model Management - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="brand_&_model.css">
</head>
<body>

<?php require __DIR__ . '/../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col">
            <h2 class="fw-bold text-dark">Brand & Model Management</h2>
            <p class="text-muted">Manage vehicle brands and their corresponding models.</p>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="manageTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link <?= $activeTab === 'brands' ? 'active' : '' ?>" id="brands-tab" data-bs-toggle="tab" data-bs-target="#brands" type="button" role="tab">Brands</button>
        </li>
        <li class="nav-item">
            <button class="nav-link <?= $activeTab === 'models' ? 'active' : '' ?>" id="models-tab" data-bs-toggle="tab" data-bs-target="#models" type="button" role="tab">Models</button>
        </li>
    </ul>

    <div class="tab-content" id="manageTabsContent">
        
        <!-- BRANDS TAB -->
        <div class="tab-pane fade <?= $activeTab === 'brands' ? 'show active' : '' ?>" id="brands" role="tabpanel">
            <div class="row">
                <!-- Brand List -->
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Existing Brands</h5>
                            <span class="badge bg-secondary"><?= count($brands) ?> Total</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Brand Name</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($brands)): ?>
                                            <tr><td colspan="3" class="text-center py-3">No brands found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($brands as $brand): ?>
                                                <tr>
                                                    <td><?= $brand['brand_id'] ?></td>
                                                    <td class="fw-bold"><?= htmlspecialchars($brand['brand_name']) ?></td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-outline-primary action-btn" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editBrandModal"
                                                                data-id="<?= $brand['brand_id'] ?>"
                                                                data-name="<?= htmlspecialchars($brand['brand_name']) ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <form method="post" class="d-inline" onsubmit="return confirmDelete('brand')">
                                                            <input type="hidden" name="action" value="delete_brand">
                                                            <input type="hidden" name="brand_id" value="<?= $brand['brand_id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger action-btn">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
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
                
                <!-- Add Brand Form -->
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Add New Brand</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="action" value="add_brand">
                                <div class="mb-3">
                                    <label class="form-label">Brand Name</label>
                                    <input type="text" name="brand_name" class="form-control" required placeholder="e.g. Toyota">
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-plus-circle"></i> Add Brand
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODELS TAB -->
        <div class="tab-pane fade <?= $activeTab === 'models' ? 'show active' : '' ?>" id="models" role="tabpanel">
             <div class="row">
                <!-- Model List -->
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Existing Models</h5>
                            <span class="badge bg-secondary"><?= count($models) ?> Total</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                <table class="table table-hover mb-0">
                                    <thead class="sticky-top bg-light">
                                        <tr>
                                            <th>Model</th>
                                            <th>Brand</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($models)): ?>
                                            <tr><td colspan="3" class="text-center py-3">No models found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($models as $model): ?>
                                                <tr>
                                                    <td class="fw-bold"><?= htmlspecialchars($model['model_name']) ?></td>
                                                    <td><span class="brand-badge"><?= htmlspecialchars($model['brand_name']) ?></span></td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-outline-primary action-btn" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editModelModal"
                                                                data-id="<?= $model['model_id'] ?>"
                                                                data-name="<?= htmlspecialchars($model['model_name']) ?>"
                                                                data-brand-id="<?= $model['brand_id'] ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <form method="post" class="d-inline" onsubmit="return confirmDelete('model')">
                                                            <input type="hidden" name="action" value="delete_model">
                                                            <input type="hidden" name="model_id" value="<?= $model['model_id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger action-btn">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
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
                
                <!-- Add Model Form -->
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Add New Model</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="action" value="add_model">
                                <div class="mb-3">
                                    <label class="form-label">Select Brand</label>
                                    <select name="brand_id" class="form-select" required>
                                        <option value="" disabled selected>Choose Brand</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?= $brand['brand_id'] ?>"><?= htmlspecialchars($brand['brand_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Model Name</label>
                                    <input type="text" name="model_name" class="form-control" required placeholder="e.g. Prius">
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-plus-circle"></i> Add Model
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Edit Brand Modal -->
<div class="modal fade" id="editBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_brand">
                    <input type="hidden" name="brand_id" id="edit_brand_id">
                    <div class="mb-3">
                        <label class="form-label">Brand Name</label>
                        <input type="text" name="brand_name" id="edit_brand_name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Model Modal -->
<div class="modal fade" id="editModelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Model</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_model">
                    <input type="hidden" name="model_id" id="edit_model_id">
                    <div class="mb-3">
                        <label class="form-label">Brand</label>
                        <select name="brand_id" id="edit_brand_select" class="form-select" required>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= $brand['brand_id'] ?>"><?= htmlspecialchars($brand['brand_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Model Name</label>
                        <input type="text" name="model_name" id="edit_model_name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="brand_&_model.js"></script>
</body>
</html>
