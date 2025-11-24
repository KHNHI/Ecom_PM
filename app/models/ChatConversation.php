<?php
require_once __DIR__ . '/../../core/BaseModel.php';

class ChatConversation extends BaseModel {
    protected $table = 'chat_conversations';
    protected $primaryKey = 'conversation_id';
    
    /**
     * Tìm hoặc tạo conversation cho user
     */
    public function findOrCreateForUser($userId, $sessionId = null) {
        try {
            // Nếu user đã đăng nhập, tìm theo user_id hoặc session_id
            if ($userId) {
                $sql = "SELECT * FROM {$this->table} 
                        WHERE (user_id = :user_id OR session_id = :session_id)
                        AND status IN ('active', 'pending')
                        ORDER BY last_message_at DESC 
                        LIMIT 1";
                
                $this->db->query($sql);
                $this->db->bind(':user_id', $userId);
                $this->db->bind(':session_id', $sessionId);
            } else {
                // Nếu là guest (chưa đăng nhập), CHỈ tìm conversation không có user_id
                $sql = "SELECT * FROM {$this->table} 
                        WHERE session_id = :session_id
                        AND user_id IS NULL
                        AND status IN ('active', 'pending')
                        ORDER BY last_message_at DESC 
                        LIMIT 1";
                
                $this->db->query($sql);
                $this->db->bind(':session_id', $sessionId);
            }
            
            $conversation = $this->db->single();
            
            if ($conversation) {
                $conv = (array) $conversation;
                
                // Nếu user đã đăng nhập nhưng conversation chưa có user_id, cập nhật
                if ($userId && !$conv['user_id']) {
                    $updateSql = "UPDATE {$this->table} 
                                 SET user_id = :user_id 
                                 WHERE conversation_id = :conversation_id";
                    $this->db->query($updateSql);
                    $this->db->bind(':user_id', $userId);
                    $this->db->bind(':conversation_id', $conv['conversation_id']);
                    $this->db->execute();
                    
                    // Cập nhật lại conversation object
                    $conv['user_id'] = $userId;
                }
                
                return $conv;
            }
            
            // Tạo conversation mới nếu chưa có
            $data = [
                'user_id' => $userId,
                'session_id' => $sessionId,
                'status' => 'pending',
                'last_message_at' => date('Y-m-d H:i:s')
            ];
            
            $conversationId = $this->create($data);
            
            if ($conversationId) {
                $result = $this->getById($conversationId);
                return $result ? (array) $result : null;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error in findOrCreateForUser: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Xóa conversation và tất cả tin nhắn liên quan
     */
    public function deleteConversation($conversationId) {
        try {
            // Xóa tất cả tin nhắn trước
            $sqlMessages = "DELETE FROM chat_messages WHERE conversation_id = :conversation_id";
            $this->db->query($sqlMessages);
            $this->db->bind(':conversation_id', $conversationId);
            $this->db->execute();
            
            // Xóa conversation
            $sqlConv = "DELETE FROM {$this->table} WHERE conversation_id = :conversation_id";
            $this->db->query($sqlConv);
            $this->db->bind(':conversation_id', $conversationId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Error in deleteConversation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy tất cả conversation cho admin
     */
    public function getAllForAdmin($status = null, $limit = 50, $offset = 0) {
        try {
            $sql = "SELECT c.*, 
                    u.name as user_name, 
                    u.email as user_email,
                    admin.name as admin_name,
                    (SELECT message FROM chat_messages 
                     WHERE conversation_id = c.conversation_id 
                     ORDER BY created_at DESC LIMIT 1) as last_message
                    FROM {$this->table} c
                    LEFT JOIN users u ON c.user_id = u.user_id
                    LEFT JOIN users admin ON c.admin_id = admin.user_id";
            
            if ($status) {
                $sql .= " WHERE c.status = :status";
            }
            
            $sql .= " ORDER BY c.last_message_at DESC LIMIT {$limit} OFFSET {$offset}";
            
            $this->db->query($sql);
            
            if ($status) {
                $this->db->bind(':status', $status);
            }
            
            $results = $this->db->resultSet();
            return $results ? array_map(function($obj) { return (array) $obj; }, $results) : [];
        } catch (Exception $e) {
            error_log("Error in getAllForAdmin: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Đếm số conversation chưa đọc
     */
    public function countUnreadForAdmin() {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                    WHERE unread_admin_count > 0 AND status != 'closed'";
            $this->db->query($sql);
            $result = $this->db->single();
            return $result->count ?? 0;
        } catch (Exception $e) {
            error_log("Error in countUnreadForAdmin: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Gán admin cho conversation
     */
    public function assignAdmin($conversationId, $adminId) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET admin_id = :admin_id, status = 'active', updated_at = NOW()
                    WHERE conversation_id = :conversation_id";
            
            $this->db->query($sql);
            $this->db->bind(':admin_id', $adminId);
            $this->db->bind(':conversation_id', $conversationId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Error in assignAdmin: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cập nhật trạng thái conversation
     */
    public function updateStatus($conversationId, $status) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET status = :status, updated_at = NOW()
                    WHERE conversation_id = :conversation_id";
            
            $this->db->query($sql);
            $this->db->bind(':status', $status);
            $this->db->bind(':conversation_id', $conversationId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Error in updateStatus: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cập nhật last_message_at
     */
    public function updateLastMessage($conversationId) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET last_message_at = NOW(), updated_at = NOW()
                    WHERE conversation_id = :conversation_id";
            
            $this->db->query($sql);
            $this->db->bind(':conversation_id', $conversationId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Error in updateLastMessage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Tăng unread count
     */
    public function incrementUnreadCount($conversationId, $type = 'admin') {
        try {
            $field = $type === 'admin' ? 'unread_admin_count' : 'unread_customer_count';
            $sql = "UPDATE {$this->table} 
                    SET {$field} = {$field} + 1
                    WHERE conversation_id = :conversation_id";
            
            $this->db->query($sql);
            $this->db->bind(':conversation_id', $conversationId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Error in incrementUnreadCount: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reset unread count
     */
    public function resetUnreadCount($conversationId, $type = 'customer') {
        try {
            $field = $type === 'admin' ? 'unread_admin_count' : 'unread_customer_count';
            $sql = "UPDATE {$this->table} 
                    SET {$field} = 0
                    WHERE conversation_id = :conversation_id";
            
            $this->db->query($sql);
            $this->db->bind(':conversation_id', $conversationId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Error in resetUnreadCount: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy conversation details với thông tin user
     */
    public function getConversationDetails($conversationId) {
        try {
            $sql = "SELECT c.*, 
                    u.name as user_name, 
                    u.email as user_email,
                    u.phone as user_phone,
                    admin.name as admin_name
                    FROM {$this->table} c
                    LEFT JOIN users u ON c.user_id = u.user_id
                    LEFT JOIN users admin ON c.admin_id = admin.user_id
                    WHERE c.conversation_id = :conversation_id";
            
            $this->db->query($sql);
            $this->db->bind(':conversation_id', $conversationId);
            
            $result = $this->db->single();
            return $result ? (array) $result : null;
        } catch (Exception $e) {
            error_log("Error in getConversationDetails: " . $e->getMessage());
            return null;
        }
    }
}
