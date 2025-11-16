<?php

/**
 * Notification Model
 * Manages customer notifications (order updates, collection new items, etc)
 */
class Notification extends BaseModel {
    protected $table = 'notifications';
    protected $primaryKey = 'notification_id';

    /**
     * Get unread notifications for user
     */
    public function getUnreadByUserId($userId, $limit = 10) {
        $sql = "SELECT n.*, c.slug as collection_slug 
                FROM " . $this->table . " n
                LEFT JOIN collection c ON n.ref_type = 'collection' AND n.ref_id = c.collection_id
                WHERE n.user_id = :user_id AND n.is_read = 0
                ORDER BY n.created_at DESC
                LIMIT :limit";
        
        $this->db->query($sql);
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }

    /**
     * Get all notifications for user (unread and read)
     */
    public function getByUserId($userId, $limit = 20, $offset = 0) {
        $sql = "SELECT n.*, c.slug as collection_slug 
                FROM " . $this->table . " n
                LEFT JOIN collection c ON n.ref_type = 'collection' AND n.ref_id = c.collection_id
                WHERE n.user_id = :user_id
                ORDER BY n.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $this->db->query($sql);
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as count FROM " . $this->table . " 
                WHERE user_id = :user_id AND is_read = 0";
        
        $this->db->query($sql);
        $this->db->bind(':user_id', $userId);
        $result = $this->db->single();
        
        return $result->count ?? 0;
    }

    /**
     * Create notification
     */
    public function create($data) {
        $sql = "INSERT INTO " . $this->table . "
                (user_id, title, message, type, ref_type, ref_id, created_at)
                VALUES (:user_id, :title, :message, :type, :ref_type, :ref_id, NOW())";
        
        $this->db->query($sql);
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':message', $data['message']);
        $this->db->bind(':type', $data['type'] ?? 'system');
        $this->db->bind(':ref_type', $data['ref_type'] ?? null);
        $this->db->bind(':ref_id', $data['ref_id'] ?? null);
        
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        $sql = "UPDATE " . $this->table . "
                SET is_read = 1, read_at = NOW()
                WHERE notification_id = :notification_id AND user_id = :user_id";
        
        $this->db->query($sql);
        $this->db->bind(':notification_id', $notificationId);
        $this->db->bind(':user_id', $userId);
        
        return $this->db->execute();
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead($userId) {
        $sql = "UPDATE " . $this->table . "
                SET is_read = 1, read_at = NOW()
                WHERE user_id = :user_id AND is_read = 0";
        
        $this->db->query($sql);
        $this->db->bind(':user_id', $userId);
        
        return $this->db->execute();
    }

    /**
     * Delete notification
     */
    public function delete($notificationId, $userId) {
        $sql = "DELETE FROM " . $this->table . "
                WHERE notification_id = :notification_id AND user_id = :user_id";
        
        $this->db->query($sql);
        $this->db->bind(':notification_id', $notificationId);
        $this->db->bind(':user_id', $userId);
        
        return $this->db->execute();
    }

    /**
     * Delete all notifications for user
     */
    public function deleteAll($userId) {
        $sql = "DELETE FROM " . $this->table . "
                WHERE user_id = :user_id";
        
        $this->db->query($sql);
        $this->db->bind(':user_id', $userId);
        
        return $this->db->execute();
    }

    /**
     * Send order status notification to user
     */
    public function notifyOrderStatus($userId, $orderId, $orderStatus) {
        $statusMessages = [
            'pending' => [
                'title' => 'Đơn hàng đang chờ xác nhận',
                'message' => 'Đơn hàng #' . $orderId . ' của bạn đã được tạo và đang chờ xác nhận từ cửa hàng.'
            ],
            'paid' => [
                'title' => 'Thanh toán thành công',
                'message' => 'Đơn hàng #' . $orderId . ' của bạn đã được thanh toán thành công.'
            ],
            'shipped' => [
                'title' => 'Đơn hàng đang được giao',
                'message' => 'Đơn hàng #' . $orderId . ' đã được gửi và đang trên đường đến bạn.'
            ],
            'delivered' => [
                'title' => 'Đơn hàng đã được giao',
                'message' => 'Đơn hàng #' . $orderId . ' đã được giao thành công.'
            ],
            'cancelled' => [
                'title' => 'Đơn hàng đã bị hủy',
                'message' => 'Đơn hàng #' . $orderId . ' đã bị hủy.'
            ]
        ];

        $message = $statusMessages[$orderStatus] ?? [
            'title' => 'Cập nhật đơn hàng',
            'message' => 'Đơn hàng #' . $orderId . ' có cập nhật mới'
        ];

        return $this->create([
            'user_id' => $userId,
            'title' => $message['title'],
            'message' => $message['message'],
            'type' => 'order_status',
            'ref_type' => 'order',
            'ref_id' => $orderId
        ]);
    }

    /**
     * Send collection notification to all users
     */
    public function notifyNewCollection($collectionId, $collectionName) {
        // Get all active users
        $sql = "SELECT user_id FROM users WHERE is_active = 1 AND role = 'customer'";
        $this->db->query($sql);
        $this->db->execute();
        $users = $this->db->resultSet();

        $notificationId = 0;
        foreach ($users as $user) {
            $notificationId = $this->create([
                'user_id' => $user->user_id,
                'title' => 'Bộ sưu tập mới',
                'message' => 'Bộ sưu tập mới "' . $collectionName . '" đã được thêm. Hãy khám phá ngay!',
                'type' => 'collection',
                'ref_type' => 'collection',
                'ref_id' => $collectionId
            ]);
        }

        return $notificationId;
    }

    /**
     * Send promotion notification
     */
    public function notifyPromotion($userId, $message, $discountCode = null) {
        return $this->create([
            'user_id' => $userId,
            'title' => 'Khuyến mãi đặc biệt',
            'message' => $message,
            'type' => 'promotion'
        ]);
    }
}
?>
