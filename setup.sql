-- Create the messages table
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    message_text TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create the online_users table
CREATE TABLE online_users (
    username VARCHAR(255) PRIMARY KEY,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Optional: Add an index on the username in the messages table if frequent lookups by user are expected.
-- CREATE INDEX idx_username ON messages (username);

-- Optional: Add an index on the timestamp in the messages table for faster sorting/filtering by time.
-- CREATE INDEX idx_timestamp ON messages (timestamp);
