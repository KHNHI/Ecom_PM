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
        /* Chat page specific styles */
        .chat-admin-container {
            display: flex;
            height: calc(100vh - 200px);
            width: 100%;
            gap: 0;
            background: white;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .chat-sidebar {
            width: 350px;
            background: white;
            border-right: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 8px rgba(0,0,0,0.05);
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
            color: #495057;
            font-weight: 500;
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
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
        }
        
        .conversation-item:hover {
            background: #f8f9fa;
        }
        
        .conv-name {
            font-weight: 600;
            font-size: 15px;
            color: #212529;
        }
        
        .conv-time {
            font-size: 12px;
            color: #6c757d;
        }
        
        .conv-preview {
            font-size: 14px;
            color: #495057;
            margin-top: 4px;
        }
        
        .btn-delete-conv {
            background: #ff4757;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
            opacity: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        
        .conversation-item:hover .btn-delete-conv {
            opacity: 1;
        }
        
        .btn-delete-conv:hover {
            background: #ee5a6f;
            transform: scale(1.05);
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
            align-items: start;
            margin-bottom: 8px;
        }
        
        .conv-name {
            font-weight: 600;
            font-size: 14px;
            color: #212529;
        }
        
        .conv-time {
            font-size: 12px;
            color: #868e96;
        }
        
        .conv-preview {
            font-size: 13px;
            color: #495057;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .conv-badge {
            position: absolute;
            top: 16px;
            right: 20px;
            background: #ff4757;
            color: white;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }
        
        .chat-main-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-customer-info h3 {
            margin: 0 0 4px 0;
            font-size: 18px;
        }
        
        .chat-customer-meta {
            font-size: 13px;
            color: #868e96;
        }
        
        .chat-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            padding: 8px 16px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        .btn-action:hover {
            background: #f8f9fa;
        }
        
        .btn-action.danger {
            color: #ff4757;
            border-color: #ff4757;
        }
        
        .btn-action.danger:hover {
            background: #fff5f5;
        }
        
        .chat-messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .admin-chat-message {
            display: flex;
            margin-bottom: 16px;
            animation: fadeIn 0.3s ease-in;
        }
        
        .admin-chat-message.customer {
            justify-content: flex-start;
        }
        
        .admin-chat-message.admin {
            justify-content: flex-end;
        }
        
        .admin-message-bubble {
            max-width: 60%;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .admin-chat-message.customer .admin-message-bubble {
            background: white;
            color: #212529;
            border-bottom-left-radius: 4px;
        }
        
        .admin-chat-message.admin .admin-message-bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .admin-message-sender {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 4px;
            opacity: 0.8;
        }
        
        .admin-message-time {
            font-size: 11px;
            opacity: 0.7;
            margin-top: 4px;
        }
        
        .chat-input-area {
            padding: 20px;
            border-top: 1px solid #dee2e6;
            background: white;
        }
        
        .quick-replies {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
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
        }
        
        .chat-input-container {
            display: flex;
            gap: 12px;
        }
        
        .chat-textarea {
            flex: 1;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            resize: none;
            outline: none;
            transition: border-color 0.2s;
            min-height: 80px;
        }
        
        .chat-textarea:focus {
            border-color: #667eea;
        }
        
        .btn-send {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-send:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 40px;
            text-align: center;
        }
        
        .empty-state-icon {
            color: #adb5bd;
            margin-bottom: 24px;
            opacity: 0.6;
        }
        
        .empty-state-title {
            font-size: 20px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 12px;
        }
        
        .empty-state-text {
            font-size: 15px;
            color: #868e96;
            line-height: 1.6;
            max-width: 400px;
        }
    </style>
</head>
<body>
    <?php include dirname(__FILE__) . '/../components/header.html'; ?>
    
    <?php include dirname(__FILE__) . '/../components/sidebar.html'; ?>
    
    <div class="main-content">
        <main class="content">
            <div class="chat-admin-container">
        <!-- Sidebar with conversation list -->
        <div class="chat-sidebar">
            <div class="chat-sidebar-header">
                <h2>Tin nhắn</h2>
                <div class="chat-filters">
                    <button class="filter-btn <?= !$status ? 'active' : '' ?>" onclick="filterConversations('all')">
                        Tất cả
                    </button>
                    <button class="filter-btn <?= $status === 'pending' ? 'active' : '' ?>" onclick="filterConversations('pending')">
                        Chờ xử lý
                    </button>
                    <button class="filter-btn <?= $status === 'active' ? 'active' : '' ?>" onclick="filterConversations('active')">
                        Đang chat
                    </button>
                </div>
            </div>
            
            <div class="conversation-list" id="conversation-list">
                <?php if (empty($conversations)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">💬</div>
                        <div class="empty-state-text">Chưa có tin nhắn</div>
                    </div>
                <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                    <div class="conversation-item <?= $conv['unread_admin_count'] > 0 ? 'unread' : '' ?>">
                        <div onclick="viewConversation(<?= $conv['conversation_id'] ?>)" style="flex: 1; cursor: pointer;">
                            <div class="conv-header">
                                <div class="conv-name">
                                    <?= htmlspecialchars($conv['user_name'] ?? $conv['customer_name'] ?? 'Khách') ?>
                                </div>
                                <div class="conv-time">
                                    <?= date('H:i', strtotime($conv['last_message_at'])) ?>
                                </div>
                            </div>
                            <div class="conv-preview">
                                <?= htmlspecialchars($conv['last_message'] ?? 'Chưa có tin nhắn') ?>
                            </div>
                            <?php if ($conv['unread_admin_count'] > 0): ?>
                                <span class="conv-badge"><?= $conv['unread_admin_count'] ?></span>
                            <?php endif; ?>
                        </div>
                        <button class="btn-delete-conv" onclick="event.stopPropagation(); deleteConversation(<?= $conv['conversation_id'] ?>)" title="Xóa cuộc hội thoại">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main chat area -->
        <div class="chat-main">
            <div class="empty-state">
                <svg class="empty-state-icon" width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    <path d="M8 10h.01M12 10h.01M16 10h.01"></path>
                </svg>
                <div class="empty-state-title">Chào mừng đến với Chat Admin</div>
                <div class="empty-state-text">Chọn một cuộc hội thoại bên trái để bắt đầu trò chuyện với khách hàng</div>
            </div>
        </div>
            </div>
            </main>
        </div>
    
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
    
    <script>
        function filterConversations(status) {
            const url = status === 'all' 
                ? '/Ecom_PM/admin/index.php?url=chat' 
                : `/Ecom_PM/admin/index.php?url=chat&status=${status}`;
            window.location.href = url;
        }
        
        function viewConversation(id) {
            window.location.href = `/Ecom_PM/admin/index.php?url=chat/view/${id}`;
        }
        
        async function deleteConversation(id) {
            if (!confirm('Bạn có chắc muốn xóa cuộc hội thoại này? Tất cả tin nhắn sẽ bị xóa vĩnh viễn!')) {
                return;
            }
            
            try {
                const response = await fetch('/Ecom_PM/admin/index.php?url=chat/delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ conversation_id: id })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Xóa thành công!');
                    location.reload();
                } else {
                    alert('Lỗi: ' + (result.message || 'Không thể xóa'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Không thể xóa cuộc hội thoại');
            }
        }
        
        // Auto refresh conversation list every 10 seconds
        setInterval(() => {
            location.reload();
        }, 10000);
    </script>
</body>
</html>
