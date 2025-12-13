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

    // Construct Query for Room
     $queryParams = [
        'keyword' => $keyword,
        'province_id' => $province_id,
        'district_id' => $district_id,
        'city_id' => $city_id,
        'min_price' => $_GET['room_min_price'] ?? '',
        'max_price' => $_GET['room_max_price'] ?? '',
        'room_type_id' => $_GET['room_type_id'] ?? '',
        'category' => 'room'
     ];
     
     $queryString = http_build_query($queryParams);
     header("Location: " . app_url('public/search/room_search/room_search.php?' . $queryString)); 
     exit;

    // Construct Query for Vehicle
    $queryParams = [
        'keyword' => $keyword,
        'province_id' => $province_id,
        'district_id' => $district_id,
        'city_id' => $city_id,
        'vehicle_type_id' => $_GET['veh_type_id'] ?? '',
        'brand_id' => $_GET['veh_brand_id'] ?? '',
        'model_id' => $_GET['veh_model_id'] ?? '',
        'category' => 'vehicle'
    ];
    
     $queryString = http_build_query($queryParams);
     header("Location: " . app_url('public/search/vehicle_search/vehicle_search.php?' . $queryString));
     exit;
} else {
    // Default
    header("Location: " . app_url('index.php'));
    exit;
}
