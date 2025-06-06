let lastId = 0;
const msgs = document.getElementById('messages');
const form = document.getElementById('send-form');
const input = document.getElementById('message');
// currentUser should be defined in chat.php, e.g., <script>const currentUser = '...';</script>

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
        })
        .catch(error => {
            console.error('Error fetching messages:', error);
            // Could update UI to show error to user, e.g. a small banner
        });
}

function addMessage(m) {
    const div = document.createElement('div');
    div.id = 'message-' + m.id;

    const messageDate = new Date(m.timestamp * 1000);
    const roughTime = messageDate.getHours().toString().padStart(2, '0') + ':' + messageDate.getMinutes().toString().padStart(2, '0');

    if (m.user === 'system') {
        div.className = 'message system-message';
        div.innerHTML = `<span class="text">${m.text}</span> <span class="message-time">${roughTime}</span>`;
    } else {
        // Ensure currentUser is available, or provide a fallback.
        // This script assumes 'currentUser' is a global variable defined in the HTML.
        if (typeof currentUser !== 'undefined' && m.user === currentUser) {
            div.className = 'message current-user-message';
        } else {
            div.className = 'message';
        }

        const detailedTimeStr = messageDate.toLocaleString();
        const readByArray = m.read_by || [];
        const readByList = readByArray.length > 0 ? readByArray.join(', ') : 'None';
        const readCount = readByArray.length;

        div.innerHTML = `<strong>${m.user}</strong> <span class="text">${m.text}</span> <span class="message-time">${roughTime}</span>` +
            ` <span class="read">已读 ${readCount}</span>` +
            ` <div class="meta" style="display:none">` +
            `  <span class="detailed-time" style="display:none">${detailedTimeStr}</span>` +
            `  <span class="detailed-read-by" style="display:none">Read by: ${readByList}</span>` +
            ` </div>`;

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
    msgs.appendChild(div);
    // No scrollTop adjustment here
}

form.onsubmit = e => {
    e.preventDefault();
    const text = input.value.trim();
    if (!text) return;
    fetch('send.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'text=' + encodeURIComponent(text)
    }).then(() => {
        input.value = '';
        input.focus();
        // Call fetchMessages immediately after sending for faster update of own message
        // However, this might conflict with the setInterval if not handled carefully.
        // For now, rely on the setInterval or a slight delay.
        // A common pattern is to add the message optimistically to the UI here.
    }).catch(error => console.error('Error sending message:', error));
};

// Ensure fetchMessages is defined before setInterval uses it.
const messageFetchInterval = setInterval(fetchMessages, 2000);
fetchMessages(); // Initial fetch

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
