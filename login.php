<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Debug log
    error_log("Login attempt - Username: " . $username);
    
    // Basic validation
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter both username and password']);
        exit;
    }
    
    // Check user
    $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Debug log
    error_log("Query result rows: " . $result->num_rows);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Debug log
        error_log("Stored password hash: " . $user['password']);
        error_log("Password verification result: " . (password_verify($password, $user['password']) ? 'true' : 'false'));
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'debug' => [
                    'user_id' => $user['id'],
                    'username' => $user['username']
                ]
            ]);
            exit;
        }
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Invalid username or password',
        'debug' => [
            'username_provided' => $username,
            'rows_found' => $result ? $result->num_rows : 0
        ]
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?> 