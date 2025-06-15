let lastId = 0;
const msgs = document.getElementById('messages');
const form = document.getElementById('send-form');
const input = document.getElementById('message');
const destructTimeSelector = document.getElementById('self-destruct-time'); // Added
let activeMessageTimers = {}; // Added
let currentUserSentBurnMessages = new Set(); // Added: Track BAR messages sent by current user

// currentUser should be defined in chat.php, e.g., <script>const currentUser = '...';</script>

// Function to load user preferences
function loadPreferences() {
    const preferredDestructTime = localStorage.getItem('preferredDestructTime');
    if (preferredDestructTime && destructTimeSelector) {
        destructTimeSelector.value = preferredDestructTime;
    } else if (destructTimeSelector) {
        destructTimeSelector.value = "60"; // Default to 1 minute
    }
}

// Helper function to remove message elements with optional animation
function removeMessageElement(messageId, immediate = false) {
    const messageElement = document.getElementById('message-' + messageId);
    if (messageElement) {
        if (immediate) {
            messageElement.remove();
        } else {
            messageElement.classList.add('message-fading-out');
            setTimeout(() => {
                messageElement.remove();
            }, 500); // Animation duration
        }
    }
    // Clear any associated timers
    if (activeMessageTimers[messageId]) {
        if (activeMessageTimers[messageId].intervalId) {
            clearInterval(activeMessageTimers[messageId].intervalId);
        }
        if (activeMessageTimers[messageId].timeoutId) {
            clearTimeout(activeMessageTimers[messageId].timeoutId);
        }
        delete activeMessageTimers[messageId];
    }
    // Also clean up from the set tracking user's own BAR messages
    if (currentUserSentBurnMessages.has(messageId)) {
        currentUserSentBurnMessages.delete(messageId);
    }
}

// Helper function to format remaining time
function formatRemainingTime(totalSeconds) {
    if (totalSeconds <= 0) {
        return 'Expired';
    }

    const days = Math.floor(totalSeconds / 86400);
    totalSeconds %= 86400;
    const hours = Math.floor(totalSeconds / 3600);
    totalSeconds %= 3600;
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = Math.floor(totalSeconds % 60);

    if (days > 0) {
        return `${days}d ${hours}h`;
    }
    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }
    if (minutes > 0) {
        return `${minutes}m ${seconds}s`;
    }
    return `${seconds}s`;
}


function fetchMessages() {
    fetch('fetch.php?since=' + lastId)
        .then(r => {
            if (!r.ok) {
                // Attempt to parse error if JSON, otherwise use statusText
                return r.json().catch(() => null).then(errorBody => {
                    throw new Error('Network response was not ok: ' + r.statusText + (errorBody ? ' - ' + JSON.stringify(errorBody) : ''));
                });
            }
            return r.json();
        })
        .then(data => {
            if (!data || !data.messages) {
                console.warn('Received no messages data or malformed data:', data);
                return; // Exit if data is not as expected
            }

            document.getElementById('online-count').textContent = '在线人数: ' + data.online;

            const messagesContainer = msgs;
            const isScrolledToBottom = messagesContainer.scrollHeight - messagesContainer.clientHeight <= messagesContainer.scrollTop + 5;
            let newMessagesWereAdded = false;
            const idsToMarkRead = [];

            data.messages.forEach(m => {
                if (!m || typeof m.id === 'undefined') {
                    console.warn('Skipping malformed message object:', m);
                    return; // Skip this message
                }

                lastId = Math.max(lastId, m.id);
                idsToMarkRead.push(m.id);

                let existingMsgDiv = document.getElementById('message-' + m.id);

                const readByArray = m.read_by || [];
                const readCount = readByArray.length;
                const readByList = readByArray.length > 0 ? readByArray.join(', ') : 'None';

                if (existingMsgDiv) {
                    const readSpan = existingMsgDiv.querySelector('.read');
                    if (readSpan) readSpan.textContent = '已读 ' + readCount;

                    const detailedReadBySpan = existingMsgDiv.querySelector('.detailed-read-by');
                    if (detailedReadBySpan) detailedReadBySpan.textContent = 'Read by: ' + readByList;

                    // Potentially update destruct timer display if message already exists (e.g. for other users' view)
                    // For now, addMessage handles initial rendering of timer. Future enhancement could update existing.

                } else {
                    addMessage(m);
                    newMessagesWereAdded = true;
                }
            });

            if (newMessagesWereAdded && isScrolledToBottom) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }

            if (idsToMarkRead.length > 0) {
                fetch('read.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'ids[]=' + idsToMarkRead.join('&ids[]=')
                }).catch(error => console.error('Error marking messages as read:', error));
            }

            // Check for current user's sent BAR messages that are no longer on server
            const serverMessageIds = new Set(data.messages.map(msg => msg.id));
            const localBurnMessageIdsToCheck = Array.from(currentUserSentBurnMessages); // Create a copy

            localBurnMessageIdsToCheck.forEach(localMessageId => {
                if (!serverMessageIds.has(localMessageId)) {
                    // This message was a 'burn after read' sent by current user,
                    // but it's no longer coming from the server (likely read and deleted).
                    // So, remove it from the sender's UI as well.
                    console.log(`Detected burn-after-read message ${localMessageId} (sent by current user) is no longer on server. Removing from local UI.`);
                    removeMessageElement(localMessageId);
                    // removeMessageElement will also clean it up from currentUserSentBurnMessages Set.
                }
            });

        })
        .catch(error => {
            console.error('Error fetching messages:', error);
            // Could update UI to show error to user, e.g. a small banner
        });
}

