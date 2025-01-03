<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to post']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "connectionzim");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed']);
    exit;
}

if (isset($_POST['submit'])) {
    $user_id = $_SESSION['user_id'];
    $content = $_POST['content'];
    $date = date('Y-m-d H:i:s');
    
    // Get user's info
    $user_query = "SELECT username, full_name, profile_pic FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    
    $name = $user_data['full_name'];
    $title = $user_data['username'];
    
    $media_file = "";
    $media_type = "";
    
    if(isset($_FILES["photo"]) && $_FILES["photo"]["error"] == 0) {
        $file_type = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_type;
        
        // Check if it's an image or video
        $allowed_image_types = array('jpg', 'jpeg', 'png', 'gif');
        $allowed_video_types = array('mp4', 'webm', 'ogg');
        
        if(in_array($file_type, $allowed_image_types)) {
            $upload_dir = "uploads/images/";
            $media_type = "image";
        } elseif(in_array($file_type, $allowed_video_types)) {
            $upload_dir = "uploads/videos/";
            $media_type = "video";
        } else {
            die(json_encode(['success' => false, 'message' => 'Invalid file type']));
        }
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $target_file = $upload_dir . $new_filename;
        
        if(move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            $media_file = $target_file;
        } else {
            die(json_encode(['success' => false, 'message' => 'Error uploading file']));
        }
    }
    
    // Update database table structure
    $sql = "INSERT INTO post (user_id, name, title, content, photo, media_file, media_type, date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error preparing statement: ' . $conn->error]);
        exit;
    }
    
    $empty_photo = ""; // For backward compatibility
    $stmt->bind_param("isssssss", $user_id, $name, $title, $content, $empty_photo, $media_file, $media_type, $date);
    
    if($stmt->execute()) {
        $post_id = $stmt->insert_id;
        
        // Return the new post data
        echo json_encode([
            'success' => true,
            'post' => [
                'id' => $post_id,
                'user_id' => $user_id,
                'name' => $name,
                'title' => $title,
                'content' => $content,
                'media_file' => $media_file,
                'media_type' => $media_type,
                'date' => $date,
                'profile_pic' => $user_data['profile_pic']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error posting: ' . $stmt->error]);
    }
    
    $stmt->close();
}

$conn->close();
?>