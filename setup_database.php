<?php
require_once 'db_connect.php';

// Create post table
$sql = "CREATE TABLE IF NOT EXISTS post (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    content TEXT,
    media_file VARCHAR(255),
    media_type ENUM('image', 'video'),
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql)) {
    echo "Post table created successfully<br>";
} else {
    echo "Error creating post table: " . $conn->error . "<br>";
}

// Create followers table
$sql = "CREATE TABLE IF NOT EXISTS followers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, following_id)
)";

if ($conn->query($sql)) {
    echo "Followers table created successfully<br>";
} else {
    echo "Error creating followers table: " . $conn->error . "<br>";
}

// Create tags table
$sql = "CREATE TABLE IF NOT EXISTS tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tag_name VARCHAR(50) UNIQUE NOT NULL
)";

if ($conn->query($sql)) {
    echo "Tags table created successfully<br>";
} else {
    echo "Error creating tags table: " . $conn->error . "<br>";
}

// Create post_tags table
$sql = "CREATE TABLE IF NOT EXISTS post_tags (
    post_id INT NOT NULL,
    tag_id INT NOT NULL,
    FOREIGN KEY (post_id) REFERENCES post(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (post_id, tag_id)
)";

if ($conn->query($sql)) {
    echo "Post_tags table created successfully<br>";
} else {
    echo "Error creating post_tags table: " . $conn->error . "<br>";
}

// Add bio column to users table if it doesn't exist
$sql = "SHOW COLUMNS FROM users LIKE 'bio'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN bio TEXT";
    if ($conn->query($sql)) {
        echo "Bio column added to users table<br>";
    } else {
        echo "Error adding bio column: " . $conn->error . "<br>";
    }
}

// Create likes table
$sql = "CREATE TABLE IF NOT EXISTS likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES post(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (post_id, user_id)
)";

if ($conn->query($sql)) {
    echo "Likes table created successfully<br>";
} else {
    echo "Error creating likes table: " . $conn->error . "<br>";
}

// Create comments table
$sql = "CREATE TABLE IF NOT EXISTS comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES post(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql)) {
    echo "Comments table created successfully<br>";
} else {
    echo "Error creating comments table: " . $conn->error . "<br>";
}

// Add missing columns to users table if they don't exist
$columns_to_add = [
    'full_name' => 'VARCHAR(100)',
    'bio' => 'TEXT',
    'profile_pic' => 'VARCHAR(255) DEFAULT "default_avatar.jpg"'
];

foreach ($columns_to_add as $column => $type) {
    $sql = "SHOW COLUMNS FROM users LIKE '$column'";
    $result = $conn->query($sql);
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE users ADD COLUMN $column $type";
        if ($conn->query($sql)) {
            echo "$column column added to users table<br>";
        } else {
            echo "Error adding $column column: " . $conn->error . "<br>";
        }
    }
}

$conn->close();
echo "Database setup completed!";
?> 