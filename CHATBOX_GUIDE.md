# Hệ thống Chatbox - Hướng dẫn cài đặt và sử dụng

## 📋 Tổng quan

Hệ thống chatbox cho phép khách hàng trò chuyện trực tiếp với admin để được tư vấn và hỗ trợ về sản phẩm. Hệ thống hỗ trợ:

- ✅ Chat real-time giữa khách hàng và admin
- ✅ Chatbox widget ở góc phải màn hình
- ✅ Thông báo tin nhắn mới
- ✅ Quản lý conversations cho admin
- ✅ Quick replies (câu trả lời nhanh) cho admin
- ✅ Lịch sử tin nhắn
- ✅ Hỗ trợ cả user đã đăng nhập và guest

## 🚀 Cài đặt

### Bước 1: Import Database

Chạy file SQL để tạo các bảng cần thiết:

```bash
mysql -u root -p db_ecom < database/create_chat_tables.sql
```

Hoặc import thủ công qua phpMyAdmin:

1. Mở phpMyAdmin
2. Chọn database `db_ecom`
3. Chọn tab "Import"
4. Chọn file `database/create_chat_tables.sql`
5. Click "Go"

### Bước 2: Thêm Chatbox vào Layout

Thêm CSS và JavaScript vào layout của bạn:

**Trong `<head>`:**

```html
<link rel="stylesheet" href="/public/assets/css/chatbox.css" />
```

**Trước tag `</body>`:**

```html
<script src="/public/assets/js/chatbox.js"></script>
```

### Bước 3: Kiểm tra Routes

Routes đã được tự động thêm vào:

**Customer Routes (`configs/router.php`):**

- `/chat/conversation` - Lấy/tạo conversation
- `/chat/send` - Gửi tin nhắn
- `/chat/new-messages` - Lấy tin nhắn mới
- `/chat/mark-read` - Đánh dấu đã đọc

**Admin Routes (`configs/admin_router.php`):**

- `/admin/chat` - Danh sách conversations
- `/admin/chat/view/{id}` - Xem chi tiết conversation
- `/admin/chat/send` - Gửi tin nhắn
- `/admin/chat/quick-replies` - Lấy câu trả lời nhanh

## 💬 Sử dụng

### Cho Khách hàng

1. **Mở Chatbox**: Click vào nút chat ở góc phải dưới màn hình
2. **Gửi tin nhắn**: Nhập tin nhắn và nhấn Enter hoặc click nút gửi
3. **Nhận thông báo**: Badge hiển thị số tin nhắn chưa đọc từ admin

### Cho Admin

1. **Truy cập**: Vào `/admin/chat` để xem danh sách conversations
2. **Lọc conversations**:

   - Tất cả: Xem tất cả cuộc hội thoại
   - Chờ xử lý: Chỉ xem conversations mới chưa có admin
   - Đang chat: Conversations đang active

3. **Trả lời tin nhắn**:

   - Click vào conversation để xem chi tiết
   - Nhập tin nhắn trong ô chat
   - Hoặc sử dụng Quick Replies để trả lời nhanh
   - Nhấn "Gửi" hoặc Enter

4. **Quản lý conversation**:
   - **Đóng chat**: Khi hỗ trợ xong, click "Đóng chat"
   - **Mở lại**: Nếu cần hỗ trợ thêm, click "Mở lại"

## 🎯 Tính năng chi tiết

### 1. Real-time Messaging

- Hệ thống sử dụng **polling** mỗi 3 giây để kiểm tra tin nhắn mới
- Tự động scroll xuống tin nhắn mới nhất
- Hiển thị thời gian gửi tin nhắn

### 2. Thông báo

- **Badge** trên nút chatbox hiển thị số tin nhắn chưa đọc
- **Browser notification** (nếu user cho phép)
- Admin nhận thông báo có tin nhắn mới từ khách

### 3. Quick Replies

Admin có thể sử dụng câu trả lời nhanh có sẵn:

- Chào mừng
- Hỏi về sản phẩm
- Kiểm tra đơn hàng
- Chính sách đổi trả
- Thời gian giao hàng
- Cảm ơn

### 4. Quản lý Conversations

**Trạng thái conversation:**

- `pending`: Chờ admin xử lý (màu cam)
- `active`: Đang chat (màu xanh)
- `closed`: Đã đóng (màu xám)

**Auto-assign:**

- Admin đầu tiên trả lời sẽ được gán vào conversation
- Các admin khác vẫn có thể xem và join vào conversation

## 🛠️ Tùy chỉnh

### Thay đổi màu sắc Chatbox

Sửa file `public/assets/css/chatbox.css`:

```css
/* Thay đổi gradient màu chính */
.chatbox-button {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Thay đổi màu tin nhắn customer */
.chat-message.customer .message-bubble {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
```

