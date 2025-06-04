<?php
session_start();
if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
    $online = json_decode(@file_get_contents(__DIR__ . '/data/online.json'), true);
    if (!is_array($online)) $online = [];
    unset($online[$user]);
    file_put_contents(__DIR__ . '/data/online.json', json_encode($online, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $messages = json_decode(@file_get_contents(__DIR__ . '/data/messages.json'), true);
    if (!is_array($messages)) $messages = [];
    $id = end($messages)['id'] ?? 0;
    $messages[] = [
        'id' => $id + 1,
        'user' => 'system',
        'text' => $user . ' 离开了聊天室',
        'timestamp' => time(),
        'read_by' => []
    ];
    file_put_contents(__DIR__ . '/data/messages.json', json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
session_destroy();
header('Location: index.php');
?>
