<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$post_id = $data['post_id'] ?? 0;
$content = trim($data['content'] ?? '');

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Content cannot be empty']);
    exit;
}

try {
    // Verify post belongs to user
    $check_sql = "SELECT user_id FROM post WHERE id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();

    if (!$post || $post['user_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // Update post
    $sql = "UPDATE post SET content = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $content, $post_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Post updated successfully'
        ]);
    } else {
        throw new Exception($conn->error);
    }
    
} catch (Exception $e) {
    error_log("Edit post error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error updating post'
    ]);
}
?> 