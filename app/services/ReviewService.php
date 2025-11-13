<?php

require_once __DIR__ . '/../../core/BaseModel.php';

/**
 * ReviewService - Xử lý logic liên quan đến đánh giá sản phẩm
 */
class ReviewService extends BaseModel {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Lấy tất cả đánh giá của một sản phẩm (đã được phê duyệt)
     */
    public function getProductReviews($productId, $limit = 10, $offset = 0) {
        $sql = "SELECT pr.*, u.name as reviewer_name 
                FROM product_reviews pr 
                INNER JOIN users u ON pr.user_id = u.user_id 
                WHERE pr.product_id = :product_id 
                AND pr.status = 'approved' 
                ORDER BY pr.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $this->db->query($sql);
        $this->db->bind(':product_id', $productId);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    /**
     * Lấy thống kê đánh giá của sản phẩm
     */
    public function getProductReviewStats($productId) {
        $sql = "SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as average_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM product_reviews 
                WHERE product_id = :product_id 
                AND status = 'approved'";
        
        $this->db->query($sql);
        $this->db->bind(':product_id', $productId);
        
        return $this->db->single();
    }
    
    /**
     * Tạo HTML stars cho hiển thị rating
     */
    public function renderStars($rating, $maxStars = 5) {
        $html = '';
        $fullStars = floor($rating);
        $hasHalfStar = ($rating - $fullStars) >= 0.5;
        
        // Full stars
        for ($i = 0; $i < $fullStars; $i++) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        }
        
        // Half star
        if ($hasHalfStar) {
            $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
            $fullStars++;
        }
        
        // Empty stars
        for ($i = $fullStars; $i < $maxStars; $i++) {
            $html .= '<i class="far fa-star text-muted"></i>';
        }
        
        return $html;
    }
    
    /**
     * Format thời gian đánh giá
     */
    public function formatReviewTime($datetime) {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'vừa xong';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' phút trước';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' giờ trước';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' ngày trước';
        } else {
            return date('d/m/Y', $time);
        }
    }
}
?>