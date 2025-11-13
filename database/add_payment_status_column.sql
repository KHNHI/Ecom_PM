-- =====================================================
-- Migration: Thêm Column payment_status vào Table orders
-- Ngày: 2025-10-24
-- Mục đích: Để admin có thể cập nhật trạng thái thanh toán
-- =====================================================

-- Kiểm tra xem column payment_status đã tồn tại chưa
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'orders' 
  AND COLUMN_NAME = 'payment_status';

-- Nếu chưa có, chạy lệnh sau:
ALTER TABLE orders 
ADD COLUMN payment_status ENUM('paid', 'unpaid') DEFAULT 'unpaid' 
COMMENT 'Trạng thái thanh toán: paid (đã thanh toán), unpaid (chưa thanh toán)'
AFTER order_status;

-- Cập nhật các đơn hàng cũ
-- Nếu order_status = 'delivered' hoặc 'shipped', set payment_status = 'paid'
UPDATE orders 
SET payment_status = 'paid' 
WHERE order_status IN ('delivered', 'shipped', 'paid');

-- Kiểm tra kết quả
SELECT order_id, order_status, payment_status, total_amount, created_at 
FROM orders 
ORDER BY created_at DESC 
LIMIT 10;

-- =====================================================
-- Hoàn tất!
-- =====================================================


