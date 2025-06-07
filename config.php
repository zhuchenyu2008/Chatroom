<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'your_db_user'); // Replace with your database username
define('DB_PASSWORD', 'your_db_password'); // Replace with your database password
define('DB_NAME', 'chat_app'); // Replace with your database name

// Establish a database connection
$mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($mysqli->connect_error) {
    // In a real application, you would log this error and show a user-friendly message.
    // For now, we'll just kill the script.
    die("Connection failed: " . $mysqli->connect_error);
}

// Set charset to utf8mb4 for full Unicode support
$mysqli->set_charset("utf8mb4");

?>
