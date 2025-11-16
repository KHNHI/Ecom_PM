<?php
session_start();
$_SESSION['user_id'] = 31;

require 'configs/config.php';
require 'configs/database.php';
require 'core/BaseModel.php';
require 'app/models/Notification.php';

echo "=== Creating Test Notifications ===\n\n";

$model = new Notification();

// Create order notifications
$data = [
    'user_id' => 31,
    'title' => 'Đơn hàng #50 đang giao hàng',
    'message' => 'Đơn hàng của bạn đang được giao bởi đơn vị vận chuyển.',
    'type' => 'order_status',
    'ref_type' => 'order',
    'ref_id' => 50
];

$id1 = $model->create($data);
echo "✓ Created notification 1: ID=$id1\n";

// Create collection notification
$data = [
    'user_id' => 31,
    'title' => 'Bộ sưu tập mới: Charm Vàng',
    'message' => 'Khám phá bộ sưu tập Charm Vàng độc đáo',
    'type' => 'collection',
    'ref_type' => 'collection',
    'ref_id' => 5
];

$id2 = $model->create($data);
echo "✓ Created notification 2: ID=$id2\n";

// Create promotion notification
$data = [
    'user_id' => 31,
    'title' => 'Khuyến mãi: Giảm 20%',
    'message' => 'Giảm 20% cho tất cả sản phẩm trong tuần này',
    'type' => 'promotion',
    'ref_type' => null,
    'ref_id' => null
];

$id3 = $model->create($data);
echo "✓ Created notification 3: ID=$id3\n";

echo "\nChecking notifications:\n";
$unread = $model->getUnreadByUserId(31, 10);
echo "Unread: " . count($unread) . " items\n";

$all = $model->getByUserId(31, 20, 0);
echo "All: " . count($all) . " items\n";

echo "\n=== Complete ===\n";
