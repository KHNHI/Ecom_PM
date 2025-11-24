<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../models/ChatConversation.php';
require_once __DIR__ . '/../models/ChatMessage.php';
require_once __DIR__ . '/../models/User.php';

class ChatController extends BaseController {
    private $conversationModel;
    private $messageModel;
    private $userModel;
    
    public function __construct() {
        $this->conversationModel = new ChatConversation();
        $this->messageModel = new ChatMessage();
        $this->userModel = new User();
    }
    
    /**
     * Lấy hoặc tạo conversation cho user hiện tại
     */
    public function getOrCreateConversation() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $sessionId = session_id();
            
            $conversation = $this->conversationModel->findOrCreateForUser($userId, $sessionId);
            
            if (!$conversation) {
                $this->jsonResponse(false, 'Không thể tạo cuộc hội thoại');
                return;
            }
            
            // Lấy tin nhắn của conversation
            $messages = $this->messageModel->getByConversation($conversation['conversation_id'], 50);
            
            // Reset unread count cho customer
            $this->conversationModel->resetUnreadCount($conversation['conversation_id'], 'customer');
            
            $this->jsonResponse(true, 'Success', [
                'conversation' => $conversation,
                'messages' => $messages
            ]);
        } catch (Exception $e) {
            error_log("Error in getOrCreateConversation: " . $e->getMessage());
            $this->jsonResponse(false, 'Lỗi server: ' . $e->getMessage());
        }
    }
    
    /**
     * Gửi tin nhắn mới
     */
    public function sendMessage() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['conversation_id']) || empty($data['message'])) {
                $this->jsonResponse(false, 'Thiếu thông tin');
                return;
            }
            
            $userId = $_SESSION['user_id'] ?? null;
            $senderName = 'Khách';
            
            if ($userId) {
                $user = $this->userModel->getById($userId);
                if ($user) {
                    // Convert object to array if needed
                    $user = is_object($user) ? (array) $user : $user;
                    $senderName = $user['name'] ?? 'Khách';
                }
            }
            
            $messageData = [
                'conversation_id' => $data['conversation_id'],
                'sender_type' => 'customer',
                'sender_id' => $userId,
                'sender_name' => $senderName,
                'message' => trim($data['message'])
            ];
            
            $messageId = $this->messageModel->createMessage($messageData);
            
            if (!$messageId) {
                $this->jsonResponse(false, 'Không thể gửi tin nhắn');
                return;
            }
            
            // Cập nhật conversation
            $this->conversationModel->updateLastMessage($data['conversation_id']);
            $this->conversationModel->incrementUnreadCount($data['conversation_id'], 'admin');
            
            // Lấy tin nhắn vừa tạo
            $message = $this->messageModel->getById($messageId);
            
            $this->jsonResponse(true, 'Success', [
                'message' => (array) $message
            ]);
        } catch (Exception $e) {
            error_log("Error in sendMessage: " . $e->getMessage());
            $this->jsonResponse(false, 'Lỗi server');
        }
    }
    
    /**
     * Lấy tin nhắn mới (polling)
     */
    public function getNewMessages() {
        try {
            $conversationId = $_GET['conversation_id'] ?? null;
            $afterId = $_GET['after_id'] ?? 0;
            
            if (!$conversationId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Thiếu conversation_id'], 400);
            }
            
            $messages = $this->messageModel->getNewMessages($conversationId, $afterId);
            
            // Đánh dấu tin nhắn từ admin là đã đọc
            if (!empty($messages)) {
                $this->messageModel->markAsRead($conversationId, 'customer');
                $this->conversationModel->resetUnreadCount($conversationId, 'customer');
            }
            
            // Lấy thông tin conversation để check unread count
            $conversation = $this->conversationModel->getById($conversationId);
            $conv = (array) $conversation;
            
            $this->jsonResponse(true, 'Success', [
                'messages' => $messages,
                'unread_count' => $conv['unread_customer_count'] ?? 0
            ]);
        } catch (Exception $e) {
            error_log("Error in getNewMessages: " . $e->getMessage());
            $this->jsonResponse(false, 'Lỗi server');
        }
    }
    
    /**
     * Đánh dấu tin nhắn đã đọc
     */
    public function markMessagesAsRead() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $conversationId = $data['conversation_id'] ?? null;
            
            if (!$conversationId) {
                $this->jsonResponse(false, 'Thiếu conversation_id');
                return;
            }
            
            $this->messageModel->markAsRead($conversationId, 'customer');
            $this->conversationModel->resetUnreadCount($conversationId, 'customer');
            
            $this->jsonResponse(true, 'Success');
        } catch (Exception $e) {
            error_log("Error in markMessagesAsRead: " . $e->getMessage());
            $this->jsonResponse(false, 'Lỗi server');
        }
    }
    
    /**
     * Lấy lịch sử tin nhắn
     */
    public function getMessages() {
        try {
            $conversationId = $_GET['conversation_id'] ?? null;
            $limit = $_GET['limit'] ?? 50;
            $offset = $_GET['offset'] ?? 0;
            
            if (!$conversationId) {
                $this->jsonResponse(false, 'Thiếu conversation_id');
                return;
            }
            
            $messages = $this->messageModel->getByConversation($conversationId, $limit, $offset);
            
            $this->jsonResponse(true, 'Success', [
                'messages' => $messages
            ]);
        } catch (Exception $e) {
            error_log("Error in getMessages: " . $e->getMessage());
            $this->jsonResponse(false, 'Lỗi server');
        }
    }
    
    /**
     * Xóa conversation của khách (guest) khi thoát trang
     */
    public function clearGuestConversation() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $conversationId = $data['conversation_id'] ?? null;
            
            if (!$conversationId) {
                $this->jsonResponse(false, 'Thiếu conversation_id');
                return;
            }
            
            // Chỉ xóa nếu user chưa đăng nhập
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                // Lấy thông tin conversation để kiểm tra
                $conversation = $this->conversationModel->getById($conversationId);
                
                if ($conversation) {
                    $conv = is_object($conversation) ? (array) $conversation : $conversation;
                    
                    // Chỉ xóa nếu conversation không có user_id (là guest)
                    if (empty($conv['user_id'])) {
                        $deleted = $this->conversationModel->deleteConversation($conversationId);
                        
                        if ($deleted) {
                            $this->jsonResponse(true, 'Đã xóa conversation');
                            return;
                        }
                    }
                }
            }
            
            // Nếu user đã đăng nhập hoặc conversation có user_id, không xóa
            $this->jsonResponse(true, 'Giữ lại conversation');
        } catch (Exception $e) {
            error_log("Error in clearGuestConversation: " . $e->getMessage());
            $this->jsonResponse(false, 'Lỗi server');
        }
    }
}
