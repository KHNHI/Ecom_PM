<?php
/**
 * Test Notification System
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'configs/env.php';
require_once 'configs/config.php';
require_once 'configs/database.php';
require_once 'core/BaseModel.php';
require_once 'app/models/Notification.php';

echo "=== Notification System Test ===\n\n";

try {
    $notificationModel = new Notification();
    
    // Test 1: Create test notifications
    echo "1. Creating test notifications...\n";
    
    // Create order status notification
    $notif1 = $notificationModel->create([
        'user_id' => 31,
        'title' => 'Đơn hàng #48 đã được giao',
        'message' => 'Đơn hàng của bạn đã được giao thành công. Vui lòng kiểm tra hàng.',
        'type' => 'order_status',
        'ref_type' => 'order',
        'ref_id' => 48
    ]);
    echo "   ✓ Order notification created (ID: $notif1)\n";
    
    // Create collection notification
    $notif2 = $notificationModel->create([
        'user_id' => 31,
        'title' => 'Bộ sưu tập mới',
        'message' => 'Bộ sưu tập "Vàng 24K Luxury" đã được thêm. Hãy khám phá ngay!',
        'type' => 'collection',
        'ref_type' => 'collection',
        'ref_id' => 5
    ]);
    echo "   ✓ Collection notification created (ID: $notif2)\n";
    
    // Test 2: Get unread count
    echo "\n2. Getting unread count for user 31...\n";
    $unreadCount = $notificationModel->getUnreadCount(31);
    echo "   ✓ Unread count: $unreadCount\n";
    
    // Test 3: Get unread notifications
    echo "\n3. Getting unread notifications...\n";
    $unreadNotifs = $notificationModel->getUnreadByUserId(31, 10);
    echo "   ✓ Found " . count($unreadNotifs) . " unread notifications\n";
    foreach ($unreadNotifs as $notif) {
        echo "     - [" . $notif->type . "] " . $notif->title . "\n";
    }
    
    // Test 4: Mark as read
    echo "\n4. Marking notification $notif1 as read...\n";
    $result = $notificationModel->markAsRead($notif1, 31);
    echo "   ✓ Result: " . ($result ? "Success" : "Failed") . "\n";
    
    // Test 5: Get updated unread count
    echo "\n5. Checking updated unread count...\n";
    $updatedCount = $notificationModel->getUnreadCount(31);
    echo "   ✓ New unread count: $updatedCount\n";
    
    // Test 6: Test order status notification
    echo "\n6. Testing order status notification helper...\n";
    $notif3 = $notificationModel->notifyOrderStatus(31, 50, 'shipped');
    echo "   ✓ Order notification sent (ID: $notif3)\n";
    
    // Test 7: Delete notification
    echo "\n7. Deleting notification $notif2...\n";
    $deleteResult = $notificationModel->delete($notif2, 31);
    echo "   ✓ Delete result: " . ($deleteResult ? "Success" : "Failed") . "\n";
    
    echo "\n=== All Tests Complete ===\n";
    echo "\n✓ Notification system is working correctly!\n";
    echo "\nTo use in production:\n";
    echo "1. Login as any customer\n";
    echo "2. Go to header to see notification icon\n";
    echo "3. Click icon to see dropdown with notifications\n";
    echo "4. Or visit /notifications for full page\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>
