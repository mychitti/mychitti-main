<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\DocValidationLog;
use App\Models\DocValidationRule;
use App\Services\DocumentValidationService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

/**
 * Doc AI Validation — where the admin teaches the document checker. Rules added here are
 * injected into the prompt every time a vendor uploads a document, on registration, in the
 * vendor panel and in the admin store form.
 */
class DocValidationController extends Controller
{
    const MAX_RULES = 80;

    public function index(Request $request)
    {
        DocValidationRule::ensureTable();
        DocValidationLog::ensureTable();

        $rules = DocValidationRule::query()
            ->when($request->filled('type'), fn($q) => $q->where('doc_type', $request->type))
            ->orderByDesc('updated_at')
            ->get();

        $logs = DocValidationLog::with('store')
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        $docTypes = DocValidationRule::DOC_TYPES;
        $settings = DocumentValidationService::settings();
        // Shown so the admin can see which AI agent's provider/model/key this borrows.
        $agent = DocumentValidationService::resolvedAgent();

        return view('admin-views.doc-validation.index', compact('rules', 'logs', 'docTypes', 'settings', 'agent'));
    }

    public function settings(Request $request)
    {
        $request->validate([
            'mode'     => 'required|in:block,warn',
            'model'    => 'nullable|string|max:60',
            'on_error' => 'required|in:allow,block',
        ]);

        $setting = BusinessSetting::firstOrNew(['key' => 'doc_ai_validation']);
        $setting->value = json_encode([
            'status'   => $request->has('status') ? 1 : 0,
            'mode'     => $request->mode,
            // Blank falls through to the model on the admin's active AI agent.
            'model'    => trim((string) $request->model),
            'on_error' => $request->on_error,
            'sources'  => [
                'registration' => $request->has('source_registration') ? 1 : 0,
                'vendor_panel' => $request->has('source_vendor_panel') ? 1 : 0,
                'admin_panel'  => $request->has('source_admin_panel') ? 1 : 0,
            ],
        ]);
        $setting->save();

        Toastr::success('Document validation settings updated.');

        return back();
    }

    public function store(Request $request)
    {
        $request->validate([
            'doc_type' => 'required|in:' . implode(',', array_keys(DocValidationRule::DOC_TYPES)),
            'title'    => 'required|string|max:200',
            'rule'     => 'required|string|max:5000',
            'severity' => 'required|in:block,warn',
        ]);

        DocValidationRule::ensureTable();

        if (DocValidationRule::count() >= self::MAX_RULES) {
            Toastr::error('You can keep up to ' . self::MAX_RULES . ' rules. Delete one first.');

            return back()->withInput();
        }

        DocValidationRule::create([
            'doc_type' => $request->doc_type,
            'title'    => trim((string) $request->title),
            'rule'     => trim((string) $request->rule),
            'severity' => $request->severity,
            'active'   => 1,
        ]);

        Toastr::success('Rule added. It applies to the next document uploaded.');

        return back();
    }

    public function update(Request $request)
    {
        $request->validate([
            'id'       => 'required|integer',
            'doc_type' => 'required|in:' . implode(',', array_keys(DocValidationRule::DOC_TYPES)),
            'title'    => 'required|string|max:200',
            'rule'     => 'required|string|max:5000',
            'severity' => 'required|in:block,warn',
        ]);

        $rule = DocValidationRule::find($request->id);
        if (!$rule) {
            Toastr::error('Rule not found.');

            return back();
        }

        $rule->update([
            'doc_type' => $request->doc_type,
            'title'    => trim((string) $request->title),
            'rule'     => trim((string) $request->rule),
            'severity' => $request->severity,
        ]);

        Toastr::success('Rule updated.');

        return back();
    }

    public function toggle(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $rule = DocValidationRule::find($request->id);
        if (!$rule) {
            Toastr::error('Rule not found.');

            return back();
        }

        $rule->update(['active' => $rule->active ? 0 : 1]);
        Toastr::success($rule->active ? 'Rule is now applied.' : 'Rule paused.');

        return back();
    }

    public function delete(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $rule = DocValidationRule::find($request->id);
        if ($rule) {
            $rule->delete();
        }

        Toastr::success('Rule deleted.');

        return back();
    }

    /**
     * Test console: run a real document through the checker with the rules as they stand,
     * so the admin can see the verdict before a vendor does. Nothing is stored on a store.
     */
    public function preview(Request $request, DocumentValidationService $validator)
    {
        $request->validate([
            'doc_type'        => 'required|in:id_doc,gst_doc,fssai_doc,other',
            'test_file'       => 'required|file|mimes:jpeg,jpg,png,webp,pdf|max:10240',
            'expected_number' => 'nullable|string|max:100',
        ]);

        $result = $validator->validate(
            $request->file('test_file'),
            $request->doc_type,
            ['number' => $request->expected_number],
            ['source' => 'admin_panel', 'store_id' => null, 'vendor_id' => null, 'force' => true]
        );

        return response()->json(['success' => true, 'result' => $result]);
    }
}
