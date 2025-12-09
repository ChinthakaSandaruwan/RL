<?php
require __DIR__ . '/../../../config/db.php';

ensure_session_started();
$user = current_user();

// Check if user is Owner (role_id = 3)
if (!$user || $user['role_id'] != 3) {
    header('Location: ' . app_url('index.php'));
    exit;
}

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$pdo = get_pdo();
$errors = [];
$success = null;

// Display message from redirect
$packageMessage = $_SESSION['package_required_message'] ?? null;
unset($_SESSION['package_required_message']);

// Fetch all active packages
$stmt = $pdo->query("
    SELECT p.*, pt.type_name, ps.status_name 
    FROM package p
    JOIN package_type pt ON p.package_type_id = pt.type_id
    JOIN package_status ps ON p.status_id = ps.status_id
    WHERE p.status_id = 1
    ORDER BY p.price ASC
");
$packages = $stmt->fetchAll();

// Fetch owner's current packages
$stmt = $pdo->prepare("
    SELECT bp.*, p.package_name, p.max_properties, p.max_rooms, p.max_vehicles, 
           ss.status_name as subscription_status, ps.status_name as payment_status
    FROM bought_package bp
    JOIN package p ON bp.package_id = p.package_id
    JOIN subscription_status ss ON bp.status_id = ss.status_id
    JOIN payment_status ps ON bp.payment_status_id = ps.status_id
    WHERE bp.user_id = ?
    ORDER BY bp.bought_date DESC
");
$stmt->execute([$user['user_id']]);
$myPackages = $stmt->fetchAll();

// Handle package purchase (simplified - in production, integrate payment gateway)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['package_id'])) {
    $packageId = intval($_POST['package_id']);
    
    // Get package details
    $stmt = $pdo->prepare("SELECT * FROM package WHERE package_id = ? AND status_id = 1");
    $stmt->execute([$packageId]);
    $package = $stmt->fetch();
    
    if ($package) {
        try {
            $pdo->beginTransaction();
            
            // Calculate expiry date based on duration
            $expiresDate = null;
            if ($package['duration_days']) {
                $expiresDate = date('Y-m-d H:i:s', strtotime('+' . $package['duration_days'] . ' days'));
            }
            
            // Insert bought package
            $stmt = $pdo->prepare("
                INSERT INTO bought_package (
                    user_id, package_id, bought_date, expires_date,
                    remaining_properties, remaining_rooms, remaining_vehicles,
                    status_id, payment_status_id
                ) VALUES (?, ?, NOW(), ?, ?, ?, ?, 1, 1)
            ");
            
            $stmt->execute([
                $user['user_id'],
                $packageId,
                $expiresDate,
                $package['max_properties'],
                $package['max_rooms'],
                $package['max_vehicles']
            ]);
            
            $pdo->commit();
            
            $success = "Package purchased successfully! You can now add listings. Please complete the payment to activate your package.";
            
            // Refresh my packages
            $stmt = $pdo->prepare("
                SELECT bp.*, p.package_name, p.max_properties, p.max_rooms, p.max_vehicles, 
                       ss.status_name as subscription_status, ps.status_name as payment_status
                FROM bought_package bp
                JOIN package p ON bp.package_id = p.package_id
                JOIN subscription_status ss ON bp.status_id = ss.status_id
                JOIN payment_status ps ON bp.payment_status_id = ps.status_id
                WHERE bp.user_id = ?
                ORDER BY bp.bought_date DESC
            ");
            $stmt->execute([$user['user_id']]);
            $myPackages = $stmt->fetchAll();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Database Error: " . $e->getMessage();
        }
    } else {
        $errors[] = "Invalid package selected.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Ads Package - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= app_url('public/profile/profile.css') ?>">
    <style>
        .package-card {
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }
        .package-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
        }
        .package-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 1;
        }
        .price-tag {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2d6a4f;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        .feature-list li:last-child {
            border-bottom: none;
        }
        .alert-package {
            border-left: 4px solid #2d6a4f;
            background-color: #f0f9f4;
        }
    </style>
</head>
<body>

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark">Purchase Ads Package</h2>
            <p class="text-muted">Choose a package to start listing your properties, rooms, or vehicles</p>
        </div>
    </div>

    <?php if ($packageMessage): ?>
        <div class="alert alert-warning alert-package shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($packageMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success shadow-sm mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger shadow-sm mb-4">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $err): ?><li><?= $err ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- My Active Packages -->
    <?php if (!empty($myPackages)): ?>
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">My Packages</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Package Name</th>
                            <th>Properties</th>
                            <th>Rooms</th>
                            <th>Vehicles</th>
                            <th>Expires</th>
                            <th>Status</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myPackages as $pkg): ?>
                        <tr>
                            <td><?= htmlspecialchars($pkg['package_name']) ?></td>
                            <td>
                                <span class="badge bg-success"><?= $pkg['remaining_properties'] ?></span> / <?= $pkg['max_properties'] ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $pkg['remaining_rooms'] ?></span> / <?= $pkg['max_rooms'] ?>
                            </td>
                            <td>
                                <span class="badge bg-warning text-dark"><?= $pkg['remaining_vehicles'] ?></span> / <?= $pkg['max_vehicles'] ?>
                            </td>
                            <td><?= $pkg['expires_date'] ? date('M d, Y', strtotime($pkg['expires_date'])) : 'Never' ?></td>
                            <td>
                                <span class="badge bg-<?= $pkg['subscription_status'] == 'active' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($pkg['subscription_status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= in_array($pkg['payment_status'], ['paid', 'success']) ? 'success' : 'warning' ?>">
                                    <?= ucfirst($pkg['payment_status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Available Packages -->
    <h4 class="fw-bold mb-4">Available Packages</h4>
    <div class="row g-4">
        <?php foreach ($packages as $package): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card package-card shadow-sm position-relative">
                <?php if ($package['type_name'] == 'yearly'): ?>
                    <span class="package-badge badge bg-success">Best Value</span>
                <?php endif; ?>
                
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-3"><?= htmlspecialchars($package['package_name']) ?></h5>
                    
                    <div class="price-tag mb-3">
                        LKR <?= number_format($package['price'], 2) ?>
                        <?php if ($package['duration_days']): ?>
                            <small class="text-muted fs-6">/ <?= $package['duration_days'] ?> days</small>
                        <?php endif; ?>
                    </div>
                    
                    <ul class="feature-list mb-4">
                        <?php if ($package['max_properties'] > 0): ?>
                        <li>
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <strong><?= $package['max_properties'] ?></strong> Properties
                        </li>
                        <?php endif; ?>
                        
                        <?php if ($package['max_rooms'] > 0): ?>
                        <li>
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <strong><?= $package['max_rooms'] ?></strong> Rooms
                        </li>
                        <?php endif; ?>
                        
                        <?php if ($package['max_vehicles'] > 0): ?>
                        <li>
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <strong><?= $package['max_vehicles'] ?></strong> Vehicles
                        </li>
                        <?php endif; ?>
                        
                        <?php if ($package['duration_days']): ?>
                        <li>
                            <i class="bi bi-calendar-check text-primary me-2"></i>
                            Valid for <strong><?= $package['duration_days'] ?> days</strong>
                        </li>
                        <?php else: ?>
                        <li>
                            <i class="bi bi-infinity text-primary me-2"></i>
                            <strong>Unlimited</strong> Duration
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <?php if ($package['description']): ?>
                    <p class="text-muted small mb-4"><?= htmlspecialchars($package['description']) ?></p>
                    <?php endif; ?>
                    
                    <form method="post" class="d-grid">
                        <input type="hidden" name="package_id" value="<?= $package['package_id'] ?>">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-cart-plus me-2"></i>Purchase Now
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="alert alert-info mt-5">
        <h6 class="fw-bold"><i class="bi bi-info-circle me-2"></i>How it works:</h6>
        <ol class="mb-0 ps-3">
            <li>Select and purchase a package that suits your needs</li>
            <li>Complete the payment process</li>
            <li>Once payment is confirmed, you can start adding your listings</li>
            <li>Each property/room/vehicle you add will use one slot from your package</li>
        </ol>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
