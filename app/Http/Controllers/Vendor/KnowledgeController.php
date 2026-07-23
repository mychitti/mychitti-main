<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\StoreKnowledgeDoc;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class KnowledgeController extends Controller
{
    /** Per store, so one vendor pasting a book can't bloat the auto-reply context. */
    const MAX_DOCS = 30;

    public function index(Request $request)
    {
        StoreKnowledgeDoc::ensureTable();
        $storeId = Helpers::get_store_id();

        $docs = StoreKnowledgeDoc::where('store_id', $storeId)
            ->when($request->filled('type'), fn($q) => $q->where('doc_type', $request->type))
            ->orderByDesc('updated_at')
            ->get();

        $docTypes = StoreKnowledgeDoc::DOC_TYPES;

        return view('vendor-views.knowledge.index', compact('docs', 'docTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'doc_type' => 'required|in:' . implode(',', array_keys(StoreKnowledgeDoc::DOC_TYPES)),
            'title'    => 'required|string|max:200',
            'content'  => 'required|string|max:20000',
        ]);

        StoreKnowledgeDoc::ensureTable();
        $storeId = Helpers::get_store_id();

        if (StoreKnowledgeDoc::where('store_id', $storeId)->count() >= self::MAX_DOCS) {
            Toastr::error('You can keep up to ' . self::MAX_DOCS . ' knowledge documents. Delete one you no longer need first.');
            return back()->withInput();
        }

        StoreKnowledgeDoc::create([
            'store_id' => $storeId,
            'doc_type' => $request->doc_type,
            'title'    => trim((string) $request->title),
            'content'  => trim((string) $request->content),
            'active'   => 1,
        ]);

        Toastr::success('Knowledge added. Auto-reply will use it to answer customer messages.');
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

        $doc = StoreKnowledgeDoc::where('store_id', Helpers::get_store_id())->find($request->id);
        if (!$doc) {
            Toastr::error('Document not found.');
            return back();
        }

        $doc->update([
            'doc_type' => $request->doc_type,
            'title'    => trim((string) $request->title),
            'content'  => trim((string) $request->content),
        ]);

        Toastr::success('Knowledge updated.');
        return back();
    }

    public function toggle(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $doc = StoreKnowledgeDoc::where('store_id', Helpers::get_store_id())->find($request->id);
        if (!$doc) {
            Toastr::error('Document not found.');
            return back();
        }

        $doc->update(['active' => $doc->active ? 0 : 1]);
        Toastr::success($doc->active ? 'Document is now used for auto-reply.' : 'Document paused — auto-reply will not use it.');
        return back();
    }

    public function delete(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        StoreKnowledgeDoc::where('store_id', Helpers::get_store_id())
            ->where('id', $request->id)
            ->delete();

        Toastr::success('Document deleted.');
        return back();
    }
}
