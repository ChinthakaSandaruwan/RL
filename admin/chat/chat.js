document.addEventListener('DOMContentLoaded', function () {
    const replyForm = document.getElementById('replyForm');
    const messageInput = document.getElementById('messageInput');
    const chatMessages = document.getElementById('chatMessages');
    const conversationId = document.getElementById('conversationId')?.value;

    // Delete Conversation - Always active (works even without conversation selected)
    document.addEventListener('click', function (e) {
        if (e.target.closest('.delete-conversation-btn')) {
            e.preventDefault();
            e.stopPropagation();

            const btn = e.target.closest('.delete-conversation-btn');
            const convId = btn.getAttribute('data-conversation-id');

            console.log('Delete button clicked, conversation ID:', convId);

            if (convId) {
                // Show SweetAlert confirmation
                Swal.fire({
                    title: 'Delete Conversation?',
                    text: "This will permanently delete the conversation and all its messages. This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch('api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `action=delete&conversation_id=${convId}`
                        })
                            .then(res => res.json())
                            .then(data => {
                                console.log('Delete response:', data);
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: 'Conversation has been deleted.',
                                        timer: 1500,
                                        showConfirmButton: false
                                    }).then(() => {
                                        window.location.href = 'index.php';
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Failed to delete: ' + (data.message || 'Unknown error')
                                    });
                                }
                            })
                            .catch(err => {
                                console.error('Delete error:', err);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'An error occurred. Check console for details.'
                                });
                            });
                    }
                });
            } else {
                console.error('No conversation ID found on button');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No conversation ID found'
                });
            }
        }
    });

    // Load Conversations via AJAX
    function loadConversations() {
        fetch('api.php?action=list_conversations')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.conversations) {
                    const container = document.getElementById('conversationsList');
                    const selectedId = new URLSearchParams(window.location.search).get('conversation_id');

                    if (data.conversations.length === 0) {
                        container.innerHTML = '<div class="list-group-item text-center text-muted py-4">No conversations yet</div>';
                    } else {
                        container.innerHTML = data.conversations.map(conv => `
                            <a href="?conversation_id=${conv.conversation_id}" 
                               class="list-group-item list-group-item-action ${selectedId == conv.conversation_id ? 'active' : ''}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">${escapeHtml(conv.name)}</h6>
                                        <small class="text-muted">${escapeHtml(conv.email)}</small>
                                        <br>
                                        <small class="text-muted">${formatDate(conv.updated_at)}</small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        ${conv.unread_count > 0 ? `<span class="badge bg-danger">${conv.unread_count}</span>` : ''}
                                        <button class="btn btn-sm btn-outline-danger delete-conversation-btn" 
                                                data-conversation-id="${conv.conversation_id}"
                                                type="button"
                                                title="Delete conversation">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </a>
                        `).join('');
                    }
                }
            })
            .catch(err => console.error('Load conversations error:', err));
    }

    function formatDate(dateStr) {
        const date = new Date(dateStr);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const month = months[date.getMonth()];
        const day = date.getDate();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${month} ${day}, ${hours}:${minutes}`;
    }

    // Initial load
    loadConversations();

    // Refresh conversations every 5 seconds
    setInterval(loadConversations, 5000);

    // Exit early if no conversation is selected (rest of handlers need conversationId)
    if (!conversationId) return;

    let lastMessageId = 0;

    // Send Reply
    if (replyForm) {
        replyForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const message = messageInput.value.trim();
            if (!message) return;

            fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=send&conversation_id=${conversationId}&message=${encodeURIComponent(message)}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        messageInput.value = '';
                        addAdminMessage(message);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed to send',
                            text: data.message || 'Failed to send message'
                        });
                    }
                })
                .catch(err => {
                    console.error('Send error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while sending the message. Please try again.'
                    });
                });
        });
    }

    // Close Conversation
    const closeConvBtn = document.getElementById('closeConversationBtn');
    if (closeConvBtn) {
        closeConvBtn.addEventListener('click', function () {
            Swal.fire({
                title: 'End Conversation?',
                text: 'Are you sure you want to end this conversation? The customer will be able to start a new one.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, end it',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                console.log('Ending conversation, sending message first...');
                const endMessage = 'This conversation has been ended by our support team. Thank you for contacting us. Feel free to start a new conversation if you need further assistance.';

                // First, send automatic message
                fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=send&conversation_id=${conversationId}&message=${encodeURIComponent(endMessage)}`
                })
                    .then(res => res.json())
                    .then(data => {
                        console.log('Send message response:', data);
                        if (data.success) {
                            console.log('Message sent successfully, waiting 2 seconds for customer to receive...');
                            // Wait 2 seconds to allow customer to poll and receive the message
                            return new Promise(resolve => {
                                setTimeout(() => {
                                    console.log('Now closing conversation...');
                                    resolve();
                                }, 2000);
                            });
                        } else {
                            console.error('Failed to send message:', data.message);
                            throw new Error('Failed to send message: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .then(() => {
                        // Now close the conversation
                        return fetch('api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `action=close&conversation_id=${conversationId}`
                        });
                    })
                    .then(res => res.json())
                    .then(data => {
                        console.log('Close conversation response:', data);
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Conversation Ended',
                                text: 'The conversation has been ended successfully. Customer can start a new conversation.',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = 'index.php';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed',
                                text: 'Failed to end conversation: ' + (data.message || 'Unknown error')
                            });
                        }
                    })
                    .catch(err => {
                        console.error('Close error:', err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error ending conversation: ' + err.message
                        });
                    });
            });
        });
    }

    // Poll for new messages
    setInterval(fetchNewMessages, 3000);

    function fetchNewMessages() {
        // Get all current message IDs
        const messages = chatMessages.querySelectorAll('[data-message-id]');
        if (messages.length > 0) {
            lastMessageId = Math.max(...Array.from(messages).map(m => parseInt(m.dataset.messageId)));
        }

        fetch(`api.php?action=fetch&conversation_id=${conversationId}&last_id=${lastMessageId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.messages) {
                    data.messages.forEach(msg => {
                        if (msg.sender_type === 'user') {
                            addUserMessage(msg.message, msg.message_id, msg.created_at);
                        }
                    });
                }
            })
            .catch(err => console.error('Fetch error:', err));
    }

    function addAdminMessage(text) {
        const msgDiv = document.createElement('div');
        msgDiv.classList.add('mb-3', 'text-end');
        msgDiv.innerHTML = `
            <div class="d-inline-block" style="max-width: 70%;">
                <div class="p-2 rounded bg-primary text-white">
                    ${escapeHtml(text)}
                </div>
                <small class="text-muted">${new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</small>
            </div>
        `;
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function addUserMessage(text, id, time) {
        const msgDiv = document.createElement('div');
        msgDiv.classList.add('mb-3');
        msgDiv.setAttribute('data-message-id', id);
        msgDiv.innerHTML = `
            <div class="d-inline-block" style="max-width: 70%;">
                <div class="p-2 rounded bg-light">
                    ${escapeHtml(text)}
                </div>
                <small class="text-muted">${new Date(time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</small>
            </div>
        `;
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
