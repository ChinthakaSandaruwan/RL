<?php
// Simple landing page

require __DIR__ . '/config/db.php';

ensure_session_started();



$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= app_url('public/footer/footer.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> <!-- Ensure FontAwesome is available for footer icons -->
</head>
<body class="bg-light">
<?php require __DIR__ . '/public/navbar/navbar.php'; ?>

<?php require __DIR__ . '/public/hero/hero.php'; ?>

<?php require __DIR__ . '/public/search/search/search.php'; ?>


<?php require __DIR__ . '/public/property/load/load_property.php'; ?>

<?php require __DIR__ . '/public/room/load/load_room.php'; ?>

<?php require __DIR__ . '/public/vehicle/load/load_vehicle.php'; ?>

<?php require __DIR__ . '/public/footer/footer.php'; ?>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
