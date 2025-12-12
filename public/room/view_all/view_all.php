<?php
require __DIR__ . '/../../../config/db.php';
ensure_session_started();
$user = current_user();

$pdo = get_pdo();

// Get filter parameters
$searchQuery = trim($_GET['search'] ?? '');
$roomType = $_GET['type'] ?? '';
$minBeds = intval($_GET['min_beds'] ?? 0);
$minBathrooms = intval($_GET['min_bathrooms'] ?? 0);
$minPrice = floatval($_GET['min_price'] ?? 0);
$maxPrice = floatval($_GET['max_price'] ?? 0);
$sortBy = $_GET['sort'] ?? 'newest';

// Fetch room types for filter
$roomTypes = $pdo->query("SELECT * FROM room_type ORDER BY type_name ASC")->fetchAll();

// Convert room type name to ID if needed
$roomTypeId = null;
if ($roomType) {
    if (is_numeric($roomType)) {
        $roomTypeId = intval($roomType);
    } else {
        foreach ($roomTypes as $type) {
            if (strcasecmp($type['type_name'], $roomType) === 0) {
                $roomTypeId = $type['type_id'];
                break;
            }
        }
    }
}

// Build query
$sql = "
    SELECT 
        r.*, 
        rt.type_name, 
        ri.image_path,
        c.name_en AS city_name,
        d.name_en AS district_name,
        u.name AS owner_name,
        (
            SELECT COUNT(DISTINCT ra.amenity_id)
            FROM room_amenity ra
            WHERE ra.room_id = r.room_id
        ) AS amenity_count
    FROM room r
    JOIN room_type rt ON r.room_type_id = rt.type_id
    LEFT JOIN room_image ri ON r.room_id = ri.room_id AND ri.primary_image = 1
    LEFT JOIN room_location rl ON r.room_id = rl.room_id
    LEFT JOIN cities c ON rl.city_id = c.id
    LEFT JOIN districts d ON c.district_id = d.id
    LEFT JOIN user u ON r.owner_id = u.user_id
    WHERE r.status_id = 1
";

$params = [];

if ($searchQuery) {
    $sql .= " AND (r.title LIKE ? OR r.description LIKE ? OR c.name_en LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($roomTypeId) {
    $sql .= " AND r.room_type_id = ?";
    $params[] = $roomTypeId;
}

if ($minBeds > 0) {
    $sql .= " AND r.beds >= ?";
    $params[] = $minBeds;
}

if ($minBathrooms > 0) {
    $sql .= " AND r.bathrooms >= ?";
    $params[] = $minBathrooms;
}

if ($minPrice > 0) {
    $sql .= " AND r.price_per_day >= ?";
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $sql .= " AND r.price_per_day <= ?";
    $params[] = $maxPrice;
}

// Sorting
switch ($sortBy) {
    case 'price_low':
        $sql .= " ORDER BY r.price_per_day ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY r.price_per_day DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY r.created_at ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY r.created_at DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$totalRooms = count($rooms);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Rooms - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="view_all.css">
</head>
<body>

<?php require __DIR__ . '/../../navbar/navbar.php'; ?>

<div class="hero-section">
    <div class="container">
        <h1 class="display-4 fw-bold text-white">Find Your Perfect Room</h1>
        <p class="lead text-white-50">Comfortable and affordable rooms for rent across Sri Lanka</p>
    </div>
</div>

<div class="container py-5">
    <!-- Search & Filter Bar -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" placeholder="Search rooms..." 
                               value="<?= htmlspecialchars($searchQuery) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="type" class="form-select">
                            <option value="">All Room Types</option>
                            <?php foreach ($roomTypes as $type): ?>
                            <option value="<?= $type['type_id'] ?>" <?= $roomTypeId == $type['type_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['type_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="min_beds" class="form-select">
                            <option value="">Any Beds</option>
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= $minBeds == $i ? 'selected' : '' ?>><?= $i ?>+ Bed<?= $i > 1 ? 's' : '' ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <select name="min_bathrooms" class="form-select">
                            <option value="">Any Bathrooms</option>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?= $i ?>" <?= $minBathrooms == $i ? 'selected' : '' ?>><?= $i ?>+ Bathroom<?= $i > 1 ? 's' : '' ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="min_price" class="form-control" placeholder="Min Daily Rent" 
                               value="<?= $minPrice > 0 ? $minPrice : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="max_price" class="form-control" placeholder="Max Daily Rent" 
                               value="<?= $maxPrice > 0 ? $maxPrice : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn w-100" style="background-color: var(--fern); border-color: var(--fern); color: white;">
                            <i class="bi bi-search me-1"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0"><?= $totalRooms ?> Room<?= $totalRooms != 1 ? 's' : '' ?> Found</h5>
        <div class="d-flex gap-2 align-items-center">
            <label class="small text-muted mb-0">Sort by:</label>
            <select class="form-select form-select-sm" style="width: auto;" onchange="updateSort(this.value)">
                <option value="newest" <?= $sortBy == 'newest' ? 'selected' : '' ?>>Newest First</option>
                <option value="oldest" <?= $sortBy == 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                <option value="price_low" <?= $sortBy == 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_high" <?= $sortBy == 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
            </select>
        </div>
    </div>

    <!-- Rooms Grid -->
    <?php if (empty($rooms)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <p class="lead mt-3">No rooms found matching your criteria.</p>
            <a href="view_all.php" class="btn" style="background-color: var(--fern); border-color: var(--fern); color: white;">Clear Filters</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($rooms as $room): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="position-relative">
                        <?php if ($room['image_path']): ?>
                        <img src="<?= app_url($room['image_path']) ?>" class="card-img-top" style="height: 200px; object-fit: cover;"
                             alt="<?= htmlspecialchars($room['title']) ?>">
                        <?php else: ?>
                        <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="bi bi-house text-white" style="font-size: 3rem;"></i>
                        </div>
                        <?php endif; ?>
                        <div class="position-absolute top-0 end-0 m-2">
                            <span class="badge" style="background-color: var(--fern);"><?= htmlspecialchars($room['type_name']) ?></span>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= htmlspecialchars($room['title']) ?></h5>
                        <p class="text-muted small mb-2">
                            <i class="bi bi-geo-alt"></i> 
                            <?= htmlspecialchars($room['city_name'] ?? 'Location not specified') ?>, 
                            <?= htmlspecialchars($room['district_name'] ?? '') ?>
                        </p>
                        <p class="card-text text-muted small flex-grow-1">
                            <?= htmlspecialchars(substr($room['description'], 0, 100)) ?>...
                        </p>
                        <div class="mb-3">
                            <span class="badge bg-light text-dark me-1">
                                <i class="bi bi-people"></i> <?= $room['maximum_guests'] ?> Guests
                            </span>
                            <span class="badge bg-light text-dark me-1">
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