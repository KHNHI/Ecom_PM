# Hướng dẫn tích hợp Chatbox vào Layout

## Cách 1: Thêm vào Footer Component (Khuyến nghị)

Mở file `app/views/customer/components/footer.php` và thêm code sau **TRƯỚC tag `</footer>` cuối cùng**:

```html
<!-- Chatbox Widget - Thêm phần này -->
<link rel="stylesheet" href="/public/assets/css/chatbox.css" />
<script src="/public/assets/js/chatbox.js"></script>
<!-- End Chatbox Widget -->
```

## Cách 2: Thêm vào từng trang

Nếu bạn muốn chatbox chỉ hiển thị trên một số trang cụ thể, thêm code trên vào cuối file PHP của trang đó:

**Ví dụ trong `app/views/customer/pages/home.php`:**

```php
<?php
// ... nội dung trang home ...
?>

<!-- Chatbox Widget -->
<link rel="stylesheet" href="/public/assets/css/chatbox.css">
<script src="/public/assets/js/chatbox.js"></script>
```

## Cách 3: Tích hợp vào Header/Base Layout

Nếu project của bạn có base layout hoặc master template:

**Trong phần `<head>`:**

```html
<link rel="stylesheet" href="/public/assets/css/chatbox.css" />
```

**Trước tag `</body>`:**

```html
<script src="/public/assets/js/chatbox.js"></script>
</body>
```

## Kiểm tra kết quả

1. Mở trình duyệt và truy cập vào trang web
2. Bạn sẽ thấy một nút chat tròn màu tím ở góc phải bên dưới màn hình
3. Click vào nút để mở chatbox
4. Thử gửi một tin nhắn

## Lưu ý quan trọng

⚠️ **Đảm bảo đã chạy SQL migration trước:**

```bash
mysql -u root -p db_ecom < database/create_chat_tables.sql
```

⚠️ **Kiểm tra đường dẫn CSS/JS đúng:**

- Nếu CSS/JS không load, kiểm tra đường dẫn tuyệt đối
- Có thể cần thay `/public/assets/` thành đường dẫn phù hợp với cấu trúc project

⚠️ **Kiểm tra routes đã được thêm:**

- Customer routes trong `configs/router.php`
- Admin routes trong `configs/admin_router.php`

## Tùy chỉnh vị trí Chatbox

Mặc định chatbox ở góc phải dưới. Để thay đổi vị trí, sửa trong `public/assets/css/chatbox.css`:

```css
.chatbox-widget {
  position: fixed;
  bottom: 20px; /* Khoảng cách từ dưới */
  right: 20px; /* Khoảng cách từ phải */
  /* Để ở góc trái, đổi right thành left */
}
```

## Demo Admin Panel

Để xem và trả lời tin nhắn từ khách hàng:

1. Đăng nhập admin: `/admin/login`
2. Truy cập: `/admin/chat`
3. Click vào conversation để xem và trả lời

---

Đã hoàn tất! Chatbox sẽ tự động hoạt động sau khi tích hợp 🎉
