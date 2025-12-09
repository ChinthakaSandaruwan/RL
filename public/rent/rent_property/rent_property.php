<?php
require_once __DIR__ . '/../../../config/db.php';
ensure_session_started();

$user = current_user();
if (!$user) {
    header("Location: " . app_url('auth/login'));
    exit;
}

$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($property_id <= 0) {
    header("Location: " . app_url());
    exit;
}

$pdo = get_pdo();
$errors = [];
$success = null;

// Fetch property details
$stmt = $pdo->prepare("
    SELECT 
        p.*, 
        pt.type_name,
        pl.address, pl.google_map_link,
        c.name_en as city_name, d.name_en as district_name,
        u.name as owner_name, u.user_id as owner_id
    FROM property p
    LEFT JOIN property_type pt ON p.property_type_id = pt.type_id
    LEFT JOIN property_location pl ON p.property_id = pl.property_id
    LEFT JOIN cities c ON pl.city_id = c.id
    LEFT JOIN districts d ON c.district_id = d.id
    LEFT JOIN user u ON p.owner_id = u.user_id
    WHERE p.property_id = ? AND p.status_id = 1
");
$stmt->execute([$property_id]);
$property = $stmt->fetch();

if (!$property) {
    header("Location: " . app_url());
    exit;
}

// Get primary image
$stmt_img = $pdo->prepare("SELECT image_path FROM property_image WHERE property_id = ? AND primary_image = 1 LIMIT 1");
$stmt_img->execute([$property_id]);
$primary_image = $stmt_img->fetchColumn();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prevent owners from renting their own properties
    if ($user['user_id'] == $property['owner_id']) {
        $errors[] = "You cannot rent your own property.";
    }
    
    // Check if user already has a pending or active rental for this property
    $stmt_check = $pdo->prepare("
        SELECT COUNT(*) FROM property_rent 
        WHERE customer_id = ? AND property_id = ? AND status_id IN (2, 3)
    ");
    $stmt_check->execute([$user['user_id'], $property_id]);
    if ($stmt_check->fetchColumn() > 0) {
        $errors[] = "You already have a pending or active rental request for this property.";
    }
    
    if (empty($errors)) {
        try {
            $stmt_rent = $pdo->prepare("
                INSERT INTO property_rent (property_id, customer_id, price_per_month, status_id, created_at)
                VALUES (?, ?, ?, 2, NOW())
            ");
            $stmt_rent->execute([
                $property_id,
                $user['user_id'],
                $property['price_per_month']
            ]);
            
            $success = "Rental request submitted successfully! The property owner will review your request.";
            
            // Optionally, create a notification for the owner
            $stmt_notif = $pdo->prepare("
                INSERT INTO notification (user_id, title, message, type_id, property_id, created_at)
                VALUES (?, ?, ?, 1, ?, NOW())
            ");
            $stmt_notif->execute([
                $property['owner_id'],
                "New Rental Request",
                $user['name'] . " has requested to rent your property: " . $property['title'],
                $property_id
            ]);
            
        } catch (PDOException $e) {
            $errors[] = "An error occurred while processing your request. Please try again.";
            error_log("Rent property error: " . $e->getMessage());
        }
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rent Property - <?= htmlspecialchars($property['title']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">
    
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="rent_property.css">
</head>
<body class="bg-light">

<?php require_once __DIR__ . '/../../navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= app_url() ?>">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?= app_url('public/property/view/property_view.php?id=' . $property_id) ?>">Property Details</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Rent Property</li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="text-center mb-4">
                <h1 class="h2 fw-bold" style="color: var(--hunter-green);">
                    <i class="bi bi-house-check me-2"></i>Rent Property
                </h1>
                <p class="text-muted">Complete your rental request</p>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <div class="text-center mb-4">
                    <a href="<?= app_url('public/my_rent/my_rent.php') ?>" class="btn btn-primary">
                        <i class="bi bi-calendar-check me-2"></i>View My Rentals
                    </a>
                    <a href="<?= app_url() ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-house me-2"></i>Back to Home
                    </a>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <!-- Property Summary Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Property Summary</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <?php if ($primary_image): ?>
                                <img src="<?= app_url($primary_image) ?>" class="img-fluid rounded" alt="Property">
                            <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 150px;">
                                    <i class="bi bi-house text-muted" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <h5 class="fw-bold mb-2"><?= htmlspecialchars($property['title']) ?></h5>
                            <p class="text-muted mb-2">
                                <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                <?= htmlspecialchars(implode(', ', array_filter([$property['address'], $property['city_name'], $property['district_name']]))) ?>
                            </p>
                            <div class="mb-2">
                                <span class="badge bg-success me-2"><?= htmlspecialchars($property['type_name']) ?></span>
                            </div>
                            <h4 class="text-success mb-0">
                                LKR <?= number_format($property['price_per_month'], 2) ?> 
                                <small class="text-muted fs-6">/ month</small>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rental Request Form -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Confirm Rental Request</h5>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        
                        <!-- User Information (Read-only) -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Your Information</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted small">Full Name</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small">Email</label>
                                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small">Mobile Number</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['mobile_number']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small">NIC</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['nic']) ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- Rental Details -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Rental Details</h6>
                            <div class="bg-light p-3 rounded">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Monthly Rent:</span>
                                    <strong>LKR <?= number_format($property['price_per_month'], 2) ?></strong>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold">Total Amount:</span>
                                    <strong class="text-success">LKR <?= number_format($property['price_per_month'], 2) ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Terms -->
                        <div class="alert alert-info mb-4">
                            <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2"></i>Important Information</h6>
                            <ul class="small mb-0">
                                <li>Your rental request will be sent to the property owner for review.</li>
                                <li>The owner will contact you to discuss further details.</li>
                                <li>Please ensure your contact information is up to date.</li>
                                <li>You can track your rental status in the "My Rent" section.</li>
                            </ul>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                                <i class="bi bi-send me-2"></i>Submit Rental Request
                            </button>
                            <a href="<?= app_url('public/property/view/property_view.php?id=' . $property_id) ?>" class="btn btn-outline-secondary btn-lg">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="rent_property.js"></script>
</body>
</html>
