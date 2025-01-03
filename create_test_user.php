<?php
require_once 'db_connect.php';

// First, let's see what users exist
$result = $conn->query("SELECT * FROM users");
echo "<h3>Existing Users:</h3>";
while($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Username: {$row['username']}, Email: {$row['email']}<br>";
}

// Now create a new test user
$username = "testuser";
$email = "testuser@example.com";
$password = "password123";
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$fullname = "Test User";

// First delete if exists
$conn->query("DELETE FROM users WHERE username = 'testuser' OR email = 'testuser@example.com'");

// Create new user
$sql = "INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $username, $email, $password_hash, $fullname);

if ($stmt->execute()) {
    echo "<h3>Test User Created:</h3>";
    echo "Username: testuser<br>";
    echo "Password: password123<br>";
    echo "Email: testuser@example.com<br>";
    
    // Verify the password hash
    $verify_sql = "SELECT password FROM users WHERE username = 'testuser'";
    $verify_result = $conn->query($verify_sql);
    $stored_user = $verify_result->fetch_assoc();
    
    echo "<h3>Password Verification Test:</h3>";
    echo "Stored Hash: " . $stored_user['password'] . "<br>";
    echo "Verification Result: " . (password_verify($password, $stored_user['password']) ? 'PASS' : 'FAIL');
} else {
    echo "Error creating user: " . $conn->error;
}

$conn->close();
?> 