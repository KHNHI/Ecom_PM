<?php
/**
 * AdminService - Business Logic for Admin Operations
 * Xử lý logic chung cho admin panel
 */
class AdminService {
    private $productModel;
    private $categoryModel;
    private $orderModel;
    private $userModel;
    private $reviewModel;
    
    public function __construct() {
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->orderModel = new Order();
        $this->userModel = new User();
        $this->reviewModel = new Review();
    }
    
    /**
     * Lấy thống kê tổng quan cho dashboard
     */
    public function getDashboardStats() {
        return [
            'total_products' => $this->productModel->getTotalCount(),
            'total_categories' => $this->categoryModel->getTotalCount(),
            'total_orders' => $this->orderModel->getTotalCount(),
            'total_customers' => $this->userModel->getTotalCount(),
            'total_reviews' => $this->reviewModel->getTotalCount(),
            'total_revenue' => $this->orderModel->getTotalRevenue()
        ];
    }
    
    /**
     * Lấy dữ liệu cho dashboard
     */
    public function getDashboardData() {
        $stats = $this->getDashboardStats();
        $recentOrders = $this->orderModel->getRecentOrders(5);

        // Enrich recent orders with product names for view (keep logic in service layer)
        if (!empty($recentOrders)) {
            foreach ($recentOrders as $idx => $order) {
                $items = $this->orderModel->getOrderItems($order->order_id);
                if (!empty($items)) {
                    $names = array_map(function($it) {
                        return $it->product_name ?? ($it->name ?? '');
                    }, $items);
                    // Join unique names, limit display length
                    $uniqueNames = array_values(array_unique($names));
                    $productNamesStr = implode(', ', $uniqueNames);
                    if (strlen($productNamesStr) > 80) {
                        $productNamesStr = substr($productNamesStr, 0, 77) . '...';
                    }
                    $order->product_names = $productNamesStr;
                } else {
                    $order->product_names = 'Chưa có sản phẩm';
                }
                // also ensure customer_name/email exist for view
                $order->customer_name = $order->customer_name ?? ($order->full_name ?? null);
                $order->customer_email = $order->customer_email ?? ($order->email ?? null);
                $recentOrders[$idx] = $order;
            }
        }

        return [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'bestSellers' => $this->productModel->getBestSellers(3)
        ];
    }
    
    /**
     * Xử lý flash messages
     */
    public function getFlashMessage() {
        $message = '';
        if (isset($_SESSION['success'])) {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">' . 
                      $_SESSION['success'] . 
                      '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . 
                      $_SESSION['error'] . 
                      '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            unset($_SESSION['error']);
        }
        return $message;
    }
    
    /**
     * Set flash message
     */
    public function setFlashMessage($type, $message) {
        $_SESSION[$type] = $message;
    }
}
