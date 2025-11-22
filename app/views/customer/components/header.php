<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Ecom_PM/helpers/session_helper.php';
$isLoggedIn = SessionHelper::isLoggedIn();
$user = SessionHelper::getUser();
?>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <!-- Logo - Link về trang chủ -->
        <a class="navbar-brand" href="<?= url('/') ?>">
            <i class="fas fa-gem me-2"></i>JEWELRY
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <!-- Home -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('/') ?>">Trang chủ</a>
                </li>
                
                <!-- About Us -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('/about') ?>">Thông tin</a>
                </li>
                
                <!-- Category Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="categoryDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Danh mục sản phẩm
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="categoryDropdown">
                        <li><a class="dropdown-item" href="<?= url('/products') ?>">Tất cả sản phẩm</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <!-- Categories sẽ được load bằng JavaScript -->
                        <div id="categoriesDropdown">
                            <li><span class="dropdown-item text-muted">Loading...</span></li>
                        </div>
                    </ul>
                </li>
                
                <!-- Collection Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="collectionDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Bộ sưu tập
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="collectionDropdown">
                        <!-- Collections sẽ được load bằng JavaScript -->
                        <div id="collectionsDropdown">
                            <li><span class="dropdown-item text-muted">Loading...</span></li>
                        </div>
                    </ul>
                </li>
            </ul>
            
            <!-- Search Form -->
            <form class="d-flex me-3" id="searchForm">
                <div class="input-group">
                    <input class="form-control search-input" type="search" placeholder="Tìm kiếm sản phẩm..." id="searchInput">
                    <button class="btn btn-outline-secondary search-btn" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                
            </form>
            
            <!-- Right Side Actions -->
            <ul class="navbar-nav">
                <?php if ($isLoggedIn): ?>
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" id="notificationToggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark" id="notificationBadge" style="display: none;">
                                0
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" style="width: 400px; max-height: 500px; overflow-y: auto;" id="notificationDropdown">
                            <div class="dropdown-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Thông báo</h6>
                                <button class="btn btn-sm btn-link" onclick="markAllNotificationsRead()">
                                    <small>Đánh dấu tất cả đã đọc</small>
                                </button>
                            </div>
                            <div id="notificationsList" style="max-height: 400px; overflow-y: auto;">
                                <div class="text-center text-muted py-3">
                                    <small>Đang tải...</small>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center small" href="<?= url('/notifications') ?>">
                                Xem tất cả thông báo →
                            </a>
                        </div>
                    </li>
                <?php endif; ?>
                
                <!-- Shopping Cart -->
                <li class="nav-item">
                    <a class="nav-link position-relative" href="<?= url('/cart') ?>">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartBadge">
                            0
                        </span>
                    </a>
                </li>
                
                <?php if ($isLoggedIn): ?>
                    <!-- User Dropdown (Đã đăng nhập) -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= SessionHelper::getAvatarUrl() ?>" class="rounded-circle me-2" width="30" height="30" alt="Avatar">
                            <span class="d-none d-lg-inline"><?= htmlspecialchars($user->name ?: 'User') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <div class="dropdown-header">
                                    <div class="fw-bold"><?= htmlspecialchars($user->name ?: 'User') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($user->email) ?></small>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= url('/profile') ?>">
                                <i class="fas fa-user me-2"></i>Hồ sơ cá nhân
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="logout()">
                                <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Auth Links (Chưa đăng nhập) -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('/signin') ?>">
                            <i class="fas fa-sign-in-alt me-1"></i>Đăng nhập
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-2" href="<?= url('/signup') ?>">
                            <i class="fas fa-user-plus me-1"></i>Đăng ký
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- JavaScript for User Actions -->
<script>
// Logout function
function logout() {
    if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
        fetch('<?= url('/auth/logout') ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '<?= url('/') ?>';
            } else {
                alert('Có lỗi xảy ra khi đăng xuất');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Fallback: redirect to logout page
            window.location.href = '<?= url('/auth/logout') ?>';
        });
    }
}

// Search functionality
document.getElementById('searchForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const searchTerm = document.getElementById('searchInput').value.trim();
    if (searchTerm) {
        // Redirect to products page with search parameter
        window.location.href = '/Ecom_PM/products?search=' + encodeURIComponent(searchTerm);
    }
});

// Also handle button click directly
document.querySelector('#searchForm button')?.addEventListener('click', function(e) {
    e.preventDefault();
    const searchTerm = document.getElementById('searchInput').value.trim();
    if (searchTerm) {
        window.location.href = '/Ecom_PM/products?search=' + encodeURIComponent(searchTerm);
    }
});

// Update cart badge (sẽ implement sau)
function updateCartBadge() {
    // TODO: Fetch cart count from API
    // document.getElementById('cartBadge').textContent = count;
}

