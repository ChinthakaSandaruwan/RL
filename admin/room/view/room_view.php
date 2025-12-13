<?php
require __DIR__ . '/../../../config/db.php';

ensure_session_started();
$user = current_user();

// Check if user is Admin (role_id = 2)
if (!$user || $user['role_id'] != 2) {
    header('Location: ' . app_url('index.php'));
    exit;
}

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$pdo = get_pdo();
$csrf_token = generate_csrf_token(); // Generate Token
$roomId = intval($_GET['id'] ?? 0);

if (!$roomId) {
    header('Location: ' . app_url('admin/index/index.php'));
    exit;
}

// 1. Fetch Room Details + Location + Owner + Type
$sql = "
    SELECT 
        r.*, 
        u.name as owner_name, u.email as owner_email, u.mobile_number as owner_phone, u.profile_image as owner_image,
        rt.type_name, 
        ls.status_name,
        rl.address, rl.postal_code, rl.google_map_link,
        c.name_en as city_name,
        d.name_en as district_name,
        p.name_en as province_name
    FROM room r
    LEFT JOIN user u ON r.owner_id = u.user_id
    LEFT JOIN room_type rt ON r.room_type_id = rt.type_id
    LEFT JOIN listing_status ls ON r.status_id = ls.status_id
    LEFT JOIN room_location rl ON r.room_id = rl.room_id
    LEFT JOIN cities c ON rl.city_id = c.id
    LEFT JOIN districts d ON c.district_id = d.id
    LEFT JOIN provinces p ON d.province_id = p.id
    WHERE r.room_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$roomId]);
$room = $stmt->fetch();

if (!$room) {
    die("Room not found.");
}

// 2. Fetch Images
$stmt = $pdo->prepare("SELECT * FROM room_image WHERE room_id = ? ORDER BY primary_image DESC, image_id ASC");
$stmt->execute([$roomId]);
$images = $stmt->fetchAll();

// 3. Fetch Amenities
$stmt = $pdo->prepare("
    SELECT a.amenity_name, a.category 
    FROM room_amenity ra 
    JOIN amenity a ON ra.amenity_id = a.amenity_id 
    WHERE ra.room_id = ?
    ORDER BY a.amenity_name ASC
");
$stmt->execute([$roomId]);
$amenities = $stmt->fetchAll();

// 4. Fetch Meal Plans
$stmt = $pdo->prepare("
    SELECT mt.type_name, rm.price 
    FROM room_meal rm 
    JOIN meal_type mt ON rm.meal_type_id = mt.type_id 
    WHERE rm.room_id = ?
    ORDER BY mt.type_name ASC
");
$stmt->execute([$roomId]);
$meals = $stmt->fetchAll();

// Handle Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF Token");
    }

    $action = $_POST['action'] ?? '';
    
    // Include Notification Logic
    require_once __DIR__ . '/../../notification/owner/room_approval_notification/room_approval_notification_auto.php';

    if ($action === 'approve') {
        $upd = $pdo->prepare("UPDATE room SET status_id = 1 WHERE room_id = ?");
        $upd->execute([$roomId]);
        
        if ($room) {
            notify_owner_room_status($room['owner_id'], $room['title'], 'approved');
        }

        header("Location: ".app_url("admin/room/view/room_view.php?id=$roomId"));
        exit;
    } elseif ($action === 'reject') {
        if ($room) {
            notify_owner_room_status($room['owner_id'], $room['title'], 'rejected');
            // Refund the quota
            increment_package_quota($room['owner_id'], 'room');
        }

        // Delete the room matching approval page logic
        $del = $pdo->prepare("DELETE FROM room WHERE room_id = ?");
        $del->execute([$roomId]);
        
        // Redirect to Approval List since room is deleted
        header("Location: ".app_url("admin/room/approval/room_approval.php?success=rejected"));
        exit;
    }
}

