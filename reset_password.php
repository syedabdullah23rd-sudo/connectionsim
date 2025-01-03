<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}

$message = '';
$messageType = '';
$validToken = false;
$token = $_GET['token'] ?? '';

if(!empty($token)) {
    $conn = new mysqli("localhost", "root", "", "post");
    
    // Verify token
    $sql = "SELECT user_id FROM password_resets 
            WHERE token = ? AND expires_at > NOW() 
            AND used = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $validToken = true;
        $reset_data = $result->fetch_assoc();
        
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if($password === $confirm_password) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Update password
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $hashed_password, $reset_data['user_id']);
                
                if($stmt->execute()) {
                    // Mark token as used
                    $sql = "UPDATE password_resets SET used = 1 WHERE token = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $token);
                    $stmt->execute();
                    
                    $message = "Password updated successfully! <a href='login.php'>Login now</a>";
                    $messageType = 'success';
                } else {
                    $message = "Error updating password!";
                    $messageType = 'error';
                }
            } else {
                $message = "Passwords do not match!";
                $messageType = 'error';
            }
        }
    } else {
        $message = "Invalid or expired reset link!";
        $messageType = 'error';
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CONNECTIONZIM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .reset-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            background: white;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn-submit {
            width: 100%;
            padding: 10px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-submit:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2>Reset Password</h2>
        
        <?php if($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if($validToken): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn-submit">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html> 