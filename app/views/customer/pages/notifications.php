<style>
    :root {
        --gold: #d4af37;
        --dark-gold: #b8941f;
        --light-gold: #f0e68c;
        --cream: #f8f6f0;
        --dark-brown: #3a2f28;
        --light-gray: #f5f5f5;
    }

    body {
        padding-top: 120px;
    }

    .notifications-container {
        background: white;
        border-radius: 15px;
        padding: 40px;
        margin-bottom: 40px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .notifications-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        border-bottom: 2px solid var(--light-gray);
        padding-bottom: 20px;
    }

    .notifications-header h2 {
        color: var(--dark-brown);
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        margin: 0;
    }

    .notification-item {
        padding: 15px;
        border-left: 4px solid var(--gold);
        background: var(--cream);
        margin-bottom: 15px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .notification-item:hover {
        box-shadow: 0 3px 10px rgba(212, 175, 55, 0.2);
        transform: translateX(5px);
    }

    .notification-item.read {
        border-left-color: var(--light-gray);
        background: white;
        opacity: 0.8;
    }

    .notification-item.unread {
        border-left-color: var(--dark-gold);
        background: var(--light-gold);
        opacity: 1;
    }

    .notification-title {
        color: var(--dark-brown);
        font-size: 1.1rem;
        font-weight: 600;
        display: block;
        margin-bottom: 8px;
    }

    .notification-message {
        color: #666;
        font-size: 0.95rem;
        margin-bottom: 8px;
    }

    .notification-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .notification-time {
        color: #999;
        font-size: 0.85rem;
    }

    .notification-type {
        display: inline-block;
        padding: 4px 12px;
        background: var(--gold);
        color: white;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .notification-type.order_status {
        background: #007bff;
    }

    .notification-type.collection {
        background: #28a745;
    }

    .notification-type.promotion {
        background: #ff6b6b;
    }

    .notification-filters {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .notification-filters .btn {
        border-color: var(--gold);
        color: var(--dark-brown);
        font-weight: 500;
    }

    .notification-filters .btn.active {
        background: var(--gold);
        color: white;
        border-color: var(--gold);
    }

    .empty-notifications {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-notifications i {
        font-size: 4rem;
        color: var(--light-gray);
        margin-bottom: 20px;
    }

    .empty-notifications h5 {
        color: var(--dark-brown);
        margin-bottom: 10px;
    }

    .empty-notifications p {
        color: #999;
    }

    /* Force display notifications */
    #notificationsList {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
    }
</style>

<div class="notifications-container">
        <div class="notifications-header">
            <h2>Thông báo của bạn</h2>
            <div>
                <button class="btn btn-sm btn-outline-dark" onclick="markAllAsRead()">
                    Đánh dấu tất cả đã đọc
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="notification-filters">
            <button class="btn btn-sm btn-outline-dark filter-btn active" data-filter="all">
                Tất cả
            </button>
            <button class="btn btn-sm btn-outline-dark filter-btn" data-filter="order_status">
                    <i class="fas fa-box me-1"></i>Đơn hàng
                </button>
                <button class="btn btn-sm btn-outline-dark filter-btn" data-filter="collection">
                    <i class="fas fa-images me-1"></i>Bộ sưu tập
                </button>
                <button class="btn btn-sm btn-outline-dark filter-btn" data-filter="promotion">
                    <i class="fas fa-tag me-1"></i>Khuyến mãi
                </button>
            </div>

            <!-- Notifications List -->
            <div id="full-notifications-list">
                <div class="text-center text-muted py-5">
                    <i class="fas fa-spinner fa-spin me-2"></i>Đang tải thông báo...
                </div>
            </div>
        </div>

    <script>
        let allNotifications = [];
        let currentFilter = 'all';

        // Load notifications on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOMContentLoaded fired');
            loadAllNotifications();

            // Filter buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.dataset.filter;
                    displayNotifications();
                });
            });
        });

        // Also load immediately in case DOMContentLoaded already fired
        if (document.readyState === 'loading') {
            console.log('Document still loading, waiting for DOMContentLoaded');
        } else {
            console.log('Document already loaded, calling loadAllNotifications immediately');
            loadAllNotifications();
        }

        function loadAllNotifications() {
            console.log('Loading notifications from: <?= url('/api/notifications/all') ?>?limit=100');
            fetch('<?= url('/api/notifications/all') ?>?limit=100', {
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    allNotifications = data.data || [];
                    console.log('Notifications loaded:', allNotifications.length);
                    displayNotifications();
                } else {
                    console.log('API returned success=false');
                    showEmptyNotifications();
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
                showEmptyNotifications();
            });
        }

        function displayNotifications() {
            console.log('displayNotifications called, allNotifications:', allNotifications);
            let filtered = allNotifications;

            // Apply filter
            if (currentFilter !== 'all') {
                filtered = allNotifications.filter(n => n.type === currentFilter);
            }

            // Sort by date
            filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

            console.log('Filtered notifications:', filtered);

            if (filtered.length === 0) {
                console.log('No filtered notifications, showing empty');
                showEmptyNotifications();
                return;
            }

            let html = '';
            filtered.forEach(notif => {
                const readClass = notif.is_read ? 'read' : 'unread';
                const readIcon = notif.is_read ? '✓' : '●';
                const typeText = getTypeText(notif.type);

                html += `
                    <div class="notification-item ${readClass}" onclick="handleNotificationClick(event, ${notif.notification_id}, '${notif.ref_type}', ${notif.ref_id}, '${notif.collection_slug || ''}')">
                        <div class="d-flex justify-content-between align-items-start">
                            <div style="flex: 1;">
                                <span class="notification-type ${notif.type}">${typeText}</span>
                                <div class="notification-title">${notif.title}</div>
                                <div class="notification-message">${notif.message}</div>
                                <div class="notification-meta">
                                    <span class="notification-time">${formatTime(notif.created_at)}</span>
                                    ${!notif.is_read ? '<small class="text-warning fw-bold">Chưa đọc</small>' : ''}
                                </div>
                            </div>
                            <button class="btn btn-close ms-2" onclick="deleteNotifOnClick(event, ${notif.notification_id})"></button>
                        </div>
                    </div>
                `;
            });

            console.log('Generated HTML length:', html.length);
            const element = document.getElementById('full-notifications-list');
            console.log('Element found:', element);
            if (element) {
                element.innerHTML = html;
                console.log('HTML set to element');
            }
        }

        function showEmptyNotifications() {
            document.getElementById('full-notifications-list').innerHTML = `
                <div class="empty-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <h5>Không có thông báo</h5>
                    <p>Bạn sẽ nhận được thông báo khi có cập nhật từ đơn hàng hoặc bộ sưu tập mới.</p>
                </div>
            `;
        }

        function handleNotificationClick(event, notificationId, refType, refId, refSlug) {
            event.stopPropagation();
            // Mark as read (ONLY - do not delete)
            markAsRead(notificationId);

            // Navigate
            if (refType === 'order') {
                window.location.href = '<?= url('/order-detail') ?>?order_id=' + refId;
            } else if (refType === 'collection') {
                window.location.href = '<?= url('/collection/') ?>' + refSlug;
            }
        }

        function markAsRead(notificationId) {
            fetch('<?= url('/api/notifications/mark-read') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'notification_id=' + notificationId,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload notifications to update UI
                    loadAllNotifications();
                }
            })
            .catch(error => console.error('Error marking as read:', error));
        }

        function markAllAsRead() {
            fetch('<?= url('/api/notifications/mark-all-read') ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadAllNotifications();
                }
            })
            .catch(error => console.error('Error marking all as read:', error));
        }

        function deleteNotifOnClick(event, notificationId) {
            event.stopPropagation();
            event.preventDefault();

            if (confirm('Bạn có chắc chắn muốn xóa thông báo này?')) {
                fetch('<?= url('/api/notifications/delete') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'notification_id=' + notificationId,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadAllNotifications();
                    }
                })
                .catch(error => console.error('Error deleting notification:', error));
            }
        }

        function formatTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'Vừa xong';
            if (diffMins < 60) return diffMins + ' phút trước';
            if (diffHours < 24) return diffHours + ' giờ trước';
            if (diffDays < 7) return diffDays + ' ngày trước';

            return date.toLocaleDateString('vi-VN');
        }

        function getTypeText(type) {
            const types = {
                'order_status': 'Đơn hàng',
                'collection': 'Bộ sưu tập',
                'promotion': 'Khuyến mãi',
                'system': 'Hệ thống'
            };
            return types[type] || 'Thông báo';
        }
    </script>
