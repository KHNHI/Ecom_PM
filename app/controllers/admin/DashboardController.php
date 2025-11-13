<?php
/**
 * DashboardController - Admin Dashboard
 * Hiển thị thống kê tổng quan cho admin
 */
class DashboardController extends BaseController {
    private $adminService;

    public function __construct() {
        $this->adminService = new AdminService();
    }

    /**
     * Dashboard chính - hiển thị thống kê
     */
    public function index() {
        try {
            // Lấy dữ liệu dashboard từ service
            $dashboardData = $this->adminService->getDashboardData();

            // Pass data to view
            $data = [
                'title' => 'Dashboard',
                'stats' => $dashboardData['stats'],
                'recentOrders' => $dashboardData['recentOrders'],
                'bestSellers' => $dashboardData['bestSellers']
            ];

            // Render admin page with layout
            $this->renderAdminPage('admin/pages/dashboard', $data);

        } catch (Exception $e) {
            // Handle error
            $this->view('admin/error', ['message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }
}
