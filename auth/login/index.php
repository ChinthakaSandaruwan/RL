<?php
// Basic login form (processing can be added later)

require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../services/sms.php';

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
                $errors[] = 'No user found with that mobile number.';
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
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="login.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header text-center border-0 pt-4 pb-2 bg-transparent">
                    <h2 class="mb-1" style="color: var(--hunter-green);">Welcome Back</h2>
                    <p class="text-muted small mb-0">Login with your mobile number</p>
                </div>
                <div class="card-body">



                    <h5>1. Request OTP</h5>
                    <form method="post" class="mb-4">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="action" value="request_otp">
                        <div class="form-floating mb-3">
                            <input type="text" name="mobile" class="form-control" id="mobileInput" placeholder="Mobile Number" required value="<?= htmlspecialchars($pendingMobile, ENT_QUOTES, 'UTF-8') ?>">
                            <label for="mobileInput">Mobile Number</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 shadow-sm">Send OTP</button>
                    </form>

                    <h5>2. Verify OTP</h5>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="action" value="verify_otp">
                        <div class="form-floating mb-3">
                            <input type="text" name="otp" class="form-control" id="otpInput" placeholder="OTP Code" required>
                            <label for="otpInput">OTP Code</label>
                        </div>
                        <button type="submit" class="btn btn-success w-100 shadow-sm">Login</button>
                    </form>

                    <div class="mt-4 text-center">
                        <p class="mb-0 text-muted">Don't have an account?</p>
                        <a href="<?= app_url('auth/register') ?>" class="fw-bold">Create an account</a>
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
        errors: <?= json_encode($errors) ?>
    };
</script>
<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="index.js"></script>
</body>
</html>
