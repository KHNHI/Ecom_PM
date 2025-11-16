# Hệ thống Thông báo cho Khách hàng - Documentation

## I. Tổng quan

Hệ thống thông báo cho phép gửi thông báo thời gian thực cho khách hàng đã đăng nhập về:

- Cập nhật trạng thái đơn hàng
- Bộ sưu tập mới
- Khuyến mãi đặc biệt
- Thông báo hệ thống

## II. Cấu trúc Database

### Tables mới tạo:

#### `notifications`

```sql
- notification_id (INT) - Primary key
- user_id (INT) - Foreign key đến users table
- title (VARCHAR 255) - Tiêu đề thông báo
- message (TEXT) - Nội dung thông báo
- type (ENUM) - Loại thông báo: order_status, collection, promotion, system
- ref_type (VARCHAR 50) - Loại entity được reference: order, collection, product
- ref_id (INT) - ID của entity được reference
- is_read (TINYINT) - Trạng thái đã đọc (0 = chưa, 1 = đã)
- read_at (DATETIME) - Thời gian đánh dấu đã đọc
- created_at (DATETIME) - Thời gian tạo
- updated_at (DATETIME) - Thời gian cập nhật
```

#### `notification_actions`

```sql
- action_id (INT) - Primary key
- notification_id (INT) - Foreign key
- user_id (INT) - Foreign key
- action_type (ENUM) - read, delete, archive
- created_at (DATETIME) - Thời gian action
```

## III. Models

### Notification Model (`app/models/Notification.php`)

**Các method chính:**

- `getUnreadByUserId($userId, $limit)` - Lấy danh sách thông báo chưa đọc
- `getByUserId($userId, $limit, $offset)` - Lấy tất cả thông báo (có phân trang)
- `getUnreadCount($userId)` - Lấy số lượng thông báo chưa đọc
- `create($data)` - Tạo thông báo mới
- `markAsRead($notificationId, $userId)` - Đánh dấu 1 thông báo đã đọc
- `markAllAsRead($userId)` - Đánh dấu tất cả đã đọc
- `delete($notificationId, $userId)` - Xóa thông báo
- `deleteAll($userId)` - Xóa tất cả thông báo
- `notifyOrderStatus($userId, $orderId, $orderStatus)` - Gửi thông báo cập nhật đơn hàng
- `notifyNewCollection($collectionId, $collectionName)` - Gửi thông báo bộ sưu tập mới cho tất cả users
- `notifyPromotion($userId, $message, $discountCode)` - Gửi thông báo khuyến mãi

**Ví dụ sử dụng:**

```php
$notificationModel = new Notification();

// Lấy thông báo chưa đọc
$unread = $notificationModel->getUnreadByUserId($userId, 10);

// Lấy số lượng chưa đọc
$count = $notificationModel->getUnreadCount($userId);

// Gửi thông báo đơn hàng
$notificationModel->notifyOrderStatus($userId, $orderId, 'shipped');

// Gửi thông báo bộ sưu tập mới
$notificationModel->notifyNewCollection($collectionId, 'Bộ sưu tập Vàng 24K');

// Đánh dấu đã đọc
$notificationModel->markAsRead($notificationId, $userId);
```

## IV. Controllers

### NotificationController (`app/controllers/NotificationController.php`)

**API Endpoints:**

#### 1. Lấy thông báo chưa đọc

```
GET /api/notifications/unread?limit=10
Response: {
    "success": true,
    "message": "Lấy thông báo thành công",
    "data": [...]
}
```

#### 2. Lấy số lượng chưa đọc (cho badge)

```
GET /api/notifications/count
Response: {
    "success": true,
    "data": {"count": 5}
}
```

#### 3. Lấy tất cả thông báo (có phân trang)

```
GET /api/notifications/all?page=1&limit=20
Response: {
    "success": true,
    "data": [...]
}
```

#### 4. Đánh dấu 1 thông báo đã đọc

```
POST /api/notifications/mark-read
Body: notification_id=123
Response: {
    "success": true,
    "message": "Đánh dấu đã đọc thành công"
}
```

#### 5. Đánh dấu tất cả thông báo đã đọc

```
POST /api/notifications/mark-all-read
Response: {
    "success": true,
    "message": "Đánh dấu tất cả đã đọc thành công"
}
```

#### 6. Xóa 1 thông báo

```
POST /api/notifications/delete
Body: notification_id=123
Response: {
    "success": true,
    "message": "Xóa thông báo thành công"
}
```

#### 7. Xóa tất cả thông báo

```
POST /api/notifications/delete-all
Response: {
    "success": true,
    "message": "Xóa tất cả thông báo thành công"
}
```

## V. Views

### 1. Header Component (`app/views/customer/components/header.php`)

**Thêm notification icon:**

- Icon chuông ở header (hiển thị cho users đã đăng nhập)
- Badge hiển thị số lượng chưa đọc
- Dropdown menu hiển thị 10 thông báo mới nhất
- Button "Đánh dấu tất cả đã đọc"
- Link đến trang thông báo đầy đủ

**JavaScript functions:**

- `loadNotifications()` - Fetch danh sách thông báo từ API
- `updateNotificationUI(notifications)` - Render UI thông báo
- `handleNotificationClick(event, notificationId, refType, refId)` - Xử lý click vào thông báo
- `markNotificationAsRead(notificationId)` - Đánh dấu đã đọc
- `markAllNotificationsRead()` - Đánh dấu tất cả đã đọc
- `deleteNotification(event, notificationId)` - Xóa thông báo
- `formatNotificationTime(dateString)` - Format thời gian thành dạng "2 giờ trước"

