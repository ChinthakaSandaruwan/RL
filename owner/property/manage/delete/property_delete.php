<?php
require __DIR__ . '/../../../../config/db.php';
ensure_session_started();
$user = current_user();

// Check Role (Owner = 3)
if (!$user || !in_array($user['role_id'], [3])) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: ../manage.php');
    exit;
}

$pdo = get_pdo();

// 1. Verify Ownership & Existence
$stmt = $pdo->prepare("SELECT property_id FROM property WHERE property_id = ? AND owner_id = ?");
$stmt->execute([$id, $user['user_id']]);
$property = $stmt->fetch();

if (!$property) {
    $_SESSION['error'] = "Property not found or access denied.";
    header('Location: ../manage.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Cascading deletes usually handle relations, but manual cleanup is safer for file system (images).
    // Fetch image paths first to delete files
    $stmt = $pdo->prepare("SELECT image_path FROM property_image WHERE property_id = ?");
    $stmt->execute([$id]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Delete Database Records (Order matters if FKs are strict without CASCADE)
    // Assuming DB has CASCADE on DELETE for property_* tables, simple delete on parent works.
    // If not, we'd delete children first. Most setups here likely rely on CASCADE or manual.
    // Let's rely on standard DELETE CASCADE which is common. If it fails, I'll update.
    // For now, let's delete explicitly to be safe against strict FKs.
    
    $pdo->prepare("DELETE FROM property_amenity WHERE property_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM property_location WHERE property_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM property_image WHERE property_id = ?")->execute([$id]);
    
    // Finally delete Parent
    $pdo->prepare("DELETE FROM property WHERE property_id = ?")->execute([$id]);

    $pdo->commit();

    // Now delete physical files
    foreach ($images as $path) {
        // Path is relative: public/uploads/...
        // Need absolute path
        $fullPath = __DIR__ . '/../../../../' . $path;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    $_SESSION['success'] = "Property deleted successfully.";

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Failed to delete property: " . $e->getMessage();
}

header('Location: ../manage.php');
exit;
