<?php
require_once 'db_connect.php';

$username = "test";
$email = "test@example.com";
$password = password_hash("test123", PASSWORD_DEFAULT);
$fullname = "Test User";

// Check if user exists
$check = $conn->query("SELECT id FROM users WHERE username = 'test' OR email = 'test@example.com'");
if ($check->num_rows == 0) {
    $sql = "INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $email, $password, $fullname);
    
    if ($stmt->execute()) {
        echo "Test user created successfully!<br>";
        echo "Username: test<br>";
        echo "Password: test123";
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "Test user already exists!<br>";
    echo "Username: test<br>";
    echo "Password: test123";
}
?> 