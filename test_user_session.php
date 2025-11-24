<?php
session_start();

echo "<h2>Session Information</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/configs/config.php';
    require_once __DIR__ . '/configs/database.php';
    require_once __DIR__ . '/app/models/User.php';
    
    $userModel = new User();
    $user = $userModel->getById($_SESSION['user_id']);
    
    echo "<h2>User Information</h2>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
}
?>
