<?php
session_start();

echo "<h2>Debug Chat Session</h2>";

echo "<h3>Current Session:</h3>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";
echo "user_name: " . (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'NOT SET') . "\n";
echo "email: " . (isset($_SESSION['email']) ? $_SESSION['email'] : 'NOT SET') . "\n";
echo "\nFull Session:\n";
print_r($_SESSION);
echo "</pre>";

require_once __DIR__ . '/configs/config.php';
require_once __DIR__ . '/configs/database.php';
require_once __DIR__ . '/app/models/ChatConversation.php';
require_once __DIR__ . '/app/models/ChatMessage.php';

$conversationModel = new ChatConversation();
$messageModel = new ChatMessage();

// Tìm conversation theo session
$userId = $_SESSION['user_id'] ?? null;
$sessionId = session_id();

echo "<h3>Looking for conversation with:</h3>";
echo "<pre>";
echo "user_id: " . ($userId ?: 'NULL') . "\n";
echo "session_id: " . $sessionId . "\n";
echo "</pre>";

$conversation = $conversationModel->findOrCreateForUser($userId, $sessionId);

if ($conversation) {
    echo "<h3>Found/Created Conversation:</h3>";
    echo "<pre>";
    print_r($conversation);
    echo "</pre>";
    
    // Lấy tin nhắn mới nhất
    $messages = $messageModel->getByConversation($conversation['conversation_id'], 5);
    
    echo "<h3>Latest 5 Messages:</h3>";
    echo "<pre>";
    print_r($messages);
    echo "</pre>";
}
?>
