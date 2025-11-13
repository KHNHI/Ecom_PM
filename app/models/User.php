<?php
require_once __DIR__ . '/../../core/BaseModel.php';

class User extends BaseModel {
    protected $table = 'users';
    protected $primaryKey = 'user_id';

    public function __construct() {
        parent::__construct();
    }

    /**
     * Create a new user
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  SET email = :email,
                      password_hash = :password_hash,
                      name = :name,
                      phone = :phone,
                      verification_token = :verification_token,
                      token_expires_at = :token_expires_at,
                      created_at = NOW()";
        
        $this->db->query($query);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password_hash', $data['password_hash']);
        $this->db->bind(':name', $data['name'] ?? null);
        $this->db->bind(':phone', $data['phone'] ?? null);
        $this->db->bind(':verification_token', $data['verification_token'] ?? null);
        $this->db->bind(':token_expires_at', $data['token_expires_at'] ?? null);
        
        return $this->db->execute();
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $this->db->query($query);
        $this->db->bind(':email', $email);
        return $this->db->single();
    }
    
    /**
     * Find user by verification token
     */
    public function findByVerificationToken($token) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE verification_token = :token 
                  AND token_expires_at > NOW()
                  LIMIT 1";
        
        $this->db->query($query);
        $this->db->bind(':token', $token);
        return $this->db->single();
    }
    
    /**
     * Verify user email
     */
    public function verifyEmail($userId) {
        $query = "UPDATE " . $this->table . " 
                  SET is_active = 1, 
                      verification_token = NULL, 
                      token_expires_at = NULL 
                  WHERE user_id = :user_id";
        
        $this->db->query($query);
        $this->db->bind(':user_id', $userId);
        return $this->db->execute();
    }
    
    /**
     * Update verification token
     */
    public function updateVerificationToken($email, $token, $expires) {
        $query = "UPDATE " . $this->table . " 
                  SET verification_token = :token, 
                      token_expires_at = :expires 
                  WHERE email = :email";
        
        $this->db->query($query);
        $this->db->bind(':token', $token);
        $this->db->bind(':expires', $expires);
        $this->db->bind(':email', $email);
        return $this->db->execute();
    }


    /**
     * Lấy tất cả users với thống kê đơn hàng
     */
    public function getAllWithOrderCount($onlyActive = true) {
        $sql = "SELECT u.*, 
                       COALESCE(oc.order_count, 0) as order_count,
                       r.role_name
                FROM {$this->table} u
                LEFT JOIN (
                    SELECT user_id, COUNT(*) as order_count
                    FROM orders
                    GROUP BY user_id
                ) oc ON u.user_id = oc.user_id
                LEFT JOIN roles r ON u.role_id = r.role_id";
        
        if ($onlyActive) {
            $sql .= " WHERE u.is_active = 1";
        }
        
        $sql .= " ORDER BY u.created_at DESC";
        $this->db->query($sql);
        return $this->db->resultSet();
    }

    /**
     * Đếm tổng số users
     */
    public function getTotalCount() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $this->db->query($sql);
        $result = $this->db->single();
        return $result->total;
    }

    /**
     * Đếm số users active
     */
    public function getActiveCount() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE is_active = 1";
        $this->db->query($sql);
        $result = $this->db->single();
        return $result->total;
    }

    /**
     * Đếm số users có đơn hàng
     */
    public function getUsersWithOrdersCount() {
        $sql = "SELECT COUNT(DISTINCT u.user_id) as total 
                FROM {$this->table} u 
                INNER JOIN orders o ON u.user_id = o.user_id";
        $this->db->query($sql);
        $result = $this->db->single();
        return $result->total;
    }

    /**
     * Đếm số users mới trong tháng này
     */
    public function getNewUsersThisMonthCount() {
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} 
                WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                AND YEAR(created_at) = YEAR(CURRENT_DATE())";
        $this->db->query($sql);
        $result = $this->db->single();
        return $result->total;
    }

    // Update user profile

    /**
     * Clean expired tokens
     */
    public function cleanExpiredTokens() {
        $query = "UPDATE " . $this->table . " 
                  SET verification_token = NULL, 
                      token_expires_at = NULL 
                  WHERE token_expires_at < NOW() 
                  AND token_expires_at IS NOT NULL";
        
        $this->db->query($query);
        return $this->db->execute();
    }
    
    /**
     * Update reset token for user
     */
    public function updateResetToken($email, $token, $expires) {
        $query = "UPDATE users SET reset_token = :token, reset_expires_at = :expires WHERE email = :email";
        
        $this->db->query($query);
        $this->db->bind(':token', $token);
        $this->db->bind(':expires', $expires);
        $this->db->bind(':email', $email);
        
        return $this->db->execute();
    }
    
    /**
     * Find user by reset token
     */
    public function findByResetToken($token) {
        $query = "SELECT * FROM users WHERE reset_token = :token AND reset_expires_at > NOW()";
        
        error_log("DEBUG findByResetToken - Token: " . $token);
        error_log("DEBUG findByResetToken - Query: " . $query);
        
        $this->db->query($query);
        $this->db->bind(':token', $token);
        
        $result = $this->db->single();
        error_log("DEBUG findByResetToken - Result: " . ($result ? 'FOUND' : 'NOT_FOUND'));
        
        return $result;
    }
    
    /**
     * Reset user password and clear reset token
     */
    public function resetPassword($userId, $hashedPassword) {
        $query = "UPDATE users SET password_hash = :password, verification_token = NULL, token_expires_at = NULL WHERE user_id = :user_id";
        
        $this->db->query($query);
        $this->db->bind(':password', $hashedPassword);
        $this->db->bind(':user_id', $userId);
        
        return $this->db->execute();
    }
    
    /**
     * Update user profile
     */

    public function updateProfile($userId, $data) {
        try {
            error_log("UpdateProfile - User ID: $userId");
            error_log("UpdateProfile - Data: " . json_encode($data));
            
            $fields = [];
            $params = [':user_id' => $userId];
            
            foreach ($data as $key => $value) {
                if ($value !== null && $value !== '') {
                    $fields[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }
            
            if (empty($fields)) {
                error_log("UpdateProfile - No fields to update");
                return false;
            }
            
            $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = :user_id";
            error_log("UpdateProfile - Query: $query");
            error_log("UpdateProfile - Params: " . json_encode($params));
            
            $this->db->query($query);
            foreach ($params as $param => $value) {
                $this->db->bind($param, $value);
            }
            
            $result = $this->db->execute();
            error_log("UpdateProfile - Result: " . ($result ? 'SUCCESS' : 'FAILED'));
            return $result;
        } catch (Exception $e) {
            error_log("UpdateProfile - Exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user avatar
     */
    public function updateAvatar($userId, $avatarPath) {
        $query = "UPDATE users SET avatar = :avatar WHERE user_id = :user_id";
        
        $this->db->query($query);
        $this->db->bind(':avatar', $avatarPath);
        $this->db->bind(':user_id', $userId);
        
        return $this->db->execute();
    }
    
    /**
     * Update user password
     */
    public function updatePassword($userId, $hashedPassword) {
        $query = "UPDATE users SET password_hash = :password WHERE user_id = :user_id";
        
        $this->db->query($query);
        $this->db->bind(':password', $hashedPassword);
        $this->db->bind(':user_id', $userId);
        
        return $this->db->execute();
    }
    
    /**
     * Find user by ID
     */
    public function findById($userId) {
        $query = "SELECT * FROM users WHERE user_id = :user_id";
        
        $this->db->query($query);
        $this->db->bind(':user_id', $userId);
        
        return $this->db->single();
    }
    
    /**
     * Check if token is valid
     */
    public function isTokenValid($expires) {
        return strtotime($expires) >= time();
    }
    
    /**
     * Get user's default address
     */
    public function getDefaultAddress($userId) {
        $query = "SELECT * FROM user_addresses WHERE user_id = :user_id AND is_default = 1 LIMIT 1";
        
        $this->db->query($query);
        $this->db->bind(':user_id', $userId);
        
        return $this->db->single();
    }
    
    /**
     * Get all user addresses
     */
    public function getUserAddresses($userId) {
        $query = "SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC";
        
        $this->db->query($query);
        $this->db->bind(':user_id', $userId);
        
        return $this->db->resultSet();
    }
    
    /**
     * Add or update user address
     */
    public function saveUserAddress($userId, $addressData) {
        try {
            error_log("SaveUserAddress - User ID: $userId");
            error_log("SaveUserAddress - Address data: " . json_encode($addressData));
            
            // If this is set as default, remove default from other addresses
            if (isset($addressData['is_default']) && $addressData['is_default'] == 1) {
                $clearDefaultQuery = "UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id";
                $this->db->query($clearDefaultQuery);
                $this->db->bind(':user_id', $userId);
                $this->db->execute();
                error_log("SaveUserAddress - Cleared other default addresses");
            }
            
            // Check if address already exists (by street + province combination)
            $checkQuery = "SELECT user_address_id FROM user_addresses 
                           WHERE user_id = :user_id AND street = :street AND province = :province LIMIT 1";
            $this->db->query($checkQuery);
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':street', $addressData['street']);
            $this->db->bind(':province', $addressData['province']);
            $existingAddress = $this->db->single();
            
            if ($existingAddress) {
                error_log("SaveUserAddress - Updating existing address ID: " . $existingAddress->user_address_id);
            // Update existing address (no district column in table)
            $updateQuery = "UPDATE user_addresses SET 
                           ward = :ward,
                           country = :country,
                           postal_code = :postal_code,
                           is_default = :is_default
                           WHERE user_address_id = :address_id";
            
            $this->db->query($updateQuery);
            $this->db->bind(':full_name', $addressData['full_name']);
            $this->db->bind(':phone', $addressData['phone']);
            $this->db->bind(':ward', $addressData['ward'] ?? '');
            $this->db->bind(':country', $addressData['country'] ?? 'Vietnam');
            $this->db->bind(':postal_code', $addressData['postal_code'] ?? null);
            $this->db->bind(':is_default', $addressData['is_default'] ?? 0);
            $this->db->bind(':address_id', $existingAddress->user_address_id);                $result = $this->db->execute();
                error_log("SaveUserAddress - Update result: " . ($result ? 'SUCCESS' : 'FAILED'));
                return $result;
            } else {
                error_log("SaveUserAddress - Inserting new address");
            // Insert new address (no district column in table)
            $insertQuery = "INSERT INTO user_addresses 
                           (user_id, street, ward, province, country, postal_code, is_default) 
                           VALUES (:user_id, :street, :ward, :province, :country, :postal_code, :is_default)";

            $this->db->query($insertQuery);
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':street', $addressData['street']);
            $this->db->bind(':ward', $addressData['ward'] ?? '');
            $this->db->bind(':province', $addressData['province']);
            $this->db->bind(':country', $addressData['country'] ?? 'Vietnam');
            $this->db->bind(':postal_code', $addressData['postal_code'] ?? null);
            $this->db->bind(':is_default', $addressData['is_default'] ?? 0);                $result = $this->db->execute();
                error_log("SaveUserAddress - Insert result: " . ($result ? 'SUCCESS' : 'FAILED'));
                return $result;
            }
        } catch (Exception $e) {
            error_log("SaveUserAddress - Exception: " . $e->getMessage());
            error_log("SaveUserAddress - Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Lấy tất cả khách hàng với thống kê
     */
    public function getCustomersWithStats() {
        $sql = "SELECT u.*, 
                       COALESCE(o.order_count, 0) as order_count,
                       COALESCE(o.total_spent, 0) as total_spent
                FROM {$this->table} u
                LEFT JOIN (
                    SELECT user_id, 
                           COUNT(*) as order_count,
                           SUM(total_amount) as total_spent
                    FROM orders
                    GROUP BY user_id
                ) o ON u.user_id = o.user_id
                WHERE u.role = 'customer'
                ORDER BY u.created_at DESC";
        $this->db->query($sql);
        return $this->db->resultSet();
    }


    /**
     * Cập nhật trạng thái người dùng
     */
    public function updateUserStatus($id, $isActive) {
        $sql = "UPDATE {$this->table} SET is_active = :is_active WHERE user_id = :id";
        $this->db->query($sql);
        $this->db->bind(':is_active', $isActive);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Xóa người dùng
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Toggle trạng thái active của người dùng
     */
    public function toggleActive($id) {
        $sql = "UPDATE {$this->table} 
                SET is_active = IF(is_active = 1, 0, 1) 
                WHERE user_id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}