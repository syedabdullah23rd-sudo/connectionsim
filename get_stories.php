<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "post");

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed']));
}

$sql = "SELECT s.*, u.username, u.profile_pic 
        FROM stories s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
        ORDER BY s.created_at DESC";

$result = $conn->query($sql);
$stories = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $stories[] = $row;
    }
}

echo json_encode($stories);
$conn->close();
?> 