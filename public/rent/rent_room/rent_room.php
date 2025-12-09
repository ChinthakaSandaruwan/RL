<?php
require_once __DIR__ . '/../../../config/db.php';
ensure_session_started();

$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;

if ($room_id <= 0) {
    header("Location: " . app_url());
    exit;
}

$pdo = get_pdo();

// Fetch room details
$stmt = $pdo->prepare("
    SELECT 
        r.*, 
        rt.type_name,
        rl.address, rl.google_map_link, rl.postal_code,
        c.name_en as city_name, d.name_en as district_name,
        u.name as owner_name, u.email as owner_email, u.mobile_number as owner_phone
    FROM room r
    LEFT JOIN room_type rt ON r.room_type_id = rt.type_id
    LEFT JOIN room_location rl ON r.room_id = rl.room_id
    LEFT JOIN cities c ON rl.city_id = c.id
    LEFT JOIN districts d ON c.district_id = d.id
    LEFT JOIN user u ON r.owner_id = u.user_id
    WHERE r.room_id = ?
");
$stmt->execute([$room_id]);
$room = $stmt->fetch();

if (!$room) {
    header("Location: " . app_url());
    exit;
}

// Fetch primary image
$stmt_img = $pdo->prepare("SELECT image_path FROM room_image WHERE room_id = ? ORDER BY primary_image DESC LIMIT 1");
$stmt_img->execute([$room_id]);
$primary_image = $stmt_img->fetchColumn();

if (!$primary_image) {
    $primary_image = 'public/assets/images/placeholder-room.jpg';
}

// Fetch available meals
$stmt_meals = $pdo->prepare("
    SELECT mt.type_id, mt.type_name, rm.price
    FROM room_meal rm
    JOIN meal_type mt ON rm.meal_type_id = mt.type_id
    WHERE rm.room_id = ?
");
$stmt_meals->execute([$room_id]);
$meals = $stmt_meals->fetchAll();

// Handle form submission
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = current_user();
    
    if (!$user) {
        $error = 'Please log in to make a booking.';
    } else {
        $checkin_date = $_POST['checkin_date'] ?? '';
        $checkout_date = $_POST['checkout_date'] ?? '';
        $guests = (int)($_POST['guests'] ?? 1);
        $meal_id = !empty($_POST['meal_id']) ? (int)$_POST['meal_id'] : null;
        $special_requests = $_POST['special_requests'] ?? '';
        
        // Validation
        if (empty($checkin_date) || empty($checkout_date)) {
            $error = 'Please select check-in and check-out dates.';
        } elseif (strtotime($checkin_date) < strtotime('today')) {
            $error = 'Check-in date cannot be in the past.';
        } elseif (strtotime($checkout_date) <= strtotime($checkin_date)) {
            $error = 'Check-out date must be after check-in date.';
        } elseif ($guests < 1 || $guests > $room['maximum_guests']) {
            $error = 'Number of guests must be between 1 and ' . $room['maximum_guests'];
        } else {
            // Calculate total
            $datetime1 = new DateTime($checkin_date);
            $datetime2 = new DateTime($checkout_date);
            $interval = $datetime1->diff($datetime2);
            $days = $interval->days;
            
            $room_total = $days * $room['price_per_day'];
            $meal_total = 0;
            
            if ($meal_id) {
                foreach ($meals as $meal) {
                    if ($meal['type_id'] == $meal_id) {
                        $meal_total = $days * $guests * $meal['price'];
                        break;
                    }
                }
            }
            
            $total_amount = $room_total + $meal_total;
            
            // Get default rent status (pending = 2)
            try {
                $stmt_insert = $pdo->prepare("
                    INSERT INTO room_rent 
                    (room_id, customer_id, checkin_date, checkout_date, guests, meal_id, price_per_night, total_amount, status_id, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 2, NOW())
                ");
                
                $stmt_insert->execute([
                    $room_id,
                    $user['user_id'],
                    $checkin_date,
                    $checkout_date,
                    $guests,
                    $meal_id,
                    $room['price_per_day'],
                    $total_amount
                ]);
                
                $success = true;
                
                // You could redirect to a confirmation page or payment page here
                // header("Location: " . app_url('public/rent/confirmation.php?rent_id=' . $pdo->lastInsertId()));
                // exit;
                
            } catch (PDOException $e) {
                $error = 'Booking failed. Please try again. Error: ' . $e->getMessage();
            }
        }
    }
}

$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book <?= htmlspecialchars($room['title']) ?> - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">
    
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="rent_room.css">
</head>
<body class="bg-light">

<?php require_once __DIR__ . '/../../navbar/navbar.php'; ?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= app_url() ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= app_url('public/room/view_all.php') ?>">Rooms</a></li>
            <li class="breadcrumb-item"><a href="<?= app_url('public/room/view/room_view.php?id=' . $room_id) ?>"><?= htmlspecialchars($room['title']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Book Now</li>
        </ol>
    </nav>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <strong>Booking Successful!</strong> Your room reservation has been submitted. The owner will contact you shortly to confirm.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Booking Form -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h4 fw-bold mb-4"><i class="bi bi-calendar-check text-primary me-2"></i>Complete Your Booking</h2>
                    
                    <?php if (!$user): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Please <a href="<?= app_url('public/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])) ?>" class="alert-link">log in</a> to make a booking.
                    </div>
                    <?php endif; ?>

                    <form method="POST" id="bookingForm" <?= !$user ? 'style="pointer-events: none; opacity: 0.6;"' : '' ?>>
                        <div class="row g-3">
                            <!-- Check-in Date -->
                            <div class="col-md-6">
                                <label for="checkin_date" class="form-label fw-semibold">
                                    <i class="bi bi-box-arrow-in-right text-success me-1"></i> Check-in Date
                                </label>
                                <input type="date" class="form-control form-control-lg" id="checkin_date" name="checkin_date" 
                                       min="<?= date('Y-m-d') ?>" required <?= !$user ? 'disabled' : '' ?>>
                            </div>

                            <!-- Check-out Date -->
                            <div class="col-md-6">
                                <label for="checkout_date" class="form-label fw-semibold">
                                    <i class="bi bi-box-arrow-right text-danger me-1"></i> Check-out Date
                                </label>
                                <input type="date" class="form-control form-control-lg" id="checkout_date" name="checkout_date" 
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required <?= !$user ? 'disabled' : '' ?>>
                            </div>

                            <!-- Number of Guests -->
                            <div class="col-md-6">
                                <label for="guests" class="form-label fw-semibold">
                                    <i class="bi bi-people text-primary me-1"></i> Number of Guests
                                </label>
                                <input type="number" class="form-control form-control-lg" id="guests" name="guests" 
                                       min="1" max="<?= $room['maximum_guests'] ?>" value="1" required <?= !$user ? 'disabled' : '' ?>>
                                <small class="text-muted">Maximum: <?= $room['maximum_guests'] ?> guests</small>
                            </div>

                            <!-- Meal Selection -->
                            <?php if (!empty($meals)): ?>
                            <div class="col-md-6">
                                <label for="meal_id" class="form-label fw-semibold">
                                    <i class="bi bi-egg-fried text-warning me-1"></i> Meal Plan (Optional)
                                </label>
                                <select class="form-select form-select-lg" id="meal_id" name="meal_id" <?= !$user ? 'disabled' : '' ?>>
                                    <option value="">No meals</option>
                                    <?php foreach ($meals as $meal): ?>
                                        <option value="<?= $meal['type_id'] ?>" data-price="<?= $meal['price'] ?>">
                                            <?= htmlspecialchars($meal['type_name']) ?> - LKR <?= number_format($meal['price']) ?>/person/day
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <!-- Special Requests -->
                            <div class="col-12">
                                <label for="special_requests" class="form-label fw-semibold">
                                    <i class="bi bi-chat-left-text text-info me-1"></i> Special Requests (Optional)
                                </label>
                                <textarea class="form-control" id="special_requests" name="special_requests" rows="3" 
                                          placeholder="Any special requirements or requests..." <?= !$user ? 'disabled' : '' ?>></textarea>
                            </div>

                            <!-- Pricing Summary (Dynamic) -->
                            <div class="col-12">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h5 class="fw-bold mb-3">Pricing Summary</h5>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Number of nights:</span>
                                            <strong id="nightsCount">0</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Room rate (LKR <?= number_format($room['price_per_day']) ?> × <span id="nightsCount2">0</span> nights):</span>
                                            <strong id="roomTotal">LKR 0.00</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2" id="mealRow" style="display: none !important;">
                                            <span>Meals (<span id="mealName"></span>):</span>
                                            <strong id="mealTotal">LKR 0.00</strong>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <h5 class="fw-bold mb-0">Total Amount:</h5>
                                            <h5 class="fw-bold text-primary mb-0" id="grandTotal">LKR 0.00</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="col-12">
                                <button type="submit" class="btn btn-success btn-lg w-100" <?= !$user ? 'disabled' : '' ?>>
                                    <i class="bi bi-check-circle me-2"></i> Confirm Booking
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Room Summary Sidebar -->
        <div class="col-lg-4">
            <!-- Room Details Card -->
            <div class="card border-0 shadow-sm mb-4 sticky-top" style="top: 20px;">
                <img src="<?= app_url($primary_image) ?>" class="card-img-top" alt="Room" 
                     onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
                <div class="card-body">
                    <span class="badge bg-primary mb-2"><?= htmlspecialchars($room['type_name']) ?></span>
                    <h5 class="fw-bold mb-2"><?= htmlspecialchars($room['title']) ?></h5>
                    <p class="text-muted small mb-3">
                        <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                        <?= htmlspecialchars(implode(', ', array_filter([$room['city_name'], $room['district_name']]))) ?>
                    </p>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Price per night:</span>
                        <h5 class="fw-bold text-primary mb-0">LKR <?= number_format($room['price_per_day']) ?></h5>
                    </div>

                    <hr>

                    <div class="row g-2 text-center small">
                        <div class="col-4">
                            <i class="bi bi-door-closed d-block text-primary fs-4"></i>
                            <strong><?= $room['beds'] ?></strong> Beds
                        </div>
                        <div class="col-4">
                            <i class="bi bi-droplet d-block text-primary fs-4"></i>
                            <strong><?= $room['bathrooms'] ?></strong> Baths
                        </div>
                        <div class="col-4">
                            <i class="bi bi-people d-block text-primary fs-4"></i>
                            <strong><?= $room['maximum_guests'] ?></strong> Guests
                        </div>
                    </div>

                    <hr>

                    <h6 class="fw-bold mb-2">Contact Owner</h6>
                    <p class="small mb-2"><i class="bi bi-person-fill me-1"></i> <?= htmlspecialchars($room['owner_name']) ?></p>
                    <p class="small mb-0"><i class="bi bi-telephone-fill me-1"></i> <?= htmlspecialchars($room['owner_phone']) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="rent_room.js"></script>
<script>
    // Pass room data to JavaScript
    const roomData = {
        pricePerDay: <?= $room['price_per_day'] ?>,
        maxGuests: <?= $room['maximum_guests'] ?>
    };
</script>
</body>
</html>
