<?php
// Footer component (fragment) - intended to be included from page templates.
// This file was converted from a standalone HTML document into a PHP fragment
// so it can be included like `include __DIR__ . '/footer.php'` in pages.
?>

<style>
/* Footer styles (kept here for backward compatibility). Consider moving to a global CSS file. */
.footer {
    background: linear-gradient(135deg, #b8860b 0%, #4a3f35 100%);
    color: white;
    padding: 50px 60px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}
.footer-container {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1.5fr;
    gap: 40px;
    max-width: 1400px;
    margin: 0 auto;
}

.footer-brand {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.brand-title {
    font-size: 20px;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 5px;
}

.brand-subtitle {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 20px;
}

.contact-info {
    display: flex;
    flex-direction: column;
    
    font-size: 12px;
    line-height: 1.6;
}

.contact-info p {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.contact-info i {
    margin-top: 3px;
    min-width: 16px;
}

.contact-info a {
    color: white;
    text-decoration: none;
}

.social-links {
    display: flex;
    gap: 15px;
    margin-top: 10px;
}

.social-icon {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all .3s;
    border: 1px solid rgba(255,255,255,0.2);
}

.social-icon:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-3px);
}

.footer-section {
    display: flex;
    flex-direction: column;
}

.section-title-footer {
    font-size: 16px;
    font-weight: 400;
    margin-bottom: 20px;
    color: #ffd700;
    text-transform: uppercase;

}

.footer-links {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.footer-link {
    color: white;
    text-decoration: none;
    font-size: 14px;
    transition: all .3s;
    cursor: pointer;
}

.footer-link:hover {
    padding-left: 10px;
    color: #ffd700;
}

.newsletter {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.newsletter-text {
    font-size: 13px;
    line-height: 1.6;
}

.newsletter-form {
    display: flex;
    background: rgba(255,255,255,0.1);
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.2);
}

.newsletter-input {
    flex: 1;
    padding: 12px 20px;
    background: transparent;
    border: none;
    color: white;
    font-size: 14px;
    outline: none;
}

.newsletter-input::placeholder {
    color: rgba(255,255,255,0.6);
}

.newsletter-button {
    background: transparent;
    border: none;
    padding: 0 20px;
    cursor: pointer;
    transition: all .3s;
    color: white;
}

.newsletter-button:hover {
    color: #ffd700;
}
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #28a745;
    color: white;
    padding: 15px 25px;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    display: none;
    animation: slideIn .3s ease-out;
    z-index: 1000;
}

.notification.show {
    display: block;
}

.notification.error {
    background: #dc3545;
}
@media (max-width: 1024px) {
    .footer-container {
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
}

@media (max-width: 640px) {
    .footer {
        padding: 30px 20px;
    }
    .footer-container {
        grid-template-columns: 1fr;
        gap: 30px;
    }
}

@keyframes slideIn {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>

<div class="notification" id="notification"></div>

<footer class="footer">
    <div class="footer-container">
        <!-- Brand Section -->
        <div class="footer-brand">
            <div>
                <div class="brand-title">TRANG SỨC CAO CẤP</div>
                <div class="brand-subtitle">JEWELRY STORE</div>
            </div>

            <div class="contact-info">
                <p>
                    <i class="fas fa-map-marker-alt"></i>
                    <span>123 Đường Hoà Cúc, Quận 1, TP. Hồ Chí Minh</span>
                </p>
                <p>
                    <i class="fas fa-phone"></i>
                    <a href="tel:0909123456">0909 123 456</a>
                </p>
                <p>
                    <i class="fas fa-envelope"></i>
                    <a href="mailto:support@jewerlyshop.vn">support@jewerlyshop.vn</a>
                </p>
                <p>
                    <i class="fas fa-clock"></i>
                    <span>Giờ mở cửa: 8h00 - 21h00 (T2 - CN)</span>
                </p>
            </div>

            <div class="social-links">
                <div class="social-icon" onclick="handleSocial('facebook')">
                    <i class="fab fa-facebook-f"></i>
                </div>
                <div class="social-icon" onclick="handleSocial('twitter')">
                    <i class="fab fa-twitter"></i>
                </div>
                <div class="social-icon" onclick="handleSocial('instagram')">
                    <i class="fab fa-instagram"></i>
                </div>
                <div class="social-icon" onclick="handleSocial('linkedin')">
                    <i class="fab fa-linkedin-in"></i>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="footer-section">
            <div class="section-title-footer">Liên kết nhanh</div>
            <div class="footer-links">
                <a class="footer-link" onclick="handleNavigation('main-page')">Trang chủ</a>
                <a class="footer-link" onclick="handleNavigation('categories')">Danh mục</a>
                <a class="footer-link" onclick="handleNavigation('collections')">Bộ sưu tập</a>
                <a class="footer-link" onclick="handleNavigation('about')">Về chúng tôi</a>
            </div>
        </div>

        <!-- Support Links -->
        <div class="footer-section">
            <div class="section-title-footer">Trợ giúp</div>
            <div class="footer-links">
                <a class="footer-link" onclick="handleSupport('about')">Về chúng tôi</a>
                <a class="footer-link" onclick="handleSupport('library')">Hòm thư hỗ trợ</a>
                <a class="footer-link" onclick="handleSupport('order-guide')">Hướng dẫn đặt hàng</a>
                <a class="footer-link" onclick="handleSupport('size-guide')">Hướng dẫn chọn size</a>
            </div>
        </div>

        <!-- Newsletter -->
        <div class="footer-section newsletter">
            <div class="section-title-footer">Đăng ký nhận tin</div>
            <div class="newsletter-text">
                Đăng ký thành viên để nhận ưu đãi và các thông báo mới nhất!
            </div>
            <form class="newsletter-form" onsubmit="handleNewsletter(event)">
                <input type="email" class="newsletter-input" placeholder="Nhập email của bạn" id="emailInput" required>
                <button type="submit" class="newsletter-button">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</footer>

<script>
    const socialLinks = {
        facebook: 'https://facebook.com/jewerlystore',
        twitter: 'https://twitter.com/jewerlystore',
        instagram: 'https://instagram.com/jewerlystore',
        linkedin: 'https://linkedin.com/company/jewerlystore'
    };

    const navigationPages = {
        'main-page': '/',
        'categories': '/danh-muc',
        'collections': '/bo-suu-tap',
        'about': '/ve-chung-toi'
    };

    const supportPages = {
        'about': '/ve-chung-toi',
        'library': '/ho-tro',
        'order-guide': '/huong-dan-dat-hang',
        'size-guide': '/huong-dan-chon-size'
    };

    function handleSocial(platform) {
        const url = socialLinks[platform];
        if (url) {
            window.open(url, '_blank');
            showNotification(`Đang chuyển đến ${platform.charAt(0).toUpperCase() + platform.slice(1)}...`);
        }
    }

    function handleNavigation(page) {
        const url = navigationPages[page];
        if (url) {
            showNotification('Đang chuyển trang...');
            setTimeout(() => {
                // window.location.href = url;
                console.log(`Navigate to: ${url}`);
            }, 500);
        }
    }

    function handleSupport(page) {
        const url = supportPages[page];
        if (url) {
            showNotification('Đang tải trang hỗ trợ...');
            setTimeout(() => {
                // window.location.href = url;
                console.log(`Navigate to support: ${url}`);
            }, 500);
        }
    }

    function handleNewsletter(event) {
        event.preventDefault();
        const emailInput = document.getElementById('emailInput');
        const email = emailInput.value.trim();

        if (email && validateEmail(email)) {
            console.log(`Subscribing email: ${email}`);
            showNotification('Đăng ký thành công! Cảm ơn bạn đã đăng ký nhận tin.');
            emailInput.value = '';
        } else {
            showNotification('Vui lòng nhập email hợp lệ!', true);
        }
    }

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function showNotification(message, isError = false) {
        const notification = document.getElementById('notification');
        if (!notification) return;
        notification.textContent = message;
        notification.className = 'notification show' + (isError ? ' error' : '');
        setTimeout(() => { notification.classList.remove('show'); }, 3000);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const phoneLink = document.querySelector('a[href^="tel:"]');
        if (phoneLink) phoneLink.addEventListener('click', () => showNotification('Đang thực hiện cuộc gọi...'));
        const emailLink = document.querySelector('a[href^="mailto:"]');
        if (emailLink) emailLink.addEventListener('click', () => showNotification('Đang mở ứng dụng email...'));
    });
</script>
