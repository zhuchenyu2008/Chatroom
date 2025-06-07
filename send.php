<?php
session_start();
require_once __DIR__ . '/config.php'; // Include database configuration

if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit();
}

$text = trim($_POST['text'] ?? '');
if ($text === '') {
    exit();
}

$username = $_SESSION['user'];
$message_text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

// Insert message into database
$stmt = $mysqli->prepare("INSERT INTO messages (username, message_text) VALUES (?, ?)");
if ($stmt === false) {
    // Handle error, perhaps log it or send a specific HTTP response
    // For now, just exit. In a real app, more robust error handling is needed.
    error_log("MySQLi prepare failed for messages: " . $mysqli->error);
    http_response_code(500);
    exit('Database error preparing message statement.');
}
$stmt->bind_param("ss", $username, $message_text);

if (!$stmt->execute()) {
    // Handle error
    error_log("MySQLi execute failed for messages: " . $stmt->error);
    http_response_code(500);
    $stmt->close();
    exit('Database error executing message statement.');
}
$stmt->close();

// Update user's last active timestamp in online_users table
// Using INSERT ... ON DUPLICATE KEY UPDATE to either insert a new user or update existing one's timestamp
$stmt_online = $mysqli->prepare("INSERT INTO online_users (username, last_active) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE last_active = NOW()");
if ($stmt_online === false) {
    error_log("MySQLi prepare failed for online_users: " . $mysqli->error);
    http_response_code(500);
    exit('Database error preparing online_users statement.');
}
$stmt_online->bind_param("s", $username);

if (!$stmt_online->execute()) {
    error_log("MySQLi execute failed for online_users: " . $stmt_online->error);
    http_response_code(500);
    $stmt_online->close();
    exit('Database error executing online_users statement.');
}
$stmt_online->close();

// No need to explicitly send back data, client usually doesn't expect a body for POST like this
// unless it's for confirmation or returning the new resource ID.
// Given the old code didn't send anything back either, this is fine.
http_response_code(200); // OK
?>
