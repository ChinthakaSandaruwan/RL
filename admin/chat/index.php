<?php
require_once __DIR__ . '/../../config/db.php';

ensure_session_started();
$user = current_user();

// Check if user is Admin or Super Admin
if (!$user || !in_array($user['role_id'], [1, 2])) {
    header('Location: ' . app_url('index.php'));
    exit;
}

$pdo = get_pdo();

// Fetch all conversations with user info
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

// Get selected conversation
$selectedConversation = $_GET['conversation_id'] ?? null;
$messages = [];

if ($selectedConversation) {
    $stmt = $pdo->prepare("
        SELECT m.*, u.name as sender_name 
        FROM chat_messages m
        LEFT JOIN user u ON m.sender_id = u.user_id
        WHERE m.conversation_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$selectedConversation]);
    $messages = $stmt->fetchAll();
    
    // Mark as read
    $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE conversation_id = ? AND sender_type = 'user'")->execute([$selectedConversation]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Management - Rental Lanka</title>
    
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="shortcut icon" href="<?= app_url('public/favicon/favicon.ico') ?>">
    
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="chat.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../../public/navbar/navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Conversations List -->
        <div class="col-md-4 col-lg-3">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Conversations</h5>
                </div>
                <div class="list-group list-group-flush" id="conversationsList" style="max-height: 600px; overflow-y: auto;">
                    <!-- Conversations will be loaded here via AJAX -->
                    <div class="list-group-item text-center text-muted py-4">
                        <i class="bi bi-hourglass"></i> Loading...
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Window -->
        <div class="col-md-8 col-lg-9">
            <?php if ($selectedConversation): ?>
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Chat</h5>
                        <button class="btn btn-sm btn-danger" id="closeConversationBtn">
                            <i class="bi bi-stop-circle"></i> End Conversation
                        </button>
                    </div>
                    <div class="card-body" style="height: 500px; overflow-y: auto;" id="chatMessages">
                        <?php foreach ($messages as $msg): ?>
                            <div class="mb-3 <?= $msg['sender_type'] === 'admin' ? 'text-end' : '' ?>" data-message-id="<?= $msg['message_id'] ?>">
                                <div class="d-inline-block" style="max-width: 70%;">
                                    <div class="p-2 rounded <?= $msg['sender_type'] === 'admin' ? 'bg-primary text-white' : 'bg-light' ?>">
                                        <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                    </div>
                                    <small class="text-muted"><?= date('H:i', strtotime($msg['created_at'])) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer bg-white">
                        <form id="replyForm" class="d-flex gap-2">
                            <input type="hidden" id="conversationId" value="<?= $selectedConversation ?>">
                            <input type="text" id="messageInput" class="form-control" placeholder="Type your message..." required>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send-fill"></i> Send
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-chat-text display-1 text-muted"></i>
                        <h4 class="mt-3">Select a conversation</h4>
                        <p class="text-muted">Choose a conversation from the list to start chatting</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="chat.js"></script>

</body>
</html>
