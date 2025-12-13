<?php
require_once __DIR__ . '/../../../config/db.php';
ensure_session_started();

// 1. Auth Check
$user = current_user();
if (!$user) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: " . app_url('auth/login/index.php'));
    exit;
}

$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($property_id <= 0) {
    header("Location: " . app_url('public/property/view_all.php'));
    exit;
}

$pdo = get_pdo();
$error = '';
$error = '';
$success = '';
$csrf_token = generate_csrf_token();

// 2. Fetch Property
$stmt = $pdo->prepare("
    SELECT p.*, u.name as owner_name, u.mobile_number as owner_mobile, pt.type_name
    FROM property p
    JOIN user u ON p.owner_id = u.user_id
    LEFT JOIN property_type pt ON p.property_type_id = pt.type_id
    WHERE p.property_id = ? AND p.status_id = 1
");
$stmt->execute([$property_id]);
$property = $stmt->fetch();

if (!$property) {
    die("Property not found or not available.");
}

// 3. Handle Post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die("Invalid Request (CSRF)");

    // Check if user already has a pending/active rent for this property
    $sqlCheck = "SELECT COUNT(*) FROM property_rent 
                 WHERE property_id = ? AND status_id IN (2, 3)";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([$property_id]);
    
    if ($stmtCheck->fetchColumn() > 0) {
        $error = "This property is currently under application or already rented.";
    } else {
        try {
            // Insert
            $pdo->beginTransaction();
            
            $sql = "INSERT INTO property_rent 
                (property_id, customer_id, price_per_month, status_id, created_at) 
                VALUES (?, ?, ?, 2, NOW())";
            
            $stmtInsert = $pdo->prepare($sql);
            $stmtInsert->execute([
                $property_id, 
                $user['user_id'], 
                $property['price_per_month']
            ]);
            
            $pdo->commit();

            // Send SMS to Owner
            if (!empty($property['owner_mobile'])) {
                require_once __DIR__ . '/../../../services/sms.php';
                $msg = "New rent request for property '{$property['title']}' by {$user['name']}. Please check your dashboard.";
                smslenz_send_sms($property['owner_mobile'], $msg);
            }
            
            header("Location: " . app_url('public/my_rent/my_rent.php')); 
            exit;
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = "Request failed: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rent <?= htmlspecialchars($property['title']) ?> - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= app_url('public/favicon/android-chrome-192x192.png') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= app_url('public/favicon/android-chrome-512x512.png') ?>">
    <link rel="shortcut icon" href="<?= app_url('public/favicon/favicon.ico') ?>">
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="rent_property.css">
</head>
<body class="bg-light">

<?php require_once __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h2 class="mb-4 fw-bold text-hunter-green">Lease Application</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger shadow-sm border-0"><?= $error ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Property Summary -->
                <div class="col-md-5">
                    <div class="card shadow-sm border-0 h-100 overflow-hidden">
                        <?php 
                        $stmtImg = $pdo->prepare("SELECT image_path FROM property_image WHERE property_id = ? ORDER BY primary_image DESC LIMIT 1");
                        $stmtImg->execute([$property_id]);
                        $img = $stmtImg->fetchColumn();
                        $imgUrl = $img ? app_url($img) : 'https://via.placeholder.com/400x250?text=Property';
                        ?>
                        <img src="<?= $imgUrl ?>" class="card-img-top" alt="Property" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-hunter-green"><?= htmlspecialchars($property['title']) ?></h5>
                            <span class="badge bg-soft-success text-success mb-2"><?= htmlspecialchars($property['type_name']) ?></span>
                            
                            <ul class="list-group list-group-flush small mt-3">
                                <li class="list-group-item d-flex justify-content-between px-0 bg-transparent">
                                    <span>Rent (Per Month)</span>
                                    <span class="fw-bold">LKR <?= number_format($property['price_per_month'], 2) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0 bg-transparent">
                                    <span>Bedrooms</span>
                                    <span class="fw-bold"><?= $property['bedrooms'] ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Booking Form -->
                <div class="col-md-7">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <h5 class="fw-bold mb-3 text-secondary">Application Details</h5>
                                <p class="text-muted small">You are applying to rent this property. The owner will review your request.</p>
                                
                                <div class="bg-light p-3 rounded mb-4 border border-light">
                                    <div class="d-flex justify-content-between fw-bold fs-5 text-hunter-green">
                                        <span>Monthly Rent:</span>
                                        <span>LKR <?= number_format($property['price_per_month'], 2) ?></span>
                                    </div>
                                </div>

                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" required id="agreeTerms">
                                    <label class="form-check-label small text-muted" for="agreeTerms">
                                        I agree to the <a href="#" class="text-success">Terms of Service</a> and confirm that I wish to request this property.
                                    </label>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-theme btn-lg shadow-sm">
                                        Submit Application
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
