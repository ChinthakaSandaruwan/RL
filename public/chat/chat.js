document.addEventListener('DOMContentLoaded', function () {
    const launcher = document.querySelector('.chat-launcher');
    const chatWindow = document.querySelector('.chat-window');
    const chatInput = document.querySelector('.chat-input');
    const sendBtn = document.querySelector('.send-btn');
    const chatBody = document.querySelector('.chat-body');

    if (!launcher || !chatWindow) return;

    let lastMessageId = 0;
    let pollInterval;
    let conversationId = null; // Track the conversation ID

    // Toggle Chat
    launcher.addEventListener('click', function () {
        this.classList.toggle('active');
        chatWindow.classList.toggle('active');

        const isExpanded = this.classList.contains('active');
        this.setAttribute('aria-expanded', isExpanded);

        if (isExpanded) {
            fetchMessages(); // Load conversation history first
            startPolling();
        } else {
            stopPolling();
        }
    });

    // Close button in header
    const closeBtn = document.querySelector('.chat-close-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            launcher.classList.remove('active');
            chatWindow.classList.remove('active');
            launcher.setAttribute('aria-expanded', 'false');
            stopPolling();
        });
    }

    // Option Buttons
    const optionBtns = document.querySelectorAll('.chat-option-btn');
    optionBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const action = this.textContent.trim();
            if (action === 'Chat now') {
                chatInput.focus();
            } else if (action === 'Just browsing') {
                document.querySelector('.chat-options')?.remove();
            }
        });
    });

    // Send Message
    sendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    // End Conversation
    const endConvBtn = document.getElementById('endConversationBtn');
    if (endConvBtn) {
        endConvBtn.addEventListener('click', function () {
            if (!conversationId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Active Chat',
                    text: 'There is no active conversation to end.',
                    confirmButtonColor: '#3a5a40'
                });
                return;
            }

            // First, send "Chat ended" message
            fetch('public/chat/api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=send&message=${encodeURIComponent('Chat ended')}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Then end the conversation
                        return fetch('public/chat/api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `action=end&conversation_id=${conversationId}`
                        });
                    } else {
                        throw new Error('Failed to send message');
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showConversationEnded();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed to End Conversation',
                            text: data.message || 'An unknown error occurred. Please try again.',
                            confirmButtonColor: '#3a5a40'
                        });
                    }
                })
                .catch(err => {
                    console.error('End error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Could not connect to server. Please check your connection and try again.',
                        confirmButtonColor: '#3a5a40'
                    });
                });
        });
    }

    function sendMessage() {
        const message = chatInput.value.trim();
        if (!message) return;

        chatInput.value = '';

        fetch('public/chat/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=send&message=${encodeURIComponent(message)}`
        })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    console.error('Failed to send:', data.message);
                } else if (data.conversation_id) {
                    conversationId = data.conversation_id;
                    // Immediately fetch to show the sent message
                    fetchMessages();
                }
            })
            .catch(err => console.error('Send error:', err));
    }

    function fetchMessages() {
        fetch(`public/chat/api.php?action=fetch&last_id=${lastMessageId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Store conversation ID
                    if (data.conversation_id) {
                        conversationId = data.conversation_id;
                    }

                    // Process and display new messages FIRST (including final admin message)
                    if (data.messages && data.messages.length > 0) {
                        // Hide initial greeting if we have conversation history
                        const greeting = chatBody.querySelector('.message-row:first-child');
                        const options = document.querySelector('.chat-options');
                        if (lastMessageId === 0 && greeting) {
                            greeting.style.display = 'none';
                        }
                        if (lastMessageId === 0 && options) {
                            options.style.display = 'none';
                        }

                        data.messages.forEach(msg => {
                            if (msg.sender_type === 'admin') {
                                addBotMessage(msg.message);
                            } else if (msg.sender_type === 'user') {
                                addUserMessage(msg.message);
                            }
                            lastMessageId = Math.max(lastMessageId, msg.message_id);
                        });
                    }

                    // THEN check if conversation is closed (after messages are displayed)
                    if (data.status === 'closed') {
                        showConversationEnded();
                        return;
                    }
                }
            })
            .catch(err => console.error('Fetch error:', err));
    }

    function showConversationEnded() {
        // Hide options if visible
        const options = document.querySelector('.chat-options');
        if (options) options.remove();

        // Show conversation ended message
        const existingEndMsg = document.querySelector('.conversation-ended');
        if (!existingEndMsg) {
            const msgRow = document.createElement('div');
            msgRow.classList.add('message-row', 'conversation-ended');
            msgRow.innerHTML = `
                <div class="bot-avatar-small">
                     <img src="${chatWindow.dataset.avatar}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                </div>
                <div class="message-content">
                    <div class="welcome-text"><strong>This conversation has ended.</strong></div>
                    <button class="chat-option-btn" id="startNewConversation" style="margin-top: 8px;">
                        Start New Conversation
                    </button>
                </div>
            `;
            chatBody.appendChild(msgRow);
            chatBody.scrollTop = chatBody.scrollHeight;

            // Add event listener for new conversation button
            document.getElementById('startNewConversation')?.addEventListener('click', startNewConversation);
        }

        // Disable input
        chatInput.disabled = true;
        sendBtn.disabled = true;
        const endBtn = document.getElementById('endConversationBtn');
        if (endBtn) endBtn.disabled = true;
    }

    function startNewConversation() {
        // Remove ended message
        document.querySelector('.conversation-ended')?.remove();

        // Clear chat
        const messages = chatBody.querySelectorAll('.message-row:not(:first-child)');
        messages.forEach(msg => msg.remove());

        // Reset
        lastMessageId = 0;
        conversationId = null;
        chatInput.disabled = false;
        chatInput.removeAttribute('disabled');
        sendBtn.disabled = false;
        sendBtn.removeAttribute('disabled');
        const endBtn = document.getElementById('endConversationBtn');
        if (endBtn) {
            endBtn.disabled = false;
            endBtn.removeAttribute('disabled');
        }
        chatInput.focus();

        // Add welcome back message
        addBotMessage('Hello again! How can I help you today?');
    }

    function startPolling() {
        // Poll messages every 3 seconds
        pollInterval = setInterval(fetchMessages, 3000);
    }

    function stopPolling() {
        clearInterval(pollInterval);
    }

    function addUserMessage(text) {
        const msgRow = document.createElement('div');
        msgRow.classList.add('message-row', 'user-message');
        msgRow.innerHTML = `
            <div class="message-content user-bubble" style="margin-left: auto; background: var(--hunter-green, #3a5a40); color: white; border-top-right-radius: 2px; border-top-left-radius: 12px;">
                <div class="welcome-text" style="color: white;">${escapeHtml(text)}</div>
            </div>
        `;
        chatBody.appendChild(msgRow);
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    function addBotMessage(text) {
        const msgRow = document.createElement('div');
        msgRow.classList.add('message-row');
        msgRow.innerHTML = `
            <div class="bot-avatar-small">
                 <img src="${chatWindow.dataset.avatar}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
            </div>
            <div class="message-content">
                <div class="welcome-text">${escapeHtml(text)}</div>
            </div>
        `;
        chatBody.appendChild(msgRow);
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
