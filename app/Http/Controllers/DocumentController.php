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
            'title'   => $request->title,
            'content' => $request->content,
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
}