function addMessage(m) {
    const div = document.createElement('div');
    div.id = 'message-' + m.id;

    const messageDate = new Date(m.timestamp * 1000); // Assuming m.timestamp is in seconds
    const roughTime = messageDate.getHours().toString().padStart(2, '0') + ':' + messageDate.getMinutes().toString().padStart(2, '0');

    let initialDestructInfoText = ''; // Initial text before dynamic updates

    // Determine initial text based on destruct_type for the span structure
    if (m.destruct_type === 'timed' && m.destruct_duration && m.timestamp) {
        const initialRemaining = (parseFloat(m.timestamp) + parseInt(m.destruct_duration, 10)) - (Date.now() / 1000);
        if (initialRemaining <= 0) {
            initialDestructInfoText = ' (Expired)';
        } else {
            initialDestructInfoText = ` (${formatRemainingTime(initialRemaining)})`;
        }
    } else if (m.destruct_type === 'burn_after_read') {
        initialDestructInfoText = ` (🔥 Burn after read)`;
    } else if (m.destruct_type === 'never') {
        initialDestructInfoText = ''; // Or ' (Permanent)'
    }


    if (m.user === 'system') {
        div.className = 'message system-message';
        div.innerHTML = `<span class="text">${m.text}</span> <span class="message-time">${roughTime}</span>`;
    } else {
        if (typeof currentUser !== 'undefined' && m.user === currentUser) {
            div.className = 'message current-user-message';
        } else {
            div.className = 'message';
        }

        const detailedTimeStr = messageDate.toLocaleString();
        const readByArray = m.read_by || [];
        const readByList = readByArray.length > 0 ? readByArray.join(', ') : 'None';
        const readCount = readByArray.length;

        div.innerHTML = `<strong>${m.user}</strong> <span class="text">${m.text}</span><span class="destruct-info">${initialDestructInfoText}</span> <span class="message-time">${roughTime}</span>` +
            ` <span class="read">已读 ${readCount}</span>` +
            ` <div class="meta" style="display:none">` +
            `  <span class="detailed-time" style="display:none">${detailedTimeStr}</span>` +
            `  <span class="detailed-read-by" style="display:none">Read by: ${readByList}</span>` +
            ` </div>`;
    }
    msgs.appendChild(div); // Add message to DOM first

    // --- SELF-DESTRUCT LOGIC WITH TIMERS (after element is in DOM) ---
    const destructInfoSpan = div.querySelector('.destruct-info');

    if (m.destruct_type === 'timed' && m.destruct_duration && m.timestamp && destructInfoSpan) {
        const messageEndTime = parseFloat(m.timestamp) + parseInt(m.destruct_duration, 10);

        const updateTimerDisplay = () => {
            const nowSeconds = Date.now() / 1000;
            const remainingSeconds = messageEndTime - nowSeconds;

            if (remainingSeconds <= 0) {
                destructInfoSpan.textContent = ' (Expired)';
                removeMessageElement(m.id); // This will also clear the interval via its own logic
            } else {
                destructInfoSpan.textContent = ` (${formatRemainingTime(remainingSeconds)})`;
            }
        };

        if ((messageEndTime - (Date.now() / 1000)) <= 0) {
            // If already expired when this code runs (e.g., due to slight delay or if backend didn't filter)
            destructInfoSpan.textContent = ' (Expired)';
            removeMessageElement(m.id, true); // Remove immediately, no fade
        } else {
            updateTimerDisplay(); // Initial call to set text
            const intervalId = setInterval(updateTimerDisplay, 1000);
            activeMessageTimers[m.id] = { ...activeMessageTimers[m.id], intervalId: intervalId };
        }

    } else if (m.destruct_type === 'burn_after_read' && destructInfoSpan) {
        if (m.user === currentUser) {
            destructInfoSpan.textContent = ' (🔥 阅后即焚 (他人阅读5秒后消失))'; // Updated text for sender
            destructInfoSpan.classList.add('burn-for-recipient');
            currentUserSentBurnMessages.add(m.id); // Track this message
        } else { // Message is from another user
            destructInfoSpan.textContent = ' (🔥 阅后即焚 (5秒后消失))'; // Updated text for recipient
            destructInfoSpan.classList.add('burn-for-me'); // Class for emphasis/styling

            const timeoutId = setTimeout(() => {
                removeMessageElement(m.id); // This will also clear the timeout

                fetch('delete_message.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'id=' + m.id
                })
                .then(response => response.json().catch(() => ({}))) // Allow non-JSON error responses too
                .then(data => {
                    if (data.success) {
                        console.log('Burn after read message ' + m.id + ' confirmed deleted on server.');
                    } else {
                        console.error('Server failed to delete burn after read message ' + m.id + ':', data.error || 'Unknown server error');
                    }
                })
                .catch(error => console.error('Error calling delete_message.php for ' + m.id + ':', error));
            }, 5000); // Changed timeout to 5 seconds
            activeMessageTimers[m.id] = { ...activeMessageTimers[m.id], timeoutId: timeoutId };
        }
    }
    // --- SELF-DESTRUCT LOGIC END ---

    // Scroll logic moved after message and its potential timers are set up
    // const messagesContainer = msgs; // msgs is already defined globally
    // const isScrolledToBottom = messagesContainer.scrollHeight - messagesContainer.clientHeight <= messagesContainer.scrollTop + 5;
    // if (isScrolledToBottom) { // This logic is better handled in fetchMessages after all messages are processed
    //    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    // }


    // Event listeners for meta info toggle (already existing logic)
    if (m.user !== 'system') { // System messages don't have these elements
        const timeElement = div.querySelector('.message-time');
        const readElement = div.querySelector('.read');

        if (timeElement) {
            timeElement.onclick = () => {
                const meta = div.querySelector('.meta');
                if (!meta) return;
                const detailedTime = meta.querySelector('.detailed-time');
                if (!detailedTime) return;

                detailedTime.style.display = detailedTime.style.display === 'none' ? 'block' : 'none';

                const detailedReadBy = meta.querySelector('.detailed-read-by');
                // Check if detailedReadBy exists before accessing its style
                if (detailedTime.style.display === 'block' || (detailedReadBy && detailedReadBy.style.display === 'block')) {
                    meta.style.display = 'block';
                } else {
                    meta.style.display = 'none';
                }
            };
        }

        if (readElement) {
            readElement.onclick = () => {
                const meta = div.querySelector('.meta');
                if (!meta) return;
                const detailedReadBy = meta.querySelector('.detailed-read-by');
                if (!detailedReadBy) return;

                detailedReadBy.style.display = detailedReadBy.style.display === 'none' ? 'block' : 'none';

                const detailedTime = meta.querySelector('.detailed-time');
                // Check if detailedTime exists before accessing its style
                if ((detailedTime && detailedTime.style.display === 'block') || detailedReadBy.style.display === 'block') {
                    meta.style.display = 'block';
                } else {
                    meta.style.display = 'none';
                }
            };
        }
    }
    // No scrollTop adjustment here, it's handled in fetchMessages
}

