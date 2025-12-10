<?php
// Search Filters
$s_keyword = $_GET['keyword'] ?? '';
$s_province = $_GET['province'] ?? '';
$s_district = $_GET['district'] ?? '';
$s_city = $_GET['city'] ?? '';
$s_category = $_GET['category'] ?? '';

// If searching for other categories, hide this section
if ($s_category && $s_category !== 'room') {
    return;
}

$pdo = get_pdo();
$query = "SELECT r.*, 
    (SELECT image_path FROM room_image WHERE room_id = r.room_id ORDER BY primary_image DESC LIMIT 1) as image_path
    FROM room r 
    LEFT JOIN room_location rl ON r.room_id = rl.room_id
    LEFT JOIN cities c ON rl.city_id = c.id
    LEFT JOIN districts d ON c.district_id = d.id
    WHERE r.status_id = 1";

$params = [];

if ($s_keyword) {
    $query .= " AND (r.title LIKE ? OR r.description LIKE ? OR rl.address LIKE ?)";
    $params[] = "%$s_keyword%";
    $params[] = "%$s_keyword%";
    $params[] = "%$s_keyword%";
}

if ($s_city) {
    $query .= " AND rl.city_id = ?";
    $params[] = $s_city;
} elseif ($s_district) {
    $query .= " AND c.district_id = ?";
    $params[] = $s_district;
} elseif ($s_province) {
    $query .= " AND d.province_id = ?";
    $params[] = $s_province;
}

$query .= " ORDER BY r.created_at DESC LIMIT 6";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

// Get wishlist items for current user
$wishlist_room_ids = [];
if (isset($user) && $user) {
    $stmt_wish = $pdo->prepare("SELECT room_id FROM room_wishlist WHERE customer_id = ?");
    $stmt_wish->execute([$user['user_id']]);
    $wishlist_room_ids = $stmt_wish->fetchAll(PDO::FETCH_COLUMN);
}
?>


<link rel="stylesheet" href="<?= app_url('public/room/load/load_room.css') ?>">
<section class="listings-section py-5">

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold" style="color: var(--hunter-green);">Latest Rooms</h2>
            <a href="<?= app_url('public/room/view_all/view_all.php') ?>" class="btn btn-outline-success">View All</a>
        </div>
        
        <?php if (empty($rooms)): ?>
            <div class="alert alert-info">No rooms available at the moment.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($rooms as $room): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm listing-card">
                            <div class="position-relative">
                                <img src="<?= !empty($room['image_path']) ? app_url($room['image_path']) : 'https://via.placeholder.com/400x250?text=Room' ?>" 
                                     class="card-img-top" alt="Room" style="height: 200px; object-fit: cover;">
                                <?php if (isset($user) && $user): 
                                    $inWishlist = in_array($room['room_id'], $wishlist_room_ids);
                                    $iconClass = $inWishlist ? 'bi-heart-fill' : 'bi-heart';
                                    $btnClass = $inWishlist ? 'active' : '';
                                ?>
                                <button class="btn btn-sm wishlist-btn position-absolute top-0 end-0 m-2 <?= $btnClass ?>" 
                                        onclick="toggleWishlist('room', <?= $room['room_id'] ?>, this)"
                                        title="<?= $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist' ?>">
                                    <i class="bi <?= $iconClass ?>"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <span class="badge badge-theme mb-2">Room</span>
                                <h5 class="card-title"><?= htmlspecialchars($room['title']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars(substr($room['description'], 0, 80)) ?>...</p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="price-tag">LKR <?= number_format($room['price_per_day'], 2) ?>/night</span>
                                    <a href="<?= app_url('public/room/view/room_view.php?id=' . $room['room_id']) ?>" class="btn-view-details">View <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
