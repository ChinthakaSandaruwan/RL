<?php
require_once __DIR__ . '/config/db.php';
$pdo = get_pdo();

echo "Rent Status:\n";
$stmt = $pdo->query('SELECT * FROM rent_status');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nProperty Rent Columns:\n";
$stmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'rental_db' AND TABLE_NAME = 'property_rent'");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

echo "\nRoom Rent Columns:\n";
$stmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'rental_db' AND TABLE_NAME = 'room_rent'");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

echo "\nVehicle Rent Columns:\n";
$stmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'rental_db' AND TABLE_NAME = 'vehicle_rent'");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
