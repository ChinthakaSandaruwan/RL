<?php
// Basic registration form (processing can be added later)

require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../services/email.php';

ensure_session_started();

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$pdo = get_pdo();
$errors = [];
$success = null;
$csrf_token = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF Token');
    }

    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $nic    = trim($_POST['nic'] ?? '');

    if ($name === '' || $email === '' || $mobile === '' || $nic === '') {
        $errors[] = 'Name, email, mobile, and NIC are required.';
    }

    // Name: Letters and spaces only, min 3 chars
    if (!preg_match('/^[a-zA-Z\s]{3,}$/', $name)) {
        $errors[] = 'Name must contain only letters and spaces, and be at least 3 characters long.';
    }

    // Mobile: 10 digits starting with 07
    if (!preg_match('/^07\d{8}$/', $mobile)) {
        $errors[] = 'Invalid mobile number. Must be 10 digits starting with 07 (e.g., 0712345678).';
    }

    // NIC: Old (9 digits + V/X) or New (12 digits)
    if (!preg_match('/^([0-9]{9}[x|X|v|V]|[0-9]{12})$/', $nic)) {
        $errors[] = 'Invalid NIC. Must be either 9 digits + V/X or 12 digits.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT 1 FROM `user` WHERE `mobile_number` = ? OR `email` = ? OR `nic` = ? LIMIT 1');
        $stmt->execute([$mobile, $email, $nic]);
        if ($stmt->fetch()) {
            $errors[] = 'A user with this email, mobile, or NIC already exists.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO `user` (`email`, `name`, `mobile_number`, `nic`, `role_id`, `status_id`, `created_at`) VALUES (?, ?, ?, ?, 4, 1, NOW())');
        $stmt->execute([$email, $name, $mobile, $nic]);
        
        // Send welcome email (non-blocking - registration succeeds even if email fails)
        $emailSent = send_welcome_email($email, $name);
        
        if ($emailSent) {
            $success = 'Registration successful! A welcome email has been sent to your inbox. You can now request an OTP to login.';
        } else {
            $success = 'Registration successful! You can now request an OTP to login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= app_url('public/favicon/android-chrome-192x192.png') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= app_url('public/favicon/android-chrome-512x512.png') ?>">
    <link rel="shortcut icon" href="<?= app_url('public/favicon/favicon.ico') ?>">
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="register.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header text-center border-0 pt-4 pb-2 bg-transparent">
                    <h2 class="mb-1" style="color: var(--hunter-green);">Join Rental Lanka</h2>
                    <p class="text-muted small mb-0">Create your account to get started</p>
                </div>
                <div class="card-body">




                    <?php if (isset($_GET['mobile']) && !isset($_POST['mobile'])): ?>
                        <div class="alert alert-info small py-2 mb-3">
                            <i class="fa fa-info-circle me-1"></i> No account found. Please register below.
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <div class="form-floating mb-3">
                            <input type="text" name="name" class="form-control" id="nameInput" placeholder="Full Name" required value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <label for="nameInput">Full Name</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="email" name="email" class="form-control" id="emailInput" placeholder="Email Address" required value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <label for="emailInput">Email Address</label>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" name="mobile" class="form-control" id="mobileInput" placeholder="Mobile Number" required value="<?= htmlspecialchars($_POST['mobile'] ?? $_GET['mobile'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <label for="mobileInput">Mobile Number</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" name="nic" class="form-control" id="nicInput" placeholder="NIC Number" required value="<?= htmlspecialchars($_POST['nic'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <label for="nicInput">NIC Number</label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 shadow-sm btn-lg mt-2">Create Account</button>
                    </form>

                    <div class="mt-4 text-center">
                        <p class="mb-0 text-muted">Already have an account?</p>
                        <a href="<?= app_url('auth/login/index.php') ?>" class="fw-bold">Login here</a>
                        <div class="mt-2">
                             <a href="<?= app_url('index.php') ?>" class="small text-muted">Back to home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.serverMessages = {
        success: <?= json_encode($success) ?>,
        errors: <?= json_encode($errors) ?>,
        mobile: <?= json_encode($mobile ?? null) ?>
    };
</script>
<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="index.js"></script>
</body>
</html>
