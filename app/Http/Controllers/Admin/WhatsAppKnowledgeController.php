<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAutoReply;
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

    /** Placeholder sender for the test console. Never messaged; only keys the (empty) history. */
    const PREVIEW_FROM = '910000000000';

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

    /**
     * Test console: ask the assistant something and see the answer it would send, plus the
     * knowledge it used to get there.
     *
     * Runs the real auto-reply composer at platform scope (storeId null), so what comes back is
     * what a vendor or customer messaging the MyChitti number would actually receive. Nothing is
     * sent, nothing is written to the message log, and no wallet is metered.
     */
    public function preview(Request $request)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        StoreKnowledgeDoc::ensureTable();

        $active = StoreKnowledgeDoc::where('store_id', self::PLATFORM)->where('active', 1)->count();
        if ($active === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No active knowledge documents - the assistant has nothing to answer from.',
            ], 422);
        }

        try {
            $job = new SendAutoReply(null, self::PREVIEW_FROM, trim($request->input('message')));
            $out = $job->preview();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('WA knowledge preview failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Preview failed: ' . $e->getMessage()], 500);
        }

        if (trim((string) ($out['reply'] ?? '')) === '') {
            return response()->json([
                'success' => false,
                'message' => 'The AI service returned nothing. Check the AI service is reachable and a model is configured.',
            ], 502);
        }

        return response()->json([
            'success'   => true,
            'reply'     => $out['reply'],
            'escalated' => (bool) ($out['escalated'] ?? false),
            'source'    => $out['debug']['source'] ?? 'unknown',
            'doc_count' => $out['debug']['doc_count'] ?? 0,
            'rag'       => $out['debug']['rag'] ?? [],
            'knowledge' => mb_substr((string) ($out['debug']['knowledge'] ?? ''), 0, 6000),
            'system'    => mb_substr((string) ($out['debug']['system_prompt'] ?? ''), 0, 12000),
        ]);
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
