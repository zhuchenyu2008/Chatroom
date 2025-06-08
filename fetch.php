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
    $newMessages = $messages; // $newMessages now contains all messages, including any new system messages
}

// Filter messages before sending to client
$currentTime = time();
$messagesToClient = [];

foreach ($newMessages as $message) {
    // Pass through system messages or messages without a destruct_type (e.g., older messages)
    if (!isset($message['destruct_type']) || (isset($message['user']) && $message['user'] === 'system')) {
        $messagesToClient[] = $message;
        continue;
    }

    // Handle timed messages
    if ($message['destruct_type'] === 'timed') {
        // Ensure necessary fields exist to prevent errors
        if (isset($message['timestamp']) && isset($message['destruct_duration'])) {
            $expiry_time = (int)$message['timestamp'] + (int)$message['destruct_duration'];
            if ($currentTime >= $expiry_time) {
                // Message has expired, do not add to $messagesToClient
                continue;
            }
        } else {
            // If a timed message is missing timestamp or duration, it's malformed.
            // Decide whether to send it or skip it. Skipping might be safer.
            // For now, let it pass if essential fields for expiry check are missing,
            // effectively treating it as non-expiring in this edge case.
            // A stricter approach would be to 'continue' here as well.
        }
    }

    // "burn_after_read", "never" messages, and non-expired "timed" messages are added.
    // Future logic for "burn_after_read" might involve more checks here (e.g., if already read by all recipients).
    $messagesToClient[] = $message;
}

header('Content-Type: application/json');
echo json_encode([
    'messages' => array_values($messagesToClient), // Use the filtered list
    'online' => count($online)
], JSON_UNESCAPED_UNICODE);
?>
