<?php
// class Discount extends BaseModel {
//     protected $table = 'discounts';
//     protected $primaryKey = 'discount_id';
    
//     public function createDiscount($data) {
//         $this->db->query("INSERT INTO " . $this->table . " (code, name, description, discount_type, discount_value, min_order_amount, max_discount_amount, applicable_to, target_id, usage_limit, usage_limit_per_user, start_date, end_date, is_active, created_at, updated_at) 
//                          VALUES (:code, :name, :description, :discount_type, :discount_value, :min_order_amount, :max_discount_amount, :applicable_to, :target_id, :usage_limit, :usage_limit_per_user, :start_date, :end_date, 1, NOW(), NOW())");
        
//         $this->db->bind(':code', $data['code']);
//         $this->db->bind(':name', $data['name']);
//         $this->db->bind(':description', $data['description'] ?? null);
//         $this->db->bind(':discount_type', $data['discount_type']); // 'percentage' or 'fixed_amount'
//         $this->db->bind(':discount_value', $data['discount_value']);
//         $this->db->bind(':min_order_amount', $data['min_order_amount'] ?? 0);
//         $this->db->bind(':max_discount_amount', $data['max_discount_amount'] ?? null);
//         $this->db->bind(':applicable_to', $data['applicable_to'] ?? 'all');
//         $this->db->bind(':target_id', $data['target_id'] ?? null);
//         $this->db->bind(':usage_limit', $data['usage_limit'] ?? null);
//         $this->db->bind(':usage_limit_per_user', $data['usage_limit_per_user'] ?? 1);
//         $this->db->bind(':start_date', $data['start_date']);
//         $this->db->bind(':end_date', $data['end_date']);
        
//         return $this->db->execute();
//     }

//     public function updateDiscount($id, $data) {
//         $this->db->query("UPDATE " . $this->table . " SET 
//                             code = :code, name = :name, description = :description, 
//                             discount_type = :discount_type, discount_value = :discount_value, 
//                             min_order_amount = :min_order_amount, max_discount_amount = :max_discount_amount, 
//                             applicable_to = :applicable_to, target_id = :target_id, 
//                             usage_limit = :usage_limit, usage_limit_per_user = :usage_limit_per_user, 
//                             start_date = :start_date, end_date = :end_date, is_active = :is_active, 
//                             updated_at = NOW() 
//                          WHERE discount_id = :id");
        
//         $this->db->bind(':code', $data['code']);
//         $this->db->bind(':name', $data['name']);
//         $this->db->bind(':description', $data['description']);
//         $this->db->bind(':discount_type', $data['discount_type']);
//         $this->db->bind(':discount_value', $data['discount_value']);
//         $this->db->bind(':min_order_amount', $data['min_order_amount']);
//         $this->db->bind(':max_discount_amount', $data['max_discount_amount']);
//         $this->db->bind(':applicable_to', $data['applicable_to']);
//         $this->db->bind(':target_id', $data['target_id']);
//         $this->db->bind(':usage_limit', $data['usage_limit']);
//         $this->db->bind(':usage_limit_per_user', $data['usage_limit_per_user']);
//         $this->db->bind(':start_date', $data['start_date']);
//         $this->db->bind(':end_date', $data['end_date']);
//         $this->db->bind(':is_active', $data['is_active'] ?? 1);
//         $this->db->bind(':id', $id);
        
//         return $this->db->execute();
//     }

//     public function deleteDiscount($id) {
//         // Soft delete by deactivating
//         $this->db->query("UPDATE " . $this->table . " SET is_active = 0, updated_at = NOW() WHERE discount_id = :id");
//         $this->db->bind(':id', $id);
//         return $this->db->execute();
//     }

//     public function getDiscountByCode($code) {
//         $this->db->query("SELECT * FROM " . $this->table . " WHERE code = :code AND is_active = 1");
//         $this->db->bind(':code', $code);
//         return $this->db->single();
//     }

//     public function getAllDiscounts($onlyActive = true) {
//         $sql = "SELECT * FROM " . $this->table;
//         if ($onlyActive) {
//             $sql .= " WHERE is_active = 1";
//         }
//         $sql .= " ORDER BY created_at DESC";
//         $this->db->query($sql);
//         return $this->db->resultSet();
//     }

//     // Check if a discount code is valid and applicable
//     public function validateDiscount($code, $userId, $orderAmount) {
//         $discount = $this->getDiscountByCode($code);

//         if (!$discount) {
//             return ['valid' => false, 'message' => 'Mã giảm giá không tồn tại hoặc đã hết hạn.'];
//         }

//         // Check date range
//         $now = new DateTime();
//         $startDate = new DateTime($discount->start_date);
//         $endDate = new DateTime($discount->end_date);
//         if ($now < $startDate || $now > $endDate) {
//             return ['valid' => false, 'message' => 'Mã giảm giá không hợp lệ vào thời điểm này.'];
//         }

//         // Check minimum order amount
//         if ($orderAmount < $discount->min_order_amount) {
//             return ['valid' => false, 'message' => 'Đơn hàng chưa đạt giá trị tối thiểu để áp dụng mã.'];
//         }

//         // Check total usage limit
//         if ($discount->usage_limit !== null && $discount->used_count >= $discount->usage_limit) {
//             return ['valid' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng.'];
//         }

//         // Check usage limit per user
//         $this->db->query("SELECT COUNT(*) as user_usage FROM discount_usages WHERE discount_id = :discount_id AND user_id = :user_id");
//         $this->db->bind(':discount_id', $discount->discount_id);
//         $this->db->bind(':user_id', $userId);
//         $userUsage = $this->db->single()->user_usage;

//         if ($userUsage >= $discount->usage_limit_per_user) {
//             return ['valid' => false, 'message' => 'Bạn đã hết lượt sử dụng mã giảm giá này.'];
//         }

//         return ['valid' => true, 'discount' => $discount];
//     }

//     // Record discount usage
//     public function recordUsage($discountId, $userId, $orderId) {
//         // Record in discount_usages table
//         $this->db->query("INSERT INTO discount_usages (discount_id, user_id, order_id, used_at) VALUES (:discount_id, :user_id, :order_id, NOW())");
//         $this->db->bind(':discount_id', $discountId);
//         $this->db->bind(':user_id', $userId);
//         $this->db->bind(':order_id', $orderId);
//         $this->db->execute();

//         // Increment used_count in discounts table
//         $this->db->query("UPDATE " . $this->table . " SET used_count = used_count + 1 WHERE discount_id = :discount_id");
//         $this->db->bind(':discount_id', $discountId);
//         return $this->db->execute();
//     }
//}