<?php
require_once __DIR__ . '/../../config/db.php';
ensure_session_started();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method');
}

$user = current_user();
if (!$user) {
    header("Location: " . app_url('auth/login'));
    exit;
}

$rent_id = filter_input(INPUT_POST, 'rent_id', FILTER_VALIDATE_INT);
$type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);

if (!$rent_id || !$type) {
    die('Invalid parameters');
}

$pdo = get_pdo();
$table = '';

switch ($type) {
    case 'property':
        $table = 'property_rent';
        break;
    case 'room':
        $table = 'room_rent';
        break;
    case 'vehicle':
        $table = 'vehicle_rent';
        break;
    default:
        die('Invalid type');
}

// Update status to 3 (Cancelled) ONLY if it is currently 2 (Pending)
// and belongs to the current user
$sql = "UPDATE $table 
        SET status_id = 3 
        WHERE rent_id = ? AND customer_id = ? AND status_id = 2";

$stmt = $pdo->prepare($sql);
$result = $stmt->execute([$rent_id, $user['user_id']]);

if ($result && $stmt->rowCount() > 0) {
    // Success
    header("Location: " . app_url('public/my_rent/my_rent.php?msg=cancelled'));
} else {
    // Failed (maybe not pending, or not owned by user)
    header("Location: " . app_url('public/my_rent/my_rent.php?error=cancel_failed'));
}
exit;
