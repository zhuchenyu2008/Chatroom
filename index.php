<?php
session_start();
if (isset($_SESSION['user'])) {
    header('Location: chat.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if ($name !== '') {
        $_SESSION['user'] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        // add system message for join
        $messages_content = file_get_contents(__DIR__ . '/messages.json');
        $messages = ($messages_content && ($decoded_messages = json_decode($messages_content, true)) !== null) ? $decoded_messages : [];
        $last_message = end($messages);
        $new_id = ($last_message && isset($last_message['id'])) ? $last_message['id'] + 1 : 1;
        if ($messages) { reset($messages); } // Reset array pointer if $messages was not empty
        $messages[] = [
            'id' => $new_id,
            'user' => 'system',
            'text' => $_SESSION['user'] . ' 加入了聊天室',
            'timestamp' => time(),
            'read_by' => []
        ];
        file_put_contents(__DIR__ . '/messages.json', json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        // update online list
        $online_content = file_get_contents(__DIR__ . '/online.json');
        $online = ($online_content && ($decoded_online = json_decode($online_content, true)) !== null) ? $decoded_online : (object)[];
        $online[$_SESSION['user']] = time();
        file_put_contents(__DIR__ . '/online.json', json_encode($online, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        header('Location: chat.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>匿名聊天室登录</title>
<link rel="stylesheet" href="styles.css">
</head>
<body class="login-page">
    <form method="post" class="login-form">
        <h2>请输入昵称</h2>
        <input type="text" name="name" required autofocus>
        <button type="submit">进入</button>
    </form>
</body>
</html>
