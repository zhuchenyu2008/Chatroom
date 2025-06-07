# Chatroom

一个基于PHP8.3的匿名聊天室项目。原先使用JSON文件存储数据，现已升级为使用 MySQL 数据库。

## 功能特点

### 用户体验
- **简单登录**：仅需填写昵称即可进入聊天室，无需注册或密码。
- **精美界面**：采用类似 ChatGPT 的高级配色方案。
- **流畅动画**：消息发送和接收时的平滑动画效果。
- **响应式设计**：自适应不同设备屏幕尺寸。

### 核心功能
- **实时通信**：前端轮询获取新消息，实现实时刷新。
- **在线状态**：显示当前在线人数，用户进出会发送系统消息。
- **时间显示**：点击消息内容可显示具体发送时间。

## 技术栈
- PHP 8.3
- MySQL
- HTML, CSS, JavaScript (for frontend)

## 环境要求
- PHP 8.3 or later
- MySQL server
- Web server (like Apache or Nginx, or use PHP's built-in server for development)

## 安装与配置

### 1. 克隆仓库 (Clone Repository)
```bash
git clone <repository_url>
cd <repository_directory>
```

### 2. 数据库设置 (Database Setup)
本项目使用 MySQL 数据库存储消息和在线用户信息。

**a. 创建数据库和用户:**
   - 登录到您的 MySQL 服务器。
   - 创建一个新的数据库，例如 `chat_app`。
     ```sql
     CREATE DATABASE chat_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     ```
   - 创建一个数据库用户，例如 `chat_user`，并授予其对 `chat_app` 数据库的权限。
     ```sql
     CREATE USER 'chat_user'@'localhost' IDENTIFIED BY 'your_strong_password';
     GRANT ALL PRIVILEGES ON chat_app.* TO 'chat_user'@'localhost';
     FLUSH PRIVILEGES;
     ```
     *请务必将 `your_strong_password` 替换为您自己的安全密码。*

**b. 配置数据库连接 (`config.php`):**
   - 复制或重命名 `config.php.example` (如果提供) 为 `config.php`，或者直接编辑 `config.php`。
   - 打开 `config.php` 文件。
   - 修改以下常量以匹配您的数据库设置：
     ```php
     define('DB_HOST', 'localhost');         // MySQL 服务器地址
     define('DB_USERNAME', 'chat_user');     // 您创建的数据库用户名
     define('DB_PASSWORD', 'your_strong_password'); // 您设置的密码
     define('DB_NAME', 'chat_app');          // 您创建的数据库名
     ```
     *(确保此文件存在于项目根目录，并且包含正确的凭据。)*

**c. 初始化数据库表 (`init_db.php`):**
   - 在项目根目录下，通过命令行运行以下脚本来创建所需的数据表：
     ```bash
     php init_db.php
     ```
   - 这将执行 `setup.sql` 文件中的命令，在您的数据库中创建 `messages` 和 `online_users` 表。
   - 您应该会看到成功消息。如果遇到错误，请检查 `config.php` 中的设置和 MySQL 服务器的连接。

## 运行方式 (Running the Application)
1. 确保已完成上述数据库设置步骤。
2. 在项目根目录执行 PHP 内建的开发服务器：
   ```bash
   php -S localhost:8000
   ```
3. 通过浏览器访问 `http://localhost:8000/index.php` 即可体验。

   对于生产环境，建议使用更健壮的 Web 服务器如 Apache 或 Nginx，并配置指向项目根目录。

## 注意
- 原有的“消息已读”功能在此 MySQL 版本中已简化移除。
```
