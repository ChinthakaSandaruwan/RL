<?php
require_once __DIR__ . '/config/db.php';
$pdo = get_pdo();

echo "Property Rent Row:\n";
$stmt = $pdo->query('SELECT * FROM property_rent LIMIT 1');
print_r(array_keys($stmt->fetch(PDO::FETCH_ASSOC) ?: []));

echo "\nRoom Rent Row:\n";
$stmt = $pdo->query('SELECT * FROM room_rent LIMIT 1');
print_r(array_keys($stmt->fetch(PDO::FETCH_ASSOC) ?: []));

echo "\nVehicle Rent Row:\n";
$stmt = $pdo->query('SELECT * FROM vehicle_rent LIMIT 1');
print_r(array_keys($stmt->fetch(PDO::FETCH_ASSOC) ?: []));
