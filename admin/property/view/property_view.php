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
$propertyId = intval($_GET['id'] ?? 0);

if (!$propertyId) {
    header('Location: ' . app_url('admin/index/index.php'));
    exit;
}

// Fetch property details
$stmt = $pdo->prepare("SELECT p.*, u.name as owner_name, u.email as owner_email, u.mobile_number as owner_phone,
    pt.type_name, ls.status_name,
    pl.address, pl.postal_code, pl.google_map_link
    FROM property p
    LEFT JOIN user u ON p.owner_id = u.user_id
    LEFT JOIN property_type pt ON p.property_type_id = pt.type_id
    LEFT JOIN listing_status ls ON p.status_id = ls.status_id
    LEFT JOIN property_location pl ON p.property_id = pl.property_id
    WHERE p.property_id = ?");
$stmt->execute([$propertyId]);
$property = $stmt->fetch();

if (!$property) {
    header('Location: ' . app_url('admin/index/index.php'));
    exit;
}

// Fetch all property images
$stmt = $pdo->prepare("SELECT * FROM property_image WHERE property_id = ? ORDER BY primary_image DESC, image_id ASC");
$stmt->execute([$propertyId]);
$images = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Property - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="property_view.css">
</head>
<body>

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="mb-4">
        <a href="<?= app_url('admin/property/approval/property_approval.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Approvals
        </a>
    </div>

    <!-- Property Header -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="card-title mb-2"><?= htmlspecialchars($property['title']) ?></h2>
                    <p class="text-muted mb-2">Property Code: <strong><?= htmlspecialchars($property['property_code']) ?></strong></p>
                </div>
                <span class="badge bg-<?= $property['status_id'] == 1 ? 'success' : ($property['status_id'] == 4 ? 'warning' : 'danger') ?> fs-6">
                    <?= htmlspecialchars($property['status_name']) ?>
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Images Section -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Property Images</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($images)): ?>
                        <div class="text-center py-5">
                            <img src="https://via.placeholder.com/600x400?text=No+Images" class="img-fluid" alt="No images">
                        </div>
                    <?php else: ?>
                        <div id="propertyCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach ($images as $index => $image): ?>
                                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                        <img src="<?= app_url($image['image_path']) ?>" class="d-block w-100 property-main-image" alt="Property image">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($images) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#propertyCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#propertyCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                </button>
                            <?php endif; ?>
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
                    <p><?= nl2br(htmlspecialchars($property['description'] ?? 'No description provided.')) ?></p>
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
                            'living_rooms' => 'Living Rooms',
                            'kitchen' => 'Kitchen',
                            'garden' => 'Garden',
                            'gym' => 'Gym',
                            'pool' => 'Swimming Pool',
                            'parking' => 'Parking',
                            'water_supply' => 'Water Supply',
                            'electricity_supply' => 'Electricity'
                        ];
                        foreach ($amenities as $key => $label):
                            if ($property[$key]):
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
                    <p class="mb-2"><strong>Name:</strong> <?= htmlspecialchars($property['owner_name']) ?></p>
                    <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($property['owner_email']) ?></p>
                    <p class="mb-0"><strong>Phone:</strong> <?= htmlspecialchars($property['owner_phone']) ?></p>
                </div>
            </div>

            <!-- Property Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Property Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Type:</strong></td>
                            <td><?= htmlspecialchars($property['type_name'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Price:</strong></td>
                            <td>LKR <?= number_format($property['price_per_month'], 2) ?>/mo</td>
                        </tr>
                        <tr>
                            <td><strong>Area:</strong></td>
                            <td><?= number_format($property['sqft'], 2) ?> sqft</td>
                        </tr>
                        <tr>
                            <td><strong>Bedrooms:</strong></td>
                            <td><?= $property['bedrooms'] ?></td>
                        </tr>
                        <tr>
                            <td><strong>Bathrooms:</strong></td>
                            <td><?= $property['bathrooms'] ?></td>
                        </tr>
                        <tr>
                            <td><strong>Listed:</strong></td>
                            <td><?= date('M j, Y', strtotime($property['created_at'])) ?></td>
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
                    <p class="mb-2"><strong>Address:</strong><br><?= htmlspecialchars($property['address'] ?? 'N/A') ?></p>
                    <p class="mb-0"><strong>Postal Code:</strong> <?= htmlspecialchars($property['postal_code'] ?? 'N/A') ?></p>
                    <?php if ($property['google_map_link']): ?>
                        <hr>
                        <a href="<?= htmlspecialchars($property['google_map_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-geo-alt"></i> View on Map
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="property_view.js"></script>
</body>
</html>
