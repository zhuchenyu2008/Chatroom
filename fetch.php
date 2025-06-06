<?php
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    exit();
}
$since = intval($_GET['since'] ?? 0);
$messages_content = file_get_contents(__DIR__ . '/messages.json');
$messages = ($messages_content && ($decoded_messages = json_decode($messages_content, true)) !== null) ? $decoded_messages : [];
$newMessages = $messages;
// update online timestamps and remove stale
$online_content = file_get_contents(__DIR__ . '/online.json');
$online = ($online_content && ($decoded_online = json_decode($online_content, true)) !== null) ? $decoded_online : (object)[];
$now = time();
$changed = false;
foreach ($online as $user => $ts) {
    if ($now - $ts > 60) {
        unset($online[$user]);
        $last_message = end($messages);
        $new_id = ($last_message && isset($last_message['id'])) ? $last_message['id'] + 1 : 1;
        if ($messages) { reset($messages); } // Reset array pointer
        $messages[] = [
            'id' => $new_id,
            'user' => 'system',
            'text' => $user . ' 离开了聊天室',
            'timestamp' => $now,
            'read_by' => []
        ];
        $changed = true;
    }
}
$online[$_SESSION['user']] = $now;
file_put_contents(__DIR__ . '/online.json', json_encode($online, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
if ($changed) {
    file_put_contents(__DIR__ . '/messages.json', json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $newMessages = $messages;
}
header('Content-Type: application/json');
echo json_encode([
    'messages' => array_values($newMessages),
    'online' => count($online)
], JSON_UNESCAPED_UNICODE);
?>