form.onsubmit = e => {
    e.preventDefault();
    const text = input.value.trim();
    if (!text) return;

    const selectedDestructTime = destructTimeSelector ? destructTimeSelector.value : "60"; // Default if selector not found

    fetch('send.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'text=' + encodeURIComponent(text) + '&destruct_time=' + selectedDestructTime
    }).then(response => {
        if (!response.ok) {
            // Try to parse error from backend if available
            return response.json().catch(() => null).then(errorBody => {
                 throw new Error('Network response was not ok: ' + response.statusText + (errorBody ? ' - ' + JSON.stringify(errorBody) : ''));
            });
        }
        return response.json(); // Assuming send.php might return the sent message or a success status
    }).then(data => {
        // console.log('Message sent successfully:', data); // Optional: log success
        input.value = '';
        input.focus();
        if (destructTimeSelector) { // Save preference only if selector exists
             localStorage.setItem('preferredDestructTime', selectedDestructTime);
        }
        // Optimistically add message or rely on next fetchMessages call
        // fetchMessages(); // Immediate fetch can be good, but also handled by interval
    }).catch(error => console.error('Error sending message:', error));
};

// Ensure fetchMessages is defined before setInterval uses it.
const messageFetchInterval = setInterval(fetchMessages, 2000);

// Initial actions when the script loads
loadPreferences(); // Load user preferences for self-destruct time
fetchMessages(); // Initial fetch of messages

window.addEventListener('beforeunload', () => {
    if (navigator.sendBeacon) {
        navigator.sendBeacon('logout.php');
    } else {
        // Synchronous XHR is deprecated and unreliable here, but was a common fallback.
        // For modern browsers, sendBeacon is preferred. If not available, it might not send.
        // Using fetch with keepalive is a more modern alternative for unreliable sendBeacon fallbacks.
        try {
          fetch('logout.php', { method: 'POST', keepalive: true, credentials: 'omit' });
        } catch(e) {
          // Silently fail if fetch with keepalive also fails (e.g. browser doesn't support)
        }
    }
});
