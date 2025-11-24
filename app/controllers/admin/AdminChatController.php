<?php
// BaseController đã được load bởi AdminRouter
// Models cũng đã được load bởi admin/index.php

class AdminChatController extends BaseController {
    private $conversationModel;
    private $messageModel;
    
    public function __construct() {
        $this->conversationModel = new ChatConversation();
        $this->messageModel = new ChatMessage();
        $this->checkAdminAuth();
    }
    
    /**
     * Kiểm tra quyền admin
     */
    private function checkAdminAuth() {
        if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
            header('Location: /Ecom_PM/admin/login');
            exit;
        }
    }
    
    /**
     * Trang quản lý chat
     */
    public function index() {
        $status = $_GET['status'] ?? null;
        $conversations = $this->conversationModel->getAllForAdmin($status, 50, 0);
        $unreadCount = $this->conversationModel->countUnreadForAdmin();
        
        $this->view('admin/chat/index', [
            'conversations' => $conversations,
            'status' => $status,
            'unreadCount' => $unreadCount,
            'pageTitle' => 'Quản lý Chat'
        ]);
    }
    
    /**
     * Xem chi tiết conversation
     */
    public function viewConversation($conversationId) {
        $conversation = $this->conversationModel->getConversationDetails($conversationId);
        
        if (!$conversation) {
            $_SESSION['error'] = 'Không tìm thấy cuộc hội thoại';
            header('Location: /admin/chat');
            exit;
        }
        
        $messages = $this->messageModel->getByConversation($conversationId, 100);
        
        // Đánh dấu đã đọc và reset unread count
        $this->messageModel->markAsRead($conversationId, 'admin');
        $this->conversationModel->resetUnreadCount($conversationId, 'admin');
        
        // Gán admin nếu chưa có
        if (!$conversation['admin_id']) {
            $adminUserId = $_SESSION['admin_user_id'] ?? null;
            $adminName = $_SESSION['admin_name'] ?? 'Admin';
            
            if ($adminUserId) {
                $this->conversationModel->assignAdmin($conversationId, $adminUserId);
                $conversation['admin_id'] = $adminUserId;
                $conversation['admin_name'] = $adminName;
            }
        }
        
        $this->view('admin/chat/view', [
            'conversation' => $conversation,
            'messages' => $messages,
            'pageTitle' => 'Chi tiết Chat'
        ]);
    }
    
    /**
     * API: Lấy danh sách conversations
     */
    public function getConversations() {
        try {
            $status = $_GET['status'] ?? null;
            $limit = $_GET['limit'] ?? 50;
            $offset = $_GET['offset'] ?? 0;
            
            $conversations = $this->conversationModel->getAllForAdmin($status, $limit, $offset);
            $unreadCount = $this->conversationModel->countUnreadForAdmin();
            
            return $this->jsonResponse(true, 'Lấy danh sách thành công', [
                'conversations' => $conversations,
                'unreadCount' => $unreadCount
            ]);
        } catch (Exception $e) {
            error_log("Error in getConversations: " . $e->getMessage());
            return $this->jsonResponse(false, 'Lỗi server');
        }
    }
    
    /**
     * API: Gửi tin nhắn từ admin
     */
    public function sendMessage() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['conversation_id']) || empty($data['message'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Thiếu thông tin'], 400);
            }
            
            $messageData = [
                'conversation_id' => $data['conversation_id'],
                'sender_type' => 'admin',
                'sender_id' => $_SESSION['admin_user_id'] ?? null,
                'sender_name' => $_SESSION['admin_name'] ?? 'Admin',
                'message' => $data['message']
            ];
            
            $messageId = $this->messageModel->createMessage($messageData);
            
            if (!$messageId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Không thể gửi tin nhắn'], 500);
            }
            
            // Cập nhật conversation
            $this->conversationModel->updateLastMessage($data['conversation_id']);
            $this->conversationModel->incrementUnreadCount($data['conversation_id'], 'customer');
            
            // Gán admin nếu chưa có
            $conversation = $this->conversationModel->getById($data['conversation_id']);
            if (!$conversation['admin_id']) {
                $this->conversationModel->assignAdmin($data['conversation_id'], $_SESSION['user_id']);
            }
            
            // Lấy tin nhắn vừa tạo
            $message = $this->messageModel->getById($messageId);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => $message
            ]);
        } catch (Exception $e) {
            error_log("Error in sendMessage: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Lỗi server'], 500);
        }
    }
    
    /**
     * API: Lấy tin nhắn mới
     */
    public function getNewMessages() {
        try {
            $conversationId = $_GET['conversation_id'] ?? null;
            $afterId = $_GET['after_id'] ?? 0;
            
            if (!$conversationId) {
                return $this->jsonResponse(false, 'Thiếu conversation_id');
            }
            
            $messages = $this->messageModel->getNewMessages($conversationId, $afterId);
            
            // Đánh dấu tin nhắn từ customer là đã đọc
            if (!empty($messages)) {
                $this->messageModel->markAsRead($conversationId, 'admin');
                $this->conversationModel->resetUnreadCount($conversationId, 'admin');
            }
            
            return $this->jsonResponse(true, 'Success', $messages);
        } catch (Exception $e) {
            error_log("Error in getNewMessages: " . $e->getMessage());
            return $this->jsonResponse(false, 'Lỗi server');
        }
    }
    
    /**
     * API: Cập nhật trạng thái conversation
     */
    public function updateStatus() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['conversation_id']) || empty($data['status'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Thiếu thông tin'], 400);
            }
            
            $result = $this->conversationModel->updateStatus($data['conversation_id'], $data['status']);
            
            if ($result) {
                return $this->jsonResponse(['success' => true]);
            }
            
            return $this->jsonResponse(['success' => false, 'message' => 'Không thể cập nhật'], 500);
        } catch (Exception $e) {
            error_log("Error in updateStatus: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Lỗi server'], 500);
        }
    }
    
    /**
     * API: Gán admin cho conversation
     */
    public function assignToMe() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['conversation_id'])) {
                $this->jsonResponse(false, 'Thiếu conversation_id');
                return;
            }
            
            $adminUserId = $_SESSION['admin_user_id'] ?? null;
            if (!$adminUserId) {
                $this->jsonResponse(false, 'Không tìm thấy thông tin admin');
                return;
            }
            
            $result = $this->conversationModel->assignAdmin($data['conversation_id'], $adminUserId);
            
            if ($result) {
                $this->jsonResponse(true, 'Đã gán admin thành công');
            } else {
                $this->jsonResponse(false, 'Không thể gán admin');
            }
        } catch (Exception $e) {
            error_log("Error in assignToMe: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Lỗi server'], 500);
        }
    }
    
    /**
     * API: Lấy quick replies
     */
    public function getQuickReplies() {
        try {
            $db = Database::getInstance();
            $sql = "SELECT * FROM chat_quick_replies WHERE is_active = 1 ORDER BY sort_order, title";
            $db->query($sql);
            $replies = $db->resultSet();
            
            // Convert objects to arrays
            $repliesArray = array_map(function($obj) { 
                return (array) $obj; 
            }, $replies ?: []);
            
            return $this->jsonResponse(true, 'Success', $repliesArray);
        } catch (Exception $e) {
            error_log("Error in getQuickReplies: " . $e->getMessage());
            return $this->jsonResponse(false, 'Lỗi server');
        }
    }
    
    /**
     * API: Xóa conversation
     */
    public function deleteConversation() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['conversation_id'])) {
                return $this->jsonResponse(false, 'Thiếu thông tin');
            }
            
            $result = $this->conversationModel->deleteConversation($data['conversation_id']);
            
            if ($result) {
                return $this->jsonResponse(true, 'Xóa cuộc hội thoại thành công');
            }
            
            return $this->jsonResponse(false, 'Không thể xóa cuộc hội thoại');
        } catch (Exception $e) {
            error_log("Error in deleteConversation: " . $e->getMessage());
            return $this->jsonResponse(false, 'Lỗi server');
        }
    }
}
