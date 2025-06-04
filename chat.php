<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>聊天室</title>
<link rel="stylesheet" href="styles.css">
</head>
<body class="chat-page">
<header>
    <div class="header-left">欢迎，<?php echo htmlspecialchars($_SESSION['user']); ?></div>
    <div class="header-right" id="online-count"></div>
</header>
<div id="messages" class="messages"></div>
<form id="send-form" class="send-form">
    <input type="text" id="message" autocomplete="off" placeholder="输入消息..." required>
    <button type="submit">发送</button>
</form>
<script src="main.js"></script>
</body>
</html>
