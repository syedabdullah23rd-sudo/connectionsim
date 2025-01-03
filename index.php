<?php
session_start();

// If user is already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to CONNECTIONZIM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <main class="landing-page">
        <div class="welcome-section">
            <h1>Welcome to CONNECTIONZIM</h1>
            <p>Connect with professionals, share your work, and grow your network.</p>
            <div class="cta-buttons">
                <button onclick="showLoginModal()" class="btn-primary">Login</button>
                <button onclick="showSignupModal()" class="btn-secondary">Sign Up</button>
            </div>
        </div>
    </main>

    <style>
    .landing-page {
        padding-top: 60px;
        margin-left: 300px;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f3f2ef;
    }

    .welcome-section {
        text-align: center;
        padding: 40px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        max-width: 600px;
        width: 90%;
    }

    .welcome-section h1 {
        color: var(--primary-color);
        margin-bottom: 20px;
    }

    .welcome-section p {
        color: #666;
        margin-bottom: 30px;
        font-size: 18px;
    }

    .cta-buttons {
        display: flex;
        gap: 20px;
        justify-content: center;
    }

    .cta-buttons button {
        padding: 12px 30px;
        font-size: 16px;
        border-radius: 24px;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
        border: none;
    }

    .btn-secondary {
        background: transparent;
        color: var(--primary-color);
        border: 2px solid var(--primary-color);
    }
    </style>
</body>
</html> 