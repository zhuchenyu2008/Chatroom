<?php
// cleanup_messages.php - Intended to be run from CLI or a cron job.

$messages_file = __DIR__ . '/messages.json';
// Open for read/write, create if not exists, pointer at start.
// Using 'c+' ensures the file is not truncated on open if it exists.
$file_handle = fopen($messages_file, 'c+');

if (!$file_handle) {
    echo "Error: Failed to open messages file ('$messages_file') for cleanup." . PHP_EOL;
    exit(1); // Exit with an error code
}

if (flock($file_handle, LOCK_EX)) { // Acquire exclusive lock
    $messages_content = '';
    // Read the entire stream content. Essential for 'c+' mode before writing.
    while (!feof($file_handle)) {
        $messages_content .= fread($file_handle, 8192);
    }

    // Handle empty file or invalid JSON content
    $messages = (!empty($messages_content) && ($decoded_messages = json_decode($messages_content, true)) !== null) ? $decoded_messages : [];

    $original_count = count($messages);
    $currentTime = time();
    $cleanedMessages = [];
    $messages_changed = false;
    $removed_count = 0;

    foreach ($messages as $message) {
        $keep_message = true; // Assume we keep the message by default

        if (isset($message['destruct_type']) && $message['destruct_type'] === 'timed') {
            if (isset($message['timestamp']) && isset($message['destruct_duration'])) {
                $expiry_time = (int)$message['timestamp'] + (int)$message['destruct_duration'];
                if ($currentTime >= $expiry_time) {
                    $keep_message = false; // Mark for removal
                }
            }
            // else: malformed timed message, keep it as we can't determine expiry.
        }
        // Note: Burn-after-read messages are directly deleted by delete_message.php.
        // No specific logic is needed here for them unless the overall deletion strategy changes.
        // System messages or messages with no destruct_type will also be kept.

        if ($keep_message) {
            $cleanedMessages[] = $message;
        } else {
            $messages_changed = true;
            $removed_count++;
        }
    }

    if ($messages_changed) {
        // Important: Truncate the file *after* reading and before writing new content.
        if (ftruncate($file_handle, 0)) {
            rewind($file_handle); // Reset file pointer to the beginning
            if (fwrite($file_handle, json_encode($cleanedMessages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                fflush($file_handle); // Ensure all output is written before releasing lock
                echo "Cleanup complete. Removed $removed_count expired timed messages. File updated." . PHP_EOL;
            } else {
                // Error writing, attempt to restore original content if possible (complex)
                // or at least log the error. For now, just log.
                echo "Error: Failed to write cleaned messages to file. File may be in an inconsistent state." . PHP_EOL;
                // Attempt to write original content back - best effort
                rewind($file_handle);
                ftruncate($file_handle, 0);
                fwrite($file_handle, $messages_content); // Write original content back
                fflush($file_handle);
                // Exit with error because operation failed
                flock($file_handle, LOCK_UN); // Release lock before exiting
                fclose($file_handle);
                exit(1);
            }
        } else {
            echo "Error: Failed to truncate messages file. No changes made." . PHP_EOL;
            // No changes made, so release lock and exit with error
            flock($file_handle, LOCK_UN);
            fclose($file_handle);
            exit(1);
        }
    } else {
        echo "Cleanup complete. No timed messages required removal." . PHP_EOL;
    }

    flock($file_handle, LOCK_UN); // Release lock
} else {
    echo "Error: Failed to acquire lock on messages file ('$messages_file') for cleanup. Another process might be holding it." . PHP_EOL;
    fclose($file_handle); // Close the handle if lock was not acquired
    exit(1); // Exit with an error code
}

fclose($file_handle);
exit(0); // Success
?>
