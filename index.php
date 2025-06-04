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
        $messages = json_decode(file_get_contents(__DIR__ . '/data/messages.json'), true);
        $id = end($messages)['id'] ?? 0;
        $messages[] = [
            'id' => $id + 1,
            'user' => 'system',
            'text' => $_SESSION['user'] . ' 加入了聊天室',
            'timestamp' => time(),
            'read_by' => []
        ];
        file_put_contents(__DIR__ . '/data/messages.json', json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        // update online list
        $online = json_decode(file_get_contents(__DIR__ . '/data/online.json'), true);
        $online[$_SESSION['user']] = time();
        file_put_contents(__DIR__ . '/data/online.json', json_encode($online, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        header('Location: chat.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
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
