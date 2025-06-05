<?php
session_start();
if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
    $online_content = file_get_contents(__DIR__ . '/online.json');
    $online = ($online_content && ($decoded_online = json_decode($online_content, true)) !== null) ? $decoded_online : (object)[];
    unset($online[$user]);
    file_put_contents(__DIR__ . '/online.json', json_encode($online, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $messages_content = file_get_contents(__DIR__ . '/messages.json');
    $messages = ($messages_content && ($decoded_messages = json_decode($messages_content, true)) !== null) ? $decoded_messages : [];
    $last_message = end($messages);
    $new_id = ($last_message && isset($last_message['id'])) ? $last_message['id'] + 1 : 1;
    if ($messages) { reset($messages); } // Reset array pointer if $messages was not empty
    $messages[] = [
        'id' => $new_id,
        'user' => 'system',
        'text' => $user . ' 离开了聊天室',
        'timestamp' => time(),
        'read_by' => []
    ];
    file_put_contents(__DIR__ . '/messages.json', json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
session_destroy();
header('Location: index.php');
?>
