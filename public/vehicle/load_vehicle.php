<?php
// Fetch latest active vehicles
$pdo = get_pdo();
$stmt = $pdo->query("SELECT v.*, vt.type_name,
    (SELECT image_path FROM vehicle_image WHERE vehicle_id = v.vehicle_id AND primary_image = 1 LIMIT 1) as primary_image
    FROM vehicle v 
    LEFT JOIN vehicle_type vt ON v.vehicle_type_id = vt.type_id
    WHERE v.status_id = 1 
    ORDER BY v.created_at DESC LIMIT 6");
$vehicles = $stmt->fetchAll();
?>

<section class="listings-section py-5" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold" style="color: var(--hunter-green);">Latest Vehicles</h2>
            <a href="<?= app_url('public/vehicle/view_all.php') ?>" class="btn btn-outline-success">View All</a>
        </div>
        
        <?php if (empty($vehicles)): ?>
            <div class="alert alert-info">No vehicles available at the moment.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($vehicles as $vehicle): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm listing-card">
                            <img src="<?= $vehicle['primary_image'] ? app_url($vehicle['primary_image']) : 'https://via.placeholder.com/400x250?text=Vehicle' ?>" 
                                 class="card-img-top" alt="<?= htmlspecialchars($vehicle['title']) ?>" style="height: 200px; object-fit: cover;">
                            <div class="card-body">
                                <span class="badge bg-warning text-dark mb-2"><?= htmlspecialchars($vehicle['type_name'] ?? 'Vehicle') ?></span>
                                <h5 class="card-title"><?= htmlspecialchars($vehicle['title']) ?></h5>
                                <p class="card-text text-muted"><strong><?= htmlspecialchars($vehicle['model']) ?></strong></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-success">LKR <?= number_format($vehicle['price_per_day'], 2) ?>/day</span>
                                    <a href="<?= app_url('public/vehicle/details.php?id=' . $vehicle['vehicle_id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
