<?php
require __DIR__ . '/../../../config/db.php';
ensure_session_started();
$user = current_user();

// Check if user is admin (role_id = 2)
if (!$user || $user['role_id'] != 2) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();

// Flash Data Retrieval
$errors = $_SESSION['_flash']['errors'] ?? [];
$success = $_SESSION['_flash']['success'] ?? null;
$_POST = $_SESSION['_flash']['old'] ?? $_POST;
unset($_SESSION['_flash']);

$csrf_token = generate_csrf_token();

// Fetch package types
$packageTypes = $pdo->query("SELECT * FROM package_type ORDER BY type_name ASC")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF Token');
    }

    $packageName = trim($_POST['package_name'] ?? '');
    $packageTypeId = intval($_POST['package_type_id'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $durationDays = $_POST['duration_days'] === '' ? null : intval($_POST['duration_days']);
    $maxProperties = intval($_POST['max_properties'] ?? 0);
    $maxRooms = intval($_POST['max_rooms'] ?? 0);
    $maxVehicles = intval($_POST['max_vehicles'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    // Validation
    if (!$packageName) $errors[] = 'Package name is required.';
    if (!$packageTypeId) $errors[] = 'Package type is required.';
    if ($price <= 0) $errors[] = 'Price must be greater than 0.';
    if ($maxProperties < 0 || $maxRooms < 0 || $maxVehicles < 0) {
        $errors[] = 'Listing limits cannot be negative.';
    }
    if ($maxProperties == 0 && $maxRooms == 0 && $maxVehicles == 0) {
        $errors[] = 'At least one listing type must be allowed.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO package (
                    package_name, package_type_id, price, duration_days,
                    max_properties, max_rooms, max_vehicles, description, status_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            $stmt->execute([
                $packageName,
                $packageTypeId,
                $price,
                $durationDays,
                $maxProperties,
                $maxRooms,
                $maxVehicles,
                $description
            ]);

            $_SESSION['_flash']['success'] = "Package created successfully!";
            
            // Redirect to self to clear form (PRG)
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
            
        } catch (Exception $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
    
    // If we're here, there were errors
    if (!empty($errors)) {
        $_SESSION['_flash']['errors'] = $errors;
        $_SESSION['_flash']['old'] = $_POST;
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Package - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="create_package.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold"><i class="bi bi-box-seam me-2"></i>Create New Package</h2>
                    <p class="text-muted mb-0">Add a new ads package for owners</p>
                </div>
                <a href="<?= app_url('admin/index/index.php') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i><?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" id="packageForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                        <!-- Package Details -->
                        <h5 class="fw-bold mb-3" style="color: var(--hunter-green);">Package Details</h5>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-medium">Package Name *</label>
                                <input type="text" name="package_name" class="form-control" 
                                       placeholder="e.g., Basic Plan, Premium Plan"
                                       value="<?= htmlspecialchars($_POST['package_name'] ?? '') ?>" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-medium">Package Type *</label>
                                <select name="package_type_id" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($packageTypes as $type): ?>
                                    <option value="<?= $type['type_id'] ?>" 
                                            <?= (($_POST['package_type_id'] ?? '') == $type['type_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type['type_name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium">Price (LKR) *</label>
                                <input type="number" step="0.01" name="price" class="form-control" 
                                       placeholder="0.00"
                                       value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium">Duration (Days)</label>
                                <input type="number" name="duration_days" class="form-control" 
                                       placeholder="Leave empty for lifetime"
                                       value="<?= htmlspecialchars($_POST['duration_days'] ?? '') ?>">
                                <small class="text-muted">Leave empty for unlimited/lifetime access</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-medium">Description</label>
                                <textarea name="description" class="form-control" rows="3"
                                          placeholder="Describe what's included in this package..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- Listing Limits -->
                        <h5 class="fw-bold mb-3" style="color: var(--hunter-green);">Listing Limits</h5>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">
                                    <i class="bi bi-house-door text-primary"></i> Max Properties
                                </label>
                                <input type="number" name="max_properties" class="form-control" 
                                       min="0" value="<?= htmlspecialchars($_POST['max_properties'] ?? '0') ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-medium">
                                    <i class="bi bi-door-closed text-success"></i> Max Rooms
                                </label>
                                <input type="number" name="max_rooms" class="form-control" 
                                       min="0" value="<?= htmlspecialchars($_POST['max_rooms'] ?? '0') ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-medium">
                                    <i class="bi bi-car-front text-warning"></i> Max Vehicles
                                </label>
                                <input type="number" name="max_vehicles" class="form-control" 
                                       min="0" value="<?= htmlspecialchars($_POST['max_vehicles'] ?? '0') ?>">
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="<?= app_url('admin/index/index.php') ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                            <button type="submit" class="btn px-4" style="background-color: var(--fern); border-color: var(--fern); color: white;">
                                <i class="bi bi-plus-circle me-1"></i> Create Package
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="create_package.js"></script>
</body>
</html>