// Determine Price Label (Daily or Monthly)
$price = $room['rent_per_month'] ?? $room['price_per_day'] ?? 0;
$priceLabel = isset($room['rent_per_month']) ? '/month' : '/day';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Details - Admin View</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="room_view.css">
    <style>
        .feature-icon { width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; background: #eef2f6; color: #2c3e50; border-radius: 50%; margin-right: 10px; }
        .owner-avatar { width: 60px; height: 60px; object-fit: cover; border-radius: 50%; }
        .carousel-item img { height: 400px; object-fit: cover; }
    </style>
</head>
<body class="bg-light">

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="mb-4 d-flex justify-content-between">
        <a href="<?= app_url('admin/room/approval/room_approval.php') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <div>
            <?php if ($room['status_id'] == 4): // Pending ?>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="action" value="approve">
                    <button class="btn btn-success me-2"><i class="fas fa-check"></i> Approve</button>
                </form>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="action" value="reject">
                    <button class="btn btn-danger"><i class="fas fa-times"></i> Reject</button>
                </form>
            <?php else: ?>
                <span class="badge bg-<?= $room['status_id'] == 1 ? 'success' : 'secondary' ?> fs-5">
                    <?= htmlspecialchars($room['status_name']) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Header -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h2 class="fw-bold mb-1"><?= htmlspecialchars($room['title']) ?></h2>
                            <p class="text-muted mb-0">
                                <i class="fas fa-map-marker-alt text-danger"></i> 
                                <?= htmlspecialchars($room['city_name'] ?? '') ?>, <?= htmlspecialchars($room['district_name'] ?? '') ?>
                            </p>
                        </div>
                        <div class="text-end">
                            <h3 class="text-success fw-bold mb-0">LKR <?= number_format($price, 2) ?></h3>
                            <small class="text-muted"><?= $priceLabel ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Images -->
            <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                <?php if (!empty($images)): ?>
                    <div id="roomCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php foreach ($images as $k => $img): ?>
                                <div class="carousel-item <?= $k === 0 ? 'active' : '' ?>">
                                    <img src="<?= app_url($img['image_path']) ?>" class="d-block w-100" alt="Room Image">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#roomCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#roomCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="p-5 text-center bg-light">No images available</div>
                <?php endif; ?>
            </div>

            <!-- Overview -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-4">Overview</h5>
                    <div class="row text-center g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-bed fa-2x text-primary mb-2"></i>
                                <div class="fw-bold"><?= $room['room_type_id'] ?></div> <!-- Should assume fetched type name in variable-->
                                <div class="small text-muted"><?= htmlspecialchars($room['type_name']) ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <div class="fw-bold"><?= $room['maximum_guests'] ?></div>
                                <div class="small text-muted">Max Guests</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-bed fa-2x text-primary mb-2"></i>
                                <div class="fw-bold"><?= $room['beds'] ?> Beds</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-bath fa-2x text-primary mb-2"></i>
                                <div class="fw-bold"><?= $room['bathrooms'] ?> Baths</div>
                            </div>
                        </div>
                    </div>
                    <h5 class="fw-bold">Description</h5>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($room['description'])) ?></p>
                </div>
            </div>

            <!-- Amenities -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Amenities</h5>
                    <?php if ($amenities): ?>
                        <div class="row">
                            <?php foreach ($amenities as $am): ?>
                                <div class="col-md-4 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <?= htmlspecialchars($am['amenity_name']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No specific amenities listed.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Meals -->
             <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Available Meal Plans</h5>
                    <?php if ($meals): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light"><tr><th>Meal Plan</th><th>Price (LKR)</th></tr></thead>
                                <tbody>
                                    <?php foreach ($meals as $m): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($m['type_name']) ?></td>
                                            <td><?= number_format($m['price'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No meal plans offered.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Owner Profile -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center">
                    <?php 
                        $ownerImgSrc = $room['owner_image'] ? app_url($room['owner_image']) : 'https://ui-avatars.com/api/?name='.urlencode($room['owner_name']).'&background=random&color=ffffff&size=150';
                    ?>
                    <img src="<?= $ownerImgSrc ?>" class="owner-avatar mb-3" alt="Owner">
                    <h5 class="fw-bold"><?= htmlspecialchars($room['owner_name']) ?></h5>
                    <p class="text-muted small mb-3">Property Owner</p>
                    <div class="d-grid gap-2">
                        <a href="mailto:<?= htmlspecialchars($room['owner_email']) ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-envelope me-2"></i> Email Owner
                        </a>
                        <a href="tel:<?= htmlspecialchars($room['owner_phone']) ?>" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-phone me-2"></i> Call Owner
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Info -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">System Info</div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Room Code:</span>
                            <span class="fw-bold"><?= htmlspecialchars($room['room_code']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Created At:</span>
                            <span class="text-muted"><?= date('Y-m-d', strtotime($room['created_at'])) ?></span>
                        </li>
                        <li class="list-group-item px-0">
                            <strong>Google Map:</strong>
                            <?php if ($room['google_map_link']): ?>
                                <a href="<?= htmlspecialchars($room['google_map_link']) ?>" target="_blank" class="d-block text-truncate mt-1">View on Map</a>
                            <?php else: ?>
                                <span class="text-muted">Not Provided</span>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
