<?php
// Ensure partial inclusion safety
if (!function_exists('get_pdo')) {
    // Ideally this shouldn't happen if included in index.php, but for safety
}

$pdo = get_pdo();

// Fetch Provinces
$stmt = $pdo->query("SELECT * FROM `provinces` ORDER BY `name_en` ASC");
$search_provinces = $stmt->fetchAll();

// Fetch all districts and cities (for JavaScript cascading)
$stmt = $pdo->query("SELECT * FROM `districts` ORDER BY `name_en` ASC");
$search_districts = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM `cities` ORDER BY `name_en` ASC");
$search_cities = $stmt->fetchAll();

// Get current search params to pre-fill
$s_keyword = $_GET['keyword'] ?? '';
$s_province = $_GET['province'] ?? '';
$s_district = $_GET['district'] ?? '';
$s_city = $_GET['city'] ?? '';
$s_category = $_GET['category'] ?? ''; // property, room, vehicle
?>

<div class="search-container container my-5 position-relative" style="z-index: 10;">
    <div class="card border-0 shadow-lg search-card">
        <div class="card-body p-4">
            <form action="<?= app_url('index.php') ?>" method="GET" id="mainSearchForm">
                <div class="row g-3">
                    <!-- Keyword -->
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">Keyword</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-primary"></i></span>
                            <input type="text" name="keyword" class="form-control border-start-0 ps-0" placeholder="What are you looking for?" value="<?= htmlspecialchars($s_keyword) ?>">
                        </div>
                    </div>

                    <!-- Location Group -->
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">Province</label>
                        <select name="province" id="search_province" class="form-select">
                            <option value="">All Provinces</option>
                            <?php foreach ($search_provinces as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $s_province == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name_en']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">District</label>
                        <select name="district" id="search_district" class="form-select" <?= empty($s_district) ? 'disabled' : '' ?>>
                            <option value="">All Districts</option>
                            <!-- Populated by JS -->
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">City</label>
                        <select name="city" id="search_city" class="form-select" <?= empty($s_city) ? 'disabled' : '' ?>>
                            <option value="">All Cities</option>
                            <!-- Populated by JS -->
                        </select>
                    </div>

                    <!-- Category -->
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">Searching For</label>
                        <select name="category" class="form-select fw-semibold text-primary">
                            <option value="">All Categories</option>
                            <option value="property" <?= $s_category == 'property' ? 'selected' : '' ?>>Property</option>
                            <option value="room" <?= $s_category == 'room' ? 'selected' : '' ?>>Room</option>
                            <option value="vehicle" <?= $s_category == 'vehicle' ? 'selected' : '' ?>>Vehicle</option>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <div class="col-lg-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 h-100 py-2 fw-bold shadow-sm" style="min-height: 48px;">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?= app_url('public/search/search/search.css') ?>">
<script>
    const searchDistricts = <?= json_encode($search_districts) ?>;
    const searchCities = <?= json_encode($search_cities) ?>;
    const currentDistrict = "<?= $s_district ?>";
    const currentCity = "<?= $s_city ?>";
</script>
<script src="<?= app_url('public/search/search/search.js') ?>"></script>
