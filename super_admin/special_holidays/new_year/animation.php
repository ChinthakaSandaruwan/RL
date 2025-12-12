<?php
$statusFile = __DIR__ . '/status.json';
$isEnabled = false;

if (file_exists($statusFile)) {
    $data = json_decode(file_get_contents($statusFile), true);
    $isEnabled = isset($data['enabled']) && $data['enabled'];
}

if ($isEnabled) {
    echo '<link rel="stylesheet" href="' . app_url('super_admin/special_holidays/new_year/animation.css') . '">';
    echo '<script src="' . app_url('super_admin/special_holidays/new_year/animation.js') . '"></script>';
}
?>
