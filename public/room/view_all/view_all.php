්]) ?></h5>
                        <p class="text-muted small mb-2">
                            <i class="bi bi-geo-alt"></i> 
                            <?= htmlspecialchars($room['city_name'] ?? 'Location not specified') ?>, 
                            <?= htmlspecialchars($room['district_name'] ?? '') ?>
                        </p>
                        <p class="card-text text-muted small flex-grow-1">
                            <?= htmlspecialchars(substr($room['description'], 0, 100)) ?>...
                        </p>
                        <div class="room-features mb-3">
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-people"></i> <?= $room['maximum_guests'] ?> Guests
                            </span>
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-door-open"></i> <?= $room['beds'] ?> Beds
                            </span>
                            <?php if ($room['amenity_count'] > 0): ?>
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-star"></i> <?= $room['amenity_count'] ?> Amenities
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="h4 mb-0 fw-bold" style="color: var(--fern);">LKR <?= number_format($room['price_per_day'], 2) ?></span>
                                <small class="text-muted">/day</small>
                            </div>
                            <a href="<?= app_url('public/room/view/room_view.php?id=' . $room['room_id']) ?>" 
                               class="btn btn-sm" style="background-color: var(--fern); border-color: var(--fern); color: white;">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../footer/footer.php'; ?>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="view_all.js"></script>
</body>
</html>
්