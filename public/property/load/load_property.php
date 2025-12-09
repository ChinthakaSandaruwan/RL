<?php
// Fetch latest active properties
$pdo = get_pdo();
$stmt = $pdo->query("SELECT p.*, pt.type_name, 
    (SELECT image_path FROM property_image WHERE property_id = p.property_id AND primary_image = 1 LIMIT 1) as primary_image
    FROM property p 
    LEFT JOIN property_type pt ON p.property_type_id = pt.type_id
    WHERE p.status_id = 1 
    ORDER BY p.created_at DESC LIMIT 6");
$properties = $stmt->fetchAll();
?>

<section class="listings-section py-5" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold" style="color: var(--hunter-green);">Latest Properties</h2>
            <a href="<?= app_url('public/property/view_all.php') ?>" class="btn btn-outline-success">View All</a>
        </div>
        
        <?php if (empty($properties)): ?>
            <div class="alert alert-info">No properties available at the moment.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($properties as $property): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm listing-card">
                            <img src="<?= $property['primary_image'] ? app_url($property['primary_image']) : 'https://via.placeholder.com/400x250?text=Property' ?>" 
                                 class="card-img-top" alt="<?= htmlspecialchars($property['title']) ?>" style="height: 200px; object-fit: cover;">
                            <div class="card-body">
                                <span class="badge bg-success mb-2"><?= htmlspecialchars($property['type_name'] ?? 'Property') ?></span>
                                <h5 class="card-title"><?= htmlspecialchars($property['title']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars(substr($property['description'], 0, 80)) ?>...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-success">LKR <?= number_format($property['price_per_month'], 2) ?>/mo</span>
                                    <a href="<?= app_url('public/property/view/property_view.php?id=' . $property['property_id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.listing-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.listing-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
}
</style>
