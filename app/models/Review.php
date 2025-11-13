<?php

class Review extends BaseModel
{
    protected $table = 'product_reviews';
    protected $primaryKey = 'review_id';

    // Các status có sẵn
    const REVIEW_STATUS = [
        'pending' => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối',
        'hidden' => 'Ẩn'
    ];

    // Tạo review mới
    public function createReview($productId, $userId, $rating, $title = null, $comment = null)
    {
        try {
            // Validate rating
            if ($rating < 1 || $rating > 5) {
                throw new Exception("Rating must be between 1 and 5");
            }

            // Kiểm tra user đã mua sản phẩm chưa (tạm thời comment để test)
            // if (!$this->hasUserPurchasedProduct($userId, $productId)) {
            //     throw new Exception("User must purchase product before reviewing");
            // }

            // Kiểm tra user đã review sản phẩm này chưa
            if ($this->hasUserReviewedProduct($userId, $productId)) {
                throw new Exception("User has already reviewed this product");
            }

            $sql = "INSERT INTO {$this->table} (product_id, user_id, rating, title, comment, status, created_at) 
                    VALUES (:product_id, :user_id, :rating, :title, :comment, 'pending', NOW())";
            
            $this->db->query($sql);
            $this->db->bind(':product_id', $productId);
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':rating', $rating);
            $this->db->bind(':title', $title);
            $this->db->bind(':comment', $comment);
            
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log("Error creating review: " . $e->getMessage());
            return false;
        }
    }

    // Kiểm tra user đã mua sản phẩm chưa
    public function hasUserPurchasedProduct($userId, $productId)
    {
        try {
            $sql = "SELECT COUNT(*) as count
                    FROM orders o
                    JOIN order_items oi ON o.order_id = oi.order_id
                    WHERE o.user_id = :user_id 
                    AND oi.product_id = :product_id 
                    AND o.order_status IN ('delivered', 'shipped')";
            
            $this->db->query($sql);
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':product_id', $productId);
            
            $result = $this->db->single();
            return $result && $result->count > 0;
        } catch (Exception $e) {
            error_log("Error checking user purchase: " . $e->getMessage());
            return false;
        }
    }

    // Kiểm tra user đã review sản phẩm chưa
    public function hasUserReviewedProduct($userId, $productId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                    WHERE user_id = :user_id AND product_id = :product_id";
            
            $this->db->query($sql);
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':product_id', $productId);
            
            $result = $this->db->single();
            return $result && $result->count > 0;
        } catch (Exception $e) {
            error_log("Error checking user review: " . $e->getMessage());
            return false;
        }
    }

    // Lấy reviews của sản phẩm
    public function getProductReviews($productId, $status = 'approved', $limit = 10, $offset = 0)
    {
        try {
            $sql = "SELECT r.*, u.name as user_name, u.email
                    FROM {$this->table} r
                    JOIN users u ON r.user_id = u.user_id
                    WHERE r.product_id = :product_id";
            
            if ($status) {
                $sql .= " AND r.status = :status";
            }
            
            $sql .= " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
            
            $this->db->query($sql);
            $this->db->bind(':product_id', $productId);
            
            if ($status) {
                $this->db->bind(':status', $status);
            }
            
            $this->db->bind(':limit', $limit);
            $this->db->bind(':offset', $offset);
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("Error getting product reviews: " . $e->getMessage());
            return [];
        }
    }

    // Lấy review theo ID
    // public function getReviewById($reviewId)
    // {
    //     try {
    //         $sql = "SELECT r.*, u.name as user_name, u.email, p.name as product_name, p.slug
    //                 FROM {$this->table} r
    //                 JOIN users u ON r.user_id = u.user_id
    //                 JOIN products p ON r.product_id = p.product_id
    //                 WHERE r.review_id = :review_id";
            
    //         $this->db->query($sql);
    //         $this->db->bind(':review_id', $reviewId);
            
    //         return $this->db->single();
    //     } catch (Exception $e) {
    //         error_log("Error getting review by ID: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // // Lấy reviews của user
    // public function getUserReviews($userId, $limit = 10, $offset = 0)
    // {
    //     try {
    //         $sql = "SELECT r.*, p.name as product_name, p.slug, p.base_price,
    //                        (SELECT image_path FROM images img 
    //                         JOIN image_usages iu ON img.image_id = iu.image_id 
    //                         WHERE iu.entity_id = p.product_id AND iu.entity_type = 'product' 
    //                         AND iu.is_primary = 1 LIMIT 1) as primary_image
    //                 FROM {$this->table} r
    //                 JOIN products p ON r.product_id = p.product_id
    //                 WHERE r.user_id = :user_id
    //                 ORDER BY r.created_at DESC
    //                 LIMIT :limit OFFSET :offset";
            
    //         $this->db->query($sql);
    //         $this->db->bind(':user_id', $userId);
    //         $this->db->bind(':limit', $limit);
    //         $this->db->bind(':offset', $offset);
            
    //         return $this->db->resultSet();
    //     } catch (Exception $e) {
    //         error_log("Error getting user reviews: " . $e->getMessage());
    //         return [];
    //     }
    // }

    // Cập nhật status review
    public function updateReviewStatus($reviewId, $status)
    {
        try {
            // Validate status
            if (!array_key_exists($status, self::REVIEW_STATUS)) {
                throw new Exception("Invalid review status: $status");
            }

            $sql = "UPDATE {$this->table} SET status = :status WHERE review_id = :review_id";
            $this->db->query($sql);
            $this->db->bind(':status', $status);
            $this->db->bind(':review_id', $reviewId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Error updating review status: " . $e->getMessage());
            return false;
        }
    }

    // Duyệt review
//     public function approveReview($reviewId)
//     {
//         return $this->updateReviewStatus($reviewId, 'approved');
//     }

//     // Từ chối review
//     public function rejectReview($reviewId)
//     {
//         return $this->updateReviewStatus($reviewId, 'rejected');
//     }

//     // Cập nhật review
//     public function updateReview($reviewId, $rating = null, $title = null, $comment = null, $userId = null)
//     {
//         try {
//             $updateFields = [];
//             $params = [':review_id' => $reviewId];

//             if ($rating !== null) {
//                 if ($rating < 1 || $rating > 5) {
//                     throw new Exception("Rating must be between 1 and 5");
//                 }
//                 $updateFields[] = "rating = :rating";
//                 $params[':rating'] = $rating;
//             }

//             if ($title !== null) {
//                 $updateFields[] = "title = :title";
//                 $params[':title'] = $title;
//             }

//             if ($comment !== null) {
//                 $updateFields[] = "comment = :comment";
//                 $params[':comment'] = $comment;
//             }

//             if (empty($updateFields)) {
//                 return false;
//             }

//             // Reset status về pending khi update
//             $updateFields[] = "status = 'pending'";

//             $sql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . " WHERE review_id = :review_id";
            
//             // Nếu có userId, chỉ cho phép update review của chính user đó
//             if ($userId) {
//                 $sql .= " AND user_id = :user_id";
//                 $params[':user_id'] = $userId;
//             }

//             $this->db->query($sql);
            
//             foreach ($params as $param => $value) {
//                 $this->db->bind($param, $value);
//             }
            
//             return $this->db->execute();
//         } catch (Exception $e) {
//             error_log("Error updating review: " . $e->getMessage());
//             return false;
//         }
//     }

//     // Xóa review
//     public function deleteReview($reviewId, $userId = null)
//     {
//         try {
//             $sql = "DELETE FROM {$this->table} WHERE review_id = :review_id";
            
//             if ($userId) {
//                 $sql .= " AND user_id = :user_id";
//             }

//             $this->db->query($sql);
//             $this->db->bind(':review_id', $reviewId);
            
//             if ($userId) {
//                 $this->db->bind(':user_id', $userId);
//             }
            
//             return $this->db->execute();
//         } catch (Exception $e) {
//             error_log("Error deleting review: " . $e->getMessage());
//             return false;
//         }
//     }

//     // Lấy thống kê rating của sản phẩm
//     public function getProductRatingStats($productId)
//     {
//         try {
//             $sql = "SELECT 
//                         AVG(rating) as average_rating,
//                         COUNT(*) as total_reviews,
//                         SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
//                         SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
//                         SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
//                         SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
//                         SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
//                     FROM {$this->table}
//                     WHERE product_id = :product_id AND status = 'approved'";
            
//             $this->db->query($sql);
//             $this->db->bind(':product_id', $productId);
            
//             $result = $this->db->single();
            
//             if ($result) {
//                 $result->average_rating = $result->average_rating ? round((float)$result->average_rating, 1) : 0;
//                 $result->total_reviews = (int)$result->total_reviews;
//             }
            
//             return $result;
//         } catch (Exception $e) {
//             error_log("Error getting product rating stats: " . $e->getMessage());
//             return false;
//         }
//     }

//     // Lấy reviews pending cần duyệt
//     public function getPendingReviews($limit = 20, $offset = 0)
//     {
//         try {
//             $sql = "SELECT r.*, u.name as user_name, u.email, p.name as product_name, p.slug
//                     FROM {$this->table} r
//                     JOIN users u ON r.user_id = u.user_id
//                     JOIN products p ON r.product_id = p.product_id
//                     WHERE r.status = 'pending'
//                     ORDER BY r.created_at ASC
//                     LIMIT :limit OFFSET :offset";
            
//             $this->db->query($sql);
//             $this->db->bind(':limit', $limit);
//             $this->db->bind(':offset', $offset);
            
//             return $this->db->resultSet();
//         } catch (Exception $e) {
//             error_log("Error getting pending reviews: " . $e->getMessage());
//             return [];
//         }
//     }

//     // Lấy review mới nhất
//     public function getLatestReviews($limit = 5)
//     {
//         try {
//             $sql = "SELECT r.*, u.name as user_name, p.name as product_name, p.slug,
//                            (SELECT image_path FROM images img 
//                             JOIN image_usages iu ON img.image_id = iu.image_id 
//                             WHERE iu.entity_id = p.product_id AND iu.entity_type = 'product' 
//                             AND iu.is_primary = 1 LIMIT 1) as primary_image
//                     FROM {$this->table} r
//                     JOIN users u ON r.user_id = u.user_id
//                     JOIN products p ON r.product_id = p.product_id
//                     WHERE r.status = 'approved' AND p.is_active = 1
//                     ORDER BY r.created_at DESC
//                     LIMIT :limit";
            
//             $this->db->query($sql);
//             $this->db->bind(':limit', $limit);
            
//             return $this->db->resultSet();
//         } catch (Exception $e) {
//             error_log("Error getting latest reviews: " . $e->getMessage());
//             return [];
//         }
//     }

//     // Lấy sản phẩm có rating cao nhất
//     public function getTopRatedProducts($limit = 10, $minReviews = 3)
//     {
//         try {
//             $sql = "SELECT p.*, AVG(r.rating) as average_rating, COUNT(r.review_id) as review_count,
//                            (SELECT image_path FROM images img 
//                             JOIN image_usages iu ON img.image_id = iu.image_id 
//                             WHERE iu.entity_id = p.product_id AND iu.entity_type = 'product' 
//                             AND iu.is_primary = 1 LIMIT 1) as primary_image
//                     FROM products p
//                     JOIN {$this->table} r ON p.product_id = r.product_id
//                     WHERE r.status = 'approved' AND p.is_active = 1
//                     GROUP BY p.product_id
//                     HAVING review_count >= :min_reviews
//                     ORDER BY average_rating DESC, review_count DESC
//                     LIMIT :limit";
            
//             $this->db->query($sql);
//             $this->db->bind(':min_reviews', $minReviews);
//             $this->db->bind(':limit', $limit);
            
//             $results = $this->db->resultSet();
            
//             // Round average rating
//             foreach ($results as &$result) {
//                 $result['average_rating'] = round((float)$result['average_rating'], 1);
//             }
            
//             return $results;
//         } catch (Exception $e) {
//             error_log("Error getting top rated products: " . $e->getMessage());
//             return [];
//         }
//     }

//     // Đếm số lượng reviews theo status
//     public function countReviewsByStatus($status = null)
//     {
//         try {
//             $sql = "SELECT COUNT(*) as count FROM {$this->table}";
            
//             if ($status) {
//                 $sql .= " WHERE status = :status";
//             }
            
//             $this->db->query($sql);
            
//             if ($status) {
//                 $this->db->bind(':status', $status);
//             }
            
//             $result = $this->db->single();
//             return $result ? (int)$result->count : 0;
//         } catch (Exception $e) {
//             error_log("Error counting reviews by status: " . $e->getMessage());
//             return 0;
//         }
//     }

//     // Kiểm tra user có thể review sản phẩm không
//     public function canUserReviewProduct($userId, $productId)
//     {
//         return $this->hasUserPurchasedProduct($userId, $productId) && 
//                !$this->hasUserReviewedProduct($userId, $productId);
//     }

//     // Lấy products user có thể review
//     public function getProductsUserCanReview($userId, $limit = 10, $offset = 0)
//     {
//         try {
//             $sql = "SELECT DISTINCT p.*, o.created_at as purchase_date,
//                            (SELECT image_path FROM images img 
//                             JOIN image_usages iu ON img.image_id = iu.image_id 
//                             WHERE iu.entity_id = p.product_id AND iu.entity_type = 'product' 
//                             AND iu.is_primary = 1 LIMIT 1) as primary_image
//                     FROM products p
//                     JOIN order_items oi ON p.product_id = oi.product_id
//                     JOIN orders o ON oi.order_id = o.order_id
//                     WHERE o.user_id = :user_id 
//                     AND o.order_status IN ('delivered', 'shipped')
//                     AND p.is_active = 1
//                     AND p.product_id NOT IN (
//                         SELECT product_id FROM {$this->table} 
//                         WHERE user_id = :user_id
//                     )
//                     ORDER BY o.created_at DESC
//                     LIMIT :limit OFFSET :offset";
            
//             $this->db->query($sql);
//             $this->db->bind(':user_id', $userId);
//             $this->db->bind(':limit', $limit);
//             $this->db->bind(':offset', $offset);
            
//             return $this->db->resultSet();
//         } catch (Exception $e) {
//             error_log("Error getting products user can review: " . $e->getMessage());
//             return [];
//         }
//     }
 public function getTotalCount() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $this->db->query($sql);
        $result = $this->db->single();
        return $result ? (int)$result->total : 0;
    } 
}
 
?>