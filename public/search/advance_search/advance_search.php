<?php
// Advanced Search Component (Modal)
// This file is included in search.php or index.php

$pdo = get_pdo();

// Fetch Data for Advanced Filters
// Property Types
$prop_types = $pdo->query("SELECT * FROM `property_type` ORDER BY `type_name` ASC")->fetchAll();
// Room Types
$room_types = $pdo->query("SELECT * FROM `room_type` ORDER BY `type_name` ASC")->fetchAll();
// Vehicle Types, Brands, Fuels, Transmissions
$veh_types = $pdo->query("SELECT * FROM `vehicle_type` ORDER BY `type_name` ASC")->fetchAll();
$veh_brands = $pdo->query("SELECT * FROM `vehicle_brand` ORDER BY `brand_name` ASC")->fetchAll();
$veh_fuels = $pdo->query("SELECT * FROM `fuel_type` ORDER BY `type_name` ASC")->fetchAll();
$veh_trans = $pdo->query("SELECT * FROM `transmission_type` ORDER BY `type_name` ASC")->fetchAll();

// Amenities (Grouped by category is ideal, but for now fetching all distinguishing by category column)
$all_amenities = $pdo->query("SELECT * FROM `amenity` ORDER BY `amenity_name` ASC")->fetchAll();
$prop_amenities = array_filter($all_amenities, fn($a) => in_array($a['category'], ['property', 'both']));
$room_amenities = array_filter($all_amenities, fn($a) => in_array($a['category'], ['room', 'both']));

// Provinces (Already available in main scope usually, but fetching to be safe if included standalone)
// Relying on main scope variables if possible, but for robustness:
if (!isset($search_provinces)) {
    $search_provinces = $pdo->query("SELECT * FROM `provinces` ORDER BY `name_en` ASC")->fetchAll();
}
?>

