<?php
session_start();

require_once __DIR__ . '/configs/config.php';
require_once __DIR__ . '/configs/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Update Conversations</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #004085; background: #cce5ff; padding: 10px; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        tr:hover { background: #f5f5f5; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
        .btn:hover { background: #5568d3; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h2>🔄 Cập nhật User ID cho Conversations</h2>";

try {
    $db = Database::getInstance();
    
    // Lấy tất cả tin nhắn từ customer có sender_id
    $sql = "SELECT DISTINCT 
            cm.conversation_id,
            cm.sender_id,
            cm.sender_name,
            c.session_id,
            c.user_id as current_user_id
            FROM chat_messages cm
            JOIN chat_conversations c ON cm.conversation_id = c.conversation_id
            WHERE cm.sender_type = 'customer' 
            AND cm.sender_id IS NOT NULL
            AND c.user_id IS NULL
            ORDER BY cm.conversation_id";
    
    $db->query($sql);
    $messages = $db->resultSet();
    
    if (empty($messages)) {
        echo "<div class='info'>✅ Không có conversation nào cần cập nhật. Tất cả đã có user_id!</div>";
    } else {
        echo "<div class='info'>📋 Tìm thấy " . count($messages) . " conversation(s) cần cập nhật user_id</div>";
        
        echo "<table>";
        echo "<tr><th>Conversation ID</th><th>Session ID</th><th>User ID sẽ gán</th><th>Tên User</th><th>Kết quả</th></tr>";
        
        $updated = 0;
        $errors = 0;
        
        foreach ($messages as $msg) {
            echo "<tr>";
            echo "<td>#{$msg->conversation_id}</td>";
            echo "<td>" . substr($msg->session_id, 0, 15) . "...</td>";
            echo "<td>{$msg->sender_id}</td>";
            echo "<td>{$msg->sender_name}</td>";
            
            // Cập nhật user_id
            $updateSql = "UPDATE chat_conversations 
                         SET user_id = :user_id 
                         WHERE conversation_id = :conversation_id";
            $db->query($updateSql);
            $db->bind(':user_id', $msg->sender_id);
            $db->bind(':conversation_id', $msg->conversation_id);
            
            if ($db->execute()) {
                echo "<td style='color: green;'>✅ Đã cập nhật</td>";
                $updated++;
            } else {
                echo "<td style='color: red;'>❌ Lỗi</td>";
                $errors++;
            }
            
            echo "</tr>";
        }
        
        echo "</table>";
        
        if ($updated > 0) {
            echo "<div class='success'>✅ Đã cập nhật thành công {$updated} conversation(s)!</div>";
        }
        
        if ($errors > 0) {
            echo "<div class='error'>❌ Có {$errors} conversation(s) gặp lỗi khi cập nhật</div>";
        }
    }
    
    // Hiển thị danh sách sau khi cập nhật
    echo "<h3>📊 Danh sách Conversations sau cập nhật:</h3>";
    
    $checkSql = "SELECT c.conversation_id, c.user_id, c.session_id, u.name as user_name, u.email
                FROM chat_conversations c
                LEFT JOIN users u ON c.user_id = u.user_id
                ORDER BY c.conversation_id DESC
                LIMIT 10";
    $db->query($checkSql);
    $conversations = $db->resultSet();
    
    echo "<table>";
    echo "<tr><th>ID</th><th>User ID</th><th>Tên User</th><th>Email</th><th>Session ID</th></tr>";
    
    foreach ($conversations as $conv) {
        echo "<tr>";
        echo "<td>#{$conv->conversation_id}</td>";
        echo "<td>" . ($conv->user_id ?: '<span style="color: red;">NULL</span>') . "</td>";
        echo "<td>" . ($conv->user_name ?: '<span style="color: gray;">Khách</span>') . "</td>";
        echo "<td>" . ($conv->email ?: '-') . "</td>";
        echo "<td>" . substr($conv->session_id, 0, 15) . "...</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<a href='/Ecom_PM/admin/index.php?url=chat' class='btn'>🔙 Quay lại Chat Management</a>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Lỗi: " . $e->getMessage() . "</div>";
    error_log("Update conversations error: " . $e->getMessage());
}

echo "</div></body></html>";
?>
