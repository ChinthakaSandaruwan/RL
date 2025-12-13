<?php
// Advanced Search Results Handler
require __DIR__ . '/../../config/db.php';
ensure_session_started();

$category = $_GET['category'] ?? 'property';
$keyword = $_GET['keyword'] ?? '';
$province_id = $_GET['province_id'] ?? '';
$district_id = $_GET['district_id'] ?? '';
$city_id = $_GET['city_id'] ?? '';

if ($category === 'property') {
    // Map Property Parameters
    $queryParams = [
        'search' => $keyword,
        'province_id' => $province_id,
        'district_id' => $district_id,
        'city_id' => $city_id,
        'type' => $_GET['prop_type_id'] ?? '',
        'min_price' => $_GET['prop_min_price'] ?? '',
        'max_price' => $_GET['prop_max_price'] ?? '',
        'sqft_min' => $_GET['prop_sqft_min'] ?? '',
        'sqft_max' => $_GET['prop_sqft_max'] ?? '',
        'min_bedrooms' => $_GET['prop_beds'] ?? '',
        'min_bathrooms' => $_GET['prop_baths'] ?? '',
        'amenities' => $_GET['prop_amenities'] ?? []
    ];

    $queryString = http_build_query($queryParams);
    header("Location: " . app_url('public/property/view_all/view_all.php?' . $queryString));
    exit;

} elseif ($category === 'room') {
    // Map Room Parameters
    $queryParams = [
        'search' => $keyword,
        'province_id' => $province_id,
        'district_id' => $district_id,
        'city_id' => $city_id,
        'type' => $_GET['room_type_id'] ?? '',
        'min_price' => $_GET['room_min_price'] ?? '',
        'max_price' => $_GET['room_max_price'] ?? '',
        'min_beds' => $_GET['room_beds'] ?? '',
        'guests' => $_GET['room_guests'] ?? '',
        'amenities' => $_GET['room_amenities'] ?? []
    ];
     
    $queryString = http_build_query($queryParams);
    header("Location: " . app_url('public/room/view_all/view_all.php?' . $queryString)); 
    exit;

} elseif ($category === 'vehicle') {
    // Map Vehicle Parameters
    $queryParams = [
        'search' => $keyword,
        'province_id' => $province_id,
        'district_id' => $district_id,
        'city_id' => $city_id,
        'type' => $_GET['veh_type_id'] ?? '',
        'brand' => $_GET['veh_brand_id'] ?? '',
        // 'model' => $_GET['veh_model_id'] ?? '', // Model input not present in form provided, but good to have if added later
        'fuel' => $_GET['veh_fuel_id'] ?? '',
        'transmission' => $_GET['veh_trans_id'] ?? '',
        'seats' => $_GET['veh_seats'] ?? '',
        'driver' => $_GET['veh_driver'] ?? '',
        'min_price' => $_GET['veh_min_price'] ?? '',
        'max_price' => $_GET['veh_max_price'] ?? ''
    ];
    
    $queryString = http_build_query($queryParams);
    header("Location: " . app_url('public/vehicle/view_all/view_all.php?' . $queryString));
    exit;

} else {
    // Default Fallback
    header("Location: " . app_url('index.php'));
    exit;
}

