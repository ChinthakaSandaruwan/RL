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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Lanka - Rent Properties, Rooms & Vehicles in Sri Lanka</title>
    <meta name="description" content="Discover Rental Lanka, the premier platform for renting properties, rooms, and vehicles in Sri Lanka. Connect with owners, find your dream home or ride, and experience seamless rentals.">
    <meta name="keywords" content="rent sri lanka, house for rent, room for rent, vehicle rental sri lanka, car hire, property rental, colombo apartments, rental lanka">
    <meta name="author" content="Rental Lanka">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#4A90E2">
    
    <!-- Canonical URL - Prevents duplicate content issues -->
    <link rel="canonical" href="<?= app_url() ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= app_url() ?>">
    <meta property="og:title" content="Rental Lanka - Rent Properties, Rooms & Vehicles in Sri Lanka">
    <meta property="og:description" content="Discover Rental Lanka, the premier platform for renting properties, rooms, and vehicles in Sri Lanka. Connect with owners, find your dream home or ride.">
    <meta property="og:image" content="<?= app_url('public/assets/images/hero_house.png') ?>">

    <!-- Preload LCP Image in WebP format -->
    <link rel="preload" as="image" href="<?= app_url('public/assets/images/hero_house.webp') ?>" type="image/webp">
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

    <!-- Enhanced JSON-LD Structured Data for Rich Results -->
    
    <!-- 1. Website Schema -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "Rental Lanka",
      "alternateName": "RentalLanka",
      "url": "<?= app_url() ?>",
      "description": "Sri Lanka's premier platform for renting properties, rooms, and vehicles. Find verified listings with direct owner connections.",
      "potentialAction": {
        "@type": "SearchAction",
        "target": {
          "@type": "EntryPoint",
          "urlTemplate": "<?= app_url('search.php?q={search_term_string}') ?>"
        },
        "query-input": "required name=search_term_string"
      }
    }
    </script>
    
    <!-- 2. Organization Schema -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "LocalBusiness",
      "@id": "<?= app_url() ?>#organization",
      "name": "Rental Lanka",
      "image": "<?= app_url('public/favicon/apple-touch-icon.png') ?>",
      "logo": "<?= app_url('public/favicon/apple-touch-icon.png') ?>",
      "url": "<?= app_url() ?>",
      "telephone": "+94-XX-XXXXXXX",
      "priceRange": "LKR",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "Sri Lanka",
        "addressCountry": "LK"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": 7.8731,
        "longitude": 80.7718
      },
      "areaServed": {
        "@type": "Country",
        "name": "Sri Lanka"
      },
      "description": "Rental Lanka is Sri Lanka's leading rental marketplace connecting property owners with renters for homes, rooms, and vehicles across the island.",
      "sameAs": [
        "https://www.facebook.com/rentallanka",
        "https://twitter.com/rentallanka",
        "https://www.instagram.com/rentallanka"
      ],
      "hasOfferCatalog": {
        "@type": "OfferCatalog",
        "name": "Rental Services",
        "itemListElement": [
          {
            "@type": "OfferCatalog",
            "name": "Property Rentals",
            "itemListElement": [
              {
                "@type": "Offer",
                "itemOffered": {
                  "@type": "Service",
                  "name": "House Rentals",
                  "description": "Find houses for rent across Sri Lanka"
                }
              },
              {
                "@type": "Offer",
                "itemOffered": {
                  "@type": "Service",
                  "name": "Apartment Rentals",
                  "description": "Modern apartments and condos for rent"
                }
              }
            ]
          },
          {
            "@type": "OfferCatalog",
            "name": "Room Rentals",
            "itemListElement": [
              {
                "@type": "Offer",
                "itemOffered": {
                  "@type": "Service",
                  "name": "Room Rentals",
                  "description": "Affordable rooms for students and professionals"
                }
              }
            ]
          },
          {
            "@type": "OfferCatalog",
            "name": "Vehicle Rentals",
            "itemListElement": [
              {
                "@type": "Offer",
                "itemOffered": {
                  "@type": "Service",
                  "name": "Car Rentals",
                  "description": "Rent cars, vans, and vehicles for travel"
                }
              }
            ]
          }
        ]
      }
    }
    </script>
    
    <!-- 3. BreadcrumbList Schema -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "name": "Home",
          "item": "<?= app_url() ?>"
        }
      ]
    }
    </script>

    <!-- Critical CSS - Load Bootstrap synchronously but optimized -->
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    
    <!-- Preload icon fonts but defer loading to prevent render blocking -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"></noscript>
    
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    
    <!-- Non-critical CSS - Load asynchronously -->
    <link rel="preload" href="<?= app_url('public/footer/footer.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="<?= app_url('public/footer/footer.css') ?>"></noscript>
    
    <!-- Inline critical CSS for instant render -->
    <style>
        /* Critical above-the-fold CSS */
        body { background-color: #f8f9fa; margin: 0; font-family: system-ui, -apple-system, sans-serif; }
        .hero-section { position: relative; overflow: hidden; }
        .hero-carousel-item img { display: block; width: 100%; height: auto; }
        .carousel-fade .carousel-item { opacity: 0; transition: opacity 0.6s ease-in-out; }
        .carousel-fade .carousel-item.active { opacity: 1; }
    </style>
    
    <!-- Font display swap to prevent FOIT -->
    <style>
        @font-face {
            font-family: 'Bootstrap Icons';
            font-display: swap;
        }
        @font-face {
            font-family: 'Font Awesome 6 Free';
            font-display: swap;
        }
    </style>
    
    <!-- Lenis Smooth Scroll CSS -->
    <style>
        html.lenis, html.lenis body {
            height: auto;
        }
        .lenis.lenis-smooth {
            scroll-behavior: auto !important;
        }
        .lenis.lenis-smooth [data-lenis-prevent] {
            overscroll-behavior: contain;
        }
        .lenis.lenis-stopped {
            overflow: hidden;
        }
        .lenis.lenis-scrolling iframe {
            pointer-events: none;
        }
    </style>
</head>
<body class="bg-light">
<?php require __DIR__ . '/public/navbar/navbar.php'; ?>

<main id="main-content">
<?php require __DIR__ . '/public/hero/hero.php'; ?>

<?php require __DIR__ . '/public/search/search/search.php'; ?>



<?php require __DIR__ . '/public/property/load/load_property.php'; ?>

<?php require __DIR__ . '/public/room/load/load_room.php'; ?>

<?php require __DIR__ . '/public/vehicle/load/load_vehicle.php'; ?>
<?php require __DIR__ . '/public/user_recommendations/user_recommendations.php'; ?>

<br><br><br>
<?php require __DIR__ . '/public/review/review.php'; ?>
</main>

<?php require __DIR__ . '/public/footer/footer.php'; ?>

<script defer src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<?php require __DIR__ . '/public/index_scrollup/scrollup.php'; ?>
<?php require __DIR__ . '/public/chat/chat.php'; ?>
<?php require_once __DIR__ . '/super_admin/special_holidays/global_loader.php'; ?>

<!-- Lenis Smooth Scroll Script -->
<script src="https://unpkg.com/lenis@1.1.18/dist/lenis.min.js"></script> 
<script>
    const lenis = new Lenis({
        duration: 1.2,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
        smooth: true,
        smoothTouch: false, // Disable on touch devices for better performance/native feel
    });

    function raf(time) {
        lenis.raf(time);
        requestAnimationFrame(raf);
    }

    requestAnimationFrame(raf);
</script>
</body>
</html>
