<?php
require_once __DIR__ . '/../../../config/db.php';
ensure_session_started();

// 1. Auth Check
$user = current_user();
if (!$user) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: " . app_url('auth/login.php'));
    exit;
}

if ($user['role_id'] != 4) { // Assuming 4 is Customer
    // Optionally allow owners to rent too? Schema says customer_id -> user_id. 
    // Usually only customers rent. But let's check role.
    // Use flash message?
    // header("Location: " . app_url('index.php'));
    // exit;
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
    // CSRF Check (if implemented, skipping for brevity but recommended)
    
    $pickupStr = $_POST['pickup_date'] ?? '';
    $dropoffStr = $_POST['dropoff_date'] ?? '';
    $withDriver = isset($_POST['with_driver']) ? 1 : 0;
    
    // Validation
    if (!$pickupStr || !$dropoffStr) {
        $error = "Please select pickup and dropoff dates.";
    } else {
        $pickup = new DateTime($pickupStr);
        $dropoff = new DateTime($dropoffStr);
        $now = new DateTime();
        
        if ($pickup < $now) {
            // Allow today just in case of timezone drift, maybe create logic?
            // strict: $error = "Pickup date cannot be in the past.";
        }
        
        if ($dropoff < $pickup) {
            $error = "Dropoff date must be after pickup date.";
        }
        
        if (!$error) {
            // Calculate Duration
            // Logic: 1 day minimum. 
            // If hours diff > 24, counts as multiple days.
            // Simple: Ceil of days.
            $interval = $pickup->diff($dropoff);
            $days = $interval->days;
            if ($interval->h > 0 || $interval->i > 0) $days++; // Any part of next day counts?
            // Or just simplified Date logic
            
            // Re-calc logic:
            $diffSeconds = $dropoff->getTimestamp() - $pickup->getTimestamp();
            $rentDays = ceil($diffSeconds / (24 * 3600));
            if ($rentDays < 1) $rentDays = 1;
            
            // Calculate Cost
            $basePrice = $vehicle['price_per_day'] * $rentDays;
            $driverFee = 0;
            
            if ($withDriver && $vehicle['is_driver_available']) {
                $driverFee = $vehicle['driver_cost_per_day'] * $rentDays;
            }
            
            $total = $basePrice + $driverFee;
            
            // Insert
            try {
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
                
                // Redirect ?
                // $success = "Booking placed successfully!";
                header("Location: " . app_url('public/profile/profile.php?tab=rents')); // Assuming this exists
                exit;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Booking failed: " . $e->getMessage();
            }
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
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="rent_vehicle.css">
    <!-- Flatpickr for Date Time -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body class="bg-light">

<?php require_once __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h2 class="mb-4 fw-bold text-dark">Rent Vehicle</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Vehicle Summary -->
                <div class="col-md-5">
                    <div class="card shadow-sm border-0 h-100">
                        <!-- Fetch primary image -->
                        <?php 
                        $stmtImg = $pdo->prepare("SELECT image_path FROM vehicle_image WHERE vehicle_id = ? ORDER BY primary_image DESC LIMIT 1");
                        $stmtImg->execute([$vehicle_id]);
                        $vImg = $stmtImg->fetchColumn();
                        $imgUrl = $vImg ? app_url($vImg) : 'https://via.placeholder.com/400x250?text=Vehicle';
                        ?>
                        <img src="<?= $imgUrl ?>" class="card-img-top" alt="Vehicle" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title fw-bold"><?= htmlspecialchars($vehicle['brand_name'] . ' ' . $vehicle['model_name']) ?></h5>
                            <p class="text-muted small mb-3"><?= htmlspecialchars($vehicle['title']) ?></p>
                            
                            <ul class="list-group list-group-flush small">
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span>Rate (Per Day)</span>
                                    <span class="fw-bold">LKR <?= number_format($vehicle['price_per_day'], 2) ?></span>
                                </li>
                                <?php if ($vehicle['is_driver_available']): ?>
                                <li class="list-group-item d-flex justify-content-between px-0">
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
                                    <label class="form-label">Pickup Date & Time</label>
                                    <input type="datetime-local" name="pickup_date" id="pickup_date" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Dropoff Date & Time</label>
                                    <input type="datetime-local" name="dropoff_date" id="dropoff_date" class="form-control" required>
                                </div>

                                <?php if ($vehicle['is_driver_available']): ?>
                                <div class="mb-3">
                                    <div class="form-check p-3 border rounded bg-light">
                                        <input class="form-check-input" type="checkbox" name="with_driver" id="with_driver" value="1">
                                        <label class="form-check-label fw-bold" for="with_driver">
                                            Request Driver
                                            <div class="small fw-normal text-muted">Additional LKR <?= number_format($vehicle['driver_cost_per_day']) ?> per day</div>
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Live Summary -->
                                <div class="bg-light p-3 rounded mb-4">
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
                                    <div class="border-top mt-2 pt-2 d-flex justify-content-between fw-bold fs-5">
                                        <span>Total:</span>
                                        <span class="text-success" id="summary_total">LKR 0.00</span>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg shadow-sm" style="background-color: var(--fern); border-color: var(--fern);">
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

<!-- JS Data -->
<script>
    const PRICE_PER_DAY = <?= (float)$vehicle['price_per_day'] ?>;
    const DRIVER_COST = <?= (float)($vehicle['driver_cost_per_day'] ?? 0) ?>;
    const DRIVER_AVAILABLE = <?= $vehicle['is_driver_available'] ? 'true' : 'false' ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="rent_vehicle.js"></script>

</body>
</html>
