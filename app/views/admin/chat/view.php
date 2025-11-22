<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Quản lý Chat' ?> - Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/Ecom_PM/app/views/admin/assets/css/variables.css">
    <link rel="stylesheet" href="/Ecom_PM/app/views/admin/assets/css/main.css">
    
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        
        .chat-admin-container {
            display: flex;
            height: calc(100vh - 120px);
            gap: 0;
            background: #f8f9fa;
        }
        
        .chat-sidebar {
            width: 350px;
            background: white;
            border-right: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
        }
        
        .chat-sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            background: white;
        }
        
        .chat-sidebar-header h2 {
            margin: 0 0 16px 0;
            font-size: 20px;
            color: #212529;
            font-weight: 700;
        }
        
        .chat-filters {
            display: flex;
            gap: 8px;
        }
        
        .filter-btn {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        .filter-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .conversation-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .conversation-item {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f3f5;
            transition: background 0.2s;
            position: relative;
            background: white;
            cursor: pointer;
        }
        
        .conversation-item:hover {
            background: #f8f9fa;
        }
        
        .conversation-item.active {
            background: #e7f3ff;
            border-left: 3px solid #667eea;
        }
        
        .conv-user-name {
            font-weight: 600;
            font-size: 15px;
            color: #212529;
        }
        
        .conv-time {
            font-size: 12px;
            color: #6c757d;
        }
        
        .conv-last-message {
            font-size: 14px;
            color: #495057;
            margin-top: 4px;
        }
        
        .conversation-item:hover {
            background: #f8f9fa;
        }
        
        .conversation-item.active {
            background: #e7f3ff;
            border-left: 3px solid #667eea;
        }
        
        .conversation-item.unread {
            background: #fff4e6;
        }
        
        .conv-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .conv-user-name {
            font-weight: 600;
            font-size: 15px;
            color: #212529;
        }
        
        .conv-time {
            font-size: 12px;
            color: #868e96;
        }
        
        .conv-last-message {
            font-size: 14px;
            color: #495057;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .conv-status {
            display: inline-block;
            padding: 3px 8px;
            font-size: 11px;
            border-radius: 4px;
            margin-top: 8px;
        }
        
        .conv-status.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .conv-status.active {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .conv-status.closed {
            background: #d4edda;
            color: #155724;
        }
        
        .unread-badge {
            position: absolute;
            top: 16px;
            right: 20px;
            background: #ff6b6b;
            color: white;
            font-size: 11px;
            padding: 3px 7px;
            border-radius: 12px;
            font-weight: 600;
        }
        
        .chat-view-container {
            flex: 1;
            background: white;
            display: flex;
            flex-direction: column;
        }
        
        .chat-view-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
        }
        
        .chat-customer-info h2 {
            margin: 0 0 8px 0;
            font-size: 20px;
            color: #212529;
            font-weight: 700;
        }
        
        .chat-customer-meta {
            font-size: 14px;
            color: #6c757d;
        }
        
        .chat-view-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn {
            padding: 10px 20px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            color: #212529;
            transition: all 0.2s;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            background: #f8f9fa;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-danger {
            background: #ff4757 !important;
            color: white !important;
            border-color: #ff4757 !important;
            font-weight: 600;
        }
        
        .btn-danger:hover {
            background: #ee5a6f !important;
            border-color: #ee5a6f !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 71, 87, 0.3);
        }
        
        .chat-view-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .chat-view-input {
            padding: 20px;
            border-top: 1px solid #dee2e6;
            background: white;
        }
        
        .quick-replies-section {
            margin-bottom: 16px;
        }
        
        .quick-replies-label {
            font-size: 12px;
            font-weight: 600;
            color: #868e96;
            margin-bottom: 8px;
        }
        
        .quick-replies-grid {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .quick-reply-btn {
            padding: 6px 12px;
            background: #f1f3f5;
            border: 1px solid #dee2e6;
            border-radius: 16px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .quick-reply-btn:hover {
            background: #e9ecef;
            transform: translateY(-1px);
        }
        
        .input-group {
            display: flex;
            gap: 12px;
        }
        
        .message-textarea {
            flex: 1;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            min-height: 80px;
            outline: none;
        }
        
        .message-textarea:focus {
            border-color: #667eea;
        }
        
        .btn-send {
            padding: 12px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            align-self: flex-end;
        }
        
        .btn-send:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-send:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include dirname(__FILE__) . '/../components/header.html'; ?>
    
    <?php include dirname(__FILE__) . '/../components/sidebar.html'; ?>
    
    <div class="main-content">
        <main class="content">
            <div class="chat-admin-container">
                <!-- Sidebar với danh sách conversations -->
                <div class="chat-sidebar">
                    <div class="chat-sidebar-header">
                        <h2>Tin nhắn</h2>
                        <div class="chat-filters">
                            <button class="filter-btn" data-status="">Tất cả</button>
                            <button class="filter-btn" data-status="pending">Chờ xử lý</button>
                            <button class="filter-btn" data-status="active">Đang chat</button>
                        </div>
                    </div>
                    
                    <div class="conversation-list" id="conversation-list">
                        <!-- Conversations will be loaded here -->
                    </div>
                </div>
                
                <!-- Main chat view -->
                <div class="chat-view-container">
                    <div class="chat-view-header">
                        <div class="chat-customer-info">
                            <h2><?= htmlspecialchars($conversation['user_name'] ?? $conversation['customer_name'] ?? 'Khách') ?></h2>
                            <div class="chat-customer-meta">
                                <?php if ($conversation['user_email']): ?>
                                    📧 <?= htmlspecialchars($conversation['user_email']) ?>
                                <?php endif; ?>
                                <?php if ($conversation['user_phone']): ?>
                                    | 📞 <?= htmlspecialchars($conversation['user_phone']) ?>
                                <?php endif; ?>
                                | Trạng thái: <strong><?= $conversation['status'] === 'active' ? 'Đang chat' : ($conversation['status'] === 'pending' ? 'Chờ xử lý' : 'Đã đóng') ?></strong>
                            </div>
                        </div>
                        
                        <div class="chat-view-actions">
                            <?php if ($conversation['status'] !== 'closed'): ?>
                                <button class="btn btn-danger" onclick="closeConversation()">Đóng chat</button>
                            <?php else: ?>
                                <button class="btn btn-primary" onclick="reopenConversation()">Mở lại</button>
                            <?php endif; ?>
                            <button class="btn btn-danger" onclick="deleteCurrentConversation()" style="background: #ff4757; border-color: #ff4757;">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </div>
                    </div>
        
        <div class="chat-view-messages" id="messages-container">
            <?php if (empty($messages)): ?>
                <div class="chatbox-empty">
                    <div class="chatbox-empty-icon">💬</div>
                    <div class="chatbox-empty-text">Chưa có tin nhắn</div>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="chat-message <?= htmlspecialchars($msg['sender_type']) ?>">
                        <div class="message-bubble">
                            <?php if ($msg['sender_type'] !== 'customer'): ?>
                                <div class="message-sender"><?= htmlspecialchars($msg['sender_name'] ?? 'Admin') ?></div>
                            <?php endif; ?>
                            <div class="message-text"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                            <div class="message-time"><?= date('H:i d/m/Y', strtotime($msg['created_at'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="chat-view-input">
            <div class="quick-replies-section">
                <div class="quick-replies-label">⚡ Câu trả lời nhanh:</div>
                <div class="quick-replies-grid" id="quick-replies">
                    <!-- Quick replies will be loaded here -->
                </div>
            </div>
            
            <div class="input-group">
                <textarea 
                    class="message-textarea" 
                    id="message-input" 
                    placeholder="Nhập tin nhắn..."
                    onkeydown="handleKeyPress(event)"
                ></textarea>
                <button class="btn-send" id="send-btn" onclick="sendMessage()" disabled>
                    Gửi →
                </button>
            </div>
        </div>
    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        const conversationId = <?= $conversation['conversation_id'] ?>;
        const currentConversationId = conversationId;
        let lastMessageId = <?= !empty($messages) ? end($messages)['message_id'] : 0 ?>;
        let pollInterval;
        let currentFilter = '';
        
        // Load conversations for sidebar
        async function loadConversations(status = '') {
            try {
                const url = `/Ecom_PM/admin/index.php?url=chat/conversations${status ? '&status=' + status : ''}`;
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success && result.data) {
                    renderConversations(result.data.conversations);
                }
            } catch (error) {
                console.error('Error loading conversations:', error);
            }
        }
        
        // Render conversations in sidebar
        function renderConversations(conversations) {
            const list = document.getElementById('conversation-list');
            
            if (!conversations || conversations.length === 0) {
                list.innerHTML = '<div style="padding: 20px; text-align: center; color: #868e96;">Không có tin nhắn</div>';
                return;
            }
            
            list.innerHTML = conversations.map(conv => {
                const isActive = conv.conversation_id == currentConversationId;
                const unread = conv.unread_count > 0;
                const statusClass = conv.status;
                const statusText = conv.status === 'active' ? 'Đang chat' : (conv.status === 'pending' ? 'Chờ xử lý' : 'Đã đóng');
                const time = new Date(conv.last_message_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                
                return `
                    <div class="conversation-item ${isActive ? 'active' : ''} ${unread ? 'unread' : ''}" 
                         onclick="window.location.href='/Ecom_PM/admin/index.php?url=chat/view/${conv.conversation_id}'">
                        <div class="conv-header">
                            <span class="conv-user-name">${conv.user_name || conv.customer_name || 'Khách'}</span>
                            <span class="conv-time">${time}</span>
                        </div>
                        <div class="conv-last-message">${conv.last_message || 'Chưa có tin nhắn'}</div>
                        <span class="conv-status ${statusClass}">${statusText}</span>
                        ${unread ? `<span class="unread-badge">${conv.unread_count}</span>` : ''}
                    </div>
                `;
            }).join('');
        }
        
        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentFilter = this.dataset.status;
                loadConversations(currentFilter);
            });
        });
        
        // Set active filter (all by default)
        document.querySelector('.filter-btn[data-status=""]').classList.add('active');
        
        // Load conversations on page load
        loadConversations();
        
        // Enable/disable send button
        document.getElementById('message-input').addEventListener('input', function() {
            document.getElementById('send-btn').disabled = this.value.trim() === '';
        });
        
        // Handle Enter key
        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }
        
        // Send message
        async function sendMessage() {
            const input = document.getElementById('message-input');
            const message = input.value.trim();
            
            if (!message) return;
            
            try {
                const response = await fetch('/Ecom_PM/admin/index.php?url=chat/send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        conversation_id: conversationId,
                        message: message
                    })
                });
                
                const result = await response.json();
                
                if (result.success && result.data) {
                    input.value = '';
                    document.getElementById('send-btn').disabled = true;
                    addMessage(result.data);
                    lastMessageId = result.data.message_id;
                    // Reload conversations to update last message
                    loadConversations(currentFilter);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Không thể gửi tin nhắn');
            }
        }
        
        // Add message to UI
        function addMessage(msg) {
            const container = document.getElementById('messages-container');
            const empty = container.querySelector('.chatbox-empty');
            if (empty) empty.remove();
            
            const div = document.createElement('div');
            div.className = `chat-message ${msg.sender_type}`;
            div.innerHTML = `
                <div class="message-bubble">
                    ${msg.sender_type !== 'customer' ? `<div class="message-sender">${msg.sender_name || 'Admin'}</div>` : ''}
                    <div class="message-text">${msg.message.replace(/\n/g, '<br>')}</div>
                    <div class="message-time">${new Date().toLocaleTimeString('vi-VN')}</div>
                </div>
            `;
            
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }
        
        // Load quick replies
        async function loadQuickReplies() {
            try {
                const response = await fetch('/Ecom_PM/admin/index.php?url=chat/quick-replies');
                const result = await response.json();
                
                if (result.success && result.data) {
                    const container = document.getElementById('quick-replies');
                    container.innerHTML = result.data.map(r => 
                        `<button class="quick-reply-btn" onclick="useQuickReply('${r.message.replace(/'/g, "\\'")}')">${r.title}</button>`
                    ).join('');
                }
            } catch (error) {
                console.error('Error loading quick replies:', error);
            }
        }
        
        // Use quick reply
        function useQuickReply(message) {
            document.getElementById('message-input').value = message;
            document.getElementById('send-btn').disabled = false;
            document.getElementById('message-input').focus();
        }
        
        // Check for new messages
        async function checkNewMessages() {
            try {
                const response = await fetch(`/Ecom_PM/admin/index.php?url=chat/new-messages&conversation_id=${conversationId}&after_id=${lastMessageId}`);
                const result = await response.json();
                
                if (result.success && result.data && result.data.length > 0) {
                    result.data.forEach(msg => {
                        addMessage(msg);
                        lastMessageId = msg.message_id;
                    });
                    // Reload conversations to update unread count
                    loadConversations(currentFilter);
                }
            } catch (error) {
                console.error('Error checking messages:', error);
            }
        }
        
        // Update conversation status
        async function updateStatus(status) {
            try {
                const response = await fetch('/Ecom_PM/admin/index.php?url=chat/update-status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ conversation_id: conversationId, status })
                });
                
                const result = await response.json();
                if (result.success) {
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        function closeConversation() {
            if (confirm('Bạn có chắc muốn đóng cuộc hội thoại này?')) {
                updateStatus('closed');
            }
        }
        
        function reopenConversation() {
            updateStatus('active');
        }
        
        async function deleteCurrentConversation() {
            if (!confirm('Bạn có chắc muốn xóa cuộc hội thoại này? Tất cả tin nhắn sẽ bị xóa vĩnh viễn!')) {
                return;
            }
            
            try {
                const response = await fetch('/Ecom_PM/admin/index.php?url=chat/delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ conversation_id: conversationId })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Xóa thành công!');
                    window.location.href = '/Ecom_PM/admin/index.php?url=chat';
                } else {
                    alert('Lỗi: ' + (result.message || 'Không thể xóa'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Không thể xóa cuộc hội thoại');
            }
        }
        
        // Initialize
        loadQuickReplies();
        document.getElementById('messages-container').scrollTop = document.getElementById('messages-container').scrollHeight;
        
        // Poll for new messages every 3 seconds
        pollInterval = setInterval(checkNewMessages, 3000);
        
        // Reload conversations every 10 seconds
        setInterval(() => loadConversations(currentFilter), 10000);
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Component Manager -->
    <script src="/Ecom_PM/app/views/admin/components/component-manager.js"></script>
    
    <!-- Main JS -->
    <script src="/Ecom_PM/app/views/admin/assets/js/main.js"></script>
    
    <script>
        // Set active sidebar link
        document.addEventListener('DOMContentLoaded', function() {
            const chatLink = document.querySelector('.sidebar-nav-link[data-page="chat"]');
            if (chatLink) {
                chatLink.classList.add('active');
            }
        });
    </script>
</body>
</html>
