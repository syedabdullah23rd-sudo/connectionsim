<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Story ID is required']);
    exit;
}

try {
    $story_id = (int)$_GET['id'];
    
    $sql = "SELECT s.*, u.username, u.profile_pic 
            FROM stories s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.id = ? AND s.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $story_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Story not found or expired']);
        exit;
    }
    
    $story = $result->fetch_assoc();
    
    // Debug log
    error_log("Raw story data: " . print_r($story, true));
    
    // Fix the media_url path
    if (!empty($story['media_url'])) {
        // Remove any leading slashes
        $story['media_url'] = ltrim($story['media_url'], '/');
        
        // If it's not already a full URL, make it one
        if (!filter_var($story['media_url'], FILTER_VALIDATE_URL)) {
            $story['media_url'] = 'uploads/stories/' . basename($story['media_url']);
        }
    }
    
    error_log("Final media URL: " . $story['media_url']);
    
    echo json_encode([
        'success' => true,
        'story' => $story
    ]);
    
} catch (Exception $e) {
    error_log("Story view error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading story: ' . $e->getMessage()
    ]);
}
?> 