### Thay đổi thời gian polling

Sửa file `public/assets/js/chatbox.js`:

```javascript
// Mặc định 3 giây, có thể thay đổi (tính bằng milliseconds)
this.pollInterval = setInterval(() => {
  this.checkNewMessages();
}, 3000); // Thay 3000 thành số khác
```

### Thêm Quick Replies mới

Thêm trực tiếp vào database:

```sql
INSERT INTO chat_quick_replies (title, message, category, sort_order)
VALUES ('Tiêu đề', 'Nội dung tin nhắn', 'support', 10);
```

## 📊 Cấu trúc Database

### Bảng `chat_conversations`

Lưu thông tin các cuộc hội thoại:

- `conversation_id`: ID cuộc hội thoại
- `user_id`: ID khách hàng (NULL nếu guest)
- `admin_id`: ID admin phụ trách
- `session_id`: Session ID cho guest
- `status`: Trạng thái (pending/active/closed)
- `unread_customer_count`: Số tin chưa đọc của khách
- `unread_admin_count`: Số tin chưa đọc của admin

### Bảng `chat_messages`

Lưu tin nhắn:

- `message_id`: ID tin nhắn
- `conversation_id`: ID cuộc hội thoại
- `sender_type`: Loại người gửi (customer/admin/system)
- `sender_id`: ID người gửi
- `sender_name`: Tên người gửi
- `message`: Nội dung tin nhắn
- `is_read`: Đã đọc chưa

### Bảng `chat_quick_replies`

Câu trả lời nhanh:

- `reply_id`: ID
- `title`: Tiêu đề hiển thị
- `message`: Nội dung
- `category`: Phân loại
- `sort_order`: Thứ tự sắp xếp

## 🔧 Troubleshooting

### Chatbox không hiển thị

1. Kiểm tra CSS đã được load:

```html
<link rel="stylesheet" href="/public/assets/css/chatbox.css" />
```

2. Kiểm tra JavaScript đã được load:

```html
<script src="/public/assets/js/chatbox.js"></script>
```

3. Mở Console (F12) để xem lỗi

### Tin nhắn không gửi được

1. Kiểm tra routes đã được thêm vào `configs/router.php`
2. Kiểm tra database đã import chưa
3. Xem error log tại `logs/` folder

### Admin không nhận được tin nhắn mới

1. Kiểm tra admin routes trong `configs/admin_router.php`
2. Đảm bảo admin đã đăng nhập với role `admin`
3. Refresh trang admin để cập nhật

## 📱 Responsive Design

Chatbox tự động điều chỉnh trên mobile:

- Width: 100vw - 32px
- Height: 100vh - 100px
- Button size nhỏ hơn (56x56px)

## 🔒 Bảo mật

- ✅ Session-based authentication
- ✅ SQL injection prevention (PDO prepared statements)
- ✅ XSS prevention (htmlspecialchars)
- ✅ CSRF protection (có thể thêm token)
- ✅ Role-based access control

## 📈 Tối ưu Performance

1. **Database Indexing**: Các indexes đã được tạo sẵn
2. **Polling Interval**: Mặc định 3 giây (có thể điều chỉnh)
3. **Message Limit**: Load tối đa 100 tin nhắn gần nhất
4. **Auto-cleanup**: Nên tạo cron job để xóa conversations cũ

## 🎨 Customization Tips

### Thêm Emoji Picker

Có thể tích hợp thư viện emoji picker như:

- [emoji-mart](https://github.com/missive/emoji-mart)
- [emoji-button](https://github.com/joeattardi/emoji-button)

### Thêm File Upload

Cần mở rộng:

1. Thêm input file vào chatbox
2. Upload file lên server
3. Lưu URL vào `attachment_url`

### Thêm WebSocket (nâng cao)

Để có chat thực sự real-time, có thể tích hợp:

- Socket.io
- Pusher
- Firebase Realtime Database

## 📞 Hỗ trợ

Nếu gặp vấn đề, vui lòng:

1. Kiểm tra file log tại `logs/`
2. Xem lại hướng dẫn cài đặt
3. Đảm bảo database đã được import đầy đủ
4. Kiểm tra phiên bản PHP >= 7.4

## ✨ Future Enhancements

Các tính năng có thể phát triển thêm:

- [ ] WebSocket cho real-time chat
- [ ] File/Image upload
- [ ] Typing indicator
- [ ] Emoji picker
- [ ] Chat history export
- [ ] Analytics và reporting
- [ ] Multi-language support
- [ ] Chatbot AI integration
- [ ] Video/Voice call

---

**Phiên bản**: 1.0.0  
**Ngày cập nhật**: 22/11/2025  
**Tác giả**: GitHub Copilot
