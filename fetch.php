<?php
session_start();
require_once __DIR__ . '/config.php'; // Include database configuration

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    exit('User not authenticated.');
}

$current_username = $_SESSION['user'];
$since_timestamp_sql = '1970-01-01 00:00:00'; // Default to fetch all messages if no 'since' is provided

// The 'since' parameter from the client is expected to be a message ID.
// We need to fetch the timestamp of that message ID to get subsequent messages.
if (isset($_GET['since']) && intval($_GET['since']) > 0) {
    $since_id = intval($_GET['since']);
    $stmt_since = $mysqli->prepare("SELECT timestamp FROM messages WHERE id = ? ORDER BY id DESC LIMIT 1");
    if ($stmt_since) {
        $stmt_since->bind_param("i", $since_id);
        $stmt_since->execute();
        $result_since = $stmt_since->get_result();
        if ($row_since = $result_since->fetch_assoc()) {
            $since_timestamp_sql = $row_since['timestamp'];
        }
        $stmt_since->close();
    } else {
        error_log("MySQLi prepare failed for since_timestamp: " . $mysqli->error);
        // Continue with default $since_timestamp_sql
    }
}


// Update current user's last_active timestamp
$stmt_update_active = $mysqli->prepare("INSERT INTO online_users (username, last_active) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE last_active = NOW()");
if ($stmt_update_active) {
    $stmt_update_active->bind_param("s", $current_username);
    if (!$stmt_update_active->execute()) {
        error_log("MySQLi execute failed for update_active: " . $stmt_update_active->error);
        // Non-critical, proceed
    }
    $stmt_update_active->close();
} else {
    error_log("MySQLi prepare failed for update_active: " . $mysqli->error);
    // Non-critical, proceed
}


// Identify and remove stale online users (e.g., inactive for more than 60 seconds)
// And generate system messages for users who left
$timeout_seconds = 60;
$stale_users_stmt = $mysqli->prepare("SELECT username FROM online_users WHERE last_active < NOW() - INTERVAL ? SECOND");
$system_messages_to_insert = [];

if ($stale_users_stmt) {
    $stale_users_stmt->bind_param("i", $timeout_seconds);
    $stale_users_stmt->execute();
    $stale_users_result = $stale_users_stmt->get_result();

    $departed_users = [];
    while ($row = $stale_users_result->fetch_assoc()) {
        $departed_users[] = $row['username'];
    }
    $stale_users_stmt->close();

    if (!empty($departed_users)) {
        // Prepare to insert system messages for each departed user
        $insert_msg_stmt = $mysqli->prepare("INSERT INTO messages (username, message_text) VALUES ('system', ?)");
        if ($insert_msg_stmt) {
            foreach ($departed_users as $departed_user) {
                if ($departed_user === $current_username) continue; // Don't announce self-departure if timed out somehow

                $system_message = $departed_user . ' 离开了聊天室';
                $insert_msg_stmt->bind_param("s", $system_message);
                if ($insert_msg_stmt->execute()) {
                    // System message inserted
                } else {
                    error_log("MySQLi execute failed for system message: " . $insert_msg_stmt->error);
                }
            }
            $insert_msg_stmt->close();
        } else {
            error_log("MySQLi prepare failed for system message insert: " . $mysqli->error);
        }

        // Remove stale users from online_users table
        $delete_stale_stmt = $mysqli->prepare("DELETE FROM online_users WHERE username = ?");
        if ($delete_stale_stmt) {
            foreach ($departed_users as $departed_user) {
                $delete_stale_stmt->bind_param("s", $departed_user);
                if (!$delete_stale_stmt->execute()) {
                    error_log("MySQLi execute failed for deleting stale user " . $departed_user . ": " . $delete_stale_stmt->error);
                }
            }
            $delete_stale_stmt->close();
        } else {
            error_log("MySQLi prepare failed for deleting stale users: " . $mysqli->error);
        }
    }
} else {
    error_log("MySQLi prepare failed for selecting stale users: " . $mysqli->error);
}


// Fetch new messages (including any system messages just added)
// The client side expects 'id', 'user', 'text', 'timestamp'.
// MySQL timestamp is 'YYYY-MM-DD HH:MM:SS', JS expects UNIX timestamp (seconds since epoch).
$messages_stmt = $mysqli->prepare(
    "SELECT id, username, message_text, UNIX_TIMESTAMP(timestamp) as timestamp
     FROM messages
     WHERE timestamp > ?
     ORDER BY timestamp ASC"
);
$final_messages = [];
if ($messages_stmt) {
    $messages_stmt->bind_param("s", $since_timestamp_sql);
    $messages_stmt->execute();
    $messages_result = $messages_stmt->get_result();
    while ($row = $messages_result->fetch_assoc()) {
        // The old format also had 'read_by', which we are omitting for now for simplicity with MySQL.
        // If needed, a separate 'message_read_status' table would be required.
        $final_messages[] = [
            'id' => $row['id'],
            'user' => $row['username'], // In old code this was 'user', in new schema it's 'username' from DB
            'text' => $row['message_text'],
            'timestamp' => (int)$row['timestamp'] // Ensure it's an integer
        ];
    }
    $messages_stmt->close();
} else {
    error_log("MySQLi prepare failed for fetching messages: " . $mysqli->error);
    http_response_code(500);
    exit('Database error fetching messages.');
}

// Get current online user count
$online_count = 0;
$count_stmt = $mysqli->query("SELECT COUNT(*) as count FROM online_users");
if ($count_stmt) {
    $online_count = (int)$count_stmt->fetch_assoc()['count'];
    $count_stmt->free();
} else {
    error_log("MySQLi query failed for online count: " . $mysqli->error);
    // Proceed with count 0 if query fails
}


header('Content-Type: application/json');
echo json_encode([
    'messages' => $final_messages,
    'online' => $online_count
], JSON_UNESCAPED_UNICODE);

?>
