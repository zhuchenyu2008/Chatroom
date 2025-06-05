<?php
session_start();
if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit();
}
$text = trim($_POST['text'] ?? '');
if ($text === '') {
    exit();
}
$messages_content = file_get_contents(__DIR__ . '/messages.json');
$messages = ($messages_content && ($decoded_messages = json_decode($messages_content, true)) !== null) ? $decoded_messages : [];
$last_message = end($messages);
$new_id = ($last_message && isset($last_message['id'])) ? $last_message['id'] + 1 : 1;
if ($messages) { reset($messages); } // Reset array pointer if $messages was not empty
$messages[] = [
    'id' => $new_id,
    'user' => $_SESSION['user'],
    'text' => htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
    'timestamp' => time(),
    'read_by' => [$_SESSION['user']]
];
file_put_contents(__DIR__ . '/messages.json', json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
// update activity timestamp
$online_content = file_get_contents(__DIR__ . '/online.json');
$online = ($online_content && ($decoded_online = json_decode($online_content, true)) !== null) ? $decoded_online : (object)[];
$online[$_SESSION['user']] = time();
file_put_contents(__DIR__ . '/online.json', json_encode($online, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
?>
