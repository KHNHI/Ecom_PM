<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Simulate user login with different IDs
$testUserIds = [1, 5, 11, 12];

require_once 'configs/config.php';
require_once 'configs/database.php';
require_once 'core/BaseModel.php';
require_once 'app/models/Order.php';
require_once 'app/models/Product.php';

$db = Database::getInstance();

echo "=== CHECKING WHICH USERS HAVE ORDERS ===" . PHP_EOL;

$db->query('SELECT DISTINCT user_id FROM orders WHERE user_id IS NOT NULL ORDER BY user_id');
$usersWithOrders = $db->resultSet();

foreach ($usersWithOrders as $u) {
    $db->query('SELECT COUNT(*) as cnt FROM orders WHERE user_id = :uid');
    $db->bind(':uid', $u->user_id);
    $c = $db->single();
    echo "User " . $u->user_id . ": " . $c->cnt . " orders" . PHP_EOL;
}

echo PHP_EOL . "=== TESTING API FOR USER 11 ===" . PHP_EOL;

// Simulate logged-in session
$_SESSION['user_id'] = 11;

$orderModel = new Order();
$orders = $orderModel->getOrdersByUserId(11);

echo "Orders retrieved: " . count($orders) . PHP_EOL;

if (!empty($orders)) {
    foreach ($orders as $idx => $order) {
        echo PHP_EOL . "Order $idx:";
        echo "  ID: " . $order->order_id;
        echo ", Status: " . $order->order_status;
        echo ", Amount: " . $order->total_amount;
        echo ", Created: " . $order->created_at . PHP_EOL;
        
        $items = $orderModel->getOrderItems($order->order_id);
        echo "  Items: " . count($items) . PHP_EOL;
        
        foreach ($items as $item) {
            echo "    - " . $item->product_name . " x" . $item->quantity . PHP_EOL;
        }
    }
}

echo PHP_EOL . "TEST COMPLETE" . PHP_EOL;
?>
