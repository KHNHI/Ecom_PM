<?php
require_once __DIR__ . '/../../core/BaseModel.php';

class ChatMessage extends BaseModel {
    protected $table = 'chat_messages';
    protected $primaryKey = 'message_id';
    
    /**
     * Tạo tin nhắn mới
     */
    public function createMessage($data) {
        try {
            $messageData = [
                'conversation_id' => $data['conversation_id'],
                'sender_type' => $data['sender_type'],
                'sender_id' => $data['sender_id'] ?? null,
                'sender_name' => $data['sender_name'] ?? null,
                'message' => $data['message'],
                'attachment_url' => $data['attachment_url'] ?? null,
                'attachment_name' => $data['attachment_name'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            return $this->create($messageData);
        } catch (Exception $e) {
            error_log("Error in createMessage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy tất cả tin nhắn của conversation
     */
    public function getByConversation($conversationId, $limit = 100, $offset = 0) {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE conversation_id = :conversation_id 
                    ORDER BY created_at ASC 
                    LIMIT {$limit} OFFSET {$offset}";
            
            $this->db->query($sql);
            $this->db->bind(':conversation_id', $conversationId);
            
            $results = $this->db->resultSet();
            return $results ? array_map(function($obj) { return (array) $obj; }, $results) : [];
        } catch (Exception $e) {
            error_log("Error in getByConversation: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Lấy tin nhắn mới từ một thời điểm
     */
    public function getNewMessages($conversationId, $afterMessageId) {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE conversation_id = :conversation_id 
                    AND message_id > :after_id
                    ORDER BY created_at ASC";
            
            $this->db->query($sql);
            $this->db->bind(':conversation_id', $conversationId);
            $this->db->bind(':after_id', $afterMessageId);
            
            $results = $this->db->resultSet();
            return $results ? array_map(function($obj) { return (array) $obj; }, $results) : [];
        } catch (Exception $e) {
            error_log("Error in getNewMessages: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Đánh dấu tin nhắn đã đọc
     */
    public function markAsRead($conversationId, $senderType) {
        try {
            // Đánh dấu tất cả tin nhắn chưa đọc của sender_type khác
            $sql = "UPDATE {$this->table} 
                    SET is_read = 1, read_at = NOW()
                    WHERE conversation_id = :conversation_id 
                    AND sender_type != :sender_type
                    AND is_read = 0";
            
            $this->db->query($sql);
            $this->db->bind(':conversation_id', $conversationId);
            $this->db->bind(':sender_type', $senderType);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Error in markAsRead: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Đếm tin nhắn chưa đọc
     */
    public function countUnread($conversationId, $excludeSenderType) {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                    WHERE conversation_id = :conversation_id 
                    AND sender_type != :exclude_type
                    AND is_read = 0";
            
            $this->db->query($sql);
            $this->db->bind(':conversation_id', $conversationId);
            $this->db->bind(':exclude_type', $excludeSenderType);
            
            $result = $this->db->single();
            return $result->count ?? 0;
        } catch (Exception $e) {
            error_log("Error in countUnread: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Xóa tin nhắn
     */
    public function deleteMessage($messageId) {
        return $this->deleteById($messageId);
    }
    
    /**
     * Lấy tin nhắn cuối cùng của conversation
     */
    public function getLastMessage($conversationId) {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE conversation_id = :conversation_id 
                    ORDER BY created_at DESC 
                    LIMIT 1";
            
            $this->db->query($sql);
            $this->db->bind(':conversation_id', $conversationId);
            
            $result = $this->db->single();
            return $result ? (array) $result : null;
        } catch (Exception $e) {
            error_log("Error in getLastMessage: " . $e->getMessage());
            return null;
        }
    }
}
