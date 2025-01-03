<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
$position = $_POST['position'];
$company_name = $_POST['company_name'];
$location = $_POST['location'];
$start_date = $_POST['start_date'];
$end_date = $_POST['currently_working'] ? NULL : $_POST['end_date'];
$currently_working = isset($_POST['currently_working']) ? 1 : 0;
$description = $_POST['description'];

$sql = "INSERT INTO experience (user_id, position, company_name, location, start_date, end_date, currently_working, description) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isssssss", $user_id, $position, $company_name, $location, $start_date, $end_date, $currently_working, $description);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
} 