<?php
// Search Filters
$s_keyword = $_GET['keyword'] ?? '';
$s_province = $_GET['province'] ?? '';
$s_district = $_GET['district'] ?? '';
$s_city = $_GET['city'] ?? '';
$s_category = $_GET['category'] ?? '';

// If searching for other categories, hide this section
if ($s_category && $s_category !== 'property') {
    return;
}

$pdo = get_pdo();
$query = "SELECT p.*, pt.type_name, 
    (SELECT image_path FROM property_image WHERE property_id = p.property_id AND primary_image = 1 LIMIT 1) as primary_image
    FROM property p 
    LEFT JOIN property_type pt ON p.property_type_id = pt.type_id
    LEFT JOIN property_location pl ON p.property_id = pl.property_id
    LEFT JOIN cities c ON pl.city_id = c.id
    LEFT JOIN districts d ON c.district_id = d.id
    WHERE p.status_id = 1";

$params = [];

if ($s_keyword) {
    $query .= " AND (p.title LIKE ? OR p.description LIKE ? OR pl.address LIKE ?)";
    $params[] = "%$s_keyword%";
    $params[] = "%$s_keyword%";
    $params[] = "%$s_keyword%";
}

if ($s_city) {
    $query .= " AND pl.city_id = ?";
    $params[] = $s_city;
} elseif ($s_district) {
    $query .= " AND c.district_id = ?";
    $params[] = $s_district;
} elseif ($s_province) {
    $query .= " AND d.province_id = ?";
    $params[] = $s_province;
}

$query .= " ORDER BY p.created_at DESC LIMIT 6";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$properties = $stmt->fetchAll();

// Get wishlist items for current user
$wishlist_property_ids = [];
if (isset($user) && $user) {
    $stmt_wish = $pdo->prepare("SELECT property_id FROM property_wishlist WHERE customer_id = ?");
    $stmt_wish->execute([$user['user_id']]);
    $wishlist_property_ids = $stmt_wish->fetchAll(PDO::FETCH_COLUMN);
}
?>


<link rel="stylesheet" href="<?= app_url('public/property/load/load_property.css') ?>">
<section class="listings-section py-5" style="background-color: #f8f9fa;">

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold" style="color: var(--hunter-green);">Latest Properties</h2>
            <a href="<?= app_url('public/property/view_all/view_all.php') ?>" class="btn btn-outline-success">View All</a>
        </div>
        
        <?php if (empty($properties)): ?>
            <div class="alert alert-info">No properties available at the moment.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($properties as $property): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm listing-card">
                            <div class="position-relative">
                                <img src="<?= $property['primary_image'] ? app_url($property['primary_image']) : 'https://via.placeholder.com/400x250?text=Property' ?>" 
                                     class="card-img-top" alt="<?= htmlspecialchars($property['title']) ?>" style="height: 200px; object-fit: cover;">
                                <?php if (isset($user) && $user): 
                                    $inWishlist = in_array($property['property_id'], $wishlist_property_ids);
                                    $iconClass = $inWishlist ? 'bi-heart-fill' : 'bi-heart';
                                    $btnClass = $inWishlist ? 'active' : '';
                                ?>
                                <button class="btn btn-sm wishlist-btn position-absolute top-0 end-0 m-2 <?= $btnClass ?>" 
                                        onclick="toggleWishlist('property', <?= $property['property_id'] ?>, this)"
                                        title="<?= $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist' ?>">
                                    <i class="bi <?= $iconClass ?>"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <span class="badge badge-theme mb-2"><?= htmlspecialchars($property['type_name'] ?? 'Property') ?></span>
                                <h5 class="card-title"><?= htmlspecialchars($property['title']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars(substr($property['description'], 0, 80)) ?>...</p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="price-tag">LKR <?= number_format($property['price_per_month'], 2) ?>/mo</span>
                                    <a href="<?= app_url('public/property/view/property_view.php?id=' . $property['property_id']) ?>" class="btn-view-details">View <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>


