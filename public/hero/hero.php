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
                <img src="<?= app_url('public/assets/images/hero_house.png') ?>" width="1920" height="600" class="d-block w-100" alt="Luxury House" fetchpriority="high" loading="eager">
                <div class="hero-overlay">
                    <div class="hero-content">
                        <h1 class="hero-title">Find Your Dream Home</h1>
                        <p class="hero-subtitle">Discover luxury properties in the heart of Sri Lanka.</p>
                        <a href="#" class="btn hero-btn">Explore Properties</a>
                    </div>
                </div>
            </div>
            <!-- Slide 2: Apartment -->
            <div class="carousel-item hero-carousel-item">
                <img src="<?= app_url('public/assets/images/hero_apartment.png') ?>" width="1920" height="600" class="d-block w-100" alt="Modern Apartment" loading="lazy">
                <div class="hero-overlay">
                    <div class="hero-content">
                        <h1 class="hero-title">Cozy Modern Living</h1>
                        <p class="hero-subtitle">Stylish apartments designed for comfort and convenience.</p>
                        <a href="#" class="btn hero-btn">View Apartments</a>
                    </div>
                </div>
            </div>
            <!-- Slide 3: Vehicle -->
            <div class="carousel-item hero-carousel-item">
                <img src="<?= app_url('public/assets/images/hero_vehicle.png') ?>" width="1920" height="600" class="d-block w-100" alt="Luxury Fleet" loading="lazy">
                <div class="hero-overlay">
                    <div class="hero-content">
                        <h1 class="hero-title">Travel in Style</h1>
                        <p class="hero-subtitle">Premium vehicle rentals for your journey across the island.</p>
                        <a href="#" class="btn hero-btn">Rent a Car</a>
                    </div>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</section>

<script src="<?= app_url('public/hero/hero.js') ?>"></script>