<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    $content = trim($_POST['content'] ?? '');
    $user_id = (int)$_SESSION['user_id'];
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
        exit;
    }
    
    try {
        $conn->begin_transaction();
        
        // Insert comment
        $sql = "INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $post_id, $user_id, $content);
        
        if ($stmt->execute()) {
            $comment_id = $stmt->insert_id;
            
            // Get user info
            $user_sql = "SELECT username, profile_pic FROM users WHERE id = ?";
            $stmt = $conn->prepare($user_sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_result = $stmt->get_result();
            $user_data = $user_result->fetch_assoc();
            
            // Get exact comment count
            $count_sql = "SELECT COUNT(*) as count FROM comments WHERE post_id = ?";
            $stmt = $conn->prepare($count_sql);
            $stmt->bind_param("i", $post_id);
            $stmt->execute();
            $count = $stmt->get_result()->fetch_assoc()['count'];
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'comment' => [
                    'id' => $comment_id,
                    'content' => $content,
                    'username' => $user_data['username'],
                    'profile_pic' => $user_data['profile_pic'] ?? 'default_avatar.jpg',
                    'created_at' => date('Y-m-d H:i:s')
                ],
                'count' => $count
            ]);
        } else {
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Comment error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error adding comment',
            'debug' => $e->getMessage()
        ]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
?> 