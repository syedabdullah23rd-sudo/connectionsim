<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Please login first']));
}

require_once 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$following_id = $data['user_id'];
$follower_id = $_SESSION['user_id'];

// Check if already following
$check_query = "SELECT * FROM followers WHERE follower_id = ? AND following_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $follower_id, $following_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Unfollow
    $sql = "DELETE FROM followers WHERE follower_id = ? AND following_id = ?";
    $following = false;
} else {
    // Follow
    $sql = "INSERT INTO followers (follower_id, following_id) VALUES (?, ?)";
    $following = true;
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $follower_id, $following_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'following' => $following]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating follow status']);
}

$conn->close();
?> 