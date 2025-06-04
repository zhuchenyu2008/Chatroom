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
$messages = json_decode(@file_get_contents(__DIR__ . '/data/messages.json'), true);
if (!is_array($messages)) $messages = [];
$id = end($messages)['id'] ?? 0;
$messages[] = [
    'id' => $id + 1,
    'user' => $_SESSION['user'],
    'text' => htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
    'timestamp' => time(),
    'read_by' => [$_SESSION['user']]
];
file_put_contents(__DIR__ . '/data/messages.json', json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
// update activity timestamp
$online = json_decode(@file_get_contents(__DIR__ . '/data/online.json'), true);
if (!is_array($online)) $online = [];
$online[$_SESSION['user']] = time();
file_put_contents(__DIR__ . '/data/online.json', json_encode($online, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
?>
