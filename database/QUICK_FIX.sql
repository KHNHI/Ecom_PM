-- =====================================================
-- QUICK FIX: Thêm column payment_status
-- Copy & paste vào phpMyAdmin và chạy
-- =====================================================

-- Kiểm tra table orders
DESCRIBE orders;

-- Thêm column payment_status
ALTER TABLE orders 
ADD COLUMN payment_status ENUM('paid', 'unpaid') 
NOT NULL DEFAULT 'unpaid' 
COMMENT 'Trạng thái thanh toán'
AFTER order_status;

-- Cập nhật dữ liệu cũ
UPDATE orders 
SET payment_status = 'paid' 
WHERE order_status IN ('delivered', 'shipped', 'paid');

-- Verify kết quả
SELECT order_id, order_status, payment_status, total_amount, created_at 
FROM orders 
ORDER BY created_at DESC 
LIMIT 10;

-- Kiểm tra lại cấu trúc
SHOW COLUMNS FROM orders WHERE Field = 'payment_status';

-- =====================================================
-- XONG! Bây giờ reload trang admin orders
-- =====================================================


