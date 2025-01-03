<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug log
error_log("Like handler called - POST data: " . print_r($_POST, true));

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    $user_id = (int)$_SESSION['user_id'];
    
    error_log("Processing like - Post ID: $post_id, User ID: $user_id");
    
    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit;
    }
    
    // Verify post exists
    $post_check = $conn->prepare("SELECT id FROM post WHERE id = ?");
    $post_check->bind_param("i", $post_id);
    $post_check->execute();
    if ($post_check->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }
    
    try {
        $conn->begin_transaction();
        
        // Check if already liked
        $check_sql = "SELECT id FROM likes WHERE post_id = ? AND user_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Unlike
            $sql = "DELETE FROM likes WHERE post_id = ? AND user_id = ?";
            $liked = false;
        } else {
            // Like
            $sql = "INSERT INTO likes (post_id, user_id) VALUES (?, ?)";
            $liked = true;
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $post_id, $user_id);
        
        if ($stmt->execute()) {
            // Get updated like count
            $count_sql = "SELECT COUNT(*) as count FROM likes WHERE post_id = ?";
            $stmt = $conn->prepare($count_sql);
            $stmt->bind_param("i", $post_id);
            $stmt->execute();
            $count = $stmt->get_result()->fetch_assoc()['count'];
            
            $conn->commit();
            
            $response = [
                'success' => true,
                'liked' => $liked,
                'count' => $count
            ];
            error_log("Like operation successful: " . print_r($response, true));
            echo json_encode($response);
        } else {
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Like error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Error processing like',
            'debug' => $e->getMessage()
        ]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
?> 