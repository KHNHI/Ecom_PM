<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'configs/config.php';
require_once 'configs/database.php';
require_once 'core/BaseModel.php';
require_once 'app/models/Order.php';
require_once 'app/models/Product.php';

// Simulate session
$_SESSION['user_id'] = 11; // Test with user_id 11

echo "=== TESTING ORDER API ===" . PHP_EOL;

$db = Database::getInstance();

// Check if user has orders
$db->query('SELECT COUNT(*) as total FROM orders WHERE user_id = :user_id');
$db->bind(':user_id', 11);
$result = $db->single();
echo "User 11 has " . $result->total . " orders" . PHP_EOL;

// Test Order model
$orderModel = new Order();
$orders = $orderModel->getOrdersByUserId(11);

echo "getOrdersByUserId returned: " . count($orders) . " orders" . PHP_EOL;

if (!empty($orders)) {
    $order = $orders[0];
    echo PHP_EOL . "First order:" . PHP_EOL;
    echo "Order ID: " . $order->order_id . PHP_EOL;
    echo "Order Status: " . $order->order_status . PHP_EOL;
    echo "Total Amount: " . $order->total_amount . PHP_EOL;
    
    // Test getOrderItems
    $items = $orderModel->getOrderItems($order->order_id);
    echo "Order has " . count($items) . " items" . PHP_EOL;
    
    if (!empty($items)) {
        $item = $items[0];
        echo PHP_EOL . "First item:" . PHP_EOL;
        echo "Product ID: " . $item->product_id . PHP_EOL;
        echo "Product Name: " . $item->product_name . PHP_EOL;
        echo "Quantity: " . $item->quantity . PHP_EOL;
        echo "Unit Price: " . $item->unit_price_snapshot . PHP_EOL;
    }
}

echo PHP_EOL . "=== TEST COMPLETE ===" . PHP_EOL;
?>
