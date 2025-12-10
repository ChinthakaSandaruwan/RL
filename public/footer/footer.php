<?php
// Ensure DB connection
if (!function_exists('get_pdo')) {
    require_once __DIR__ . '/../../config/db.php';
}

$footerData = [];
try {
    $pdo = get_pdo();
    $stmt = $pdo->query("SELECT * FROM footer_content WHERE footer_id = 1");
    $footerData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback if table doesn't exist yet or DB error
}

// Defaults
$companyName = $footerData['company_name'] ?? 'Rental Lanka';
$aboutText = $footerData['about_text'] ?? 'Your trusted partner for specialized vehicle and equipment rentals in Sri Lanka.';
$copyright = $footerData['copyright_text'] ?? '© 2025 Copyright: RentalLanka.com';
$address = $footerData['address'] ?? 'Sri Lanka';
$email = $footerData['email'] ?? 'info@rentallanka.com';
$phone = $footerData['phone'] ?? '+94 11 234 5678';

// Socials
$facebook = $footerData['facebook_link'] ?? '';
$twitter = $footerData['twitter_link'] ?? '';
$google = $footerData['google_link'] ?? '';
$instagram = $footerData['instagram_link'] ?? '';
$linkedin = $footerData['linkedin_link'] ?? '';
$github = $footerData['github_link'] ?? '';

// Visibility
$showSocial = $footerData['show_social_links'] ?? 1;
$showProducts = $footerData['show_products'] ?? 1;
$showUseful = $footerData['show_useful_links'] ?? 1;
$showContact = $footerData['show_contact'] ?? 1;
?>

<!-- Footer -->
<footer class="text-center text-lg-start bg-body-tertiary text-muted pt-1">
  
  <!-- Section: Social media -->
  <?php if ($showSocial): ?>
  <section class="d-flex justify-content-center justify-content-lg-between p-4 border-bottom">
    <div class="me-5 d-none d-lg-block">
      <span>Get connected with us on social networks:</span>
    </div>
    
    <div>
      <?php if($facebook): ?><a href="<?= htmlspecialchars($facebook) ?>" class="me-4 text-reset"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
      <?php if($twitter): ?><a href="<?= htmlspecialchars($twitter) ?>" class="me-4 text-reset"><i class="fab fa-twitter"></i></a><?php endif; ?>
      <?php if($google): ?><a href="<?= htmlspecialchars($google) ?>" class="me-4 text-reset"><i class="fab fa-google"></i></a><?php endif; ?>
      <?php if($instagram): ?><a href="<?= htmlspecialchars($instagram) ?>" class="me-4 text-reset"><i class="fab fa-instagram"></i></a><?php endif; ?>
      <?php if($linkedin): ?><a href="<?= htmlspecialchars($linkedin) ?>" class="me-4 text-reset"><i class="fab fa-linkedin"></i></a><?php endif; ?>
      <?php if($github): ?><a href="<?= htmlspecialchars($github) ?>" class="me-4 text-reset"><i class="fab fa-github"></i></a><?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Section: Links  -->
  <section class="">
    <div class="container text-center text-md-start mt-5">
      <div class="row mt-3">
        
        <!-- Company Info -->
        <div class="col-md-3 col-lg-4 col-xl-3 mx-auto mb-4">
          <h6 class="text-uppercase fw-bold mb-4">
            <i class="fas fa-gem me-3"></i><?= htmlspecialchars($companyName) ?>
          </h6>
          <p><?= nl2br(htmlspecialchars($aboutText)) ?></p>
        </div>

        <!-- Products -->
        <?php if ($showProducts): ?>
        <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mb-4">
          <h6 class="text-uppercase fw-bold mb-4">
            Products
          </h6>
          <p><a href="#!" class="text-reset">Properties</a></p>
          <p><a href="#!" class="text-reset">Vehicles</a></p>
          <p><a href="#!" class="text-reset">Rooms</a></p>
          <p><a href="#!" class="text-reset">Equipment</a></p>
        </div>
        <?php endif; ?>

        <!-- Useful Links -->
        <?php if ($showUseful): ?>
        <div class="col-md-3 col-lg-2 col-xl-2 mx-auto mb-4">
          <h6 class="text-uppercase fw-bold mb-4">
            Useful links
          </h6>
          <p><a href="<?= app_url('public/about_us/about.php') ?>" class="text-reset">About Us</a></p>
          <p><a href="<?= app_url('public/terms_of_service/terms_of_service.php') ?>" class="text-reset">Terms of Service</a></p>
          <p><a href="#!" class="text-reset">Help</a></p>
        </div>
        <?php endif; ?>

        <!-- Contact -->
        <?php if ($showContact): ?>
        <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mb-md-0 mb-4">
          <h6 class="text-uppercase fw-bold mb-4">Contact</h6>
          <p><i class="fas fa-home me-3"></i> <?= htmlspecialchars($address) ?></p>
          <p>
            <i class="fas fa-envelope me-3"></i>
            <?= htmlspecialchars($email) ?>
          </p>
          <p><i class="fas fa-phone me-3"></i> <?= htmlspecialchars($phone) ?></p>
        </div>
        <?php endif; ?>
        
      </div>
    </div>
  </section>

  <!-- Copyright -->
  <div class="text-center p-4" style="background-color: rgba(0, 0, 0, 0.05);">
    <?= htmlspecialchars($copyright) ?>
  </div>
</footer>
