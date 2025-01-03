<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "post";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully<br>";

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'post'");
if ($result->num_rows > 0) {
    echo "Table 'post' exists<br>";
    
    // Check table structure
    $result = $conn->query("DESCRIBE post");
    echo "Table structure:<br>";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
    
    // Check if table has data
    $result = $conn->query("SELECT COUNT(*) as count FROM post");
    $row = $result->fetch_assoc();
    echo "Number of records: " . $row['count'] . "<br>";
} else {
    echo "Table 'post' does not exist<br>";
}

$conn->close();
?> 