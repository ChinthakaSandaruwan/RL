<?php
// API for typing status
require_once __DIR__ . '/../../config/db.php';
ensure_session_started();

header('Content-Type: application/json');

$user = current_user();
$pdo = get_pdo();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$conversationId = (int)($_POST['conversation_id'] ?? $_GET['conversation_id'] ?? 0);

// Set typing status (for admin)
if ($action === 'set_typing' && $user && in_array($user['role_id'], [1, 2])) {
    $isTyping = (int)($_POST['is_typing'] ?? 0);
    
    // Store typing status in session or temp table
    $_SESSION["typing_admin_{$conversationId}"] = $isTyping ? time() : 0;
    
    echo json_encode(['success' => true]);
}

// Check typing status (for customer)
elseif ($action === 'check_typing') {
    // Check if admin was typing in last 2 seconds (reduced for faster response)
    $lastTyping = $_SESSION["typing_admin_{$conversationId}"] ?? 0;
    $isTyping = ($lastTyping > 0 && (time() - $lastTyping) < 2);
    
    echo json_encode(['success' => true, 'is_typing' => $isTyping]);
}

else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