**Auto-refresh:** Thông báo được refresh mỗi 30 giây

### 2. Notifications Page (`app/views/customer/pages/notifications.php`)

**Chức năng:**

- Hiển thị tất cả thông báo (đã đọc + chưa đọc)
- Filter theo loại: Tất cả, Đơn hàng, Bộ sưu tập, Khuyến mãi
- Sắp xếp theo thời gian mới nhất trước
- Button "Đánh dấu tất cả đã đọc"
- Xóa từng thông báo
- Click vào thông báo để xem chi tiết (đối với đơn hàng)
- Giao diện responsive

## VI. Tích hợp với các module khác

### 1. Cập nhật Trạng thái Đơn hàng

Khi admin cập nhật trạng thái đơn hàng qua Order model:

```php
// File: app/models/Order.php
public function updateStatus($orderId, $status) {
    // ... update database ...

    // Tự động gửi thông báo
    if ($result) {
        $notificationModel = new Notification();
        $order = $this->findByOrderId($orderId);
        if ($order && $order->user_id) {
            $notificationModel->notifyOrderStatus($order->user_id, $orderId, $status);
        }
    }
}
```

### 2. Bộ sưu tập mới

Khi thêm bộ sưu tập mới, gọi:

```php
$notificationModel = new Notification();
$notificationModel->notifyNewCollection($collectionId, 'Tên bộ sưu tập');
```

### 3. Khuyến mãi

Gửi khuyến mãi cho user cụ thể:

```php
$notificationModel = new Notification();
$notificationModel->notifyPromotion($userId, 'Giảm 20% cho tất cả mặt dây');
```

## VII. Frontend Integration

### Routes

```
GET  /notifications           - Xem trang thông báo
GET  /api/notifications/unread
GET  /api/notifications/count
GET  /api/notifications/all
POST /api/notifications/mark-read
POST /api/notifications/mark-all-read
POST /api/notifications/delete
POST /api/notifications/delete-all
```

### User Experience

1. **Header Icon:**

   - Icon chuông hiển thị trong navbar
   - Badge đỏ/cam hiển thị số thông báo chưa đọc
   - Click để mở dropdown

2. **Dropdown Menu:**

   - Hiển thị 10 thông báo mới nhất
   - Thông báo chưa đọc có nền sáng + viền trái
   - Hover để thấy tùy chọn xóa
   - Button "Đánh dấu tất cả đã đọc"
   - Link "Xem tất cả thông báo"

3. **Notifications Page:**
   - Danh sách đầy đủ với filter
   - Status visual (đã đọc/chưa đọc)
   - Timestamp (e.g. "2 giờ trước")
   - Xóa từng hoặc tất cả
   - Click để xem chi tiết đơn hàng

## VIII. Setup Instructions

### 1. Tạo Database Tables

```bash
mysql -u root -p < database/create_notifications_table.sql
```

### 2. Verify Files Created

```
✓ app/models/Notification.php
✓ app/controllers/NotificationController.php
✓ app/views/customer/pages/notifications.php
✓ database/create_notifications_table.sql
✓ configs/router.php (updated with notification routes)
✓ app/views/customer/components/header.php (updated with notification icon & JS)
✓ app/controllers/ProfileController.php (added notifications method)
✓ app/models/Order.php (updated updateStatus to send notifications)
```

### 3. Test the System

1. Login as customer
2. Admin updates order status
3. Customer should see notification in header badge
4. Click notification to view details
5. Visit /notifications to see full list

## IX. Các loại Thông báo

| Loại             | Khi nào gửi                            | Ví dụ                        |
| ---------------- | -------------------------------------- | ---------------------------- |
| **order_status** | Khi admin cập nhật trạng thái đơn hàng | "Đơn hàng #123 đã được giao" |
| **collection**   | Khi admin thêm bộ sưu tập mới          | "Bộ sưu tập mới: Vàng 24K"   |
| **promotion**    | Khi có khuyến mãi                      | "Giảm 20% cho bộ sưu tập"    |
| **system**       | Thông báo hệ thống                     | "Bảo trì hệ thống vào 22:00" |

## X. Security & Performance

### Security:

- ✅ Kiểm tra session user trước khi lấy thông báo
- ✅ Validation user_id khi delete/update
- ✅ CSRF token cho POST requests
- ✅ Pagination để tránh load quá nhiều dữ liệu

### Performance:

- ✅ Limit 10 thông báo cho dropdown (auto-refresh 30s)
- ✅ Pagination 20 items/page cho trang đầy đủ
- ✅ Indexing trên user_id, is_read, created_at
- ✅ Cleanup cũ notifications (optional)

## XI. Future Enhancements

1. **Email Notifications** - Gửi email ngoài web notification
2. **SMS Notifications** - Gửi SMS cho order updates quan trọng
3. **Push Notifications** - Browser push noti nếu có PWA
4. **Auto Archive** - Tự động archive notifications cũ hơn 30 ngày
5. **Notification Preferences** - Cho user chọn loại thông báo muốn nhận
6. **Bulk Sending** - Admin gửi thông báo cho multiple users
