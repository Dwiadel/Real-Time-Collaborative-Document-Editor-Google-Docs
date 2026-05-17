<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentHistory;
use App\Events\DocumentUpdated;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = Document::where('user_id', auth()->id())
                            ->latest()
                            ->get();
        return view('documents.index', compact('documents'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $document = Document::create([
            'user_id' => auth()->id(),
            'title'   => 'Dokumen Tanpa Judul',
            'content' => '',
        ]);
        return redirect()->route('documents.edit', $document->id);
    }

    public function show(Document $document)
    {
        //
    }

    public function edit(Document $document)
    {
        return view('documents.edit', compact('document'));
    }

    public function update(Request $request, Document $document)
{
    $document->update([
        'title'      => $request->title,
        'content'    => $request->content,
        'updated_by' => auth()->user()->name,
    ]);

        // Simpan history setiap 30 detik
        $lastHistory = DocumentHistory::where('document_id', $document->id)
                        ->latest()->first();

        $shouldSave = !$lastHistory ||
                      $lastHistory->created_at->diffInSeconds(now()) >= 30;

        if ($shouldSave) {
            DocumentHistory::create([
                'document_id' => $document->id,
                'user_id'     => auth()->id(),
                'title'       => $request->title,
                'content'     => $request->content,
            ]);
        }

        // Broadcast ke user lain
        broadcast(new DocumentUpdated(
            $document->id,
            $request->title,
            $request->content,
            auth()->user()->name
        ))->toOthers();

        return response()->json(['success' => true]);
    }

    public function destroy(Document $document)
    {
        $document->delete();
        return redirect()->route('documents.index');
    }

    public function history(Document $document)
    {
        $histories = DocumentHistory::where('document_id', $document->id)
                    ->with('user')
                    ->latest()
                    ->take(20)
                    ->get();
        return response()->json($histories);
    }
    public function cursor(Request $request, Document $document)
{
    $colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD'];
    $colorIndex = abs(crc32(auth()->user()->name)) % count($colors);
    
    broadcast(new \App\Events\CursorMoved(
        $document->id,
        auth()->user()->name,
        $colors[$colorIndex],
        $request->position ?? 0
    ))->toOthers();

    return response()->json(['success' => true]);
}

public function poll(Document $document)
{
    return response()->json([
        'title'      => $document->title,
        'content'    => $document->content,
        'updated_by' => $document->updatedBy ?? '',
        'updated_at' => $document->updated_at,
    ]);
}

public function setActive(Request $request, Document $document)
{
    cache()->put(
        'active_' . $document->id . '_' . auth()->id(),
        auth()->user()->name,
        now()->addSeconds(5)
    );
    return response()->json(['success' => true]);
}

public function getActiveUsers(Document $document)
{
    $users = [];
    $allUsers = \App\Models\User::all();
    foreach ($allUsers as $user) {
        $key = 'active_' . $document->id . '_' . $user->id;
        if (cache()->has($key)) {
            $users[] = [
                'name'  => $user->name,
                'color' => $this->getUserColor($user->name),
            ];
        }
    }
    return response()->json($users);
}

private function getUserColor(string $name): string
{
    $colors = ['#FF6B6B','#4ECDC4','#45B7D1','#96CEB4','#FFEAA7','#DDA0DD'];
    $index = abs(crc32($name)) % count($colors);
    return $colors[$index];
}
}