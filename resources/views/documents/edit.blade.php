<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $document->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
</head>
<body class="bg-gray-50">

    <!-- Navbar -->
    <div class="bg-white shadow px-6 py-3 flex items-center gap-4">
        <a href="{{ route('documents.index') }}" class="text-gray-500 hover:text-gray-800">← Kembali</a>
        <input type="text" id="doc-title" value="{{ $document->title }}"
            class="text-xl font-semibold border-none outline-none flex-1 bg-transparent"/>
        <span id="save-status" class="text-sm text-gray-400">Tersimpan</span>
        <button onclick="toggleHistory()"
            class="bg-gray-100 text-gray-700 px-3 py-1 rounded text-sm hover:bg-gray-200">
            📋 History
        </button>
        <div id="active-users" class="flex gap-1"></div>
    </div>

    <div class="flex">
        <!-- Editor -->
        <div class="flex-1 max-w-4xl mx-auto mt-8 bg-white shadow rounded min-h-screen p-10">
            <div id="editor" contenteditable="true"
                class="outline-none min-h-screen text-gray-800 text-base leading-relaxed"
                style="white-space: pre-wrap;">{{ $document->content }}</div>
        </div>

        <!-- Panel History -->
        <div id="history-panel" class="hidden w-72 bg-white shadow-lg p-4 mt-8 mr-4 rounded overflow-y-auto" style="max-height: 80vh;">
            <h3 class="font-bold text-gray-700 mb-3">📋 Riwayat Perubahan</h3>
            <div id="history-list" class="space-y-2">
                <p class="text-gray-400 text-sm">Memuat...</p>
            </div>
        </div>
    </div>

    <script>
        const docId = {{ $document->id }};
        const currentUser = "{{ auth()->user()->name }}";
        let saveTimeout;

        // ===== AUTO SAVE =====
        function autoSave() {
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
                });
            }, 1000);
        }

        document.getElementById('editor').addEventListener('input', autoSave);
        document.getElementById('doc-title').addEventListener('input', autoSave);

        // ===== REAL-TIME DENGAN REVERB =====
        const pusher = new Pusher('{{ env("REVERB_APP_KEY") }}', {
            wsHost: '{{ env("REVERB_HOST", "localhost") }}',
            wsPort: {{ env("REVERB_PORT", 8080) }},
            forceTLS: false,
            enabledTransports: ['ws'],
        });

        const channel = pusher.subscribe('document.{{ $document->id }}');

        channel.bind('document.updated', function(data) {
            if (data.updatedBy !== currentUser) {
                // Update konten dari user lain
                const editor = document.getElementById('editor');
                const titleEl = document.getElementById('doc-title');

                if (editor.innerText !== data.content) {
                    editor.innerText = data.content;
                }
                if (titleEl.value !== data.title) {
                    titleEl.value = data.title;
                }

                // Tampilkan siapa yang sedang edit
                document.getElementById('save-status').textContent = 
                    data.updatedBy + ' sedang mengedit...';
                setTimeout(() => {
                    document.getElementById('save-status').textContent = 'Tersimpan ✓';
                }, 2000);
            }
        });

        // ===== HISTORY =====
        function toggleHistory() {
            const panel = document.getElementById('history-panel');
            panel.classList.toggle('hidden');
            if (!panel.classList.contains('hidden')) {
                loadHistory();
            }
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
</body>
</html>