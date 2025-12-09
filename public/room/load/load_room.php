<?php
// Fetch latest active rooms
$pdo = get_pdo();
$stmt = $pdo->query("SELECT r.* 
    FROM room r 
    WHERE r.status_id = 1 
    ORDER BY r.created_at DESC LIMIT 6");
$rooms = $stmt->fetchAll();
?>

<section class="listings-section py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold" style="color: var(--hunter-green);">Latest Rooms</h2>
            <a href="<?= app_url('public/room/view_all.php') ?>" class="btn btn-outline-success">View All</a>
        </div>
        
        <?php if (empty($rooms)): ?>
            <div class="alert alert-info">No rooms available at the moment.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($rooms as $room): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm listing-card">
                            <img src="https://via.placeholder.com/400x250?text=Room" 
                                 class="card-img-top" alt="Room" style="height: 200px; object-fit: cover;">
                            <div class="card-body">
                                <span class="badge bg-info mb-2">Room</span>
                                <h5 class="card-title"><?= htmlspecialchars($room['title']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars(substr($room['description'], 0, 80)) ?>...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-success">LKR <?= number_format($room['price_per_day'], 2) ?>/night</span>
                                    <a href="<?= app_url('public/room/view/room_view.php?id=' . $room['room_id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
