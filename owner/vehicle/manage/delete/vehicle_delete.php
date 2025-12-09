<?php
require __DIR__ . '/../../../../config/db.php';
ensure_session_started();
$user = current_user();

if (!$user || !in_array($user['role_id'], [3])) { 
    header('Location: ' . app_url('auth/login')); 
    exit; 
}

$vid = $_GET['id'] ?? 0;
if (!$vid) { 
    $_SESSION['error'] = "Invalid vehicle ID.";
    header('Location: ../manage.php'); 
    exit; 
}

$pdo = get_pdo();

// Verify Owner
$stmt = $pdo->prepare("SELECT vehicle_id FROM vehicle WHERE vehicle_id = ? AND owner_id = ?");
$stmt->execute([$vid, $user['user_id']]);
if (!$stmt->fetch()) { 
    $_SESSION['error'] = "Access Denied or Vehicle not found."; 
    header('Location: ../manage.php'); 
    exit; 
}

try {
    $pdo->beginTransaction();

    // Get Images to delete files
    $stmt = $pdo->prepare("SELECT image_path FROM vehicle_image WHERE vehicle_id = ?");
    $stmt->execute([$vid]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Delete Dependents
    $pdo->prepare("DELETE FROM vehicle_location WHERE vehicle_id = ?")->execute([$vid]);
    $pdo->prepare("DELETE FROM vehicle_image WHERE vehicle_id = ?")->execute([$vid]);
    
    // Delete the vehicle
    $pdo->prepare("DELETE FROM vehicle WHERE vehicle_id = ?")->execute([$vid]);

    $pdo->commit();

    // Delete Files from filesystem
    foreach ($images as $path) {
        $fullPath = __DIR__ . '/../../../../' . $path;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    $_SESSION['success'] = "Vehicle deleted successfully.";
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Delete failed: " . $e->getMessage();
}

header('Location: ../manage.php');
exit;