// Load categories và collections dropdown
document.addEventListener('DOMContentLoaded', function() {
    // Load categories
    fetch('<?= url('/api/categories') ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const categoriesDiv = document.getElementById('categoriesDropdown');
                categoriesDiv.innerHTML = '';
                data.data.forEach(category => {
                    const li = document.createElement('li');
                    li.innerHTML = `<a class="dropdown-item" href="<?= url('/products') ?>?category=${category.category_id}">${category.name}</a>`;
                    categoriesDiv.appendChild(li);
                });
            }
        })
        .catch(error => {
            console.error('Categories loading error:', error);
            document.getElementById('categoriesDropdown').innerHTML = '<li><span class="dropdown-item text-muted">Không thể tải danh mục</span></li>';
        });
    
    // Load collections
    fetch('<?= url('/api/collections/list') ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const collectionsDiv = document.getElementById('collectionsDropdown');
                collectionsDiv.innerHTML = '';
                
                data.data.forEach(collection => {
                    const li = document.createElement('li');
                    li.innerHTML = `<a class="dropdown-item" href="<?= url('/collection/') ?>${collection.slug}">${collection.collection_name}</a>`;
                    collectionsDiv.appendChild(li);
                });
            }
        })
        .catch(error => {
            console.error('Collections loading error:', error);
            document.getElementById('collectionsDropdown').innerHTML = '<li><span class="dropdown-item text-muted">Không thể tải bộ sưu tập</span></li>';
        });

    // Update cart badge count
    updateCartBadge();

    // Load notifications for logged-in users
    <?php if ($isLoggedIn): ?>
    loadNotifications();
    // Refresh notifications every 30 seconds
    setInterval(loadNotifications, 30000);
    <?php endif; ?>
});

// Notification functions
function loadNotifications() {
    // Use 'recent' endpoint to get both read and unread (so they don't disappear after clicking)
    fetch('<?= url('/api/notifications/recent') ?>?limit=10', {
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotificationUI(data.data || []);
        }
    })
    .catch(error => console.error('Error loading notifications:', error));
}

function updateNotificationUI(notifications) {
    const badge = document.getElementById('notificationBadge');
    const list = document.getElementById('notificationsList');
    
    // Fetch accurate unread count from API
    fetch('<?= url('/api/notifications/count') ?>', {
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            const unreadCount = data.data.count || 0;
            if (unreadCount > 0) {
                badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    })
    .catch(error => console.error('Error fetching unread count:', error));
    
    // Update list - show all recent (both read and unread)
    if (notifications.length === 0) {
        list.innerHTML = '<div class="text-center text-muted py-3"><small>Không có thông báo</small></div>';
        return;
    }
    
    let html = '';
    notifications.forEach(notif => {
        const isRead = notif.is_read == 1 || notif.is_read === '1';
        const readClass = isRead ? 'bg-light' : 'bg-white border-left border-warning';
        const readIcon = isRead ? '<i class="fas fa-check-circle text-muted me-2"></i>' : '<i class="fas fa-circle text-warning me-2"></i>';
        
        html += `
            <a href="#" class="dropdown-item ${readClass} py-2" onclick="handleNotificationClick(event, ${notif.notification_id}, '${notif.ref_type}', ${notif.ref_id}, '${notif.collection_slug || ''}')">
                <div class="d-flex">
                    <div style="flex: 1;">
                        ${readIcon}
                        <strong class="notification-title">${notif.title}</strong>
                        <small class="text-muted d-block mt-1">${notif.message}</small>
                        <small class="text-muted d-block mt-1">${formatNotificationTime(notif.created_at)}</small>
                    </div>
                    <button class="btn-close ms-2" onclick="deleteNotification(event, ${notif.notification_id})" style="font-size: 0.7rem;" title="Xóa thông báo"></button>
                </div>
            </a>
        `;
    });
    
    list.innerHTML = html;
}

function handleNotificationClick(event, notificationId, refType, refId, refSlug) {
    event.preventDefault();
    event.stopPropagation();
    
    // Mark as read (ONLY - do not delete)
    markNotificationAsRead(notificationId);
    
    // Navigate based on type
    if (refType === 'order') {
        window.location.href = '<?= url('/order-detail') ?>?order_id=' + refId;
    } else if (refType === 'collection') {
        // Navigate to collection detail with slug
        window.location.href = '<?= url('/collection/') ?>' + refSlug;
    }
}

function markNotificationAsRead(notificationId) {
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
            // Reload to update badge
            loadNotifications();
        }
    })
    .catch(error => console.error('Error marking notification as read:', error));
}

function markAllNotificationsRead() {
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
            loadNotifications();
        }
    })
    .catch(error => console.error('Error marking all notifications as read:', error));
}

function deleteNotification(event, notificationId) {
    event.preventDefault();
    event.stopPropagation();
    
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
            loadNotifications();
        }
    })
    .catch(error => console.error('Error deleting notification:', error));
}

function formatNotificationTime(dateString) {
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
}/**
 * Update cart badge with current cart count
 */
function updateCartBadge() {
    // Get cart count from session or localStorage
    let cartCount = 0;
    
    // For session-based cart, we need to make an AJAX request
    fetch('<?= url('/cart/count') ?>', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cartCount = data.count || 0;
            const cartBadge = document.getElementById('cartBadge');
            if (cartBadge) {
                cartBadge.textContent = cartCount;
                cartBadge.style.display = cartCount > 0 ? 'inline' : 'none';
            }
        }
    })
    .catch(error => {
        console.error('Error updating cart badge:', error);
    });
}

// Global function to refresh cart badge (call from other pages)
window.updateCartBadge = updateCartBadge;
</script>