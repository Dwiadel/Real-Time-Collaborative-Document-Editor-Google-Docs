<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $document->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <style>
        .cursor-label {
            position: absolute;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 4px;
            color: white;
            pointer-events: none;
            white-space: nowrap;
            z-index: 100;
        }
        .cursor-line {
            position: absolute;
            width: 2px;
            height: 18px;
            pointer-events: none;
            z-index: 100;
        }
        #editor { position: relative; }
        .conflict-highlight {
            background-color: #fff3cd;
            border-left: 3px solid #ffc107;
            padding-left: 4px;
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navbar -->
    <div class="bg-white shadow px-6 py-3 flex items-center gap-4">
        <a href="{{ route('documents.index') }}" class="text-gray-500 hover:text-gray-800">← Kembali</a>
        <input type="text" id="doc-title" value="{{ $document->title }}"
            class="text-xl font-semibold border-none outline-none flex-1 bg-transparent"/>
        <span id="save-status" class="text-sm text-gray-400">Tersimpan</span>

        <!-- Active Users -->
        <div id="active-users" class="flex gap-1 items-center"></div>

        <button onclick="toggleHistory()"
            class="bg-gray-100 text-gray-700 px-3 py-1 rounded text-sm hover:bg-gray-200">
            📋 History
        </button>

        <button onclick="toggleConflict()"
            class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded text-sm hover:bg-yellow-200">
            ⚡ Who Edited
        </button>
    </div>

    <div class="flex gap-4 px-4">
        <!-- Editor -->
        <div class="flex-1 max-w-4xl mx-auto mt-8 bg-white shadow rounded p-10 min-h-screen relative" id="editor-container">
            <div id="editor" contenteditable="true"
                class="outline-none min-h-screen text-gray-800 text-base leading-relaxed"
                style="white-space: pre-wrap;">{{ $document->content }}</div>
        </div>

        <!-- Panel History -->
        <div id="history-panel" class="hidden w-72 bg-white shadow-lg p-4 mt-8 rounded overflow-y-auto" style="max-height: 80vh;">
            <h3 class="font-bold text-gray-700 mb-3">📋 Riwayat Perubahan</h3>
            <div id="history-list" class="space-y-2">
                <p class="text-gray-400 text-sm">Memuat...</p>
            </div>
        </div>

        <!-- Panel Who Edited What -->
        <div id="conflict-panel" class="hidden w-72 bg-white shadow-lg p-4 mt-8 rounded overflow-y-auto" style="max-height: 80vh;">
            <h3 class="font-bold text-gray-700 mb-3"> Who Edited What</h3>
            <div id="conflict-list" class="space-y-2">
                <p class="text-gray-400 text-sm">Belum ada aktivitas edit...</p>
            </div>
        </div>
    </div>

    <script>
    const docId = {{ $document->id }};
    const currentUser = "{{ auth()->user()->name }}";
    const myColors = ['#FF6B6B','#4ECDC4','#45B7D1','#96CEB4','#FFEAA7','#DDA0DD'];
    const myColorIndex = Math.abs(currentUser.split('').reduce((a,b) => {
        a = ((a<<5)-a)+b.charCodeAt(0); return a&a;
    }, 0)) % myColors.length;
    const myColor = myColors[myColorIndex];

    let saveTimeout;
    let editLog = [];
    let lastContent = '';
    let lastTitle = '';
    let isEditing = false;

    // ===== USER BADGE =====
    function addUserBadge(name, color) {
        const existing = document.getElementById('badge-' + name);
        if (existing) return;
        const badge = document.createElement('div');
        badge.id = 'badge-' + name;
        badge.className = 'px-2 py-1 rounded text-white text-xs font-bold';
        badge.style.backgroundColor = color;
        badge.textContent = name.charAt(0).toUpperCase();
        badge.title = name;
        document.getElementById('active-users').appendChild(badge);
    }

    addUserBadge(currentUser, myColor);

    // ===== AUTO SAVE =====
    function autoSave() {
        isEditing = true;
        document.getElementById('save-status').textContent = 'Menyimpan...';
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            const title = document.getElementById('doc-title').value;
            const content = document.getElementById('editor').innerText;
            fetch(`/documents/${docId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ title, content })
            }).then(() => {
                document.getElementById('save-status').textContent = 'Tersimpan ✓';
                lastContent = content;
                lastTitle = title;
                isEditing = false;
                logEdit(currentUser, myColor, content);
            });
        }, 1000);
    }

    document.getElementById('editor').addEventListener('input', autoSave);
    document.getElementById('doc-title').addEventListener('input', autoSave);

    // ===== POLLING REAL-TIME =====
    function pollDocument() {
        if (isEditing) return;
        fetch(`/documents/${docId}/poll`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            const editor = document.getElementById('editor');
            const titleEl = document.getElementById('doc-title');

            if (data.content !== lastContent && data.updated_by !== currentUser) {
                editor.innerText = data.content;
                lastContent = data.content;
                document.getElementById('save-status').textContent = (data.updated_by || 'User lain') + ' sedang mengedit...';
                setTimeout(() => {
                    document.getElementById('save-status').textContent = 'Tersimpan ✓';
                }, 2000);
                addUserBadge(data.updated_by || 'User', getUserColor(data.updated_by || 'User'));
                logEdit(data.updated_by || 'User', getUserColor(data.updated_by || 'User'), data.content);
            }

            if (data.title !== lastTitle && data.updated_by !== currentUser) {
                titleEl.value = data.title;
                lastTitle = data.title;
            }
        })
        .catch(() => {});
    }

    // Inisialisasi nilai awal
    lastContent = document.getElementById('editor').innerText;
    lastTitle = document.getElementById('doc-title').value;

    // Poll setiap 2 detik
setInterval(pollDocument, 2000);

// Kirim status aktif setiap 3 detik
setInterval(() => {
    fetch(`/documents/${docId}/active`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({})
    });
}, 3000);

// Cek user aktif setiap 3 detik
setInterval(() => {
    fetch(`/documents/${docId}/active-users`)
        .then(res => res.json())
        .then(users => {
            const container = document.getElementById('active-users');
            container.innerHTML = '';
            users.forEach(user => {
                const badge = document.createElement('div');
                badge.className = 'px-2 py-1 rounded text-white text-xs font-bold';
                badge.style.backgroundColor = user.color;
                badge.textContent = user.name.charAt(0).toUpperCase();
                badge.title = user.name + ' sedang online';
                container.appendChild(badge);
            });
        });
}, 3000);

    // ===== CONFLICT LOG =====
    function logEdit(userName, color, content) {
        const time = new Date().toLocaleTimeString('id-ID');
        const preview = content.substring(0, 30) + (content.length > 30 ? '...' : '');
        editLog.unshift({ userName, color, time, preview });
        if (editLog.length > 10) editLog.pop();
        updateConflictPanel();
    }

    function updateConflictPanel() {
        const list = document.getElementById('conflict-list');
        if (!list) return;
        list.innerHTML = editLog.length === 0
            ? '<p class="text-gray-400 text-sm">Belum ada aktivitas...</p>'
            : editLog.map(log => `
                <div class="border-l-4 pl-2 py-1 mb-2" style="border-color: ${log.color}">
                    <p class="text-sm font-bold" style="color: ${log.color}">${log.userName}</p>
                    <p class="text-xs text-gray-500">${log.time}</p>
                    <p class="text-xs text-gray-600 mt-1">"${log.preview}"</p>
                </div>
            `).join('');
    }

    // ===== HISTORY =====
    function toggleHistory() {
        document.getElementById('conflict-panel').classList.add('hidden');
        const panel = document.getElementById('history-panel');
        panel.classList.toggle('hidden');
        if (!panel.classList.contains('hidden')) loadHistory();
    }

    function toggleConflict() {
        document.getElementById('history-panel').classList.add('hidden');
        document.getElementById('conflict-panel').classList.toggle('hidden');
    }

    function loadHistory() {
        fetch(`/documents/${docId}/history`)
            .then(res => res.json())
            .then(histories => {
                const list = document.getElementById('history-list');
                if (histories.length === 0) {
                    list.innerHTML = '<p class="text-gray-400 text-sm">Belum ada riwayat</p>';
                    return;
                }
                list.innerHTML = histories.map(h => `
                    <div class="border rounded p-2 cursor-pointer hover:bg-blue-50"
                         onclick="restoreHistory('${h.content.replace(/'/g, "\\'")}', '${h.title.replace(/'/g, "\\'")}')">
                        <p class="text-sm font-medium text-gray-700">${h.title}</p>
                        <p class="text-xs text-gray-400">${h.user.name} • ${new Date(h.created_at).toLocaleString('id-ID')}</p>
                    </div>
                `).join('');
            });
    }

    function restoreHistory(content, title) {
        if (confirm('Pulihkan ke versi ini?')) {
            document.getElementById('editor').innerText = content;
            document.getElementById('doc-title').value = title;
            autoSave();
        }
    }
</script>