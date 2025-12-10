<?php
// Website Review Section Component
// Ideally fetches high-rated reviews from the database.
// Falling back to static testimonials if no DB reviews found or for demo purposes.

$latestReviews = [];
$useStatic = true;

// Try to fetch real reviews if possible
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("
            SELECT r.*, u.name as user_name, u.profile_image 
            FROM review r 
            JOIN user u ON r.user_id = u.user_id 
            WHERE r.rating >= 4 
            ORDER BY r.created_at DESC 
            LIMIT 6
        ");
        $fetched = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($fetched) {
            $latestReviews = $fetched;
            $useStatic = false;
        }
    } catch (Exception $e) {
        // Table might not allow simple join or exist, silent fail to static
    }
}

// Static Fallback Data
if ($useStatic) {
    $latestReviews = [
        [
            'user_name' => 'Sarah Silva',
            'rating' => 5,
            'comment' => 'Rental Lanka made finding a holiday home in Kandy so easy! The direct booking process saved me so much time and money.',
            'profile_image' => null // will use default avatar
        ],
        [
            'user_name' => 'Mohamed Riaz',
            'rating' => 5,
            'comment' => 'Great platform for vehicle rentals. I found a reliable van for our family trip within minutes. Highly recommended!',
            'profile_image' => null
        ],
        [
            'user_name' => 'Dilshan Perera',
            'rating' => 4.5,
            'comment' => 'Very user-friendly website. The room listings for students are very helpful. Good job team!',
            'profile_image' => null
        ],
        [
            'user_name' => 'James Wilson',
            'rating' => 5,
            'comment' => 'As a tourist, I appreciated the verified listings. Felt much safer booking through here than social media groups.',
            'profile_image' => null
        ]
    ];
}
?>

<link rel="stylesheet" href="<?= app_url('public/review/review.css') ?>">

<section class="review-section py-5 bg-light position-relative">
    <div class="container py-4">
        
        <div class="text-center mb-5 animate-on-scroll">
            <h5 class="text-uppercase text-primary-theme letter-spacing-2 fw-bold small">Testimonials</h5>
            <h2 class="display-5 fw-bold text-hunter-green">What Our Users Say</h2>
            <div class="divider mx-auto mt-3"></div>
        </div>

        <div class="review-carousel-container position-relative">
            <!-- Navigation Buttons -->
            <button class="btn btn-circle btn-white shadow-sm review-prev text-theme d-none d-md-flex" aria-label="Previous reviews">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button class="btn btn-circle btn-white shadow-sm review-next text-theme d-none d-md-flex" aria-label="Next reviews">
                <i class="bi bi-chevron-right"></i>
            </button>

            <div class="review-track-wrapper">
                <div class="review-track d-flex gap-4 pb-4">
                    <?php foreach ($latestReviews as $review): ?>
                        <div class="card review-card border-0 shadow-sm h-100 flex-shrink-0">
                            <div class="card-body p-4 p-lg-5 d-flex flex-column">
                                <div class="mb-4 text-warning fs-5">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill' : ($i - $review['rating'] < 1 ? '-half' : '') ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <blockquote class="mb-4 flex-grow-1 text-muted fs-5 fst-italic lh-lg">
                                    "<?= htmlspecialchars($review['comment']) ?>"
                                </blockquote>
                                <div class="d-flex align-items-center mt-auto">
                                    <div class="avatar me-3">
                                        <?php if (!empty($review['profile_image'])): ?>
                                            <img src="<?= htmlspecialchars($review['profile_image']) ?>" alt="User" class="img-fluid rounded-circle">
                                        <?php else: ?>
                                            <div class="avatar-placeholder rounded-circle bg-soft-success text-success fw-bold d-flex align-items-center justify-content-center">
                                                <?= strtoupper(substr($review['user_name'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($review['user_name']) ?></h6>
                                        <small class="text-muted">Verified User</small>
                                    </div>
                                    <div class="ms-auto text-primary-theme opacity-25">
                                        <i class="bi bi-quote fs-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Mobile Indicators -->
            <div class="d-flex justify-content-center gap-2 mt-3 d-md-none">
                <?php foreach ($latestReviews as $index => $review): ?>
                    <button class="indicator-dot border-0 rounded-circle <?= $index === 0 ? 'active' : '' ?>" aria-label="Slide <?= $index + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</section>

<script src="<?= app_url('public/review/review.js') ?>"></script>
