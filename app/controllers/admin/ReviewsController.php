<?php
/**
 * ReviewController - Admin Review Management
 */
class ReviewsController extends BaseController {
    private $reviewModel;

    public function __construct() {
        $this->reviewModel = $this->model('Review');
    }

    public function index() {
        try {
            // Xử lý các action nếu có
            if (isset($_GET['action']) && isset($_GET['id'])) {
                $this->handleAction($_GET['action'], $_GET['id']);
            }

            // Lấy dữ liệu reviews với thông tin chi tiết
            $reviews = $this->reviewModel->getAllWithDetails();
            
            // Lấy thống kê reviews
            $stats = $this->reviewModel->getReviewStats();

            $data = [
                'title' => 'Đánh Giá',
                'reviews' => $reviews,
                'stats' => $stats,
                'flashMessage' => $this->getFlashMessage()
            ];

            // Render admin page with layout
            $this->renderAdminPage('admin/pages/reviews', $data);

        } catch (Exception $e) {
            $this->view('admin/error', ['message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    public function approve($id) {
        try {
            if ($this->reviewModel->updateStatus($id, 'approved')) {
                $_SESSION['success'] = 'Duyệt đánh giá thành công!';
            } else {
                $_SESSION['error'] = 'Có lỗi xảy ra khi duyệt đánh giá!';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
        }
        
        $this->index();
    }

    public function reject($id) {
        try {
            if ($this->reviewModel->updateStatus($id, 'rejected')) {
                $_SESSION['success'] = 'Từ chối đánh giá thành công!';
            } else {
                $_SESSION['error'] = 'Có lỗi xảy ra khi từ chối đánh giá!';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
        }
        
        $this->index();
    }

    public function delete($id) {
        try {
            if ($this->reviewModel->delete($id)) {
                $_SESSION['success'] = 'Xóa đánh giá thành công!';
            } else {
                $_SESSION['error'] = 'Có lỗi xảy ra khi xóa đánh giá!';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
        }
        
        $this->index();
    }

    /**
     * Xử lý các action (approve, reject, hide)
     */
    private function handleAction($action, $id) {
        try {
            switch ($action) {
                case 'approve':
                    if ($this->reviewModel->updateStatus($id, 'approved')) {
                        $_SESSION['success'] = 'Duyệt đánh giá thành công!';
                    } else {
                        $_SESSION['error'] = 'Có lỗi xảy ra khi duyệt đánh giá!';
                    }
                    break;
                    
                case 'reject':
                    if ($this->reviewModel->updateStatus($id, 'rejected')) {
                        $_SESSION['success'] = 'Từ chối đánh giá thành công!';
                    } else {
                        $_SESSION['error'] = 'Có lỗi xảy ra khi từ chối đánh giá!';
                    }
                    break;
                    
                case 'hide':
                    if ($this->reviewModel->updateStatus($id, 'hidden')) {
                        $_SESSION['success'] = 'Ẩn đánh giá thành công!';
                    } else {
                        $_SESSION['error'] = 'Có lỗi xảy ra khi ẩn đánh giá!';
                    }
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
        }
    }

    /**
     * Lấy flash message
     */
    private function getFlashMessage() {
        $message = '';
        if (isset($_SESSION['success'])) {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $_SESSION['error'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            unset($_SESSION['error']);
        }
        return $message;
    }
}
