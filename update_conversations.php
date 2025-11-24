<?php
session_start();

require_once __DIR__ . '/configs/config.php';
require_once __DIR__ . '/configs/database.php';

echo "<h2>Update Old Conversations</h2>";

try {
    $db = Database::getInstance();
    
    // Lấy tất cả conversation không có user_id nhưng có session_id
    $sql = "SELECT c.conversation_id, c.session_id, 
            (SELECT sender_id FROM chat_messages 
             WHERE conversation_id = c.conversation_id 
             AND sender_id IS NOT NULL 
             ORDER BY created_at ASC 
             LIMIT 1) as first_sender_id
            FROM chat_conversations c
            WHERE c.user_id IS NULL 
            AND c.session_id IS NOT NULL";
    
    $db->query($sql);
    $conversations = $db->resultSet();
    
    echo "<h3>Found " . count($conversations) . " conversations to update</h3>";
    
    $updated = 0;
    foreach ($conversations as $conv) {
        if ($conv->first_sender_id) {
            $updateSql = "UPDATE chat_conversations 
                         SET user_id = :user_id 
                         WHERE conversation_id = :conversation_id";
            $db->query($updateSql);
            $db->bind(':user_id', $conv->first_sender_id);
            $db->bind(':conversation_id', $conv->conversation_id);
            
            if ($db->execute()) {
                $updated++;
                echo "<p>✅ Updated conversation #{$conv->conversation_id} with user_id = {$conv->first_sender_id}</p>";
            }
        }
    }
    
    echo "<h3>✅ Updated {$updated} conversations successfully!</h3>";
    echo "<p><a href='/Ecom_PM/admin/index.php?url=chat'>Go to Chat Management</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
