<?php
// Chat Widget Component
$agentName = "Support Agent"; // Default
$agentRole = "Customer Support";
$agentAvatar = app_url('public/favicon/apple-touch-icon.png'); // Fallback to favicon since logo.png is missing

// Only show chat for logged-in users
if (function_exists('current_user')) {
    $chatUser = current_user();
    if (!$chatUser) {
        return;
    }
}

if (function_exists('is_chat_enabled') && !is_chat_enabled()) {
    return;
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= app_url('public/chat/chat.css') ?>">

<div class="chat-widget-container">
    <!-- Chat Window -->
    <div class="chat-window" data-avatar="<?= $agentAvatar ?>">
        
        <!-- Header -->
        <div class="chat-header">
            <div class="agent-profile">
                <div class="position-relative">
                    <img src="<?= $agentAvatar ?>" alt="Agent" class="agent-avatar">
                    <div class="status-indicator"></div>
                </div>
                <div class="agent-info">
                    <h3><?= htmlspecialchars($agentName) ?></h3>
                    <span><?= htmlspecialchars($agentRole) ?></span>
                </div>
            </div>
            <button class="chat-close-btn" aria-label="Close Chat">
                <svg viewBox="0 0 24 24" width="20" height="20">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" fill="white"></path>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="chat-body">
            <!-- Initial Greeting -->
            <div class="message-row">
                <div class="bot-avatar-small">
                     <img src="<?= $agentAvatar ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                </div>
                <div class="message-content">
                    <div class="gif-container">
                        <!-- Using the same handwave gif from the example or a placeholder -->
                        <img src="https://cdn.livechat-static.com/api/file/lc/img/rich-greetings/handwave.gif" alt="Wave">
                    </div>
                    <div class="welcome-text">
                        <strong>Hi! Let us know if you have any questions.</strong>
                    </div>
                </div>
            </div>

            <!-- Options -->
            <div class="chat-options">
                <button class="chat-option-btn">Chat now</button>
                <button class="chat-option-btn">Just browsing</button>
            </div>
        </div>

        <!-- Footer -->
        <div class="chat-footer">
            <div class="chat-input-group">
                <input type="text" class="chat-input" placeholder="Type a message...">
                <button class="send-btn">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
            <button class="end-conversation-btn" id="endConversationBtn">
                <i class="bi bi-stop-circle"></i> End Conversation
            </button>
        </div>
    </div>

    <!-- Launcher Button -->
    <button class="chat-launcher" aria-label="Toggle Chat">
        <!-- Open Icon (Comment Dots) -->
        <svg class="chat-launcher-icon chat-open-icon" viewBox="0 0 24 24" style="width: 28px; height: 28px;">
            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" fill="white"></path>
        </svg>
        
        <!-- Close Icon (X) -->
        <svg class="chat-launcher-icon chat-close-icon" viewBox="0 0 24 24" style="width: 28px; height: 28px;">
            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" fill="white"></path>
        </svg>
    </button>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function setupLazyChatLoader() {
    const launcher = document.querySelector('.chat-launcher');
    if (!launcher) return;

    let chatLoaded = false;

    function loadChatScriptAndTriggerClick(event) {
        if (chatLoaded) return;
        chatLoaded = true;

        if (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }

        const script = document.createElement('script');
        script.src = "<?= app_url('public/chat/chat.js') ?>";
        script.onload = function () {
            launcher.removeEventListener('click', loadChatScriptAndTriggerClick, true);
            // Trigger the click again so the real chat handler (from chat.js) runs
            launcher.click();
        };
        document.body.appendChild(script);
    }

    launcher.addEventListener('click', loadChatScriptAndTriggerClick, true);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupLazyChatLoader);
} else {
    setupLazyChatLoader();
}
</script>
