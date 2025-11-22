# Hướng Dẫn Test Thông Báo - Các Lỗi Đã Được Sửa

## ✅ Các Lỗi Đã Sửa

### 1. **Click vào thông báo chỉ đánh dấu là đã đọc, không xóa**

- **Vấn đề cũ:** Click vào thông báo bị xóa thay vì chỉ mark as read
- **Sửa:** Thay đổi hàm `handleNotificationClick()` trong header.php
  - Bây giờ chỉ gọi `markNotificationAsRead()`
  - Nút X tách biệt để xóa thông báo
- **File:** `app/views/customer/components/header.php`

### 2. **404 error khi click vào thông báo đơn hàng**

- **Vấn đề cũ:** URL sai `/order-detail?id=X` không match route
- **Sửa:** Thay đổi sang `/order-detail?order_id=X`
- **File:** Cả `header.php` và `notifications.php`

### 3. **Trang /notifications trống**

- **Vấn đề cũ:** Không hiển thị thông báo đã đọc
- **Root cause:** Dropdown chỉ load unread, trang full load tất cả
- **Sửa:** API /api/notifications/all trả về cả read và unread ✅
- **Test data:** Đã tạo 3 thông báo chưa đọc (ID 4, 5, 6)

### 4. **Dropdown hiện "Không có thông báo"**

- **Vấn đề cũ:** Tất cả thông báo cũ đều READ
- **Sửa:** Thêm test data - 3 thông báo UNREAD mới

---

## 📋 Hướng Dẫn Test

### Test 1: Notification Dropdown Header

1. **Login** vào tài khoản customer (user 31)
2. Đi đến trang chủ hoặc bất kỳ trang nào
3. **Xem header** phía trên
4. Tìm **biểu tượng chuông 🔔** bên cạnh giỏ hàng
5. **Xác nhận:**
   - ✅ Chuông hiển thị với badge đỏ "3" (3 thông báo chưa đọc)
   - ✅ Click chuông mở dropdown
   - ✅ Hiển thị 3 thông báo mới

### Test 2: Click Vào Thông Báo Đơn Hàng

1. Trong dropdown, click vào thông báo **"Đơn hàng #50 đang giao hàng"**
2. **Xác nhận:**
   - ✅ Chuyển hướng đến `/order-detail?order_id=50` ✅
   - ❌ KHÔNG có lỗi 404
   - ✅ Hiển thị chi tiết đơn hàng #50
   - ✅ Thông báo bị đánh dấu là đã đọc (không xóa)

### Test 3: Thông Báo Vẫn Ở Trong Dropdown Sau Khi Đọc

1. Quay lại homepage
2. Click chuông lại
3. **Xác nhận:**
   - ✅ Thông báo vừa click vào vẫn hiển thị (chỉ mark as read)
   - ✅ Badge giờ hiển thị "2" (còn 2 chưa đọc)
   - ✅ Thông báo #50 giờ có ikon ✓ thay vì ● (đã đọc)

### Test 4: Xóa Thông Báo Bằng Nút X

1. Trong dropdown, tìm một thông báo
2. Click nút **X** ở góc phải
3. **Xác nhận:**
   - ✅ Thông báo bị xóa khỏi dropdown
   - ✅ Badge cập nhật (giảm 1)

### Test 5: Xem Tất Cả Thông Báo

1. Click "Xem tất cả thông báo →" ở cuối dropdown
2. Hoặc navigate đến `/notifications`
3. **Xác nhận:**
   - ✅ Trang tải và hiển thị danh sách thông báo
   - ✅ KHÔNG hiển thị trang trống
   - ✅ Hiển thị tất cả 5 thông báo (bao gồm cả read và unread)
   - ✅ Filter buttons hoạt động (All, Order Status, Collection, Promotion)

### Test 6: Mark All As Read

1. Trong dropdown header, click "Đánh dấu tất cả đã đọc"
2. **Xác nhận:**
   - ✅ Badge chuông biến mất (không còn unread)
   - ✅ Dropdown reload
   - ✅ Hiện "Không có thông báo" (vì tất cả đều read)

### Test 7: Thông Báo Collection

1. Trong dropdown, click vào **"Bộ sưu tập mới: Charm Vàng"**
2. **Xác nhận:**
   - ✅ Chuyển hướng đến trang collections
   - ✅ Thông báo bị đánh dấu là đã đọc

### Test 8: Thông Báo Promotion

1. Click vào **"Khuyến mãi: Giảm 20%"**
2. **Xác nhận:**
   - ✅ Không lỗi
   - ✅ Đánh dấu là đã đọc

---

## 🔧 Test Data Đã Được Tạo

| ID  | Title                       | Type         | Status | Ref          |
| --- | --------------------------- | ------------ | ------ | ------------ |
| 4   | Đơn hàng #50 đang giao hàng | order_status | UNREAD | order:50     |
| 5   | Bộ sưu tập mới: Charm Vàng  | collection   | UNREAD | collection:5 |
| 6   | Khuyến mãi: Giảm 20%        | promotion    | UNREAD | -            |
| 1   | Đơn hàng #48 đã được giao   | order_status | READ   | order:48     |
| 3   | Đơn hàng đang được giao     | order_status | READ   | -            |

---

## 📝 Files Đã Được Sửa

1. ✅ `app/views/customer/components/header.php`

   - Sửa `handleNotificationClick()` - chỉ mark read
   - Sửa URL: `/order-detail?order_id=X`

2. ✅ `app/views/customer/pages/notifications.php`

   - Sửa `handleNotificationClick()` - chỉ mark read
   - Sửa URL: `/order-detail?order_id=X`
   - Cải thiện `markAsRead()` - reload sau mark

3. ✅ Database test data
   - Tạo 3 thông báo UNREAD mới

---

## 🐛 Debugging

Nếu vẫn gặp vấn đề:

### Kiểm tra Notifications Dropdown Trống

- Chạy: `php debug_notifications.php`
- Kiểm tra unread count có > 0 không

### Kiểm tra 404 Order Detail

- Truy cập trực tiếp: `/order-detail?order_id=50`
- Nếu 404: order ID 50 có tồn tại không

### Kiểm tra API Mark Read

```javascript
// Mở browser console và chạy:
fetch("/Ecom_PM/api/notifications/mark-read", {
  method: "POST",
  headers: { "Content-Type": "application/x-www-form-urlencoded" },
  body: "notification_id=4",
  credentials: "same-origin",
})
  .then((r) => r.json())
  .then((d) => console.log(d));
```

---

## ✨ Kết Quả Kỳ Vọng

✅ Click thông báo → Mark as read + Navigate
✅ Nút X → Xóa thông báo
✅ Badge → Cập nhật đúng
✅ Full page → Hiển thị tất cả thông báo
✅ Không 404 errors
✅ Filter hoạt động
