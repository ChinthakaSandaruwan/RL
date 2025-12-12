<?php
// API Handler for Chat Actions
require_once __DIR__ . '/../../config/db.php';
ensure_session_started();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = current_user();
$pdo = get_pdo();

if (function_exists('is_chat_enabled') && !is_chat_enabled()) {
    echo json_encode(['success' => false, 'message' => 'Chat is currently disabled.']);
    exit;
}

// Restrict to logged-in users
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// 1. Send Message
if ($action === 'send') {
    $message = trim($_POST['message'] ?? '');
    if (!$message) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        exit;
    }

    // Check latest conversation
    $stmt = $pdo->prepare("SELECT conversation_id, status FROM chat_conversations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user['user_id']]);
    $conversation = $stmt->fetch();

    // If no conversation or last one is closed, create new one
    if (!$conversation || $conversation['status'] === 'closed') {
        $stmt = $pdo->prepare("INSERT INTO chat_conversations (user_id, status) VALUES (?, 'open')");
        $stmt->execute([$user['user_id']]);
        $conversationId = $pdo->lastInsertId();
    } else {
        $conversationId = $conversation['conversation_id'];
        // Update timestamp
        $pdo->prepare("UPDATE chat_conversations SET updated_at = NOW() WHERE conversation_id = ?")->execute([$conversationId]);
    }

    // Insert Message
    $stmt = $pdo->prepare("INSERT INTO chat_messages (conversation_id, sender_id, sender_type, message) VALUES (?, ?, 'user', ?)");
    if ($stmt->execute([$conversationId, $user['user_id'], $message])) {
        echo json_encode(['success' => true, 'conversation_id' => $conversationId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send']);
    }
}

// 2. Poll Messages
elseif ($action === 'fetch') {
    // Fetch messages for this user's latest conversation (open or closed)
    // This allows customer to see final messages even after conversation is closed
    $stmt = $pdo->prepare("SELECT conversation_id, status FROM chat_conversations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user['user_id']]);
    $conversation = $stmt->fetch();

    if (!$conversation) {
        echo json_encode(['success' => true, 'messages' => [], 'conversation_id' => null, 'status' => null]);
        exit;
    }

    $lastId = (int)($_GET['last_id'] ?? 0);
    
    // PERFORMANCE OPTIMIZATION: Quick check if there are new messages
    // This prevents unnecessary full message fetches when conversation is idle
    $stmt = $pdo->prepare("SELECT MAX(message_id) as max_id FROM chat_messages WHERE conversation_id = ?");
    $stmt->execute([$conversation['conversation_id']]);
    $maxId = (int)$stmt->fetchColumn();
    
    // If no new messages, return early without fetching full message list
    if ($maxId <= $lastId) {
        echo json_encode([
            'success' => true,
            'messages' => [],
            'conversation_id' => $conversation['conversation_id'],
            'status' => $conversation['status']
        ]);
        exit;
    }

    // Fetch new messages only if there are actually new ones
    $stmt = $pdo->prepare("
        SELECT message_id, sender_type, message, created_at 
        FROM chat_messages 
        WHERE conversation_id = ? AND message_id > ? 
        ORDER BY message_id ASC
    ");
    $stmt->execute([$conversation['conversation_id'], $lastId]);
    $messages = $stmt->fetchAll();

    echo json_encode([
        'success' => true, 
        'messages' => $messages,
        'conversation_id' => $conversation['conversation_id'],
        'status' => $conversation['status']
    ]);
}

// 3. End Conversation (Customer initiated)
elseif ($action === 'end') {
    $conversationId = (int)($_POST['conversation_id'] ?? 0);
    
    if (!$conversationId) {
        echo json_encode(['success' => false, 'message' => 'Invalid conversation ID']);
        exit;
    }
    
    try {
        // Verify ownership
        $stmt = $pdo->prepare("SELECT user_id FROM chat_conversations WHERE conversation_id = ?");
        $stmt->execute([$conversationId]);
        $conv = $stmt->fetch();
        
        if (!$conv) {
            echo json_encode(['success' => false, 'message' => 'Conversation not found']);
            exit;
        }
        
        if ($conv['user_id'] != $user['user_id']) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to end this conversation']);
            exit;
        }
        
        // End conversation
        $stmt = $pdo->prepare("UPDATE chat_conversations SET status = 'closed' WHERE conversation_id = ?");
        if ($stmt->execute([$conversationId])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed']);
        }
    } catch (PDOException $e) {
        error_log("End conversation error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
