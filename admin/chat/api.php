<?php
// API Handler for Admin Chat Actions
require_once __DIR__ . '/../../config/db.php';
ensure_session_started();

header('Content-Type: application/json');

$user = current_user();
$pdo = get_pdo();

// Restrict to Admin/Super Admin
if (!$user || !in_array($user['role_id'], [1, 2])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. Send Admin Reply
if ($action === 'send') {
    $conversationId = (int)($_POST['conversation_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    if (!$conversationId || !$message) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO chat_messages (conversation_id, sender_id, sender_type, message) VALUES (?, ?, 'admin', ?)");
    if ($stmt->execute([$conversationId, $user['user_id'], $message])) {
        // Update conversation timestamp
        $pdo->prepare("UPDATE chat_conversations SET updated_at = NOW() WHERE conversation_id = ?")->execute([$conversationId]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send']);
    }
}

// 2. Fetch New Messages
elseif ($action === 'fetch') {
    $conversationId = (int)($_GET['conversation_id'] ?? 0);
    $lastId = (int)($_GET['last_id'] ?? 0);

    if (!$conversationId) {
        echo json_encode(['success' => false, 'message' => 'Invalid conversation']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT message_id, sender_type, message, created_at 
        FROM chat_messages 
        WHERE conversation_id = ? AND message_id > ? 
        ORDER BY message_id ASC
    ");
    $stmt->execute([$conversationId, $lastId]);
    $messages = $stmt->fetchAll();

    echo json_encode(['success' => true, 'messages' => $messages]);
}

// 3. Close Conversation
elseif ($action === 'close') {
    $conversationId = (int)($_POST['conversation_id'] ?? 0);
    
    if (!$conversationId) {
        echo json_encode(['success' => false, 'message' => 'Invalid conversation']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE chat_conversations SET status = 'closed' WHERE conversation_id = ?");
    if ($stmt->execute([$conversationId])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to close']);
    }
}

// 4. Delete Conversation
elseif ($action === 'delete') {
    $conversationId = (int)($_POST['conversation_id'] ?? 0);
    
    if (!$conversationId) {
        echo json_encode(['success' => false, 'message' => 'Invalid conversation']);
        exit;
    }

    try {
        // First, delete all messages in this conversation
        $stmt = $pdo->prepare("DELETE FROM chat_messages WHERE conversation_id = ?");
        $stmt->execute([$conversationId]);
        
        // Then delete the conversation
        $stmt = $pdo->prepare("DELETE FROM chat_conversations WHERE conversation_id = ?");
        if ($stmt->execute([$conversationId])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete conversation']);
        }
    } catch (PDOException $e) {
        error_log("Delete conversation error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// 5. List Conversations
elseif ($action === 'list_conversations') {
    $stmt = $pdo->query("
        SELECT 
            c.conversation_id,
            c.user_id,
            c.status,
            c.updated_at,
            u.name,
            u.email,
            (SELECT COUNT(*) FROM chat_messages WHERE conversation_id = c.conversation_id AND is_read = 0 AND sender_type = 'user') as unread_count
        FROM chat_conversations c
        JOIN user u ON c.user_id = u.user_id
        ORDER BY c.updated_at DESC
    ");
    $conversations = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'conversations' => $conversations]);
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
