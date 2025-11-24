-- Bảng lưu câu trả lời nhanh cho admin
CREATE TABLE IF NOT EXISTS `chat_quick_replies` (
  `reply_id` INT PRIMARY KEY AUTO_INCREMENT,
  `title` VARCHAR(100) NOT NULL COMMENT 'Tiêu đề hiển thị nút',
  `message` TEXT NOT NULL COMMENT 'Nội dung tin nhắn',
  `sort_order` INT DEFAULT 0 COMMENT 'Thứ tự sắp xếp',
  `is_active` TINYINT DEFAULT 1 COMMENT 'Có đang sử dụng không',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm dữ liệu mẫu
INSERT INTO `chat_quick_replies` (`title`, `message`, `sort_order`, `is_active`) VALUES
('Xin chào', 'Xin chào! Tôi có thể giúp gì cho bạn?', 1, 1),
('Cảm ơn', 'Cảm ơn bạn đã liên hệ. Chúc bạn một ngày tốt lành!', 2, 1),
('Thông tin sản phẩm', 'Bạn muốn biết thông tin về sản phẩm nào? Tôi sẽ tư vấn chi tiết cho bạn.', 3, 1),
('Giá cả', 'Về giá cả, chúng tôi có nhiều chương trình ưu đãi. Bạn quan tâm sản phẩm nào?', 4, 1),
('Giao hàng', 'Chúng tôi có dịch vụ giao hàng toàn quốc. Thời gian giao hàng 2-3 ngày.', 5, 1),
('Đợi chút', 'Vui lòng đợi trong giây lát, tôi sẽ kiểm tra thông tin cho bạn.', 6, 1);
