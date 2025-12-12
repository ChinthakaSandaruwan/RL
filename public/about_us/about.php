<?php
require_once __DIR__ . '/../../config/db.php';
ensure_session_started();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Rental Lanka | Property, Room & Vehicle Rentals in Sri Lanka</title>
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= app_url('public/favicon/android-chrome-192x192.png') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= app_url('public/favicon/android-chrome-512x512.png') ?>">
    <link rel="shortcut icon" href="<?= app_url('public/favicon/favicon.ico') ?>">
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">
    
    <!-- Canonical URL - Prevents duplicate content issues -->
    <link rel="canonical" href="<?= app_url('public/about_us/about.php') ?>">
    
    <!-- Meta Description for SEO -->
    <meta name="description" content="Learn about Rental Lanka - Sri Lanka's leading rental marketplace for properties, rooms, and vehicles. Trusted by thousands, offering verified listings and direct owner connections.">
    <meta name="keywords" content="rental lanka about, property rental sri lanka, vehicle rental company, room rental platform, sri lanka rentals, rental marketplace">
    <meta name="author" content="Rental Lanka">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= app_url('public/about_us/about.php') ?>">
    <meta property="og:title" content="About Rental Lanka - Your Trusted Rental Partner in Sri Lanka">
    <meta property="og:description" content="Discover how Rental Lanka is transforming the rental experience in Sri Lanka with verified listings, direct owner connections, and 24/7 support.">
    <meta property="og:image" content="<?= app_url('public/assets/images/logo.png') ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= app_url('public/about_us/about.php') ?>">
    <meta name="twitter:title" content="About Rental Lanka - Your Trusted Rental Partner">
    <meta name="twitter:description" content="Learn about our mission to simplify rentals in Sri Lanka through verified listings and direct connections.">
    <meta name="twitter:image" content="<?= app_url('public/assets/images/logo.png') ?>">
    
    <!-- JSON-LD Structured Data for Organization -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "Rental Lanka",
      "url": "<?= app_url() ?>",
      "logo": "<?= app_url('public/assets/images/logo.png') ?>",
      "description": "Sri Lanka's premier platform for property, room, and vehicle rentals",
      "address": {
        "@type": "PostalAddress",
        "addressCountry": "LK",
        "addressLocality": "Sri Lanka"
      },
      "sameAs": [
        "https://www.facebook.com/rentallanka",
        "https://twitter.com/rentallanka",
        "https://www.instagram.com/rentallanka"
      ]
    }
    </script>
    
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="about.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../navbar/navbar.php'; ?>

<!-- Hero Section -->
<section class="about-hero text-center text-white d-flex align-items-center justify-content-center">
    <div class="container position-relative z-1">
        <h1 class="display-3 fw-bold mb-3 animate-up">Who We Are</h1>
        <p class="lead fs-4 animate-up delay-100">Your trusted partner for properties, rooms, and vehicle rentals in Sri Lanka.</p>
    </div>
</section>

<!-- Values Section -->
<section class="py-5">
    <div class="container py-4">
        <div class="row g-4 justify-content-center">
            <div class="col-md-5">
                <div class="card h-100 border-0 shadow-sm hover-lift card-mission">
                    <div class="card-body p-5 text-center">
                        <div class="icon-box mb-4 mx-auto bg-soft-primary">
                            <i class="bi bi-bullseye fs-1 text-primary-theme"></i>
                        </div>
                        <h3 class="fw-bold mb-3">Our Mission</h3>
                        <p class="text-muted">To simplify the rental process in Sri Lanka by connecting searchers with verified owners through a seamless, secure, and user-friendly platform.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card h-100 border-0 shadow-sm hover-lift card-vision">
                    <div class="card-body p-5 text-center">
                        <div class="icon-box mb-4 mx-auto bg-soft-success">
                            <i class="bi bi-eye fs-1 text-success-theme"></i>
                        </div>
                        <h3 class="fw-bold mb-3">Our Vision</h3>
                        <p class="text-muted">To become Sri Lanka's leading digital marketplace for all rental needs, fostering trust and convenience for both locals and tourists.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Story/Description Section -->
<section class="section-story py-5 bg-white">
    <div class="container py-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="position-relative ps-4">
                    <div class="story-img-bg position-absolute bg-theme rounded-3 top-0 start-0 w-100 h-100 translate-middle-x"></div>
                    <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Building" class="img-fluid rounded-3 shadow-lg position-relative z-1">
                </div>
            </div>
            <div class="col-lg-6">
                <span class="text-uppercase text-theme fw-bold letter-spacing-1">Our Story</span>
                <h2 class="display-6 fw-bold mt-2 mb-4">Redefining Rentals in Sri Lanka</h2>
                <p class="text-secondary lead">Rental Lanka was born from a simple idea: finding a place to stay or a ride to travel shouldn't be complicated.</p>
                <p class="text-muted mb-4">
                    Whether you are looking for a luxury villa for your vacation, a budget-friendly room for your studies, or a reliable vehicle for your island tour, we bring everything under one roof. We eliminate the hassle of middle-men and connect you directly with property and vehicle owners.
                </p>
                <div class="row g-4 mt-2">
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-theme me-2"></i>
                            <span class="fw-semibold">Verified Listings</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-theme me-2"></i>
                            <span class="fw-semibold">Direct Communication</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-theme me-2"></i>
                            <span class="fw-semibold">No Hidden Fees</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-theme me-2"></i>
                            <span class="fw-semibold">24/7 Support</span>
                        </div>
                    </div>
                </div>
                <a href="<?= app_url('index.php') ?>" class="btn btn-theme btn-lg mt-5 px-5">Start Exploring</a>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-5 bg-light">
    <div class="container py-5">
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="stat-item">
                    <h2 class="display-4 fw-bold text-theme mb-0 counter" data-target="500">0</h2>
                    <span class="text-muted fw-semibold">Happy Customers</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <h2 class="display-4 fw-bold text-theme mb-0 counter" data-target="150">0</h2>
                    <span class="text-muted fw-semibold">Active Listings</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <h2 class="display-4 fw-bold text-theme mb-0 counter" data-target="50">0</h2>
                    <span class="text-muted fw-semibold">Cities Covered</span>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../footer/footer.php'; ?>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="about.js"></script>
</body>
</html>
