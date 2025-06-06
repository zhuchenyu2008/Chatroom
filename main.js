let lastId = 0;
const msgs = document.getElementById('messages');
const form = document.getElementById('send-form');
const input = document.getElementById('message');

function fetchMessages() {
    fetch('fetch.php?since=' + lastId)
        .then(r => {
            if (!r.ok) {
                throw new Error('Network response was not ok: ' + r.statusText);
            }
            return r.json();
        })
        .then(data => {
            document.getElementById('online-count').textContent = '在线人数: ' + data.online;

            const messagesContainer = msgs;
            const isScrolledToBottom = messagesContainer.scrollHeight - messagesContainer.clientHeight <= messagesContainer.scrollTop + 5;

            messagesContainer.innerHTML = '';

            const idsToMarkRead = [];

            if (data.messages && data.messages.length) { // Added check for data.messages
                data.messages.forEach(m => {
                    addMessage(m);
                    lastId = Math.max(lastId, m.id);
                    idsToMarkRead.push(m.id);
                });
            }

            if (isScrolledToBottom) {
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
        if (m.user === currentUser) {
            div.className = 'message current-user-message';
        } else {
            div.className = 'message';
        }

        const detailedTimeStr = messageDate.toLocaleString();
        const readByArray = m.read_by || []; // Ensure read_by is an array
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
                if ((detailedTime && detailedTime.style.display === 'block') || detailedReadBy.style.display === 'block') {
                    meta.style.display = 'block';
                } else {
                    meta.style.display = 'none';
                }
            };
        }
    }
    msgs.appendChild(div);
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
        // Optionally, immediately call fetchMessages or add the message optimistically
        // For now, relying on the interval fetch
    }).catch(error => console.error('Error sending message:', error));
};

setInterval(fetchMessages, 2000);
fetchMessages(); // Initial fetch

window.addEventListener('beforeunload', () => {
    // Use sendBeacon if data must be sent, otherwise, this might not always complete
    if (navigator.sendBeacon) {
        navigator.sendBeacon('logout.php');
    } else {
        // Fallback for older browsers - less reliable
        fetch('logout.php', { method: 'POST', keepalive: true, credentials: 'omit' });
    }
});
