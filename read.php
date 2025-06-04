<?php
session_start();
if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit();
}
$ids = $_POST['ids'] ?? [];
$ids = array_map('intval', $ids);
if (!$ids) exit();
$messages = json_decode(file_get_contents(__DIR__ . '/data/messages.json'), true);
foreach ($messages as &$m) {
    if (in_array($m['id'], $ids)) {
        if (!in_array($_SESSION['user'], $m['read_by'])) {
            $m['read_by'][] = $_SESSION['user'];
        }
    }
}
file_put_contents(__DIR__ . '/data/messages.json', json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
?>
