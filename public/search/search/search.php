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

// Function to get province image filename
function get_province_image($name) {
    return strtolower(str_replace(' ', '_', $name)) . '.jpg';
}
?>

<div class="search-container container my-5 position-relative" style="z-index: 10;">
    <div class="card border-0 shadow-lg search-card overflow-hidden">
        <div class="row g-0">
            <!-- Left Side: Search Form -->
            <div class="col-lg-8 p-4">
                <form action="<?= app_url('index.php') ?>" method="GET" id="mainSearchForm">
                    <div class="row g-3">
                        <div class="col-12">
                            <h4 class="mb-3 fw-bold text-dark"><i class="bi bi-search me-2 text-primary"></i>Find Your Perfect Place</h4>
                        </div>
                        
                        <!-- Keyword -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted small text-uppercase">Keyword</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-fonts text-primary"></i></span>
                                <input type="text" name="keyword" class="form-control border-start-0 ps-0" placeholder="What are you looking for?" value="<?= htmlspecialchars($s_keyword) ?>">
                            </div>
                        </div>

                        <!-- Category -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted small text-uppercase">Searching For</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-grid text-primary"></i></span>
                                <select name="category" class="form-select fw-semibold border-start-0 ps-0">
                                    <option value="">All Categories</option>
                                    <option value="property" <?= $s_category == 'property' ? 'selected' : '' ?>>Property</option>
                                    <option value="room" <?= $s_category == 'room' ? 'selected' : '' ?>>Room</option>
                                    <option value="vehicle" <?= $s_category == 'vehicle' ? 'selected' : '' ?>>Vehicle</option>
                                </select>
                            </div>
                        </div>

                        <!-- Location Group -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-muted small text-uppercase">Province</label>
                            <select name="province" id="search_province" class="form-select">
                                <option value="" data-image="default_map.jpg">All Provinces</option>
                                <?php foreach ($search_provinces as $p): ?>
                                    <option value="<?= $p['id'] ?>" 
                                            data-image="<?= get_province_image($p['name_en']) ?>"
                                            <?= $s_province == $p['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['name_en']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold text-muted small text-uppercase">District</label>
                            <select name="district" id="search_district" class="form-select" <?= empty($s_district) ? 'disabled' : '' ?>>
                                <option value="">All Districts</option>
                                <!-- Populated by JS -->
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold text-muted small text-uppercase">City</label>
                            <select name="city" id="search_city" class="form-select" <?= empty($s_city) ? 'disabled' : '' ?>>
                                <option value="">All Cities</option>
                                <!-- Populated by JS -->
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm text-uppercase letter-spacing-1">
                                <i class="bi bi-search me-2"></i>Search Now
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Right Side: Province Image -->
            <div class="col-lg-4 d-none d-lg-block position-relative">
                <div class="h-100 w-100 position-absolute top-0 start-0">
                    <div class="search-image-overlay"></div>
                    <img id="province_image" 
                         src="<?= app_url('public/search/search/provinces/central.jpg') ?>" 
                         alt="Province Image" 
                         class="img-fluid h-100 w-100 object-fit-cover"
                         style="transition: opacity 0.5s ease;">
                    <div class="position-absolute bottom-0 start-0 p-4 text-white z-2">
                        <h5 class="fw-bold mb-0 text-shadow" id="province_name_display">Discover Sri Lanka</h5>
                        <small class="text-white-50 text-shadow">Select a province to explore</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?= app_url('public/search/search/search.css') ?>">
<script>
    const searchDistricts = <?= json_encode($search_districts) ?>;
    const searchCities = <?= json_encode($search_cities) ?>;
    const currentDistrict = "<?= $s_district ?>";
    const currentCity = "<?= $s_city ?>";
    const provinceImagesPath = "<?= app_url('public/search/search/provinces/') ?>";
    
    // Determine initial image
    <?php
    $initialImage = 'central.jpg'; // Default
    if ($s_province) {
        // Find the selected province name
        foreach ($search_provinces as $p) {
            if ($p['id'] == $s_province) {
                $initialImage = get_province_image($p['name_en']);
                break;
            }
        }
    }
    ?>
    const initialImage = "<?= $initialImage ?>";
</script>
<script src="<?= app_url('public/search/search/search.js') ?>"></script>
