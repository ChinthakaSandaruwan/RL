<?php
require_once __DIR__ . '/../../config/db.php';
ensure_session_started();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Rental Lanka</title>
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= app_url('public/favicon/android-chrome-192x192.png') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= app_url('public/favicon/android-chrome-512x512.png') ?>">
    <link rel="shortcut icon" href="<?= app_url('public/favicon/favicon.ico') ?>">
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="terms_of_service.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../navbar/navbar.php'; ?>

<!-- Header -->
<div class="terms-header bg-theme text-white text-center py-5">
    <div class="container animate-up">
        <h1 class="display-4 fw-bold">Terms of Service</h1>
        <p class="lead opacity-75">Please read these terms carefully before using our services.</p>
        <p class="small opacity-50">Last Update: <?= date('F d, Y') ?></p>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <!-- Sticky Sidebar Navigation -->
        <div class="col-md-3 d-none d-md-block">
            <div class="sticky-top" style="top: 100px; z-index: 1;">
                <nav id="terms-nav" class="nav flex-column nav-pills p-3 bg-white rounded shadow-sm">
                    <a class="nav-link active" href="#acceptance">1. Acceptance of Terms</a>
                    <a class="nav-link" href="#eligibility">2. Eligibility</a>
                    <a class="nav-link" href="#accounts">3. User Accounts</a>
                    <a class="nav-link" href="#content">4. User Content</a>
                    <a class="nav-link" href="#bookings">5. Bookings & Payments</a>
                    <a class="nav-link" href="#cancellation">6. Cancellation Policy</a>
                    <a class="nav-link" href="#liability">7. Limitation of Liability</a>
                    <a class="nav-link" href="#contact">8. Contact Us</a>
                </nav>
            </div>
        </div>

        <!-- Content Area -->
        <div class="col-md-9">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    
                    <section id="acceptance" class="mb-5">
                        <h3 class="fw-bold text-hunter-green mb-3">1. Acceptance of Terms</h3>
                        <p class="text-muted">
                            By accessing or using the Rental Lanka platform ("Service"), you agree to be bound by these Terms of Service ("Terms"). If you disagree with any part of the terms, then you may not access the Service.
                        </p>
                    </section>

                    <section id="eligibility" class="mb-5">
                        <h3 class="fw-bold text-hunter-green mb-3">2. Eligibility</h3>
                        <p class="text-muted">
                            You must be at least 18 years old to use our Service. By using Rental Lanka, you represent and warrant that you have the right, authority, and capacity to enter into this agreement and to abide by all of the terms and conditions set forth herein.
                        </p>
                    </section>

                    <section id="accounts" class="mb-5">
                        <h3 class="fw-bold text-hunter-green mb-3">3. User Accounts</h3>
                        <p class="text-muted">
                            To use certain features of the app, you may be required to register for an account. You agree to keep your password confidential and will be responsible for all use of your account and password. We reserve the right to remove, reclaim, or change a username you select if we determine, in our sole discretion, that such username is inappropriate.
                        </p>
                    </section>

                    <section id="content" class="mb-5">
                        <h3 class="fw-bold text-hunter-green mb-3">4. User Content</h3>
                        <p class="text-muted">
                            Users (Owners) can post property, room, and vehicle listings. You are solely responsible for the content you post. By posting content, you grant us the right and license to use, modify, publicly perform, publicly display, reproduce, and distribute such content on and through the Service.
                        </p>
                        <div class="alert alert-light border-start border-4 border-success mt-3">
                            <strong>Note:</strong> We reserve the right to remove any content that violates our policies, fits the description of spam, or is deemed inappropriate.
                        </div>
                    </section>

                    <section id="bookings" class="mb-5">
                        <h3 class="fw-bold text-hunter-green mb-3">5. Bookings & Payments</h3>
                        <p class="text-muted">
                            Rental Lanka acts as an intermediary platform. Payments for rentals are typically handled directly between the Renter and the Owner, unless otherwise specified for specific online booking features. We facilitate the connection but are not a party to the rental agreement itself.
                        </p>
                    </section>
                    
                    <section id="cancellation" class="mb-5">
                        <h3 class="fw-bold text-hunter-green mb-3">6. Cancellation Policy</h3>
                        <p class="text-muted">
                            Cancellation policies vary by Owner and listing. Please review the specific cancellation terms provided on each listing detail page before booking. Rental Lanka is not responsible for refunds unless the issue stems directly from a platform technical failure.
                        </p>
                    </section>

                    <section id="liability" class="mb-5">
                        <h3 class="fw-bold text-hunter-green mb-3">7. Limitation of Liability</h3>
                        <p class="text-muted">
                            In no event shall Rental Lanka, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from your access to or use of or inability to access or use the Service.
                        </p>
                    </section>

                    <section id="contact" class="mb-5">
                        <h3 class="fw-bold text-hunter-green mb-3">8. Contact Us</h3>
                        <p class="text-muted mb-4">
                            If you have any questions about these Terms, please contact us.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <i class="bi bi-envelope-fill fs-3 text-hunter-green me-3"></i>
                                    <div>
                                        <h6 class="mb-0 fw-bold">Email Support</h6>
                                        <small class="text-muted">legal@rentallanka.com</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <i class="bi bi-geo-alt-fill fs-3 text-hunter-green me-3"></i>
                                    <div>
                                        <h6 class="mb-0 fw-bold">Mailing Address</h6>
                                        <small class="text-muted">123 Main St, Colombo, LK</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../footer/footer.php'; ?>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="terms_and_conditions.js"></script>
</body>
</html>
