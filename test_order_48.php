<?php
/**
 * Test order #48 data
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
require_once 'configs/env.php';
require_once 'configs/config.php';
require_once 'configs/database.php';
require_once 'core/BaseModel.php';
require_once 'app/models/Order.php';
require_once 'app/models/Product.php';

echo "=== Order #48 Test ===\n\n";

try {
    $db = Database::getInstance();
    
    // Get order 48
    echo "1. Getting order 48:\n";
    $db->query("SELECT * FROM orders WHERE order_id = 48");
    $db->execute();
    $order = $db->single();
    
    if ($order) {
        echo "   Order found:\n";
        echo "   - order_id: " . $order->order_id . " (type: " . gettype($order->order_id) . ")\n";
        echo "   - user_id: " . $order->user_id . "\n";
        echo "   - order_status: " . $order->order_status . "\n";
        echo "   - created_at: " . $order->created_at . "\n";
        
        // Get order items
        echo "\n2. Getting order items for order 48:\n";
        $db->query("SELECT oi.*, p.name as product_name 
                   FROM order_items oi
                   JOIN products p ON oi.product_id = p.product_id
                   WHERE oi.order_id = 48");
        $db->execute();
        $items = $db->resultSet();
        
        if ($items) {
            echo "   Found " . count($items) . " items:\n";
            foreach ($items as $item) {
                echo "     - Product: " . $item->product_name . " (ID: " . $item->product_id . ")\n";
                echo "       Quantity: " . $item->quantity . "\n";
            }
        } else {
            echo "   No items found!\n";
        }
    } else {
        echo "   Order 48 not found!\n";
    }
    
    echo "\n=== Test Complete ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>
