<?php
// Rental Card Component - Reusable for property, room, and vehicle rentals
// Expected variables: $rental (with 'type' field)

$status_class = [
    'Pending' => 'warning',
    'Confirmed' => 'success',
    'Rejected' => 'danger',
    'Canceled' => 'secondary',
    'Completed' => 'info'
];

$type_icons = [
    'property' => 'house-door',
    'room' => 'door-open',
    'vehicle' => 'car-front'
];

$status_badge = $status_class[$rental['status_name'] ?? 'Pending'] ?? 'secondary';
$type_icon = $type_icons[$rental['type']] ?? 'bookmark';

// Determine title and link based on type
if ($rental['type'] == 'property') {
    $title = $rental['property_title'] ?? 'Property';
    $view_link = app_url('public/property/view/property_view.php?id=' . $rental['property_id']);
    $price_label = 'Monthly Rent';
    $price = $rental['price_per_month'];
} elseif ($rental['type'] == 'room') {
    $title = $rental['room_title'] ?? 'Room';
    $view_link = app_url('public/room/view/room_view.php?id=' . $rental['room_id']);
    $price_label = 'Total Amount';
    $price = $rental['total_amount'];
} else {
    $title = $rental['vehicle_title'] ?? 'Vehicle';
    $view_link = app_url('public/vehicle/view/vehicle_view.php?id=' . $rental['vehicle_id']);
    $price_label = 'Total Amount';
    $price = $rental['total_amount'];
}
?>

<div class="card border-0 shadow-sm mb-3 rental-card">
    <div class="card-body p-3">
        <div class="row g-3">
            <!-- Image -->
            <div class="col-md-3">
                <?php if (!empty($rental['image_path'])): ?>
                    <img src="<?= app_url($rental['image_path']) ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($title) ?>" style="height: 150px; width: 100%; object-fit: cover;">
                <?php else: ?>
                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 150px;">
                        <i class="bi bi-<?= $type_icon ?> text-muted" style="font-size: 2.5rem;"></i>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Content -->
            <div class="col-md-6">
                <div class="d-flex align-items-start gap-2 mb-2">
                    <span class="badge bg-<?= $status_badge ?>"><?= htmlspecialchars($rental['status_name']) ?></span>
                    <span class="badge bg-secondary text-capitalize"><?= $rental['type'] ?></span>
                </div>
                <h5 class="fw-bold mb-2"><?= htmlspecialchars($title) ?></h5>
                <?php if (!empty($rental['type_name'])): ?>
                    <p class="text-muted small mb-1">
                        <i class="bi bi-tag me-1"></i><?= htmlspecialchars($rental['type_name']) ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($rental['address']) || !empty($rental['city_name'])): ?>
                    <p class="text-muted small mb-1">
                        <i class="bi bi-geo-alt me-1"></i>
                        <?= htmlspecialchars(implode(', ', array_filter([$rental['address'] ?? '', $rental['city_name'] ?? '']))) ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($rental['brand_name']) && !empty($rental['model_name'])): ?>
                    <p class="text-muted small mb-1">
                        <i class="bi bi-car-front me-1"></i>
                        <?= htmlspecialchars($rental['brand_name'] . ' ' . $rental['model_name']) ?>
                    </p>
                <?php endif; ?>
                <p class="text-muted small mb-0">
                    <i class="bi bi-calendar me-1"></i>Booked: <?= date('M d, Y', strtotime($rental['created_at'])) ?>
                </p>
                <?php if ($rental['type'] == 'room' && !empty($rental['checkin_date'])): ?>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-calendar-check me-1"></i>Check-in: <?= date('M d, Y', strtotime($rental['checkin_date'])) ?>
                    </p>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-calendar-x me-1"></i>Check-out: <?= date('M d, Y', strtotime($rental['checkout_date'])) ?>
                    </p>
                <?php endif; ?>
                <?php if ($rental['type'] == 'vehicle' && !empty($rental['pickup_date'])): ?>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-calendar-check me-1"></i>Pickup: <?= date('M d, Y', strtotime($rental['pickup_date'])) ?>
                    </p>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-calendar-x me-1"></i>Drop-off: <?= date('M d, Y', strtotime($rental['dropoff_date'])) ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="col-md-3">
                <div class="text-md-end">
                    <h5 class="text-success mb-2">LKR <?= number_format($price, 2) ?></h5>
                    <p class="text-muted small mb-3"><?= $price_label ?></p>
                    
                <?php 
                    // Visibility Logic: Only show owner details if status is 'Confirmed' or 'Booked'
                    $is_confirmed = (isset($rental['status_name']) && in_array($rental['status_name'], ['Confirmed', 'Booked']));
                ?>

                <!-- Owner Details Section -->
                <?php if ($is_confirmed): ?>
                    <div class="mt-3 pt-2 border-top">
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Owner Details</small>
                        <div class="d-flex align-items-center mt-1 mb-2">
                             <i class="bi bi-person-check-fill text-success me-2"></i>
                             <span class="fw-bold text-dark small"><?= htmlspecialchars($rental['owner_name'] ?? 'Owner') ?></span>
                        </div>
                        
                        <div class="d-grid gap-1">
                             <?php if (!empty($rental['owner_phone'])): ?>
                                <a href="tel:<?= htmlspecialchars($rental['owner_phone']) ?>" class="btn btn-sm btn-outline-success text-start">
                                    <i class="bi bi-telephone-fill me-2"></i>Call: <?= htmlspecialchars($rental['owner_phone']) ?>
                                </a>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $rental['owner_phone']) ?>" target="_blank" class="btn btn-sm btn-outline-success text-start">
                                    <i class="bi bi-whatsapp me-2"></i>WhatsApp
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($rental['owner_email'])): ?>
                                <a href="mailto:<?= htmlspecialchars($rental['owner_email']) ?>" class="btn btn-sm btn-outline-secondary text-start">
                                    <i class="bi bi-envelope-fill me-2"></i><?= htmlspecialchars($rental['owner_email']) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mt-3 pt-2 border-top">
                        <div class="alert alert-light border mb-0 py-2 px-2 small text-muted">
                            <i class="bi bi-lock-fill me-1"></i>Owner details hidden
                        </div>
                    </div>
                <?php endif; ?>

                <div class="d-grid mt-2 gap-2">
                    <a href="<?= $view_link ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye me-1"></i>View Listing
                    </a>

                    <?php if ($rental['status_name'] === 'Pending'): ?>
                        <form action="<?= app_url('public/my_rent/cancel_rental.php') ?>" method="POST" onsubmit="return confirm('Are you sure you want to cancel this request?');" class="d-block w-100">
                            <input type="hidden" name="rent_id" value="<?= $rental['rent_id'] ?>">
                            <input type="hidden" name="type" value="<?= $rental['type'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger w-100">
                                <i class="bi bi-x-circle me-1"></i>Cancel Request
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
