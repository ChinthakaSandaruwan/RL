<?php
// Ensure partial inclusion safety
if (!function_exists('get_pdo')) {
    // This file should be included, handled by parent
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

// Image initialization logic for JS
$initialImage = 'central.jpg'; 
if ($s_province) {
    foreach ($search_provinces as $p) {
        if ($p['id'] == $s_province) {
            $initialImage = get_province_image($p['name_en']);
            break;
        }
    }
}
?>

<div class="search-container container my-5 position-relative" style="z-index: 10;">
    <div class="card border-0 shadow-lg search-card overflow-hidden">
        <div class="row g-0">
            
            <!-- Left Side: Search Form -->
            <div class="col-lg-8 p-5">
                <form action="<?= app_url('index.php') ?>" method="GET" id="mainSearchForm">
                    <div class="row g-4">
                        <div class="col-12">
                            <h3 class="mb-4 fw-bold text-dark"><i class="bi bi-search me-2 text-primary"></i>Find Your Perfect Place</h3>
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

                        <!-- Buttons Row -->
                        <div class="col-12 mt-4">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm text-uppercase letter-spacing-1">
                                        <i class="bi bi-search me-2"></i>Search Now
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-outline-secondary w-100 py-2 fw-bold shadow-sm text-uppercase letter-spacing-1" data-bs-toggle="modal" data-bs-target="#advancedSearchModal">
                                        <i class="bi bi-sliders me-2"></i>Advanced Search
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Right Side: Province Image -->
            <div class="col-lg-4 d-none d-lg-block position-relative p-0" style="background: #ffffff; min-height: 460px;">
                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center overflow-hidden">
                     <img id="province_image" 
                         src="<?= app_url('public/search/search/provinces/central.jpg') ?>" 
                         alt="Province Image" 
                         class="w-100 h-100"
                         style="object-fit: contain; object-position: center; transition: opacity 0.5s ease; padding: 0;">
                    
                    <!-- Text Overlay -->
                    <div class="position-absolute bottom-0 start-0 w-100 p-4 text-white z-2" style="background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);">
                        <h4 class="fw-bold mb-1 text-shadow" id="province_name_display" style="text-shadow: 0 2px 5px rgba(0,0,0,0.7);">Discover Sri Lanka</h4>
                        <p class="small text-white-50 mb-0 text-shadow" style="text-shadow: 0 1px 3px rgba(0,0,0,0.7);">Select a province to explore listings</p>
                    </div>
                </div>
            </div>

        </div> <!-- End Row -->
    </div> <!-- End Card -->
</div> <!-- End Container -->

<link rel="stylesheet" href="<?= app_url('public/search/search/search.css') ?>">
<script>
    const searchDistricts = <?= json_encode($search_districts) ?>;
    const searchCities = <?= json_encode($search_cities) ?>;
    const currentDistrict = "<?= $s_district ?>";
    const currentCity = "<?= $s_city ?>";
    const provinceImagesPath = "<?= app_url('public/search/search/provinces/') ?>";
    const initialImage = "<?= $initialImage ?>";
</script>
<script src="<?= app_url('public/search/search/search.js') ?>"></script>

<!-- Include Advanced Search Modal -->
<?php require __DIR__ . '/../../search/advance_search/advance_search.php'; ?>
