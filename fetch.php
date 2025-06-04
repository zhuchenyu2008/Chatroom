<?php
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    exit();
}
$since = intval($_GET['since'] ?? 0);
$messages = json_decode(file_get_contents(__DIR__ . '/data/messages.json'), true);
$newMessages = array_filter($messages, function($m) use ($since) { return $m['id'] > $since; });
// update online timestamps and remove stale
$online = json_decode(file_get_contents(__DIR__ . '/data/online.json'), true);
$now = time();
$changed = false;
foreach ($online as $user => $ts) {
    if ($now - $ts > 60) {
        unset($online[$user]);
        $messages[] = [
            'id' => end($messages)['id'] + 1,
            'user' => 'system',
            'text' => $user . ' 离开了聊天室',
            'timestamp' => $now,
            'read_by' => []
        ];
        $changed = true;
    }
}
$online[$_SESSION['user']] = $now;
file_put_contents(__DIR__ . '/data/online.json', json_encode($online, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
if ($changed) {
    file_put_contents(__DIR__ . '/data/messages.json', json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $newMessages = array_filter($messages, function($m) use ($since) { return $m['id'] > $since; });
}
header('Content-Type: application/json');
echo json_encode([
    'messages' => array_values($newMessages),
    'online' => count($online)
], JSON_UNESCAPED_UNICODE);
?>
