<?php
require_once __DIR__ . '/config/db.php';

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

$baseUrl = app_url();

// Static Pages
$staticPages = [
    '',
    'auth/login/login.php',
    'auth/register/register.php',
    'public/about/about.php', 
    'public/contact/contact.php'
];

foreach ($staticPages as $page) {
    echo '<url>';
    echo '<loc>' . $baseUrl . '/' . $page . '</loc>';
    echo '<changefreq>monthly</changefreq>';
    echo '<priority>0.8</priority>';
    echo '</url>';
}

try {
    $pdo = get_pdo();

    // Properties
    try {
        $stmt = $pdo->query("SELECT property_id, updated_at FROM property ORDER BY updated_at DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<url>';
            echo '<loc>' . $baseUrl . '/public/property/view/property_view.php?id=' . $row['property_id'] . '</loc>';
            echo '<lastmod>' . date('Y-m-d', strtotime($row['updated_at'])) . '</lastmod>';
            echo '<changefreq>weekly</changefreq>';
            echo '<priority>0.9</priority>';
            echo '</url>';
        }
    } catch (Exception $e) {
        // Log error or ignore
    }

    // Rooms
    try {
        $stmt = $pdo->query("SELECT room_id, updated_at FROM room ORDER BY updated_at DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<url>';
            echo '<loc>' . $baseUrl . '/public/room/view/room_view.php?id=' . $row['room_id'] . '</loc>';
            echo '<lastmod>' . date('Y-m-d', strtotime($row['updated_at'])) . '</lastmod>';
            echo '<changefreq>weekly</changefreq>';
            echo '<priority>0.9</priority>';
            echo '</url>';
        }
    } catch (Exception $e) {
        // Log error or ignore
    }

    // Vehicles
    try {
        $stmt = $pdo->query("SELECT vehicle_id, updated_at FROM vehicle ORDER BY updated_at DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<url>';
            echo '<loc>' . $baseUrl . '/public/vehicle/view/vehicle_view.php?id=' . $row['vehicle_id'] . '</loc>';
            echo '<lastmod>' . date('Y-m-d', strtotime($row['updated_at'])) . '</lastmod>';
            echo '<changefreq>weekly</changefreq>';
            echo '<priority>0.9</priority>';
            echo '</url>';
        }
    } catch (Exception $e) {
        // Log error or ignore
    }
} catch (Exception $e) {
    // DB Connection failed - Sitemap will contain only static pages
    // We could log this error to a file if needed
    // error_log($e->getMessage());
}

echo '</urlset>';
?>
