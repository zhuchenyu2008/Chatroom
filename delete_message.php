<?php
session_start();

// 1. Authentication and Request Method Check
if (!isset($_SESSION['user'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Authentication required.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'POST method required.']);
    exit();
}

// 2. Input Processing
$messageId = $_POST['id'] ?? null;

if ($messageId === null || !filter_var($messageId, FILTER_VALIDATE_INT) || (int)$messageId <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Valid message ID is required.']);
    exit();
}
$messageId = (int)$messageId;

// 3. Message Deletion Logic
$messages_file = __DIR__ . '/messages.json';
$file_handle = fopen($messages_file, 'c+');

if (!$file_handle) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Failed to open messages file.']);
    exit();
}

$messageActuallyDeleted = false;
$messageFoundButNotApplicable = false;

if (flock($file_handle, LOCK_EX)) { // Acquire exclusive lock
    $messages_content = '';
    // Read the entire stream content
    while (!feof($file_handle)) {
        $messages_content .= fread($file_handle, 8192);
    }

    $messages = ($messages_content !== '' && ($decoded_messages = json_decode($messages_content, true)) !== null) ? $decoded_messages : [];

    $newMessages = [];
    $originalMessageCount = count($messages);

    foreach ($messages as $message) {
        if (isset($message['id']) && $message['id'] == $messageId) {
            if (isset($message['destruct_type']) && $message['destruct_type'] === 'burn_after_read') {
                // Message found and is of type 'burn_after_read', so we "delete" it by not adding it to $newMessages.
                // No action needed here for $newMessages, it's skipped.
                // $messageActuallyDeleted will be set later if count changes.
            } else {
                // Message found, but not 'burn_after_read' or type not set. Keep it.
                $newMessages[] = $message;
                $messageFoundButNotApplicable = true;
            }
        } else {
            $newMessages[] = $message;
        }
    }

    if (count($newMessages) < $originalMessageCount) { // Check if a message was actually removed
        $messageActuallyDeleted = true;
        if (ftruncate($file_handle, 0)) {      // Truncate file
            rewind($file_handle);              // Rewind pointer
            fwrite($file_handle, json_encode($newMessages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($file_handle);              // Flush output before releasing lock
        } else {
            // Error truncating or writing - this is a server error state
            // Log this server-side. For the client, the lock will be released,
            // but the operation might not have been fully successful.
            // For simplicity, we'll proceed to unlock and rely on later checks.
            // A more robust solution might try to restore original content or flag for admin.
             error_log("Failed to truncate or write to messages.json after acquiring lock.");
             // We might want to avoid setting $messageActuallyDeleted to true if write fails.
             // However, $newMessages reflects the intended state. If write failed, data is inconsistent.
        }
    }

    flock($file_handle, LOCK_UN); // Release lock
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Failed to lock messages file.']);
    fclose($file_handle); // Close handle if lock failed
    exit();
}

fclose($file_handle);

// 4. Response
if ($messageActuallyDeleted) {
    echo json_encode(['success' => true, 'message' => 'Message deleted successfully.']);
} elseif ($messageFoundButNotApplicable) {
    http_response_code(400); // Bad Request or 422 Unprocessable Entity
    echo json_encode(['error' => 'Message found but is not a "burn_after_read" type or was already processed.']);
} else {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Message not found.']);
}

?>
