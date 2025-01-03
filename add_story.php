<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

// Enable detailed error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_log', 'story_errors.log');

// Log the start of request
error_log("Story upload started - POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/stories/';
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        error_log("Failed to create directory: " . $upload_dir);
        die(json_encode(['success' => false, 'message' => 'Failed to create upload directory']));
    }
    chmod($upload_dir, 0777);
}

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Please login first']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

try {
    if (!isset($_FILES['story_media']) || $_FILES['story_media']['error'] !== UPLOAD_ERR_OK) {
        $error_message = isset($_FILES['story_media']) ? 
            'Upload error code: ' . $_FILES['story_media']['error'] : 
            'No file uploaded';
        throw new Exception($error_message);
    }

    $file = $_FILES['story_media'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4'];
    
    error_log("File type: " . $file['type']);
    
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Invalid file type: ' . $file['type'] . '. Allowed types: JPG, PNG, GIF, MP4');
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;

    error_log("Attempting to move file to: " . $filepath);
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        error_log("Failed to move file. Upload error: " . error_get_last()['message']);
        throw new Exception('Failed to move uploaded file. Check permissions.');
    }

    error_log("File moved successfully");

    try {
        $conn->begin_transaction();

        $sql = "INSERT INTO stories (user_id, media_url, media_type) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $user_id = $_SESSION['user_id'];
        $media_type = strpos($file['type'], 'video') === 0 ? 'video' : 'image';
        
        if (!$stmt->bind_param("iss", $user_id, $filepath, $media_type)) {
            throw new Exception('Failed to bind parameters: ' . $stmt->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute query: ' . $stmt->error);
        }

        $story_id = $stmt->insert_id;
        $conn->commit();

        error_log("Story saved successfully with ID: " . $story_id);

        echo json_encode([
            'success' => true,
            'message' => 'Story uploaded successfully',
            'story_id' => $story_id,
            'media_url' => $filepath
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Story upload error: " . $e->getMessage());
    
    if (isset($filepath) && file_exists($filepath)) {
        unlink($filepath);
        error_log("Cleaned up file: " . $filepath);
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 