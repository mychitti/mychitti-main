<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreKnowledgeDoc;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

/**
 * Platform-level Auto-Reply Knowledge for MyChitti's own WABA. Uses StoreKnowledgeDoc
 * with the platform sentinel store_id (0) so it shares storage, RAG sync and the
 * auto-reply retrieval path with vendor knowledge, scoped to the platform namespace.
 */
class WhatsAppKnowledgeController extends Controller
{
    const PLATFORM = 0;
    const MAX_DOCS = 60;

    public function index(Request $request)
    {
        StoreKnowledgeDoc::ensureTable();
        StoreKnowledgeDoc::syncMissing(self::PLATFORM);

        $docs = StoreKnowledgeDoc::where('store_id', self::PLATFORM)
            ->when($request->filled('type'), fn($q) => $q->where('doc_type', $request->type))
            ->orderByDesc('updated_at')
            ->get();

        $docTypes = StoreKnowledgeDoc::DOC_TYPES;

        return view('admin-views.whatsapp.knowledge', compact('docs', 'docTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'doc_type' => 'required|in:' . implode(',', array_keys(StoreKnowledgeDoc::DOC_TYPES)),
            'title'    => 'required|string|max:200',
            'content'  => 'required|string|max:20000',
        ]);

        StoreKnowledgeDoc::ensureTable();

        if (StoreKnowledgeDoc::where('store_id', self::PLATFORM)->count() >= self::MAX_DOCS) {
            Toastr::error('You can keep up to ' . self::MAX_DOCS . ' documents. Delete one first.');
            return back()->withInput();
        }

        $doc = StoreKnowledgeDoc::create([
            'store_id' => self::PLATFORM,
            'doc_type' => $request->doc_type,
            'title'    => trim((string) $request->title),
            'content'  => trim((string) $request->content),
            'active'   => 1,
        ]);
        $doc->syncToRag();

        Toastr::success('Knowledge added. MyChitti auto-reply will use it.');
        return back();
    }

    public function update(Request $request)
    {
        $request->validate([
            'id'       => 'required|integer',
            'doc_type' => 'required|in:' . implode(',', array_keys(StoreKnowledgeDoc::DOC_TYPES)),
            'title'    => 'required|string|max:200',
            'content'  => 'required|string|max:20000',
        ]);

        $doc = StoreKnowledgeDoc::where('store_id', self::PLATFORM)->find($request->id);
        if (!$doc) {
            Toastr::error('Document not found.');
            return back();
        }

        $doc->update([
            'doc_type' => $request->doc_type,
            'title'    => trim((string) $request->title),
            'content'  => trim((string) $request->content),
        ]);
        if ($doc->active) {
            $doc->syncToRag();
        }

        Toastr::success('Knowledge updated.');
        return back();
    }

    public function toggle(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $doc = StoreKnowledgeDoc::where('store_id', self::PLATFORM)->find($request->id);
        if (!$doc) {
            Toastr::error('Document not found.');
            return back();
        }

        $doc->update(['active' => $doc->active ? 0 : 1]);
        $doc->active ? $doc->syncToRag() : $doc->removeFromRag();
        Toastr::success($doc->active ? 'Document is now used for auto-reply.' : 'Document paused.');
        return back();
    }

    public function delete(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $doc = StoreKnowledgeDoc::where('store_id', self::PLATFORM)->find($request->id);
        if ($doc) {
            $doc->removeFromRag();
            $doc->delete();
        }

        Toastr::success('Document deleted.');
        return back();
    }
}
