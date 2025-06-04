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
    div.className = 'message';
    div.innerHTML = `<strong>${m.user}</strong>: <span class="text">${m.text}</span>` +
        ` <span class="read">已读 ${m.read_by.length}</span>` +
        ` <div class="meta" style="display:none">${new Date(m.timestamp*1000).toLocaleString()}<br>${m.read_by.join(', ')}</div>`;
    div.querySelector('.text').onclick = () => {
        const meta = div.querySelector('.meta');
        meta.style.display = meta.style.display === 'none' ? 'block' : 'none';
    };
    div.querySelector('.read').onclick = () => {
        const meta = div.querySelector('.meta');
        meta.style.display = meta.style.display === 'none' ? 'block' : 'none';
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
    });
};
setInterval(fetchMessages, 2000);
fetchMessages();
window.addEventListener('beforeunload', () => {
    navigator.sendBeacon('logout.php');
});
