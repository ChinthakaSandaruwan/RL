<?php
require_once __DIR__ . '/config/db.php';

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

$baseUrl = app_url();

// Static Pages with Priority
$staticPages = [
    ['url' => '', 'priority' => '1.0', 'changefreq' => 'daily'], // Homepage - highest priority
    ['url' => 'public/about_us/about.php', 'priority' => '0.8', 'changefreq' => 'monthly'], // Fixed path
    ['url' => 'public/contact/contact.php', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['url' => 'auth/login/login.php', 'priority' => '0.6', 'changefreq' => 'monthly'],
    ['url' => 'auth/register/register.php', 'priority' => '0.6', 'changefreq' => 'monthly'],
    ['url' => 'public/property/view_all/view_all.php', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['url' => 'public/room/view_all/view_all.php', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['url' => 'public/vehicle/view_all/view_all.php', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['url' => 'public/privacy_policy/privacy_policy.php', 'priority' => '0.5', 'changefreq' => 'yearly'],
    ['url' => 'public/terms_of_service/terms_of_service.php', 'priority' => '0.5', 'changefreq' => 'yearly'],
];

foreach ($staticPages as $page) {
    echo '<url>';
    echo '<loc>' . $baseUrl . '/' . $page['url'] . '</loc>';
    echo '<changefreq>' . $page['changefreq'] . '</changefreq>';
    echo '<priority>' . $page['priority'] . '</priority>';
    echo '</url>';
}


    // Helper function for safe XML date
    function get_safe_date($updated, $created) {
        if (!empty($updated) && strtotime($updated) !== false) {
            return date('Y-m-d', strtotime($updated));
        }
        if (!empty($created) && strtotime($created) !== false) {
            return date('Y-m-d', strtotime($created));
        }
        return date('Y-m-d'); // Fallback to today
    }

    try {
        $pdo = get_pdo();

    // Properties
    try {
        $stmt = $pdo->query("SELECT property_id, created_at, updated_at FROM property WHERE status_id = 1 ORDER BY updated_at DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<url>';
            echo '<loc>' . htmlspecialchars($baseUrl . '/public/property/view/property_view.php?id=' . $row['property_id']) . '</loc>';
            echo '<lastmod>' . get_safe_date($row['updated_at'], $row['created_at']) . '</lastmod>';
            echo '<changefreq>weekly</changefreq>';
            echo '<priority>0.9</priority>';
            echo '</url>';
        }
    } catch (Exception $e) { }

    // Rooms
    try {
        $stmt = $pdo->query("SELECT room_id, created_at, updated_at FROM room WHERE status_id = 1 ORDER BY updated_at DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<url>';
            echo '<loc>' . htmlspecialchars($baseUrl . '/public/room/view/room_view.php?id=' . $row['room_id']) . '</loc>';
            echo '<lastmod>' . get_safe_date($row['updated_at'], $row['created_at']) . '</lastmod>';
            echo '<changefreq>weekly</changefreq>';
            echo '<priority>0.9</priority>';
            echo '</url>';
        }
    } catch (Exception $e) { }

    // Vehicles
    try {
        $stmt = $pdo->query("SELECT vehicle_id, created_at, updated_at FROM vehicle WHERE status_id = 1 ORDER BY updated_at DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<url>';
            echo '<loc>' . htmlspecialchars($baseUrl . '/public/vehicle/view/vehicle_view.php?id=' . $row['vehicle_id']) . '</loc>';
            echo '<lastmod>' . get_safe_date($row['updated_at'], $row['created_at']) . '</lastmod>';
            echo '<changefreq>weekly</changefreq>';
            echo '<priority>0.9</priority>';
            echo '</url>';
        }
    } catch (Exception $e) { }
} catch (Exception $e) {
    // DB Connection failed
}

echo '</urlset>';
?>
