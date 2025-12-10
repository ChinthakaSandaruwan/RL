<link rel="stylesheet" href="<?= app_url('public/hero/hero.css') ?>">

<section class="hero-section">
    <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>
        <div class="carousel-inner">
            <!-- Slide 1: House -->
            <div class="carousel-item active hero-carousel-item">
                <picture>
                    <source srcset="<?= app_url('public/assets/images/hero_house.webp') ?>" type="image/webp">
                    <img src="<?= app_url('public/assets/images/hero_house.png') ?>" 
                         width="1920" height="600" 
                         class="d-block w-100" 
                         alt="Luxury House for Rent in Sri Lanka" 
                         fetchpriority="high" 
                         loading="eager">
                </picture>
                <div class="hero-overlay">
                    <div class="hero-content">
                        <h1 class="hero-title">Find Your Dream Home</h1>
                        <p class="hero-subtitle">Discover luxury properties in the heart of Sri Lanka.</p>
                        <a href="<?= app_url('public/property/view_all/view_all.php') ?>" class="btn hero-btn">Explore Properties</a>
                    </div>
                </div>
            </div>
            <!-- Slide 2: Apartment -->
            <div class="carousel-item hero-carousel-item">
                <picture>
                    <source srcset="<?= app_url('public/assets/images/hero_apartment.webp') ?>" type="image/webp">
                    <img src="<?= app_url('public/assets/images/hero_apartment.png') ?>" 
                         width="1920" height="600" 
                         class="d-block w-100" 
                         alt="Modern Apartment for Rent in Colombo" 
                         loading="lazy">
                </picture>
                <div class="hero-overlay">
                    <div class="hero-content">
                        <h2 class="hero-title">Cozy Modern Living</h2>
                        <p class="hero-subtitle">Stylish apartments designed for comfort and convenience.</p>
                        <a href="<?= app_url('public/room/view_all/view_all.php') ?>" class="btn hero-btn">View Apartments</a>
                    </div>
                </div>
            </div>
            <!-- Slide 3: Vehicle -->
            <div class="carousel-item hero-carousel-item">
                <picture>
                    <source srcset="<?= app_url('public/assets/images/hero_vehicle.webp') ?>" type="image/webp">
                    <img src="<?= app_url('public/assets/images/hero_vehicle.png') ?>" 
                         width="1920" height="600" 
                         class="d-block w-100" 
                         alt="Premium Vehicle Rentals in Sri Lanka" 
                         loading="lazy">
                </picture>
                <div class="hero-overlay">
                    <div class="hero-content">
                        <h2 class="hero-title">Travel in Style</h2>
                        <p class="hero-subtitle">Premium vehicle rentals for your journey across the island.</p>
                        <a href="<?= app_url('public/vehicle/view_all/view_all.php') ?>" class="btn hero-btn">Rent a Car</a>
                    </div>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev" aria-label="Previous slide">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next" aria-label="Next slide">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</section>

<script src="<?= app_url('public/hero/hero.js') ?>"></script>