<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dokumen Saya</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-10 px-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">📄 Dokumen Saya</h1>
            <form method="POST" action="{{ route('documents.store') }}">
                @csrf
                <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    + Buat Dokumen
                </button>
            </form>
        </div>

        @forelse($documents as $doc)
        <div class="bg-white rounded shadow p-4 mb-3 flex justify-between items-center">
            <a href="{{ route('documents.edit', $doc->id) }}"
               class="text-lg font-medium text-gray-700 hover:text-blue-600">
                {{ $doc->title }}
            </a>
            <div class="flex gap-2 items-center">
                <span class="text-sm text-gray-400">
                    {{ $doc->updated_at->diffForHumans() }}
                </span>
                <form method="POST" action="{{ route('documents.destroy', $doc->id) }}">
                    @csrf @method('DELETE')
                    <button class="text-red-500 text-sm hover:underline"
                        onclick="return confirm('Hapus dokumen ini?')">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="text-center text-gray-500 mt-20">
            <p class="text-xl">Belum ada dokumen</p>
            <p>Klik "Buat Dokumen" untuk memulai</p>
        </div>
        @endforelse
    </div>
</body>
</html>