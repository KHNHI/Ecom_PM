<?php
/**
 * Admin Front Controller
 * Tương tự index.php nhưng dành cho admin panel
 */

date_default_timezone_set("Asia/Ho_Chi_Minh");
session_start();
ob_start(); // Tránh lỗi khi dùng hàm liên quan header, cookie

// Check admin authentication (except login/logout routes)
$requestUri = $_SERVER['REQUEST_URI'];
$urlPath = parse_url($requestUri, PHP_URL_PATH);

// Chỉ cho phép truy cập login và logout routes mà không cần auth
$isLoginRoute = (strpos($urlPath, '/admin/login') !== false);
$isLogoutRoute = (strpos($urlPath, '/admin/logout') !== false);

// Với các route khác, bắt buộc phải đăng nhập
if (!$isLoginRoute && !$isLogoutRoute) {
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        // Redirect về login page
        header('Location: /Ecom_website/admin/login');
        exit;
    }
}

// DEBUG: Log all requests
error_log("ADMIN REQUEST: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);
error_log("GET params: " . json_encode($_GET));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data keys: " . json_encode(array_keys($_POST)));
    error_log("FILES data: " . json_encode(isset($_FILES) ? array_keys($_FILES) : []));
}

// Include configuration files
foreach (glob(__DIR__ . '/../configs/*.php') as $file) {
    require_once $file;
}

// Include core files
foreach (glob(__DIR__ . '/../core/*.php') as $file) {
    require_once $file;
}

// Nạp các file model
foreach (glob(__DIR__ . '/../app/models/*.php') as $file) {
    require_once $file;
}

// Nạp các file service
foreach (glob(__DIR__ . '/../app/services/*.php') as $file) {
    require_once $file;
}

// Register error handlers
ErrorHandler::register();

// Khởi tạo AdminRouter để xử lý URL admin
$adminRouter = new AdminRouter();
?>
