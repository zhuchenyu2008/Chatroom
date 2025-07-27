# 匿名聊天室 (Anonymous Chat Room)

一个简单的、基于 PHP 和 JSON 文件的匿名聊天室应用程序。用户可以输入昵称加入聊天，发送消息，并查看在线用户。消息支持定时自毁和阅后即焚功能。

A simple, anonymous chat room application built with PHP and file-based JSON storage. Users can join by simply choosing a nickname, send messages, and see who is online. It supports self-destructing messages, including timed deletion and "read-and-destroy" functionality.

## 🚀 主要功能 (Main Features)

* **匿名登录 (Anonymous Login):** 输入昵称即可加入聊天室，无需注册。
* **实时消息 (Real-time Messaging):** 聊天消息自动刷新，提供流畅的沟通体验。
* **在线用户 (Online Users):** 即时显示当前在线的用户列表和总人数。
* **消息已读状态 (Read Status):** 查看每条消息被多少用户阅读过。
* **自毁消息 (Self-Destructing Messages):**
    * **定时自毁 (Timed Destruction):** 可设定消息在特定时间后（如 1 分钟、1 小时、1 天）自动删除。
    * **阅后即焚 (Read and Destroy):** 消息在被其他用户阅读 5 秒后自动销毁。发送者会看到提示，告知消息已被他人阅读并即将销毁。
* **简洁界面 (Clean UI):** 采用深色主题，响应式设计，完美适配桌面和移动设备。

## 🛠️ 技术栈 (Tech Stack)

* **后端 (Backend):** PHP
* **前端 (Frontend):** HTML, CSS, JavaScript (原生, no frameworks)
* **数据存储 (Data Storage):** JSON 文件 (`messages.json` & `online.json`)

## ⚙️ 如何运行 (How to Run)

1.  **环境准备 (Prerequisites):**
    * 一个支持 PHP 的 Web 服务器 (例如 Apache, Nginx)。
    * 建议 PHP 版本 7.0 或更高。

2.  **部署文件 (Deployment):**
    * 将所有项目文件上传到你的 Web 服务器的根目录 (例如 `htdocs`, `www`, `public_html`)。

3.  **设置权限 (Set Permissions):**
    * 确保 Web 服务器对以下文件有 **写入权限**：
        * `messages.json`
        * `online.json`
    * 在 Linux/macOS 系统上，你可以使用 `chmod` 命令修改权限。例如：
        ```bash
        chmod 666 messages.json
        chmod 666 online.json
        ```
    > **⚠️ 注意 (Warning):** `666` 权限允许所有用户读写。在生产环境中，请根据你的安全策略设定更严格的权限，并确保文件所有者是 Web 服务器的运行用户 (例如 `www-data`)。

4.  **访问应用 (Access the App):**
    * 通过浏览器访问 `index.php` 即可开始使用。
    * 例如：`http://localhost/index.php` 或 `http://your-domain.com/index.php`。

## 📁 项目结构 (Project Structure)

```
.
├── index.php             # 用户登录页面 (Login page)
├── chat.php              # 聊天室主界面 (Main chat interface)
├── styles.css            # 样式表 (Stylesheet)
├── main.js               # 前端 JavaScript 逻辑 (Frontend logic)
├── send.php              # 后端 - 处理发送消息 (Backend - Handles sending messages)
├── fetch.php             # 后端 - 处理获取新消息 (Backend - Handles fetching new messages)
├── read.php              # 后端 - 处理标记消息已读 (Backend - Handles marking messages as read)
├── delete_message.php    # 后端 - 处理删除消息 (Backend - Handles deleting messages)
├── logout.php            # 后端 - 处理用户登出 (Backend - Handles user logout)
├── cleanup_messages.php  # 后端 - 定期清理过期消息和用户 (Backend - Cron job for cleanup)
├── messages.json         # 存储聊天消息 (Stores chat messages)
├── online.json           # 存储当前在线用户 (Stores online users)
└── README.md             # 本文件 (This file)
```

## 🐳 Docker 部署 (Docker Deployment)

下面示例展示了从 SSH 登录服务器开始的完整部署流程。如果你的服务器尚未安装 Docker，可先通过包管理器安装。

1. **登录并安装 Docker（如需要）**
   ```bash
   ssh your_user@<服务器IP>
   # 如果系统中没有 Docker，可以执行以下命令安装
   sudo apt update
   sudo apt install -y docker.io
   ```

2. **拉取项目并构建镜像**
   ```bash
   git clone <仓库地址> Chatroom
   cd Chatroom
   docker build -t chatroom .
   ```

3. **运行容器并映射端口**
   ```bash
   docker run -d --name chatroom -p 8080:80 chatroom
   ```

4. **访问应用**
   打开浏览器访问 `http://<服务器IP>:8080/index.php`。

## 🤝 贡献 (Contributing)

欢迎为此项目贡献代码或提出改进建议！你可以通过以下方式参与：

1.  Fork 本仓库 (Fork the Project)。
2.  创建你的功能分支 (`git checkout -b feature/AmazingFeature`)。
3.  提交你的更改 (`git commit -m 'Add some AmazingFeature'`)。
4.  将你的分支推送到远程 (`git push origin feature/AmazingFeature`)。
5.  开启一个 Pull Request。

