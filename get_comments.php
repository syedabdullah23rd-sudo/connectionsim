<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
    
    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit;
    }
    
    try {
        $sql = "SELECT c.*, u.username, u.profile_pic 
                FROM comments c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.post_id = ? 
                ORDER BY c.created_at DESC";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = [
                'id' => $row['id'],
                'content' => $row['content'],
                'username' => $row['username'],
                'profile_pic' => $row['profile_pic'] ?? 'default_avatar.jpg',
                'created_at' => $row['created_at']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'comments' => $comments
        ]);
    } catch (Exception $e) {
        error_log("Error fetching comments: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching comments'
        ]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
?> 