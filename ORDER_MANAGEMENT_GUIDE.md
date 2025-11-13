# 🛒 Order Management System - Hướng Dẫn Hoàn Chỉnh

## 📋 Mục lục
1. [Tổng Quan](#tổng-quan)
2. [Kiến Trúc Hệ Thống](#kiến-trúc-hệ-thống)
3. [Cấu Trúc Database](#cấu-trúc-database)
4. [API Endpoints](#api-endpoints)
5. [Frontend Implementation](#frontend-implementation)
6. [Review/Rating Integration](#reviewrating-integration)
7. [Flow Hoàn Chỉnh](#flow-hoàn-chỉnh)

---

## 🎯 Tổng Quan

Hệ thống quản lý đơn hàng cho phép khách hàng:

✅ **Xem danh sách đơn hàng** với các trạng thái khác nhau
✅ **Lọc đơn hàng** theo trạng thái (Chờ xác nhận, Đã thanh toán, Đang giao, Đã giao, Hủy)
✅ **Xem chi tiết đơn hàng** (sản phẩm, giá, địa chỉ giao hàng)
✅ **Hủy đơn hàng** - Chỉ áp dụng cho đơn hàng ở trạng thái "pending" (chờ xác nhận)
✅ **Đánh giá sản phẩm** - Chỉ áp dụng cho đơn hàng đã giao

---

## 🏗️ Kiến Trúc Hệ Thống

### Các Thành Phần Chính

```
Frontend (profile.php)
    ↓
JavaScript AJAX Calls
    ↓
API Routes (router.php)
    ↓
OrderController (API Methods)
    ↓
Order Model (Database Queries)
    ↓
MySQL Database
```

### Các File Liên Quan

| File | Mục Đích |
|------|----------|
| `app/views/customer/pages/profile.php` | Frontend UI + JavaScript |
| `app/controllers/OrderController.php` | API logic + data processing |
| `app/models/Order.php` | Database queries |
| `configs/router.php` | Route mapping |
| `app/controllers/ReviewController.php` | Review/Rating handler |

---

## 📊 Cấu Trúc Database

### Bảng: `orders`

```sql
CREATE TABLE orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    full_name VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(20),
    street VARCHAR(255),
    ward VARCHAR(100),
    province VARCHAR(100),
    country VARCHAR(100),
    order_status ENUM('pending', 'paid', 'shipped', 'delivered', 'cancelled'),
    payment_method VARCHAR(50),
    shipping_fee DECIMAL(10,2),
    total_amount DECIMAL(15,2),
    discount_code VARCHAR(50),
    discount_amount DECIMAL(10,2),
    notes TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

### Bảng: `order_items`

```sql
CREATE TABLE order_items (
    order_item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT,           -- Size + Color combination
    quantity INT,
    unit_price_snapshot DECIMAL(10,2),  -- Price at purchase time
    total_price DECIMAL(15,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id)
);
```

### Bảng: `product_variants`

```sql
-- Lưu color, size thông qua variant_id
CREATE TABLE product_variants (
    variant_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    color VARCHAR(50),
    size VARCHAR(50),
    sku VARCHAR(100),
    stock INT,
    price DECIMAL(10,2),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);
```

### Bảng: `payments`

```sql
CREATE TABLE payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL UNIQUE,
    payment_method VARCHAR(50),  -- 'BANK_TRANSFER_HOME', 'CASH_STORE'
    payment_status ENUM('pending', 'completed', 'failed', 'refunded'),
    amount DECIMAL(15,2),
    transaction_code VARCHAR(100),
    paid_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);
```

---

## 🔌 API Endpoints

### 1. **GET List User Orders**

**URL:** `/Ecom_website/api/orders/list`

**Method:** GET

**Headers:**
```
Accept: application/json
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Lấy danh sách đơn hàng thành công",
    "data": [
        {
            "order_id": 1,
            "user_id": 5,
            "full_name": "Nguyễn Văn A",
            "email": "user@example.com",
            "phone": "0912345678",
            "order_status": "delivered",
            "payment_method": "BANK_TRANSFER_HOME",
            "total_amount": 2500000,
            "created_at": "2024-01-15 10:30:00",
            "payment": {
                "payment_id": 1,
                "payment_status": "completed",
                "paid_at": "2024-01-15 10:45:00"
            },
            "items": [
                {
                    "product_id": 10,
                    "product_name": "Nhẫn Kim Cương",
                    "quantity": 1,
                    "unit_price": 2500000,
                    "total_price": 2500000,
                    "color": "Vàng",
                    "size": "6",
                    "product_image": "/Ecom_website/public/uploads/products/ring_1.jpg"
                }
            ]
        }
    ]
}
```

**Response (No Orders):**
```json
{
    "success": true,
    "message": "Không có đơn hàng",
    "data": null
}
```

---

### 2. **GET Order Detail**

**URL:** `/Ecom_website/api/orders/{order_id}/detail`

**Method:** GET

**Parameters:**
- `order_id` (URL parameter): ID của đơn hàng

**Response:** Tương tự như item trong list endpoint

---

### 3. **POST Cancel Order**

**URL:** `/Ecom_website/api/orders/{order_id}/cancel`

**Method:** POST

**Headers:**
```
Accept: application/json
Content-Type: application/json
```

**Parameters:**
- `order_id` (URL parameter): ID của đơn hàng để hủy

**Rules:**
- Chỉ có thể hủy đơn hàng ở trạng thái "pending" (chờ xác nhận)
- Chỉ chủ sở hữu đơn hàng mới có thể hủy

**Response (Success):**
```json
{
    "success": true,
    "message": "Đơn hàng đã được hủy thành công"
}
```

**Response (Error - Cannot Cancel):**
```json
{
    "success": false,
    "message": "Chỉ có thể hủy đơn hàng chờ xác nhận"
}
```

---

## 🎨 Frontend Implementation

### Location: `app/views/customer/pages/profile.php`

#### 1. **Tab Navigation**
```html
<button class="nav-link" id="orders-tab" data-bs-toggle="pill" data-bs-target="#orders">
    <i class="fas fa-shopping-bag me-2"></i>Đơn hàng của tôi
</button>
```

#### 2. **Tab Content**
```html
<div class="tab-pane fade" id="orders" role="tabpanel">
    <!-- Filter buttons -->
    <div class="mb-4 d-flex gap-2 flex-wrap">
        <button class="btn btn-sm btn-outline-primary active" data-filter="all">Tất cả</button>
        <button class="btn btn-sm btn-outline-primary" data-filter="pending">Chờ xác nhận</button>
        <button class="btn btn-sm btn-outline-primary" data-filter="paid">Đã thanh toán</button>
        <button class="btn btn-sm btn-outline-primary" data-filter="shipped">Đang giao</button>
        <button class="btn btn-sm btn-outline-primary" data-filter="delivered">Đã giao</button>
        <button class="btn btn-sm btn-outline-danger" data-filter="cancelled">Hủy</button>
    </div>
    
    <!-- Orders container -->
    <div id="ordersContainer" class="orders-list"></div>
</div>
```

#### 3. **CSS Classes**

| Class | Purpose |
|-------|---------|
| `.order-card` | Order item container |
| `.order-status-badge.{status}` | Status badge (pending/paid/shipped/delivered/cancelled) |
| `.order-item` | Individual product in order |
| `.btn-action.btn-cancel-order` | Cancel button (pending only) |
| `.btn-action.btn-review` | Review button (delivered only) |

#### 4. **JavaScript Functions**

```javascript
// Load orders from API
loadOrders()

// Display orders in UI
displayOrders(orders)

// Cancel order
cancelOrder(orderId)

// Show review modal
reviewOrder(orderId)

// Filter orders by status
[data-filter] button event listener
```

---

## ⭐ Review/Rating Integration

### Flow:

1. **Customer views order** → Tab "Đơn hàng của tôi"
2. **Order status = "delivered"** → "Đánh giá" button appears
3. **Click "Đánh giá"** → Review modal opens
4. **Fill review form** for each product in order:
   - Rating (1-5 stars)
   - Title
   - Comment
5. **Submit** → API call to `/Ecom_website/api/reviews/add`
6. **ReviewController validates** → Store in database
7. **Success** → Show alert, close modal

### Review Modal Structure:

```
[Review Modal]
├── Tabs for each product in order
│   ├── Product Image + Name
│   ├── Star Rating (1-5)
│   ├── Review Title Input
│   ├── Review Comment Textarea
│   └── Submit Button
```

### Integration Points:

**File:** `app/views/customer/pages/profile.php` (Lines ~1460)

**Function:** `reviewOrder(orderId)` → Opens modal with review forms

**API Endpoint:** `POST /Ecom_website/api/reviews/add`

**Review Model:** `app/models/Review.php`

**Review Controller:** `app/controllers/ReviewController.php`

---

## 🔄 Flow Hoàn Chỉnh

### Scenario 1: Customer Views Orders

```
1. User clicks "Đơn hàng của tôi" tab
   ↓
2. Tab shown event triggered
   ↓
3. loadOrders() called
   ↓
4. AJAX GET request to /Ecom_website/api/orders/list
   ↓
5. OrderController::listUserOrders()
   - Get user_id from session
   - Query orders from database
   - Enrich with order items + payment info
   - Return JSON response
   ↓
6. JavaScript displayOrders() renders HTML
   ↓
7. User sees list of orders with:
   - Order ID, Date, Status
   - Products, Prices
   - Action buttons (View, Cancel, Review)
```

### Scenario 2: Customer Cancels Order

```
1. User clicks "Hủy" button on pending order
   ↓
2. confirm() dialog shows
   ↓
3. User confirms
   ↓
4. AJAX POST to /Ecom_website/api/orders/{id}/cancel
   ↓
5. OrderController::cancelOrder($orderId)
   - Verify user owns order
   - Check order status = 'pending'
   - Update status to 'cancelled'
   - Return success response
   ↓
6. JavaScript shows alert
   ↓
7. loadOrders() refreshes list
```

### Scenario 3: Customer Reviews Product

```
1. User clicks "Đánh giá" button on delivered order
   ↓
2. reviewOrder(orderId) called
   ↓
3. showReviewModal(order) creates modal with review forms
   - One form per product in order
   - Each form has rating, title, comment fields
   ↓
4. User fills out review for product
   ↓
5. Clicks "Gửi đánh giá"
   ↓
6. AJAX POST to /Ecom_website/api/reviews/add
   - product_id: ID of product
   - rating: 1-5
   - title: Review title
   - comment: Review text
   ↓
7. ReviewController::add()
   - Validate inputs
   - Check user didn't already review product
   - Create review in database
   - Return success response
   ↓
8. JavaScript shows success alert
   ↓
9. Modal closes
```

---

## 🔗 Linking with Product Detail Page

### Location: `app/views/customer/pages/details-product.php`

### Current Reviews Section:

The product detail page already has a review section showing:
- Review statistics (rating breakdown)
- Individual reviews from customers
- Review submission form (for logged-in users)

### Integration:

When customer reviews from order management:
- Review is submitted via same endpoint: `/Ecom_website/api/reviews/add`
- Review appears in product detail page automatically
- Customer can see their review in:
  1. Order management page (history)
  2. Product detail page (recent reviews)

### Flow:

```
User Reviews from Order Management
        ↓
POST /api/reviews/add (same endpoint)
        ↓
ReviewController validates & stores
        ↓
Review appears in:
├── Product Detail Page (Recent Reviews)
└── Order Management (Order History)
```

---

## 🔐 Security Features

### 1. **User Authorization**
```php
// Check if user is logged in
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    $this->jsonResponse(false, 'Vui lòng đăng nhập');
}
```

### 2. **Order Ownership Verification**
```php
// Check if order belongs to logged-in user
if ($order->user_id != $userId) {
    http_response_code(403);
    $this->jsonResponse(false, 'Không có quyền xem đơn hàng này');
}
```

### 3. **Status Validation**
```php
// Only allow cancelling pending orders
if ($order->order_status !== 'pending') {
    $this->jsonResponse(false, 'Chỉ có thể hủy đơn hàng chờ xác nhận');
}
```

---

## 📝 Trạng Thái Đơn Hàng

| Status | Display | Meaning | Actions |
|--------|---------|---------|---------|
| `pending` | ⏳ Chờ xác nhận | Chưa thanh toán, chờ xác nhận từ admin | Cancel, View |
| `paid` | ✓ Đã thanh toán | Thanh toán xong, chờ chuẩn bị hàng | View |
| `shipped` | 🚚 Đang giao | Hàng đang trên đường | View |
| `delivered` | 📦 Đã giao | Hàng đã được giao | View, **Review** |
| `cancelled` | ✕ Hủy | Đơn hàng bị hủy (bởi customer hoặc admin) | View |

---

## 🛠️ Troubleshooting

### Issue: Reviews not appearing

**Solution:**
1. Check `reviews` table has records
2. Verify user is logged in
3. Check `/api/reviews/get` endpoint works
4. Check ReviewController::getProductReviews() method

### Issue: Cannot cancel order

**Solution:**
1. Verify order status = 'pending'
2. Check user owns the order (user_id matches)
3. Check session is set correctly

### Issue: Order items showing no color/size

**Solution:**
1. Check `product_variants` table has records
2. Verify variant_id is stored in `order_items`
3. Check getVariantInfo() method in OrderController

---

## 📚 Related Documentation

- **Product Management:** See `TECHNICAL_DOCUMENTATION.md`
- **Cart System:** See `CheckoutController.php`
- **Review System:** See `ReviewController.php`
- **Database Schema:** Run migrations in `database/` folder

---

## ✅ Checklist - Implementation Complete

- [x] Tab UI in profile.php
- [x] Filter buttons (all, pending, paid, shipped, delivered, cancelled)
- [x] Order display with product details
- [x] Cancel order functionality (pending only)
- [x] Review modal for delivered orders
- [x] API endpoints for order management
- [x] Order model methods
- [x] Database integration
- [x] Security checks (authentication, authorization, status validation)
- [x] Error handling
- [x] CSS styling for all components

---

## 📞 Support

For issues or questions about the Order Management System:
1. Check this documentation first
2. Review code comments in related files
3. Check error logs in `logs/` directory
4. Review database tables and sample data

**Last Updated:** November 2024
