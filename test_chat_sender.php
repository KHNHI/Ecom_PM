<?php
session_start();

require_once __DIR__ . '/configs/config.php';
require_once __DIR__ . '/configs/database.php';
require_once __DIR__ . '/app/models/User.php';
require_once __DIR__ . '/app/models/ChatMessage.php';
require_once __DIR__ . '/app/models/ChatConversation.php';

echo "<h2>Testing Chat Sender Name</h2>";

// Get session info
echo "<h3>1. Session Info:</h3>";
echo "<pre>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
echo "user_name: " . ($_SESSION['user_name'] ?? 'Not set') . "\n";
echo "email: " . ($_SESSION['email'] ?? 'Not set') . "\n";
echo "</pre>";

// Get user from database
if (isset($_SESSION['user_id'])) {
    $userModel = new User();
    $user = $userModel->getById($_SESSION['user_id']);
    
    echo "<h3>2. User from Database:</h3>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    // Convert to array if needed
    $userArray = is_object($user) ? (array) $user : $user;
    
    echo "<h3>3. User as Array:</h3>";
    echo "<pre>";
    print_r($userArray);
    echo "</pre>";
    
    echo "<h3>4. Sender Name would be:</h3>";
    echo "<pre>";
    echo $userArray['name'] ?? 'Khách';
    echo "</pre>";
    
    // Test getting latest messages
    $messageModel = new ChatMessage();
    $conversationModel = new ChatConversation();
    
    // Get or create conversation
    $conversation = $conversationModel->findOrCreateForUser($_SESSION['user_id'], session_id());
    
    if ($conversation) {
        echo "<h3>5. Conversation:</h3>";
        echo "<pre>";
        print_r($conversation);
        echo "</pre>";
        
        // Get latest messages
        $messages = $messageModel->getByConversation($conversation['conversation_id'], 5);
        
        echo "<h3>6. Latest 5 Messages:</h3>";
        echo "<pre>";
        print_r($messages);
        echo "</pre>";
    }
} else {
    echo "<p style='color: red;'>User not logged in!</p>";
}
?>
