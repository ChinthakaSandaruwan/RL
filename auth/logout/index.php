<?php
require __DIR__ . '/../../config/db.php';

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

logout_user();

// Redirect to login page with logout success flag
header("Location: " . app_url('auth/login/index.php?logout=success'));
exit;
?>
