<?php
// Include database configuration constants (DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME)
// We don't establish the $mysqli connection from config.php here yet,
// as we might need to create the database first.
require_once __DIR__ . '/config.php';

// --- Step 1: Connect to MySQL server (without selecting a database initially) ---
$temp_mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD);

// Check connection
if ($temp_mysqli->connect_error) {
    die("Connection to MySQL server failed: " . $temp_mysqli->connect_error . PHP_EOL);
}
echo "Successfully connected to MySQL server." . PHP_EOL;

// --- Step 2: Attempt to create the database if it doesn't exist ---
$db_name = DB_NAME; // Get database name from config
$sql_create_db = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

if ($temp_mysqli->query($sql_create_db) === TRUE) {
    echo "Database `$db_name` checked/created successfully." . PHP_EOL;
} else {
    die("Error creating database `$db_name`: " . $temp_mysqli->error . PHP_EOL);
}

// Close the temporary connection
$temp_mysqli->close();

// --- Step 3: Connect to the specific database (now that we know it exists) ---
// We can now use the $mysqli object that should have been created in config.php,
// or re-establish if config.php's connection was conditional.
// For simplicity, let's ensure $mysqli from config.php is used or re-create one.

// $mysqli should be available from config.php if it was included successfully and didn't die.
// If config.php dies on DB_NAME not found, this script needs to be more robust
// or config.php needs to not die if DB doesn't exist.
// Assuming $mysqli from config.php is now connected to DB_NAME.
global $mysqli; // Ensure we are using the global $mysqli from config.php

if (!$mysqli || $mysqli->connect_error) {
    // If $mysqli from config.php wasn't properly established or failed after DB creation.
    // This might happen if config.php tries to connect to DB_NAME before it's created by this script.
    // Let's close any existing $mysqli and reconnect explicitly.
    if ($mysqli) $mysqli->close();
    $mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($mysqli->connect_error) {
        die("Connection to database `$db_name` failed: " . $mysqli->connect_error . PHP_EOL);
    }
    $mysqli->set_charset("utf8mb4"); // Re-set charset for the new connection
    echo "Re-established connection to database `$db_name`." . PHP_EOL;
} else {
    echo "Using existing connection to database `$db_name` from config.php." . PHP_EOL;
}


// --- Step 4: Read the setup.sql file ---
$sql_file_path = __DIR__ . '/setup.sql';
if (!file_exists($sql_file_path)) {
    die("Error: `setup.sql` not found at: " . $sql_file_path . PHP_EOL);
}
$sql_commands = file_get_contents($sql_file_path);
if ($sql_commands === false) {
    die("Error reading `setup.sql`." . PHP_EOL);
}
echo "Successfully read `setup.sql`." . PHP_EOL;

// --- Step 5: Execute the SQL commands from setup.sql ---
// Use multi_query to execute all commands in the file
if ($mysqli->multi_query($sql_commands)) {
    $i = 0;
    do {
        $i++;
        // Store first result set if desired
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
        // Check if there are more results
        if ($mysqli->more_results()) {
            // echo "Processing next result set ($i)..." . PHP_EOL;
        } else {
            // echo "No more result sets." . PHP_EOL;
            break;
        }
    } while ($mysqli->next_result()); // Move to the next result set

    if ($mysqli->errno) {
        die("Error executing SQL from `setup.sql` (after $i queries): " . $mysqli->error . PHP_EOL);
    } else {
        echo "Successfully executed all commands from `setup.sql`." . PHP_EOL;
        echo "Database schema should now be initialized in `$db_name`." . PHP_EOL;
    }
} else {
    die("Error executing `setup.sql` with multi_query: " . $mysqli->error . PHP_EOL);
}

// Close the main connection
$mysqli->close();
echo "Database initialization script finished." . PHP_EOL;

?>
