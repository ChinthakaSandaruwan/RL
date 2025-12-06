<?php
require __DIR__ . '/../../config/db.php';

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

logout_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logging out...</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
</head>
<body class="bg-light">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.homeUrl = "<?= app_url('index.php') ?>";
</script>
<script src="index.js"></script>

</body>
</html>
