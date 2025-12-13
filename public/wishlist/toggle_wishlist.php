<?php
require __DIR__ . '/../../config/db.php';
ensure_session_started();
$user = current_user();

header('Content-Type: application/json');

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$type = $_POST['type'] ?? '';
$itemId = intval($_POST['item_id'] ?? 0);
$action = $_POST['action'] ?? 'toggle'; // toggle, add, remove

if (!in_array($type, ['property', 'room', 'vehicle']) || !$itemId) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$pdo = get_pdo();
$userId = $user['user_id'];

try {
    $table = $type . '_wishlist';
    $idColumn = $type . '_id';
    
    // Check if exists
    $stmt = $pdo->prepare("SELECT wishlist_id FROM $table WHERE customer_id = ? AND $idColumn = ?");
    $stmt->execute([$userId, $itemId]);
    $exists = $stmt->fetch();
    
    if ($action === 'remove' || ($action === 'toggle' && $exists)) {
        // Remove
        if ($exists) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE customer_id = ? AND $idColumn = ?");
            $stmt->execute([$userId, $itemId]);
            echo json_encode(['success' => true, 'action' => 'removed', 'in_wishlist' => false]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not in wishlist']);
        }
    } else {
        // Add
        if (!$exists) {
            $stmt = $pdo->prepare("INSERT INTO $table (customer_id, $idColumn) VALUES (?, ?)");
            $stmt->execute([$userId, $itemId]);
            echo json_encode(['success' => true, 'action' => 'added', 'in_wishlist' => true]);
        } else {
            echo json_encode(['success' => true, 'action' => 'already_exists', 'in_wishlist' => true]);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
