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
$roomId = intval($_GET['id'] ?? 0);

if (!$roomId) {
    header('Location: ' . app_url('admin/index/index.php'));
    exit;
}

// Fetch room details
$stmt = $pdo->prepare("SELECT r.*, u.name as owner_name, u.email as owner_email, u.mobile_number as owner_phone,
    rt.type_name, ls.status_name,
    rl.address, rl.postal_code, rl.google_map_link
    FROM room r
    LEFT JOIN user u ON r.owner_id = u.user_id
    LEFT JOIN room_type rt ON r.room_type_id = rt.type_id
    LEFT JOIN listing_status ls ON r.status_id = ls.status_id
    LEFT JOIN room_location rl ON r.room_id = rl.room_id
    WHERE r.room_id = ?");
$stmt->execute([$roomId]);
$room = $stmt->fetch();

if (!$room) {
    header('Location: ' . app_url('admin/index/index.php'));
    exit;
}

// Fetch room images
$stmt = $pdo->prepare("SELECT * FROM room_image WHERE room_id = ? ORDER BY primary_image DESC, image_id ASC");
$stmt->execute([$roomId]);
$roomImages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Room - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="room_view.css">
</head>
<body>

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="mb-4">
        <a href="<?= app_url('admin/room/approval/room_approval.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Approvals
        </a>
    </div>

    <!-- Room Header -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="card-title mb-2"><?= htmlspecialchars($room['title']) ?></h2>
                    <p class="text-muted mb-2">Room Code: <strong><?= htmlspecialchars($room['room_code']) ?></strong></p>
                </div>
                <span class="badge bg-<?= $room['status_id'] == 1 ? 'success' : ($room['status_id'] == 4 ? 'warning' : 'danger') ?> fs-6">
                    <?= htmlspecialchars($room['status_name']) ?>
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Room Images Carousel -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Room Images</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($roomImages)): ?>
                        <div id="roomImagesCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-indicators">
                                <?php foreach ($roomImages as $index => $image): ?>
                                    <button type="button" data-bs-target="#roomImagesCarousel" data-bs-slide-to="<?= $index ?>" 
                                        <?= $index === 0 ? 'class="active" aria-current="true"' : '' ?> 
                                        aria-label="Slide <?= $index + 1 ?>"></button>
                                <?php endforeach; ?>
                            </div>
                            <div class="carousel-inner">
                                <?php foreach ($roomImages as $index => $image): ?>
                                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                        <img src="<?= app_url($image['image_path']) ?>" class="d-block w-100 room-carousel-image" alt="Room image <?= $index + 1 ?>">
                                        <?php if ($image['primary_image']): ?>
                                            <div class="carousel-caption d-none d-md-block">
                                                <span class="badge bg-success">Primary Image</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#roomImagesCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#roomImagesCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <img src="https://via.placeholder.com/600x400?text=No+Images" class="img-fluid" alt="No images">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Description -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Description</h5>
                </div>
                <div class="card-body">
                    <p><?= nl2br(htmlspecialchars($room['description'] ?? 'No description provided.')) ?></p>
                </div>
            </div>

            <!-- Features & Amenities -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Features & Amenities</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php
                        $amenities = [
                            'ac' => 'Air Conditioning',
                            'wifi' => 'WiFi',
                            'attached_bathroom' => 'Attached Bathroom',
                            'kitchen' => 'Kitchen Access',
                            'parking' => 'Parking'
                        ];
                        foreach ($amenities as $key => $label):
                            if ($room[$key]):
                        ?>
                            <div class="col-6 col-md-4">
                                <div class="feature-badge">
                                    <i class="bi bi-check-circle-fill text-success"></i> <?= $label ?>
                                </div>
                            </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Sidebar -->
        <div class="col-lg-4">
            <!-- Owner Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Owner Information</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Name:</strong> <?= htmlspecialchars($room['owner_name']) ?></p>
                    <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($room['owner_email']) ?></p>
                    <p class="mb-0"><strong>Phone:</strong> <?= htmlspecialchars($room['owner_phone']) ?></p>
                </div>
            </div>

            <!-- Room Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Room Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Type:</strong></td>
                            <td><?= htmlspecialchars($room['type_name'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Price:</strong></td>
                            <td>LKR <?= number_format($room['price_per_day'], 2) ?>/day</td>
                        </tr>
                        <tr>
                            <td><strong>Beds:</strong></td>
                            <td><?= $room['beds'] ?></td>
                        </tr>
                        <tr>
                            <td><strong>Bathrooms:</strong></td>
                            <td><?= $room['bathrooms'] ?></td>
                        </tr>
                        <tr>
                            <td><strong>Max Guests:</strong></td>
                            <td><?= $room['maximum_guests'] ?? 'N/A' ?></td>
                        </tr>
                        <tr>
                            <td><strong>Listed:</strong></td>
                            <td><?= date('M j, Y', strtotime($room['created_at'])) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Location -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Location</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Address:</strong><br><?= htmlspecialchars($room['address'] ?? 'N/A') ?></p>
                    <p class="mb-0"><strong>Postal Code:</strong> <?= htmlspecialchars($room['postal_code'] ?? 'N/A') ?></p>
                    <?php if ($room['google_map_link']): ?>
                        <hr>
                        <a href="<?= htmlspecialchars($room['google_map_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-geo-alt"></i> View on Map
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="room_view.js"></script>
</body>
</html>
