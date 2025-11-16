<?php
/**
 * Setup Notification System Database Tables
 * Run this script once to create the notification tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'configs/env.php';
require_once 'configs/config.php';
require_once 'configs/database.php';

echo "=== Setting up Notification System ===\n\n";

try {
    $db = Database::getInstance();
    
    // Create notifications table
    echo "1. Creating notifications table...\n";
    $sql1 = "CREATE TABLE IF NOT EXISTS `notifications` (
      `notification_id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `title` varchar(255) NOT NULL,
      `message` text NOT NULL,
      `type` enum('order_status','collection','promotion','system') NOT NULL DEFAULT 'system',
      `ref_type` varchar(50) DEFAULT NULL COMMENT 'order, collection, product, etc',
      `ref_id` int(11) DEFAULT NULL COMMENT 'id của entity được reference',
      `is_read` tinyint(1) NOT NULL DEFAULT '0',
      `read_at` datetime DEFAULT NULL,
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`notification_id`),
      KEY `user_id` (`user_id`),
      KEY `is_read` (`is_read`),
      KEY `created_at` (`created_at`),
      CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->query($sql1);
    $db->execute();
    echo "   ✓ notifications table created\n";
    
    // Create notification_actions table
    echo "\n2. Creating notification_actions table...\n";
    $sql2 = "CREATE TABLE IF NOT EXISTS `notification_actions` (
      `action_id` int(11) NOT NULL AUTO_INCREMENT,
      `notification_id` int(11) NOT NULL,
      `user_id` int(11) NOT NULL,
      `action_type` enum('read','delete','archive') NOT NULL,
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`action_id`),
      KEY `notification_id` (`notification_id`),
      KEY `user_id` (`user_id`),
      CONSTRAINT `notification_actions_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE,
      CONSTRAINT `notification_actions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->query($sql2);
    $db->execute();
    echo "   ✓ notification_actions table created\n";
    
    echo "\n=== Setup Complete ===\n";
    echo "\n✓ Database tables created successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Check files created:\n";
    echo "   - app/models/Notification.php\n";
    echo "   - app/controllers/NotificationController.php\n";
    echo "   - app/views/customer/pages/notifications.php\n";
    echo "2. Routes updated in configs/router.php\n";
    echo "3. Header updated with notification icon\n";
    echo "4. Test by logging in and checking the bell icon\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    die();
}
?>
