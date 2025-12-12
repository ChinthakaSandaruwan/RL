<?php
// Basic login form (processing can be added later)

require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../services/sms.php';

ensure_session_started();

if (isset($_SESSION['user_id'])) {
    header('Location: ' . app_url('index.php'));
    exit;
}

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$pdo = get_pdo();
$errors = [];
$success = null;
$csrf_token = generate_csrf_token();

// Handle 'Change Number' request (GET)
if (isset($_GET['action']) && $_GET['action'] === 'change_number') {
    unset($_SESSION['pending_login_user_id'], $_SESSION['pending_login_mobile']);
    header('Location: ' . app_url('auth/login/index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF Token');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'request_otp') {
        $mobile = trim($_POST['mobile'] ?? '');
        if ($mobile === '') {
            $errors[] = 'Mobile number is required.';
        } elseif (!preg_match('/^07\d{8}$/', $mobile)) {
            $errors[] = 'Invalid mobile number. Must be 10 digits starting with 07 (e.g., 0712345678).';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM `user` WHERE `mobile_number` = ? LIMIT 1');
            $stmt->execute([$mobile]);
            $user = $stmt->fetch();
            if (!$user) {
                // Redirect to register page with pre-filled mobile
                header('Location: ' . app_url('auth/register/index.php?mobile=' . urlencode($mobile)));
                exit;
            } else {
                $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expiresAt = (new DateTime('+5 minutes'))->format('Y-m-d H:i:s');

                $pdo->prepare('DELETE FROM `otp_verification` WHERE `user_id` = ?')->execute([$user['user_id']]);

                $stmt = $pdo->prepare('INSERT INTO `otp_verification` (`user_id`, `otp_code`, `expires_at`, `is_verified`) VALUES (?, ?, ?, 0)');
                $stmt->execute([$user['user_id'], $otp, $expiresAt]);

                $_SESSION['pending_login_user_id'] = $user['user_id'];
                $_SESSION['pending_login_mobile'] = $mobile;

                $formattedMobile = $mobile;
                if (str_starts_with($formattedMobile, '0')) {
                    $formattedMobile = '+94' . substr($formattedMobile, 1);
                }

                $smsMessage = 'Your RentalLanka login OTP is ' . $otp . ' (valid for 5 minutes).';
                $sent = smslenz_send_sms($formattedMobile, $smsMessage);

                // Allow bypass in local environment
                $isLocal = env('APP_ENV') === 'local';

                if ($sent) {
                    $success = 'OTP sent via SMS.';
                } elseif ($isLocal) {
                    $success = 'OTP Warning: SMS failed but bypassed for LOCAL ENV. Code: ' . $otp;
                } else {
                    $errors[] = 'Failed to send OTP SMS. Please try again later.';
                    // If SMS fails, maybe don't change state? For now, we set session so UI changes.
                    // If critical failure, we might want to unset session.
                }
            }
        }
    } elseif ($action === 'verify_otp') {
        $otp = trim($_POST['otp'] ?? '');
        if ($otp === '') {
            $errors[] = 'OTP is required.';
        } elseif (!ctype_digit($otp) || strlen($otp) !== 6) {
            $errors[] = 'Invalid OTP format. Must be a 6-digit number.';
        } else {
            $pendingUserId = $_SESSION['pending_login_user_id'] ?? null;
            if (!$pendingUserId) {
                $errors[] = 'No pending OTP login. Please request a new OTP.';
            } else {
                $stmt = $pdo->prepare('SELECT * FROM `otp_verification` WHERE `user_id` = ? AND `otp_code` = ? LIMIT 1');
                $stmt->execute([$pendingUserId, $otp]);
                $row = $stmt->fetch();
                if (!$row) {
                    $errors[] = 'Invalid OTP.';
                } else {
                    if ($row['is_verified']) {
                        $errors[] = 'This OTP has already been used.';
                    } elseif (new DateTime($row['expires_at']) < new DateTime()) {
                        $errors[] = 'OTP has expired. Please request a new one.';
                    } else {
                        $pdo->prepare('UPDATE `otp_verification` SET `is_verified` = 1 WHERE `otp_id` = ?')
                            ->execute([$row['otp_id']]);


                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $pendingUserId;
                        unset($_SESSION['pending_login_user_id'], $_SESSION['pending_login_mobile']);

                        header('Location: ' . app_url('index.php'));
                        exit;
                    }
                }
            }
        }
    } elseif ($action === 'resend_otp') {
        // Resend Logic
        $pendingUserId = $_SESSION['pending_login_user_id'] ?? null;
        $mobile = $_SESSION['pending_login_mobile'] ?? null;

        if ($pendingUserId && $mobile) {
            $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = (new DateTime('+5 minutes'))->format('Y-m-d H:i:s');
            
             $pdo->prepare('DELETE FROM `otp_verification` WHERE `user_id` = ?')->execute([$pendingUserId]);
             $stmt = $pdo->prepare('INSERT INTO `otp_verification` (`user_id`, `otp_code`, `expires_at`, `is_verified`) VALUES (?, ?, ?, 0)');
             $stmt->execute([$pendingUserId, $otp, $expiresAt]);
             
             $formattedMobile = $mobile;
                if (str_starts_with($formattedMobile, '0')) {
                    $formattedMobile = '+94' . substr($formattedMobile, 1);
                }

            $smsMessage = 'Your RentalLanka login OTP is ' . $otp . ' (valid for 5 minutes).';
            $sent = smslenz_send_sms($formattedMobile, $smsMessage);
            
             $isLocal = env('APP_ENV') === 'local';
             if ($sent) {
                 $success = 'OTP resent via SMS.';
             } elseif ($isLocal) {
                 $success = 'OTP Resent (Local): ' . $otp;
             } else {
                 $errors[] = 'Failed to resend OTP SMS.';
             }
        }
    }
}

