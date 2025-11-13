<?php
/**
 * Test Order Management System
 * Verifies all components are in place
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
require_once 'configs/env.php';
require_once 'configs/config.php';
require_once 'configs/database.php';

echo "=== Order Management System Test ===\n\n";

// 1. Check files exist
$files = [
    'app/controllers/OrderController.php',
    'app/views/customer/pages/profile.php',
    'app/views/customer/pages/order-detail.php',
    'configs/router.php',
    'app/models/Order.php'
];

echo "1. Checking required files:\n";
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path) ? "✓" : "✗";
    echo "   $exists $file\n";
}

// 2. Check database connection
echo "\n2. Checking database connection:\n";
try {
    $db = Database::getInstance();
    $db->query("SELECT COUNT(*) as total FROM orders");
    $db->execute();
    echo "   ✓ Database connection successful\n";
    
    // 3. Check if orders exist
    echo "\n3. Checking test data:\n";
    $db->query("SELECT COUNT(*) as total FROM orders");
    $db->execute();
    $result = $db->single();
    echo "   ✓ Total orders: " . $result->total . "\n";
    
    // 4. Check user 11 orders
    echo "\n4. Checking user 11 orders:\n";
    $db->query("SELECT COUNT(*) as count FROM orders WHERE user_id = 11");
    $db->execute();
    $result = $db->single();
    echo "   ✓ User 11 has " . $result->count . " orders\n";
    
    if ($result->count > 0) {
        $db->query("SELECT order_id, order_status FROM orders WHERE user_id = 11 LIMIT 1");
        $db->execute();
        $order = $db->single();
        echo "   ✓ Sample order ID: " . $order->order_id . ", Status: " . $order->order_status . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
}

// 5. Check router configuration
echo "\n5. Checking router configuration:\n";
$router_file = file_get_contents(__DIR__ . '/configs/router.php');
$has_order_routes = [
    'api/orders/list' => strpos($router_file, "'api/orders/list'") !== false,
    'api/orders/detail' => strpos($router_file, "'api/orders/detail'") !== false,
    'api/orders/cancel' => strpos($router_file, "'api/orders/cancel'") !== false,
    'order-detail' => strpos($router_file, "'order-detail'") !== false
];

foreach ($has_order_routes as $route => $exists) {
    echo "   " . ($exists ? "✓" : "✗") . " Route: $route\n";
}

// 6. Check OrderController methods
echo "\n6. Checking OrderController methods:\n";
$controller_file = file_get_contents(__DIR__ . '/app/controllers/OrderController.php');
$methods = [
    'listUserOrders' => strpos($controller_file, 'public function listUserOrders') !== false,
    'getOrderDetail' => strpos($controller_file, 'public function getOrderDetail') !== false,
    'cancelOrder' => strpos($controller_file, 'public function cancelOrder') !== false,
    'showOrderDetail' => strpos($controller_file, 'public function showOrderDetail') !== false,
    'getProductImage' => strpos($controller_file, 'private function getProductImage') !== false
];

foreach ($methods as $method => $exists) {
    echo "   " . ($exists ? "✓" : "✗") . " Method: $method\n";
}

echo "\n=== Test Complete ===\n";
echo "\nTo test the system:\n";
echo "1. Login as user with ID 11 at: /Ecom_website/signin\n";
echo "2. Go to profile page: /Ecom_website/profile\n";
echo "3. Click 'Đơn hàng của tôi' tab\n";
echo "4. Click 'Chi tiết' on any order to see detail page\n";
?>