<!-- Advanced Search Modal -->
<div class="modal fade" id="advancedSearchModal" tabindex="-1" aria-labelledby="advancedSearchModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold" id="advancedSearchModalLabel"><i class="bi bi-sliders me-2"></i>Advanced Search</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <form action="<?= app_url('public/search/advanced_result.php') ?>" method="GET" id="advSearchForm">
                    
                    <div class="row g-0 h-100">
                        <!-- Sidebar: Category Selection -->
                        <div class="col-lg-3 bg-light border-end">
                            <div class="list-group list-group-flush pt-3 sticky-top" style="top: 0">
                                <label class="list-group-item bg-light border-0 fw-bold text-muted small text-uppercase px-4">Category</label>
                                <a href="#" class="list-group-item list-group-item-action active px-4 py-3 d-flex align-items-center adv-category-trigger" data-target="adv-property">
                                    <i class="bi bi-house-door me-3 fs-5"></i>
                                    <div>
                                        <div class="fw-bold">Property</div>
                                        <div class="small opacity-75">Houses, Lands, Villas</div>
                                    </div>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action px-4 py-3 d-flex align-items-center adv-category-trigger" data-target="adv-room">
                                    <i class="bi bi-door-open me-3 fs-5"></i>
                                    <div>
                                        <div class="fw-bold">Room</div>
                                        <div class="small opacity-75">Annexes, Shared Rooms</div>
                                    </div>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action px-4 py-3 d-flex align-items-center adv-category-trigger" data-target="adv-vehicle">
                                    <i class="bi bi-car-front me-3 fs-5"></i>
                                    <div>
                                        <div class="fw-bold">Vehicle</div>
                                        <div class="small opacity-75">Cars, Vans, bikes</div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- Main Content: Dynamic Fields -->
                        <div class="col-lg-9 p-4 bg-white">
                            <!-- Hidden Input for Selected Category -->
                            <input type="hidden" name="category" id="advCategoryInput" value="property">

                            <!-- Common Fields (Keyword & Location) -->
                            <h6 class="fw-bold text-primary mb-3">General Information</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold text-muted">Keyword</label>
                                    <input type="text" name="keyword" class="form-control" placeholder="Search by title, description...">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Province</label>
                                    <select name="province_id" id="adv_province" class="form-select">
                                        <option value="">All Provinces</option>
                                        <?php foreach ($search_provinces as $p): ?>
                                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name_en']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">District</label>
                                    <select name="district_id" id="adv_district" class="form-select" disabled>
                                        <option value="">All Districts</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">City</label>
                                    <select name="city_id" id="adv_city" class="form-select" disabled>
                                        <option value="">All Cities</option>
                                    </select>
                                </div>
                            </div>

                            <hr class="my-4 text-muted opacity-25">

                            <!-- PROPERTY FIELDS -->
                            <div id="adv-property" class="adv-section">
                                <h6 class="fw-bold text-primary mb-3">Property Details</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Type</label>
                                        <select name="prop_type_id" class="form-select">
                                            <option value="">Any Type</option>
                                            <?php foreach ($prop_types as $t): ?>
                                                <option value="<?= $t['type_id'] ?>"><?= htmlspecialchars($t['type_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Price Range (LKR)</label>
                                        <div class="input-group">
                                            <input type="number" name="prop_min_price" class="form-control" placeholder="Min">
                                            <span class="input-group-text">-</span>
                                            <input type="number" name="prop_max_price" class="form-control" placeholder="Max">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Area (Sqft)</label>
                                        <div class="input-group">
                                            <input type="number" name="prop_sqft_min" class="form-control" placeholder="Min">
                                            <span class="input-group-text">-</span>
                                            <input type="number" name="prop_sqft_max" class="form-control" placeholder="Max">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Bedrooms</label>
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" name="prop_beds" value="" id="pbed_any" checked>
                                            <label class="btn btn-outline-secondary btn-sm" for="pbed_any">Any</label>
                                            <input type="radio" class="btn-check" name="prop_beds" value="1" id="pbed_1">
                                            <label class="btn btn-outline-secondary btn-sm" for="pbed_1">1+</label>
                                            <input type="radio" class="btn-check" name="prop_beds" value="2" id="pbed_2">
                                            <label class="btn btn-outline-secondary btn-sm" for="pbed_2">2+</label>
                                            <input type="radio" class="btn-check" name="prop_beds" value="3" id="pbed_3">
                                            <label class="btn btn-outline-secondary btn-sm" for="pbed_3">3+</label>
                                            <input type="radio" class="btn-check" name="prop_beds" value="4" id="pbed_4">
                                            <label class="btn btn-outline-secondary btn-sm" for="pbed_4">4+</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Bathrooms</label>
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" name="prop_baths" value="" id="pbath_any" checked>
                                            <label class="btn btn-outline-secondary btn-sm" for="pbath_any">Any</label>
                                            <input type="radio" class="btn-check" name="prop_baths" value="1" id="pbath_1">
                                            <label class="btn btn-outline-secondary btn-sm" for="pbath_1">1+</label>
                                            <input type="radio" class="btn-check" name="prop_baths" value="2" id="pbath_2">
                                            <label class="btn btn-outline-secondary btn-sm" for="pbath_2">2+</label>
                                            <input type="radio" class="btn-check" name="prop_baths" value="3" id="pbath_3">
                                            <label class="btn btn-outline-secondary btn-sm" for="pbath_3">3+</label>
                                        </div>
                                    </div>
                                </div>
                                <label class="form-label small fw-bold text-muted mb-2">Amenities</label>
                                <div class="row g-2">
                                    <?php foreach ($prop_amenities as $am): ?>
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="prop_amenities[]" value="<?= $am['amenity_id'] ?>" id="pam_<?= $am['amenity_id'] ?>">
                                                <label class="form-check-label small" for="pam_<?= $am['amenity_id'] ?>"><?= htmlspecialchars($am['amenity_name']) ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- ROOM FIELDS -->
                            <div id="adv-room" class="adv-section d-none">
                                <h6 class="fw-bold text-primary mb-3">Room Details</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Room Type</label>
                                        <select name="room_type_id" class="form-select">
                                            <option value="">Any Type</option>
                                            <?php foreach ($room_types as $t): ?>
                                                <option value="<?= $t['type_id'] ?>"><?= htmlspecialchars($t['type_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Search Price (Per Day)</label>
                                        <div class="input-group">
                                            <input type="number" name="room_min_price" class="form-control" placeholder="Min">
                                            <span class="input-group-text">-</span>
                                            <input type="number" name="room_max_price" class="form-control" placeholder="Max">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Beds</label>
                                        <input type="number" name="room_beds" class="form-control" placeholder="Minimum beds">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Max Guests</label>
                                        <input type="number" name="room_guests" class="form-control" placeholder="Guests count">
                                    </div>
                                </div>
                                <label class="form-label small fw-bold text-muted mb-2">Amenities</label>
                                <div class="row g-2">
                                    <?php foreach ($room_amenities as $am): ?>
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="room_amenities[]" value="<?= $am['amenity_id'] ?>" id="ram_<?= $am['amenity_id'] ?>">
                                                <label class="form-check-label small" for="ram_<?= $am['amenity_id'] ?>"><?= htmlspecialchars($am['amenity_name']) ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- VEHICLE FIELDS -->
                            <div id="adv-vehicle" class="adv-section d-none">
                                <h6 class="fw-bold text-primary mb-3">Vehicle Details</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Type</label>
                                        <select name="veh_type_id" class="form-select">
                                            <option value="">Any Type</option>
                                            <?php foreach ($veh_types as $t): ?>
                                                <option value="<?= $t['type_id'] ?>"><?= htmlspecialchars($t['type_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Brand</label>
                                        <select name="veh_brand_id" class="form-select">
                                            <option value="">Any Brand</option>
                                            <?php foreach ($veh_brands as $b): ?>
                                                <option value="<?= $b['brand_id'] ?>"><?= htmlspecialchars($b['brand_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Fuel Type</label>
                                        <select name="veh_fuel_id" class="form-select">
                                            <option value="">Any</option>
                                            <?php foreach ($veh_fuels as $f): ?>
                                                <option value="<?= $f['type_id'] ?>"><?= htmlspecialchars($f['type_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Transmission</label>
                                        <select name="veh_trans_id" class="form-select">
                                            <option value="">Any</option>
                                            <?php foreach ($veh_trans as $t): ?>
                                                <option value="<?= $t['type_id'] ?>"><?= htmlspecialchars($t['type_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Seats</label>
                                        <input type="number" name="veh_seats" class="form-control" placeholder="Min seats">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Driver</label>
                                        <select name="veh_driver" class="form-select">
                                            <option value="">Doesn't Matter</option>
                                            <option value="1">With Driver</option>
                                            <option value="0">Without Driver</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Daily Rental Rate</label>
                                        <div class="input-group">
                                            <input type="number" name="veh_min_price" class="form-control" placeholder="Min">
                                            <span class="input-group-text">-</span>
                                            <input type="number" name="veh_max_price" class="form-control" placeholder="Max">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-link text-muted text-decoration-none" id="advResetBtn">Reset Filters</button>
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-5 fw-bold" onclick="document.getElementById('advSearchForm').submit()">Search</button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?= app_url('public/search/advance_search/advance_search.css') ?>">
<script src="<?= app_url('public/search/advance_search/advance_search.js') ?>"></script>
