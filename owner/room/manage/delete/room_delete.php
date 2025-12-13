<?php
require __DIR__ . '/../../../../config/db.php';
ensure_session_started();
$user = current_user();

if (!$user || !in_array($user['role_id'], [3])) { 
    header('Location: ' . app_url('auth/login')); 
    exit; 
}

$rid = $_GET['id'] ?? 0;
if (!$rid) { 
    $_SESSION['_flash']['error'] = "Invalid room ID.";
    header('Location: ../manage.php'); 
    exit; 
}

$pdo = get_pdo();

// Verify Owner
$stmt = $pdo->prepare("SELECT room_id FROM room WHERE room_id = ? AND owner_id = ?");
$stmt->execute([$rid, $user['user_id']]);
if (!$stmt->fetch()) { 
    $_SESSION['_flash']['error'] = "Access Denied or Room not found."; 
    header('Location: ../manage.php'); 
    exit; 
}

try {
    $pdo->beginTransaction();

    // Get Images to delete files
    $stmt = $pdo->prepare("SELECT image_path FROM room_image WHERE room_id = ?");
    $stmt->execute([$rid]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Delete Dependents
    $pdo->prepare("DELETE FROM room_amenity WHERE room_id = ?")->execute([$rid]);
    $pdo->prepare("DELETE FROM room_location WHERE room_id = ?")->execute([$rid]);
    $pdo->prepare("DELETE FROM room_meal WHERE room_id = ?")->execute([$rid]);
    $pdo->prepare("DELETE FROM room_image WHERE room_id = ?")->execute([$rid]);
    
    // Delete the room
    $pdo->prepare("DELETE FROM room WHERE room_id = ?")->execute([$rid]);

    $pdo->commit();

    // Delete Files from filesystem
    foreach ($images as $path) {
        $fullPath = __DIR__ . '/../../../../' . $path;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    $_SESSION['_flash']['success'] = "Room deleted successfully.";
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['_flash']['error'] = "Delete failed: " . $e->getMessage();
}

header('Location: ../manage.php');
exit;
