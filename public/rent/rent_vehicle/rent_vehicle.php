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

$vehicle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($vehicle_id <= 0) {
    header("Location: " . app_url('public/vehicle/view_all.php'));
    exit;
}

$pdo = get_pdo();
$error = '';
$success = '';

// 2. Fetch Vehicle
$stmt = $pdo->prepare("
    SELECT v.*, u.name as owner_name, u.mobile_number, 
    vc.color_name, vb.brand_name, vm.model_name
    FROM vehicle v
    JOIN user u ON v.owner_id = u.user_id
    LEFT JOIN vehicle_color vc ON v.color_id = vc.color_id
    LEFT JOIN vehicle_model vm ON v.model_id = vm.model_id
    LEFT JOIN vehicle_brand vb ON vm.brand_id = vb.brand_id
    WHERE v.vehicle_id = ? AND v.status_id = 1
");
$stmt->execute([$vehicle_id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    die("Vehicle not found or not available.");
}

// 3. Handle Post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickupStr = $_POST['pickup_date'] ?? '';
    $dropoffStr = $_POST['dropoff_date'] ?? '';
    $withDriver = isset($_POST['with_driver']) ? 1 : 0;
    
    // Validation
    if (!$pickupStr || !$dropoffStr) {
        $error = "Please select pickup and dropoff dates.";
    } else {
        try {
            $pickup = new DateTime($pickupStr);
            $dropoff = new DateTime($dropoffStr);
            $now = new DateTime();
            
            if ($pickup >= $dropoff) {
                $error = "Dropoff date must be after pickup date.";
            } elseif ($pickup < $now) {
                // Optional: prevent past booking
                // $error = "Pickup date cannot be in the past."; 
            }
            
            if (!$error) {
                // --- OVERLAP CHECK ---
                // Status: 2 (Pending), 3 (Approved). Adjust if your DB uses different IDs.
                // Overlap Logic: (StartA < EndB) AND (EndA > StartB)
                $sqlOverlap = "SELECT COUNT(*) FROM vehicle_rent 
                               WHERE vehicle_id = ? 
                               AND status_id IN (2, 3) 
                               AND (pickup_date < ? AND dropoff_date > ?)";
                $stmtCheck = $pdo->prepare($sqlOverlap);
                $stmtCheck->execute([
                    $vehicle_id, 
                    $dropoff->format('Y-m-d H:i:s'), 
                    $pickup->format('Y-m-d H:i:s')
                ]);
                
                if ($stmtCheck->fetchColumn() > 0) {
                    $error = "This vehicle is already booked or pending approval for the selected dates. Please choose different dates.";
                }
            }
            
            if (!$error) {
                // Calculate Duration & Cost
                $diffSeconds = $dropoff->getTimestamp() - $pickup->getTimestamp();
                $rentDays = ceil($diffSeconds / (24 * 3600));
                if ($rentDays < 1) $rentDays = 1;
                
                $basePrice = $vehicle['price_per_day'] * $rentDays;
                $driverFee = ($withDriver && $vehicle['is_driver_available']) ? ($vehicle['driver_cost_per_day'] * $rentDays) : 0;
                $total = $basePrice + $driverFee;
                
                // Insert
                $pdo->beginTransaction();
                
                $sql = "INSERT INTO vehicle_rent 
                    (vehicle_id, customer_id, pickup_date, dropoff_date, price_per_day, with_driver, driver_fee, total_amount, status_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 2)";
                
                $stmtInsert = $pdo->prepare($sql);
                $stmtInsert->execute([
                    $vehicle_id, 
                    $user['user_id'], 
                    $pickup->format('Y-m-d H:i:s'), 
                    $dropoff->format('Y-m-d H:i:s'), 
                    $vehicle['price_per_day'], 
                    $withDriver, 
                    $driverFee, 
                    $total
                ]);
                
                $pdo->commit();

                // Send SMS to Owner
                if (!empty($vehicle['mobile_number'])) {
                    require_once __DIR__ . '/../../../services/sms.php';
                    $msg = "New booking for vehicle '{$vehicle['title']}' by {$user['name']} from {$pickup->format('Y-m-d H:i')} to {$dropoff->format('Y-m-d H:i')}.";
                    smslenz_send_sms($vehicle['mobile_number'], $msg);
                }
                
                // Redirect to my_rents tab
                header("Location: " . app_url('public/my_rent/my_rent.php')); 
                exit;
            }
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = "Booking error: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rent <?= htmlspecialchars($vehicle['title']) ?> - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= app_url('public/favicon/android-chrome-192x192.png') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= app_url('public/favicon/android-chrome-512x512.png') ?>">
    <link rel="shortcut icon" href="<?= app_url('public/favicon/favicon.ico') ?>">
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="rent_vehicle.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body class="bg-light">

<?php require_once __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h2 class="mb-4 fw-bold text-hunter-green">Rent Vehicle</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger shadow-sm border-0"><?= $error ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Vehicle Summary -->
                <div class="col-md-5">
                    <div class="card shadow-sm border-0 h-100 overflow-hidden">
                        <?php 
                        $stmtImg = $pdo->prepare("SELECT image_path FROM vehicle_image WHERE vehicle_id = ? ORDER BY primary_image DESC LIMIT 1");
                        $stmtImg->execute([$vehicle_id]);
                        $vImg = $stmtImg->fetchColumn();
                        $imgUrl = $vImg ? app_url($vImg) : 'https://via.placeholder.com/400x250?text=Vehicle';
                        ?>
                        <img src="<?= $imgUrl ?>" class="card-img-top" alt="Vehicle" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-hunter-green"><?= htmlspecialchars($vehicle['brand_name'] . ' ' . $vehicle['model_name']) ?></h5>
                            <p class="text-muted small mb-3"><?= htmlspecialchars($vehicle['title']) ?></p>
                            
                            <ul class="list-group list-group-flush small">
                                <li class="list-group-item d-flex justify-content-between px-0 bg-transparent">
                                    <span>Rate (Per Day)</span>
                                    <span class="fw-bold">LKR <?= number_format($vehicle['price_per_day'], 2) ?></span>
                                </li>
                                <?php if ($vehicle['is_driver_available']): ?>
                                <li class="list-group-item d-flex justify-content-between px-0 bg-transparent">
                                    <span>Driver Fee (Per Day)</span>
                                    <span class="fw-bold">LKR <?= number_format($vehicle['driver_cost_per_day'], 2) ?></span>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Booking Form -->
                <div class="col-md-7">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <form method="POST" id="rentForm">
                                <h5 class="fw-bold mb-3 text-secondary">Booking Details</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label text-muted fw-bold small">Pickup Date & Time</label>
                                    <input type="datetime-local" name="pickup_date" id="pickup_date" class="form-control" required value="<?= htmlspecialchars($_POST['pickup_date'] ?? '') ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label text-muted fw-bold small">Dropoff Date & Time</label>
                                    <input type="datetime-local" name="dropoff_date" id="dropoff_date" class="form-control" required value="<?= htmlspecialchars($_POST['dropoff_date'] ?? '') ?>">
                                </div>

                                <?php if ($vehicle['is_driver_available']): ?>
                                <div class="mb-3">
                                    <div class="form-check p-3 border rounded bg-light">
                                        <input class="form-check-input" type="checkbox" name="with_driver" id="with_driver" value="1" <?= isset($_POST['with_driver']) ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold" for="with_driver">
                                            Request Driver
                                            <div class="small fw-normal text-muted">Additional LKR <?= number_format($vehicle['driver_cost_per_day']) ?> per day</div>
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Live Summary -->
                                <div class="bg-light p-3 rounded mb-4 border border-light">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Duration:</span>
                                        <span id="summary_days">0 Days</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Vehicle Total:</span>
                                        <span id="summary_rent">LKR 0.00</span>
                                    </div>
                                    <?php if ($vehicle['is_driver_available']): ?>
                                    <div class="d-flex justify-content-between mb-1 text-muted" id="driver_row" style="display:none;">
                                        <span>Driver Charges:</span>
                                        <span id="summary_driver">LKR 0.00</span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="border-top mt-2 pt-2 d-flex justify-content-between fw-bold fs-5 text-hunter-green">
                                        <span>Total:</span>
                                        <span id="summary_total">LKR 0.00</span>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-theme btn-lg shadow-sm">
                                        Confirm Booking
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

<script>
    const PRICE_PER_DAY = <?= (float)$vehicle['price_per_day'] ?>;
    const DRIVER_COST = <?= (float)($vehicle['driver_cost_per_day'] ?? 0) ?>;
    const DRIVER_AVAILABLE = <?= $vehicle['is_driver_available'] ? 'true' : 'false' ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="rent_vehicle.js"></script>

</body>
</html>
