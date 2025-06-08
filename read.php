<?php
// read.php
session_start();

// Robust check for user, method, and ids parameter
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

if (!isset($_POST['ids']) || !is_array($_POST['ids'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid request: "ids" parameter must be an array.']);
    exit();
}

$idsToMark = array_filter(array_map('intval', $_POST['ids']), function($id) { return $id > 0; });

if (empty($idsToMark)) {
    // No valid IDs provided, or array was empty. Nothing to do.
    // Consider if a 200 OK or 400 Bad Request is more appropriate.
    // For now, exiting silently (200 OK by default) as no change is made.
    exit();
}

$messages_file = __DIR__ . '/messages.json';
$file_handle = fopen($messages_file, 'c+');

if (!$file_handle) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Failed to open messages file.']);
    exit();
}

if (flock($file_handle, LOCK_EX)) { // Acquire exclusive lock
    $messages_content = '';
    while (!feof($file_handle)) {
        $messages_content .= fread($file_handle, 8192);
    }

    $messages = (!empty($messages_content) && ($decoded_messages = json_decode($messages_content, true)) !== null) ? $decoded_messages : [];

    $currentUser = $_SESSION['user'];
    $changed = false;

    foreach ($messages as &$m) { // Use reference to modify array directly
        if (isset($m['id']) && in_array($m['id'], $idsToMark)) {

            if (!isset($m['read_by']) || !is_array($m['read_by'])) {
                $m['read_by'] = []; // Initialize if not exists or wrong type
            }

            if (!in_array($currentUser, $m['read_by'])) {
                $m['read_by'][] = $currentUser;
                $changed = true;

                // "Burn after Read" specific logic
                if (isset($m['destruct_type']) && $m['destruct_type'] === 'burn_after_read') {
                    // Check if current user is not the sender of the message
                    if (isset($m['user']) && $currentUser !== $m['user']) {
                        // Check if 'first_read_by_other_timestamp' is not already set
                        if (!isset($m['first_read_by_other_timestamp'])) {
                            $m['first_read_by_other_timestamp'] = time();
                            // $changed is already true due to adding to read_by
                        }
                    }
                }
            }
        }
    }
    unset($m); // Unset reference to last element

    if ($changed) {
        if (ftruncate($file_handle, 0)) {
            rewind($file_handle);
            if (fwrite($file_handle, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                fflush($file_handle);
                // Successfully written, client will receive 200 OK by default
            } else {
                error_log("read.php: Failed to write to messages.json after lock and truncate.");
                // Avoid sending HTTP error code here if possible, as client might have already received some data or headers.
                // This is a server-side issue primarily.
            }
        } else {
            error_log("read.php: Failed to truncate messages.json after lock.");
        }
    }

    flock($file_handle, LOCK_UN); // Release lock
} else {
    http_response_code(503); // Service Unavailable (failed to get lock)
    echo json_encode(['error' => 'Failed to lock messages file. Please try again.']);
    fclose($file_handle); // Close handle if lock failed
    exit();
}

fclose($file_handle);

// Implicit 200 OK if no error codes were set and exited.
?>
