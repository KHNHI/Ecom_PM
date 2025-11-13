<?php
/**
 * Test API listUserOrders for user 31
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
require_once 'configs/env.php';
require_once 'configs/config.php';
require_once 'configs/database.php';
require_once 'core/BaseModel.php';
require_once 'core/BaseController.php';
require_once 'app/models/Order.php';
require_once 'app/models/Product.php';
require_once 'helpers/session_helper.php';
require_once 'app/controllers/OrderController.php';

echo "=== Testing API listUserOrders ===\n\n";

try {
    // Simulate user session
    SessionHelper::start();
    $_SESSION['user_id'] = 31; // User who has order 48
    
    // Create controller
    $controller = new OrderController();
    
    // Capture output
    ob_start();
    $controller->listUserOrders();
    $json_output = ob_get_clean();
    
    // Parse JSON
    $result = json_decode($json_output, true);
    
    echo "API Response:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    if ($result['success'] && $result['data']) {
        echo "\nFirst order details:\n";
        $firstOrder = $result['data'][0];
        echo "- order_id: " . $firstOrder['order_id'] . " (type from JSON: " . gettype($firstOrder['order_id']) . ")\n";
        echo "- items count: " . count($firstOrder['items']) . "\n";
        if (!empty($firstOrder['items'])) {
            echo "- first item:\n";
            print_r($firstOrder['items'][0]);
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>
