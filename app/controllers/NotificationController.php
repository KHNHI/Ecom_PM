<?php

/**
 * NotificationController
 * Handles notification-related requests for customers
 */
class NotificationController extends BaseController {
    private $notificationModel;

    public function __construct() {
        $this->notificationModel = new Notification();
        SessionHelper::start();
    }

    /**
     * API: Get recent notifications (unread + recent read) for header dropdown
     */
    public function getRecent() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                $this->jsonResponse(false, 'Vui lòng đăng nhập');
                return;
            }

            $limit = (int)($_GET['limit'] ?? 10);
            // Get all recent notifications (both read and unread) sorted by date
            // This way, users can see notifications even after marking them as read
            $notifications = $this->notificationModel->getByUserId($userId, $limit, 0);

            $this->jsonResponse(true, 'Lấy thông báo thành công', $notifications);
        } catch (Exception $e) {
            error_log("NotificationController getRecent Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * API: Get unread notifications for logged-in user
     */
    public function getUnread() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                $this->jsonResponse(false, 'Vui lòng đăng nhập');
                return;
            }

            $limit = (int)($_GET['limit'] ?? 10);
            $notifications = $this->notificationModel->getUnreadByUserId($userId, $limit);

            $this->jsonResponse(true, 'Lấy thông báo thành công', $notifications);
        } catch (Exception $e) {
            error_log("NotificationController getUnread Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * API: Get unread count for header badge
     */
    public function getUnreadCount() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                $this->jsonResponse(false, 'Vui lòng đăng nhập');
                return;
            }

            $count = $this->notificationModel->getUnreadCount($userId);

            $this->jsonResponse(true, 'Lấy số thông báo thành công', ['count' => $count]);
        } catch (Exception $e) {
            error_log("NotificationController getUnreadCount Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * API: Get all notifications with pagination
     */
    public function getAll() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                $this->jsonResponse(false, 'Vui lòng đăng nhập');
                return;
            }

            $page = max((int)($_GET['page'] ?? 1), 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;

            $notifications = $this->notificationModel->getByUserId($userId, $limit, $offset);

            $this->jsonResponse(true, 'Lấy danh sách thông báo thành công', $notifications);
        } catch (Exception $e) {
            error_log("NotificationController getAll Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * API: Mark single notification as read
     */
    public function markAsRead() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                $this->jsonResponse(false, 'Vui lòng đăng nhập');
                return;
            }

            $notificationId = (int)($_POST['notification_id'] ?? 0);
            if (!$notificationId) {
                $this->jsonResponse(false, 'Thiếu ID thông báo');
                return;
            }

            $result = $this->notificationModel->markAsRead($notificationId, $userId);

            if ($result) {
                $this->jsonResponse(true, 'Đánh dấu đã đọc thành công');
            } else {
                $this->jsonResponse(false, 'Không tìm thấy thông báo');
            }
        } catch (Exception $e) {
            error_log("NotificationController markAsRead Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * API: Mark all notifications as read
     */
    public function markAllAsRead() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                $this->jsonResponse(false, 'Vui lòng đăng nhập');
                return;
            }

            $result = $this->notificationModel->markAllAsRead($userId);

            if ($result) {
                $this->jsonResponse(true, 'Đánh dấu tất cả đã đọc thành công');
            } else {
                $this->jsonResponse(false, 'Có lỗi khi đánh dấu');
            }
        } catch (Exception $e) {
            error_log("NotificationController markAllAsRead Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * API: Delete notification
     */
    public function delete() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                $this->jsonResponse(false, 'Vui lòng đăng nhập');
                return;
            }

            $notificationId = (int)($_POST['notification_id'] ?? 0);
            if (!$notificationId) {
                $this->jsonResponse(false, 'Thiếu ID thông báo');
                return;
            }

            $result = $this->notificationModel->delete($notificationId, $userId);

            if ($result) {
                $this->jsonResponse(true, 'Xóa thông báo thành công');
            } else {
                $this->jsonResponse(false, 'Không tìm thấy thông báo');
            }
        } catch (Exception $e) {
            error_log("NotificationController delete Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * API: Delete all notifications
     */
    public function deleteAll() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                $this->jsonResponse(false, 'Vui lòng đăng nhập');
                return;
            }

            $result = $this->notificationModel->deleteAll($userId);

            if ($result) {
                $this->jsonResponse(true, 'Xóa tất cả thông báo thành công');
            } else {
                $this->jsonResponse(false, 'Có lỗi khi xóa');
            }
        } catch (Exception $e) {
            error_log("NotificationController deleteAll Error: " . $e->getMessage());
            $this->jsonResponse(false, 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Show full notifications page
     */
    public function showPage() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                header('Location: /Ecom_PM/signin');
                exit;
            }

            // Load config and helpers for use in included files
            require_once __DIR__ . '/../../configs/config.php';

            // Render full HTML page
            ?>
            <!DOCTYPE html>
            <html lang="vi">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Thông báo - Jewelry Store</title>
                
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
                <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
                <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
                <link href="<?= asset('css/css.css?v=' . time()) ?>" rel="stylesheet">
                
                <style>
                    body {
                        padding-top: 120px;
                    }
                </style>
            </head>
            <body>
                <?php require $_SERVER['DOCUMENT_ROOT'] . '/Ecom_PM/app/views/customer/components/header.php'; ?>
                
                <main class="container">
                    <?php require $_SERVER['DOCUMENT_ROOT'] . '/Ecom_PM/app/views/customer/pages/notifications.php'; ?>
                </main>
                
                <?php require $_SERVER['DOCUMENT_ROOT'] . '/Ecom_PM/app/views/customer/components/footer.php'; ?>
                
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
            </body>
            </html>
            <?php
        } catch (Exception $e) {
            error_log("NotificationController showPage Error: " . $e->getMessage());
            die("Lỗi: " . $e->getMessage());
        }
    }
}
?>
