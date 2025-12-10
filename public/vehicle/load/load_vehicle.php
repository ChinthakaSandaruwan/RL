<?php
// Search Filters
$s_keyword = $_GET['keyword'] ?? '';
$s_province = $_GET['province'] ?? '';
$s_district = $_GET['district'] ?? '';
$s_city = $_GET['city'] ?? '';
$s_category = $_GET['category'] ?? '';

// If searching for other categories, hide this section
if ($s_category && $s_category !== 'vehicle') {
    return;
}

$pdo = get_pdo();
$query = "SELECT v.*, vt.type_name, vm.model_name, vb.brand_name,
    (SELECT image_path FROM vehicle_image WHERE vehicle_id = v.vehicle_id AND primary_image = 1 LIMIT 1) as primary_image
    FROM vehicle v 
    LEFT JOIN vehicle_type vt ON v.vehicle_type_id = vt.type_id
    LEFT JOIN vehicle_model vm ON v.model_id = vm.model_id
    LEFT JOIN vehicle_brand vb ON vm.brand_id = vb.brand_id
    LEFT JOIN vehicle_location vl ON v.vehicle_id = vl.vehicle_id
    LEFT JOIN cities c ON vl.city_id = c.id
    LEFT JOIN districts d ON c.district_id = d.id
    WHERE v.status_id = 1";

$params = [];

if ($s_keyword) {
    $titleKeyword = "%$s_keyword%";
    $query .= " AND (v.title LIKE ? OR v.description LIKE ? OR vl.address LIKE ? OR vb.brand_name LIKE ? OR vm.model_name LIKE ?)";
    $params[] = $titleKeyword;
    $params[] = $titleKeyword;
    $params[] = $titleKeyword;
    $params[] = $titleKeyword;
    $params[] = $titleKeyword;
}

if ($s_city) {
    $query .= " AND vl.city_id = ?";
    $params[] = $s_city;
} elseif ($s_district) {
    $query .= " AND c.district_id = ?";
    $params[] = $s_district;
} elseif ($s_province) {
    $query .= " AND d.province_id = ?";
    $params[] = $s_province;
}

$query .= " ORDER BY v.created_at DESC LIMIT 6";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

// Get wishlist items for current user
$wishlist_vehicle_ids = [];
if (isset($user) && $user) {
    $stmt_wish = $pdo->prepare("SELECT vehicle_id FROM vehicle_wishlist WHERE customer_id = ?");
    $stmt_wish->execute([$user['user_id']]);
    $wishlist_vehicle_ids = $stmt_wish->fetchAll(PDO::FETCH_COLUMN);
}
?>


<link rel="stylesheet" href="<?= app_url('public/vehicle/load/load_vehicle.css') ?>">
<section class="listings-section py-5" style="background-color: #f8f9fa;">

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold" style="color: var(--hunter-green);">Latest Vehicles</h2>
            <a href="<?= app_url('public/vehicle/view_all/view_all.php') ?>" class="btn btn-outline-success">View All</a>
        </div>
        
        <?php if (empty($vehicles)): ?>
            <div class="alert alert-info">No vehicles available at the moment.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($vehicles as $vehicle): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm listing-card">
                            <div class="position-relative">
                                <img src="<?= $vehicle['primary_image'] ? app_url($vehicle['primary_image']) : 'https://via.placeholder.com/400x250?text=Vehicle' ?>" 
                                     class="card-img-top" alt="<?= htmlspecialchars($vehicle['title']) ?>" style="height: 200px; object-fit: cover;">
                                <?php if (isset($user) && $user): 
                                    $inWishlist = in_array($vehicle['vehicle_id'], $wishlist_vehicle_ids);
                                    $iconClass = $inWishlist ? 'bi-heart-fill' : 'bi-heart';
                                    $btnClass = $inWishlist ? 'active' : '';
                                ?>
                                <button class="btn btn-sm wishlist-btn position-absolute top-0 end-0 m-2 <?= $btnClass ?>" 
                                        onclick="toggleWishlist('vehicle', <?= $vehicle['vehicle_id'] ?>, this)"
                                        title="<?= $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist' ?>">
                                    <i class="bi <?= $iconClass ?>"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <span class="badge badge-theme mb-2"><?= htmlspecialchars($vehicle['type_name'] ?? 'Vehicle') ?></span>
                                <h5 class="card-title"><?= htmlspecialchars($vehicle['title']) ?></h5>
                                <p class="card-text text-muted"><strong><?= htmlspecialchars(($vehicle['brand_name'] ?? '') . ' ' . ($vehicle['model_name'] ?? '')) ?></strong></p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="price-tag">LKR <?= number_format($vehicle['price_per_day'], 2) ?>/day</span>
                                    <a href="<?= app_url('public/vehicle/view/vehicle_view.php?id=' . $vehicle['vehicle_id']) ?>" class="btn-view-details">View <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
