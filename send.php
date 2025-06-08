<?php
session_start();
if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit();
}
$text = trim($_POST['text'] ?? '');
if ($text === '') {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Message text cannot be empty.']);
    exit();
}

// Retrieve and process self-destruct time
$destruct_time_raw = $_POST['destruct_time'] ?? '60'; // Default to 60 seconds if not provided

$newMessageData = [
    'user' => $_SESSION['user'],
    'text' => htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
    'timestamp' => time(),
    'read_by' => [$_SESSION['user']] // Creator has implicitly read it
];

// Determine destruct_type and add relevant fields
if ($destruct_time_raw === "0") { // Burn after read
    $newMessageData['destruct_type'] = "burn_after_read";
} elseif ($destruct_time_raw === "-1") { // Never
    $newMessageData['destruct_type'] = "never";
} else { // Timed
    $destruct_duration = (int)$destruct_time_raw;
    // Basic validation for duration, could be expanded
    if ($destruct_duration <= 0) {
        $destruct_duration = 60; // Fallback to 60s if somehow an invalid positive number is sent
    }
    $newMessageData['destruct_type'] = "timed";
    $newMessageData['destruct_duration'] = $destruct_duration;
}

$messages_content = file_get_contents(__DIR__ . '/messages.json');
$messages = ($messages_content && ($decoded_messages = json_decode($messages_content, true)) !== null) ? $decoded_messages : [];

$last_message = end($messages);
$new_id = ($last_message && isset($last_message['id'])) ? $last_message['id'] + 1 : 1;
if ($messages) { reset($messages); }

$newMessageData['id'] = $new_id; // Add ID to the message data

$messages[] = $newMessageData;

file_put_contents(__DIR__ . '/messages.json', json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// update activity timestamp
$online_content = file_get_contents(__DIR__ . '/online.json');
$online = ($online_content && ($decoded_online = json_decode($online_content, true)) !== null) ? $decoded_online : (object)[]; // Ensure it's an object for key assignment
$online[$_SESSION['user']] = time();
file_put_contents(__DIR__ . '/online.json', json_encode($online, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Content-Type: application/json');
echo json_encode($newMessageData); // Respond with the message that was just added
?>
