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

$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($room_id <= 0) {
    header("Location: " . app_url('public/room/view_all.php'));
    exit;
}

$pdo = get_pdo();
$error = '';
$success = '';

// 2. Fetch Room
$stmt = $pdo->prepare("
    SELECT r.*, u.name as owner_name, rt.type_name
    FROM room r
    JOIN user u ON r.owner_id = u.user_id
    LEFT JOIN room_type rt ON r.room_type_id = rt.type_id
    WHERE r.room_id = ? AND r.status_id = 1
");
$stmt->execute([$room_id]);
$room = $stmt->fetch();

if (!$room) {
    die("Room not found or not available.");
}

// 3. Handle Post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checkinStr = $_POST['checkin_date'] ?? '';
    $checkoutStr = $_POST['checkout_date'] ?? '';
    $guests = (int)($_POST['guests'] ?? 1);
    
    // Validation
    if (!$checkinStr || !$checkoutStr) {
        $error = "Please select check-in and check-out dates.";
    } else {
        try {
            $checkin = new DateTime($checkinStr);
            $checkout = new DateTime($checkoutStr);
            $now = new DateTime();
            
            if ($checkin >= $checkout) {
                $error = "Check-out date must be after check-in date.";
            } elseif ($checkin < $now->modify('-1 day')) { // Allow today/recent
                // Optional
            }
            
            if ($guests > $room['maximum_guests']) {
                $error = "Maximum guests allowed is " . $room['maximum_guests'];
            }
            
            if (!$error) {
                // --- OVERLAP CHECK ---
                $sqlOverlap = "SELECT COUNT(*) FROM room_rent 
                               WHERE room_id = ? 
                               AND status_id IN (2, 3) 
                               AND (checkin_date < ? AND checkout_date > ?)";
                $stmtCheck = $pdo->prepare($sqlOverlap);
                $stmtCheck->execute([
                    $room_id, 
                    $checkout->format('Y-m-d H:i:s'), 
                    $checkin->format('Y-m-d H:i:s')
                ]);
                
                if ($stmtCheck->fetchColumn() > 0) {
                    $error = "This room is unavaiable for the selected dates.";
                }
            }
            
            if (!$error) {
                // Calculate Duration
                $diffSeconds = $checkout->getTimestamp() - $checkin->getTimestamp();
                $nights = ceil($diffSeconds / (24 * 3600));
                if ($nights < 1) $nights = 1;
                
                $total = $room['price_per_day'] * $nights;
                
                // Insert
                $pdo->beginTransaction();
                
                $sql = "INSERT INTO room_rent 
                    (room_id, customer_id, checkin_date, checkout_date, guests, price_per_night, total_amount, status_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 2)";
                
                $stmtInsert = $pdo->prepare($sql);
                $stmtInsert->execute([
                    $room_id, 
                    $user['user_id'], 
                    $checkin->format('Y-m-d H:i:s'), 
                    $checkout->format('Y-m-d H:i:s'), 
                    $guests,
                    $room['price_per_day'], 
                    $total
                ]);
                
                $pdo->commit();
                
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
    <title>Rent <?= htmlspecialchars($room['title']) ?> - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="rent_room.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body class="bg-light">

<?php require_once __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h2 class="mb-4 fw-bold text-hunter-green">Rent Room</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger shadow-sm border-0"><?= $error ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Room Summary -->
                <div class="col-md-5">
                    <div class="card shadow-sm border-0 h-100 overflow-hidden">
                        <?php 
                        $stmtImg = $pdo->prepare("SELECT image_path FROM room_image WHERE room_id = ? ORDER BY primary_image DESC LIMIT 1");
                        $stmtImg->execute([$room_id]);
                        $img = $stmtImg->fetchColumn();
                        $imgUrl = $img ? app_url($img) : 'https://via.placeholder.com/400x250?text=Room';
                        ?>
                        <img src="<?= $imgUrl ?>" class="card-img-top" alt="Room" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-hunter-green"><?= htmlspecialchars($room['title']) ?></h5>
                            <span class="badge bg-soft-success text-success mb-2"><?= htmlspecialchars($room['type_name']) ?></span>
                            
                            <ul class="list-group list-group-flush small mt-3">
                                <li class="list-group-item d-flex justify-content-between px-0 bg-transparent">
                                    <span>Price Per Night</span>
                                    <span class="fw-bold">LKR <?= number_format($room['price_per_day'], 2) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0 bg-transparent">
                                    <span>Max Guests</span>
                                    <span class="fw-bold"><?= $room['maximum_guests'] ?></span>
                                </li>
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
                                    <label class="form-label text-muted fw-bold small">Check-in Date</label>
                                    <input type="date" name="checkin_date" id="checkin_date" class="form-control" required value="<?= htmlspecialchars($_POST['checkin_date'] ?? '') ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label text-muted fw-bold small">Check-out Date</label>
                                    <input type="date" name="checkout_date" id="checkout_date" class="form-control" required value="<?= htmlspecialchars($_POST['checkout_date'] ?? '') ?>">
                                </div>

                                <div class="mb-4">
                                    <label class="form-label text-muted fw-bold small">Guests</label>
                                    <input type="number" name="guests" id="guests" class="form-control" min="1" max="<?= $room['maximum_guests'] ?>" value="1" required>
                                </div>

                                <!-- Live Summary -->
                                <div class="bg-light p-3 rounded mb-4 border border-light">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Duration:</span>
                                        <span id="summary_nights">0 Nights</span>
                                    </div>
                                    <div class="border-top mt-2 pt-2 d-flex justify-content-between fw-bold fs-5 text-hunter-green">
                                        <span>Total Amount:</span>
                                        <span id="summary_total">LKR 0.00</span>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-theme btn-lg shadow-sm">
                                        Book Now
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
    const PRICE_PER_NIGHT = <?= (float)$room['price_per_day'] ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="rent_room.js"></script>

</body>
</html>
