<?php
// Simple landing page

require __DIR__ . '/config/db.php';

ensure_session_started();

// Maintenance Mode Check
// Maintenance Mode Check
$lockFile = __DIR__ . '/maintenance.lock';
if (file_exists($lockFile)) {
    $user = current_user();
    $roleId = $user ? (int)$user['role_id'] : 0; // 0 for Guest

    // Super Admin (1) always bypasses
    if ($roleId !== 1) {
        $content = file_get_contents($lockFile);
        $blockedRoles = [];

        // Check if file contains JSON config
        $data = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            $blockedRoles = $data['blocked_roles'] ?? [];
        } else {
            // Legacy/Plain file: Block everyone except Super Admin
            $blockedRoles = [2, 3, 4, 0]; 
        }

        // If current user's role is in the blocked list, show maintenance page
        if (in_array($roleId, $blockedRoles)) {
            require __DIR__ . '/maintain_index.php';
            exit;
        }
    }
}



$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rental Lanka - Rent Properties, Rooms & Vehicles in Sri Lanka</title>
    <meta name="description" content="Discover Rental Lanka, the premier platform for renting properties, rooms, and vehicles in Sri Lanka. Connect with owners, find your dream home or ride, and experience seamless rentals.">
    <meta name="keywords" content="rent sri lanka, house for rent, room for rent, vehicle rental sri lanka, car hire, property rental, colombo apartments, rental lanka">
    <meta name="author" content="Rental Lanka">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= app_url() ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= app_url() ?>">
    <meta property="og:title" content="Rental Lanka - Rent Properties, Rooms & Vehicles in Sri Lanka">
    <meta property="og:description" content="Discover Rental Lanka, the premier platform for renting properties, rooms, and vehicles in Sri Lanka. Connect with owners, find your dream home or ride.">
    <meta property="og:image" content="<?= app_url('public/assets/images/hero_house.png') ?>">

    <!-- Preload LCP Image -->
    <link rel="preload" as="image" href="<?= app_url('public/assets/images/hero_house.png') ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= app_url() ?>">
    <meta property="twitter:title" content="Rental Lanka - Rent Properties, Rooms & Vehicles in Sri Lanka">
    <meta property="twitter:description" content="Discover Rental Lanka, the premier platform for renting properties, rooms, and vehicles in Sri Lanka.">
    <meta property="twitter:image" content="<?= app_url('public/assets/images/hero_house.png') ?>">

    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= app_url('public/favicon/android-chrome-192x192.png') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= app_url('public/favicon/android-chrome-512x512.png') ?>">
    <link rel="shortcut icon" href="<?= app_url('public/favicon/favicon.ico') ?>">
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">

    <!-- DNS Prefetch & Preconnect for External Resources -->
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "Rental Lanka",
      "url": "<?= app_url() ?>",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "<?= app_url('search.php?q={search_term_string}') ?>",
        "query-input": "required name=search_term_string"
      }
    }
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "Rental Lanka",
      "url": "<?= app_url() ?>",
      "logo": "<?= app_url('public/assets/images/logo.png') ?>", 
      "sameAs": [
        "https://www.facebook.com/rentallanka",
        "https://twitter.com/rentallanka",
        "https://www.instagram.com/rentallanka"
      ]
    }
    </script>
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= app_url('public/footer/footer.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> <!-- Ensure FontAwesome is available for footer icons -->
</head>
<body class="bg-light">
<?php require __DIR__ . '/public/navbar/navbar.php'; ?>

<main id="main-content">
<?php require __DIR__ . '/public/hero/hero.php'; ?>

<?php require __DIR__ . '/public/search/search/search.php'; ?>


<?php require __DIR__ . '/public/property/load/load_property.php'; ?>

<?php require __DIR__ . '/public/room/load/load_room.php'; ?>

<?php require __DIR__ . '/public/vehicle/load/load_vehicle.php'; ?>
<br><br><br>
<?php require __DIR__ . '/public/review/review.php'; ?>
</main>

<?php require __DIR__ . '/public/footer/footer.php'; ?>

<script defer src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