$pendingMobile = $_SESSION['pending_login_mobile'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= app_url('public/favicon/android-chrome-192x192.png') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= app_url('public/favicon/android-chrome-512x512.png') ?>">
    <link rel="shortcut icon" href="<?= app_url('public/favicon/favicon.ico') ?>">
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="login.css">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="login-wrapper">
    <div class="glass-card">
        <div class="login-header">
            <img src="<?= app_url('public/favicon/apple-touch-icon.png') ?>" alt="Rental Lanka Logo" class="brand-logo mb-3" style="max-height: 60px;">
            <h2>Welcome Back</h2>
            <p>Access your rental dashboard</p>
        </div>

        <div class="login-body">
            <?php if (empty($pendingMobile)): ?>
                <!-- Step 1: Mobile Number -->
                <form method="post" id="mobileForm" class="login-form">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="action" value="request_otp">
                    
                    <div class="input-group-custom mb-4">
                        <label for="mobileInput" class="form-label">Mobile Number</label>
                        <div class="input-field-wrapper">
                            <i class="fa-solid fa-mobile-screen-button icon"></i>
                            <input type="text" name="mobile" id="mobileInput" placeholder="07XXXXXXXX" required autofocus autocomplete="tel" value="<?= htmlspecialchars($_POST['mobile'] ?? $_GET['mobile'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="form-text">We'll send you a verification code.</div>
                    </div>

                    <button type="submit" class="btn-login">
                        Send OTPCode <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </form>
            <?php else: ?>
                <!-- Step 2: Verify OTP -->
                 <div class="otp-verification-state">
                    <div class="mb-4 text-center">
                        <span class="badge bg-soft-success text-success mb-2">OTP Sent</span>
                        <p class="text-mobile-sent">
                            Enter the code sent to <strong><?= htmlspecialchars($pendingMobile) ?></strong>
                            <a href="?action=change_number" class="change-number-link" title="Change Number"><i class="fa-solid fa-pen-to-square"></i></a>
                        </p>
                    </div>

                    <form method="post" id="otpForm" class="login-form">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="action" value="verify_otp">
                        
                        <div class="input-group-custom mb-4">
                            <label for="otpInput" class="form-label">Verification Code</label>
                            <div class="input-field-wrapper">
                                <i class="fa-solid fa-shield-halved icon"></i>
                                <input type="text" name="otp" id="otpInput" placeholder="Enter 6-digit code" required maxlength="6" pattern="\d{6}" autofocus autocomplete="one-time-code">
                            </div>
                        </div>

                        <button type="submit" class="btn-login">
                            Verify & Login <i class="fa-solid fa-check ms-2"></i>
                        </button>
                    </form>

                    <div class="mt-4 text-center">
                        <form method="post" id="resendForm" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="action" value="resend_otp">
                            <button type="submit" id="resendBtn" class="btn-link-custom border-0 bg-transparent p-0" disabled>
                                Resend OTP
                            </button>
                        </form>
                        <span id="countdownTimer" class="ms-2 text-muted fw-bold small">(60s)</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="login-footer">
            <p>New to Rental Lanka? <a href="<?= app_url('auth/register') ?>">Create Account</a></p>
            <a href="<?= app_url('index.php') ?>" class="home-link"><i class="fa-solid fa-house me-1"></i> Back to Home</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.serverMessages = {
        success: <?= json_encode($success) ?>,
        errors: <?= json_encode($errors) ?>
    };
</script>
<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="index.js"></script>
</body>
</html>
