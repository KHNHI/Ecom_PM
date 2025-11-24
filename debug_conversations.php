<?php
session_start();

require_once __DIR__ . '/configs/config.php';
require_once __DIR__ . '/configs/database.php';
require_once __DIR__ . '/app/models/ChatConversation.php';

echo "<h2>Debug Conversations</h2>";

$conversationModel = new ChatConversation();
$conversations = $conversationModel->getAllForAdmin(null, 10, 0);

echo "<h3>Raw Query Result:</h3>";
echo "<pre>";
print_r($conversations);
echo "</pre>";

echo "<h3>Formatted List:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>user_id</th><th>user_name</th><th>session_id</th><th>Display Name</th></tr>";

foreach ($conversations as $conv) {
    $displayName = $conv['user_name'] ?? $conv['customer_name'] ?? 'Khách';
    echo "<tr>";
    echo "<td>{$conv['conversation_id']}</td>";
    echo "<td>" . ($conv['user_id'] ?? 'NULL') . "</td>";
    echo "<td>" . ($conv['user_name'] ?? 'NULL') . "</td>";
    echo "<td>" . ($conv['session_id'] ?? 'NULL') . "</td>";
    echo "<td><strong>{$displayName}</strong></td>";
    echo "</tr>";
}

echo "</table>";

// Check specific user
echo "<h3>Check User ID 31:</h3>";
require_once __DIR__ . '/app/models/User.php';
$userModel = new User();
$user = $userModel->getById(31);
echo "<pre>";
print_r($user);
echo "</pre>";
?>
