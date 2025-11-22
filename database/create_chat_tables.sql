-- SQL Migration: Tạo bảng cho hệ thống Chat
-- Date: 2025-11-22
-- Purpose: Thêm chức năng chatbox cho khách hàng và admin

-- Bảng quản lý cuộc hội thoại
CREATE TABLE IF NOT EXISTS `chat_conversations` (
  `conversation_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL COMMENT 'Khách hàng đã đăng nhập',
  `admin_id` INT(11) DEFAULT NULL COMMENT 'Admin phụ trách',
  `session_id` VARCHAR(100) DEFAULT NULL COMMENT 'Session cho guest user',
  `customer_name` VARCHAR(100) DEFAULT NULL COMMENT 'Tên khách (cho guest)',
  `customer_email` VARCHAR(255) DEFAULT NULL COMMENT 'Email khách (cho guest)',
  `status` ENUM('active', 'closed', 'pending') DEFAULT 'pending' COMMENT 'pending: chờ admin, active: đang chat, closed: đã đóng',
  `last_message_at` TIMESTAMP NULL DEFAULT NULL,
  `unread_customer_count` INT DEFAULT 0 COMMENT 'Số tin nhắn chưa đọc của customer',
  `unread_admin_count` INT DEFAULT 0 COMMENT 'Số tin nhắn chưa đọc của admin',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`conversation_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_status` (`status`),
  KEY `idx_last_message` (`last_message_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  FOREIGN KEY (`admin_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng lưu tin nhắn
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `message_id` INT(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` INT(11) NOT NULL,
  `sender_type` ENUM('customer', 'admin', 'system') NOT NULL,
  `sender_id` INT(11) DEFAULT NULL COMMENT 'user_id hoặc admin_id',
  `sender_name` VARCHAR(100) DEFAULT NULL COMMENT 'Tên người gửi',
  `message` TEXT NOT NULL,
  `attachment_url` VARCHAR(500) DEFAULT NULL COMMENT 'URL file đính kèm',
  `attachment_name` VARCHAR(255) DEFAULT NULL COMMENT 'Tên file đính kèm',
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `idx_conversation_id` (`conversation_id`),
  KEY `idx_sender_type` (`sender_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_is_read` (`is_read`),
  FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations`(`conversation_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng câu trả lời nhanh cho admin
CREATE TABLE IF NOT EXISTS `chat_quick_replies` (
  `reply_id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(100) NOT NULL COMMENT 'Tiêu đề hiển thị',
  `message` TEXT NOT NULL COMMENT 'Nội dung tin nhắn',
  `category` VARCHAR(50) DEFAULT NULL COMMENT 'greeting, support, order, product, etc',
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reply_id`),
  KEY `idx_category` (`category`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert dữ liệu mẫu cho quick replies
INSERT INTO `chat_quick_replies` (`title`, `message`, `category`, `sort_order`) VALUES
('Chào mừng', 'Xin chào! Cảm ơn bạn đã liên hệ với chúng tôi. Tôi có thể giúp gì cho bạn?', 'greeting', 1),
('Hỏi về sản phẩm', 'Bạn có thể cho tôi biết bạn quan tâm đến loại trang sức nào? (Nhẫn, dây chuyền, bông tai, vòng tay...)', 'product', 2),
('Kiểm tra đơn hàng', 'Vui lòng cung cấp mã đơn hàng hoặc email đặt hàng để tôi kiểm tra giúp bạn.', 'order', 3),
('Chính sách đổi trả', 'Chúng tôi hỗ trợ đổi trả trong vòng 7 ngày kể từ ngày nhận hàng. Sản phẩm cần còn nguyên tem, hộp và chưa qua sử dụng.', 'support', 4),
('Thời gian giao hàng', 'Thời gian giao hàng thông thường là 2-3 ngày làm việc tại nội thành và 3-5 ngày làm việc tại các tỉnh thành khác.', 'support', 5),
('Cảm ơn', 'Cảm ơn bạn đã liên hệ. Nếu còn thắc mắc gì, đừng ngần ngại chat với chúng tôi nhé!', 'greeting', 6);

-- Thêm index để tối ưu performance
CREATE INDEX idx_conversation_status_updated ON chat_conversations(status, updated_at);
CREATE INDEX idx_message_conversation_created ON chat_messages(conversation_id, created_at);
