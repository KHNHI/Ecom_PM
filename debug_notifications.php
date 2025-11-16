<?php
session_start();
$_SESSION['user_id'] = 31;

require 'configs/config.php';
require 'configs/database.php';
require 'core/BaseModel.php';
require 'app/models/Notification.php';
require 'helpers/session_helper.php';

echo "=== Debug Notification System ===\n\n";

// Test 1: Check database connection
echo "1. Database Connection\n";
try {
    $db = Database::getInstance();
    echo "   ✓ Database connected\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    exit;
}

// Test 2: Check if notifications table exists
echo "\n2. Notifications Table\n";
$db->query("SHOW TABLES LIKE 'notifications'");
$tables = $db->resultSet();
if ($tables) {
    echo "   ✓ notifications table exists\n";
} else {
    echo "   ✗ notifications table not found\n";
}

// Test 3: Count notifications
echo "\n3. Notification Count\n";
$db->query("SELECT COUNT(*) as total, SUM(IF(is_read=0,1,0)) as unread FROM notifications WHERE user_id = 31");
$count = $db->single();
echo "   Total: " . $count->total . "\n";
echo "   Unread: " . $count->unread . "\n";

// Test 4: List notifications
echo "\n4. Notification List (User 31)\n";
$db->query("SELECT notification_id, title, is_read, created_at FROM notifications WHERE user_id = 31 ORDER BY created_at DESC LIMIT 5");
$notifs = $db->resultSet();
if ($notifs) {
    foreach ($notifs as $n) {
        $status = $n->is_read ? "READ" : "UNREAD";
        echo "   - ID: {$n->notification_id}, Title: {$n->title}, Status: {$status}\n";
    }
} else {
    echo "   No notifications found\n";
}

// Test 5: Check API via Notification model
echo "\n5. Notification Model Test\n";
$model = new Notification();
$unread = $model->getUnreadByUserId(31, 10);
echo "   getUnreadByUserId(31, 10): " . count($unread) . " items\n";

$all = $model->getByUserId(31, 20, 0);
echo "   getByUserId(31, 20, 0): " . count($all) . " items\n";

$count = $model->getUnreadCount(31);
echo "   getUnreadCount(31): " . $count . "\n";

// Test 6: Test mark as read
if ($notifs && count($notifs) > 0) {
    echo "\n6. Test Mark As Read\n";
    $firstNotif = $notifs[0];
    echo "   Testing with notification ID: " . $firstNotif->notification_id . "\n";
    $result = $model->markAsRead($firstNotif->notification_id, 31);
    echo "   Result: " . ($result ? "SUCCESS" : "FAILED") . "\n";
}

echo "\n=== Debug Complete ===\n";
