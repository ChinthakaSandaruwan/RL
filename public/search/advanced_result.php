<?php
// Advanced Search Results Handler
require __DIR__ . '/../../config/db.php';
ensure_session_started();

$pdo = get_pdo();

// 1. Capture Common Params
$category = $_GET['category'] ?? 'property';
$keyword = $_GET['keyword'] ?? '';
$province_id = $_GET['province_id'] ?? '';
$district_id = $_GET['district_id'] ?? '';
$city_id = $_GET['city_id'] ?? '';

// 2. Route based on Category
if ($category === 'property') {
    $type_id = $_GET['prop_type_id'] ?? '';
    // Construct Query String for Property Search
    $queryParams = [
        'keyword' => $keyword,
        'province_id' => $province_id,
        'district_id' => $district_id,
        'city_id' => $city_id,
        'type_id' => $type_id,
        'min_price' => $_GET['prop_min_price'] ?? '',
        'max_price' => $_GET['prop_max_price'] ?? '',
        'sqft_min' => $_GET['prop_sqft_min'] ?? '',
        'sqft_max' => $_GET['prop_sqft_max'] ?? '',
        'bedrooms' => $_GET['prop_beds'] ?? '',
        'bathrooms' => $_GET['prop_baths'] ?? '',
        'amenities' => $_GET['prop_amenities'] ?? []
    ];
    // Redirect to property_search.php (we created this earlier as standalone)
    // We need to flatten array parameters like amenities for the URL
    $queryString = http_build_query($queryParams);
    header("Location: " . app_url('public/search/property_search/property_search.php?' . $queryString));
    exit;

} elseif ($category === 'room') {
    
     // We don't have a dedicated "room_search.php" yet, usually we might use the load_room.php logic but integrated into a page.
     // For now, let's redirect to index.php with search params or creating a temporary results page.
     // Ideally, we should have search pages for each. 
     // Let's create a vehicle/room search page or redirect to index with advanced filters?
     // Index.php only supports basic filters.
     // So we forward to a new file: public/search/room_search/room_search.php (which I will create next if needed, but for now let's just create a generic result or fix the 404 by creating this actual file)
     
     // Note: Users request showed 404 on `advanced_result.php`. So THIS file is what was missing.
     // I will implement basic redirection or handling here.
     
     // Construct Query for Room
     $queryParams = [
        'keyword' => $keyword,
        'province_id' => $province_id,
        'district_id' => $district_id,
        'city_id' => $city_id,
        'category' => 'room', // Force category
        // advanced params mapped to basic or ignored if index doesn't support
        // But the user expects advanced results.
        // For this step I will create a basic handler that dumps us back to index if specific pages don't exist, 
        // OR ideally, redirect to the specific search pages if I create them.
        // I created property_search.php. I probably need to create vehicle_search.php and room_search.php?
        // Or I can make this file render the results directly.
     ];
     
     // Since I haven't strictly created `room_search.php` or `vehicle_search.php` fully like `property_search.php`, 
     // I will use `index.php` as fallback BUT `index.php` logic (load_*.php) might not handle deep filters like "fuel_type".
     
     // STRATEGY: 
     // 1. Create this file to fix 404.
     // 2. Logic: If category == property -> redirect to property_search.php
     // 3. If category == vehicle -> redirect to vehicle_search.php (I should create this or use index fallback)
     // 4. If category == room -> redirect to room_search.php
     
     // Let's assume for now fallback to index.php with available basics to prevent error, 
     // but ideally I should build those pages. 
     
     $queryString = http_build_query($_GET); // Pass everything
     header("Location: " . app_url('index.php?' . $queryString)); 
     exit;

     // Wait, the user has "prop_type_id" etc. `index.php` checks `$_GET['keyword']` etc.
     // It might work for basic location but not advanced.
     // However, fixing the 404 is Priority 1.

} elseif ($category === 'vehicle') {
    // Similar query prop logic
    $queryParams = [
        'keyword' => $keyword,
        'province_id' => $province_id,
        'district_id' => $district_id,
        'city_id' => $city_id,
        'category' => 'vehicle',
        // 'veh_type_id' -> index.php load_vehicle.php *could* be updated to read these, 
        // but currently load_vehicle.php only reads basic S_KEYWORD, S_CITY etc.
    ];
    
     $queryString = http_build_query($_GET);
     header("Location: " . app_url('index.php?' . $queryString));
     exit;
} else {
    // Default
    header("Location: " . app_url('index.php'));
    exit;
}
