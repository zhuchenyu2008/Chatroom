let lastId = 0;
const msgs = document.getElementById('messages');
const form = document.getElementById('send-form');
const input = document.getElementById('message');
function fetchMessages() {
    fetch('fetch.php?since=' + lastId)
        .then(r => r.json())
        .then(data => {
            document.getElementById('online-count').textContent = '在线人数: ' + data.online;
            if (data.messages.length) {
                const ids = [];
                data.messages.forEach(m => {
                    addMessage(m);
                    lastId = Math.max(lastId, m.id);
                    ids.push(m.id);
                });
                // mark as read
                fetch('read.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'ids[]=' + ids.join('&ids[]=')
                });
            }
        });
}
function addMessage(m) {
    const div = document.createElement('div');
    if (m.user === currentUser) {
        div.className = 'message current-user-message';
    } else {
        div.className = 'message';
    }

    const messageDate = new Date(m.timestamp * 1000);
    const roughTime = messageDate.getHours().toString().padStart(2, '0') + ':' + messageDate.getMinutes().toString().padStart(2, '0');
    const detailedTimeStr = messageDate.toLocaleString();
    const readByList = m.read_by.length > 0 ? m.read_by.join(', ') : 'None';

    div.innerHTML = `<strong>${m.user}</strong>: <span class="text">${m.text}</span> <span class="message-time">${roughTime}</span>` +
        ` <span class="read">已读 ${m.read_by.length}</span>` +
        ` <div class="meta" style="display:none">` +
        `  <span class="detailed-time" style="display:none">${detailedTimeStr}</span>` +
        `  <span class="detailed-read-by" style="display:none">Read by: ${readByList}</span>` +
        ` </div>`;

    div.querySelector('.message-time').onclick = () => {
        const meta = div.querySelector('.meta');
        const detailedTime = meta.querySelector('.detailed-time');
        const detailedReadBy = meta.querySelector('.detailed-read-by');

        detailedTime.style.display = detailedTime.style.display === 'none' ? 'block' : 'none';

        if (detailedTime.style.display === 'block' || detailedReadBy.style.display === 'block') {
            meta.style.display = 'block';
        } else {
            meta.style.display = 'none';
        }
    };

    div.querySelector('.read').onclick = () => {
        const meta = div.querySelector('.meta');
        const detailedTime = meta.querySelector('.detailed-time');
        const detailedReadBy = meta.querySelector('.detailed-read-by');

        detailedReadBy.style.display = detailedReadBy.style.display === 'none' ? 'block' : 'none';

        if (detailedTime.style.display === 'block' || detailedReadBy.style.display === 'block') {
            meta.style.display = 'block';
        } else {
            meta.style.display = 'none';
        }
    };
    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
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
        input.focus(); // Add this line
    });
};
setInterval(fetchMessages, 2000);
fetchMessages();
window.addEventListener('beforeunload', () => {
    navigator.sendBeacon('logout.php');
});
