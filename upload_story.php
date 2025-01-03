<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$conn = new mysqli("localhost", "root", "", "post");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify user exists
    $user_id = (int)$_SESSION['user_id'];
    
    // Check if user exists in users table
    $check_user = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $check_user->bind_param("i", $user_id);
    $check_user->execute();
    $result = $check_user->get_result();
    
    if($result->num_rows === 0) {
        die("Invalid user account");
    }
    
    $caption = $conn->real_escape_string($_POST['caption'] ?? '');
    
    // Handle file upload
    if(isset($_FILES['story_media']) && $_FILES['story_media']['error'] == 0) {
        $upload_dir = "uploads/stories/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['story_media']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $upload_dir . $new_filename;
        
        // Check file type
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'mp4');
        if(!in_array($file_extension, $allowed_types)) {
            die("Sorry, only JPG, JPEG, PNG, GIF & MP4 files are allowed.");
        }
        
        if(move_uploaded_file($_FILES['story_media']['tmp_name'], $target_file)) {
            // Prepare and check SQL statement
            $sql = "INSERT INTO stories (user_id, media_url, caption, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            
            if($stmt === false) {
                die("Error preparing statement: " . $conn->error);
            }
            
            $stmt->bind_param("iss", $user_id, $target_file, $caption);
            
            if($stmt->execute()) {
                header("Location: home.php");
                exit;
            } else {
                echo "Error executing statement: " . $stmt->error . "<br>";
                echo "User ID: " . $user_id . "<br>";
                echo "Media URL: " . $target_file . "<br>";
                echo "Caption: " . $caption;
            }
            $stmt->close();
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    } else {
        echo "No file uploaded or file upload error occurred.";
    }
}

$conn->close();
?> 