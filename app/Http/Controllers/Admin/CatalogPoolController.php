<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Jobs\VerifyCatalogSuggestions;
use App\Models\CatalogItem;
use App\Models\CatalogSuggestion;
use App\Models\InventoryItem;
use App\Services\CatalogPool;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Curation of the shared item pool — the one record per product that every store adopts.
 *
 * Two screens: the pool itself, and the queue of things stores typed that the pool does not have.
 * Nothing else in the platform writes to catalog_items, so every rule about what belongs in the
 * pool lives here.
 */
class CatalogPoolController extends Controller
{
    public function index(Request $request)
    {
        CatalogPool::ensureSchema();

        $search = trim($request->get('search', ''));
        $form   = trim($request->get('form', ''));
        $domain = $request->get('domain', CatalogPool::DOMAIN_PHARMACY);

        $items = CatalogItem::where('domain', $domain)
            ->where('status', '!=', CatalogItem::STATUS_MERGED)
            ->when($search, fn($q) => $q->where(fn($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%")))
            ->when($form, fn($q) => $q->where('form', $form))
            ->orderByDesc('usage_count')
            ->orderBy('name')
            ->paginate(30)
            ->appends($request->query());

        $counts = [
            'total'    => CatalogItem::where('domain', $domain)->where('status', CatalogItem::STATUS_ACTIVE)->count(),
            'adopted'  => CatalogItem::where('domain', $domain)->where('usage_count', '>', 0)->count(),
            'pending'  => CatalogSuggestion::where('domain', $domain)->whereIn('status', CatalogSuggestion::OPEN_STATUSES)->count(),
        ];

        $forms = CatalogPool::FORMS;

        return view('admin-views.catalog.index', compact('items', 'search', 'form', 'domain', 'counts', 'forms'));
    }

    public function store(Request $request)
    {
        CatalogPool::ensureSchema();

        $request->validate([
            'name'          => 'required|string|max:200',
            'brand'         => 'nullable|string|max:150',
            'strength_text' => 'nullable|string|max:100',
            'form'          => 'nullable|string|max:50',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = $request->only(['name', 'brand', 'strength_text', 'form']);
        if ($request->hasFile('image')) {
            $data['image'] = Helpers::upload('catalog-item/', $request->file('image')->getClientOriginalExtension(), $request->file('image'));
        }

        $before = CatalogItem::where('domain', $request->get('domain', CatalogPool::DOMAIN_PHARMACY))->count();
        $item   = CatalogPool::upsert($data, $request->get('domain', CatalogPool::DOMAIN_PHARMACY), 'admin');
        $after  = CatalogItem::where('domain', $item->domain)->count();

        $after > $before
            ? Toastr::success('Added to the pool.')
            : Toastr::warning('Already in the pool — showing the existing record (' . $item->label . ').');

        return back();
    }

    /**
     * Edit a pooled record, then push the correction out to the stores that adopted it.
     */
    public function update(Request $request, $id)
    {
        CatalogPool::ensureSchema();

        $request->validate([
            'name'          => 'required|string|max:200',
            'brand'         => 'nullable|string|max:150',
            'strength_text' => 'nullable|string|max:100',
            'form'          => 'nullable|string|max:50',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $item     = CatalogItem::findOrFail($id);
        $original = $item->getOriginal();

        $strength = trim((string) $request->strength_text);
        [$value, $unit] = CatalogPool::parseStrength($strength);

        $item->name           = $request->name;
        $item->brand          = $request->brand;
        $item->strength_text  = $strength ?: null;
        $item->strength_value = $value;
        $item->strength_unit  = $unit;
        $item->form           = CatalogPool::normaliseForm($request->form);
        $item->normalized_key = CatalogPool::key($item->domain, $item->name, $item->brand, $item->strength_text, $item->form);
        $item->status         = $request->boolean('retired') ? CatalogItem::STATUS_RETIRED : CatalogItem::STATUS_ACTIVE;

        if ($request->hasFile('image')) {
            $item->image = Helpers::upload('catalog-item/', $request->file('image')->getClientOriginalExtension(), $request->file('image'));
        }

        // The unique key is the guarantee, so an edit that collides with another record has to be
        // refused rather than allowed to blow up on the index.
        $clash = CatalogItem::where('normalized_key', $item->normalized_key)->where('id', '!=', $item->id)->first();
        if ($clash) {
            Toastr::error('That is already in the pool as "' . $clash->label . '" — merge into it instead of editing this one into a duplicate.');
            return back();
        }

        $item->save();

        $touched = CatalogPool::propagate($item, $original);
        Toastr::success('Updated.' . ($touched ? " {$touched} pharmacies were corrected too." : ''));

        return back();
    }

    /**
     * Fold one pooled record into another, carrying every store that adopted the loser across.
     *
     * A merged row is kept, not deleted: store items linked to it still resolve through the
     * merge pointer, so nothing is orphaned by tidying up.
     */
    public function merge(Request $request, $id)
    {
        CatalogPool::ensureSchema();

        $request->validate(['target_id' => 'required|integer|different:' . $id]);

        $loser  = CatalogItem::findOrFail($id);
        $winner = CatalogItem::findOrFail($request->target_id)->resolved();

        if ($winner->id === $loser->id) {
            Toastr::error('Cannot merge a record into itself.');
            return back();
        }

        DB::transaction(function () use ($loser, $winner) {
            // A store that had adopted BOTH records would end up with two items pointing at the
            // same pooled product — the duplication this whole thing exists to prevent. Those
            // keep the copy they already stocked, and their loser row is unlinked rather than
            // deleted: it still holds their price, stock and expiry.
            $alreadyOnWinner = InventoryItem::where('catalog_item_id', $winner->id)->pluck('store_id');

            InventoryItem::where('catalog_item_id', $loser->id)
                ->whereIn('store_id', $alreadyOnWinner)
                ->update(['catalog_item_id' => null]);

            InventoryItem::where('catalog_item_id', $loser->id)
                ->update(['catalog_item_id' => $winner->id]);

            if (!$winner->image && $loser->image) {
                $winner->image = $loser->image;
            }
            $winner->usage_count = InventoryItem::where('catalog_item_id', $winner->id)->count();
            $winner->save();

            $loser->status         = CatalogItem::STATUS_MERGED;
            $loser->merged_into_id = $winner->id;
            $loser->usage_count    = 0;
            $loser->save();

            CatalogSuggestion::where('catalog_item_id', $loser->id)->update(['catalog_item_id' => $winner->id]);
        });

        Toastr::success('Merged into "' . $winner->label . '".');
        return back();
    }

    /**
     * Retire rather than delete when stores are using it — a hard delete would leave their items
     * pointing at nothing.
     */
    public function destroy($id)
    {
        CatalogPool::ensureSchema();

        $item = CatalogItem::findOrFail($id);

        if (InventoryItem::where('catalog_item_id', $item->id)->exists()) {
            $item->status = CatalogItem::STATUS_RETIRED;
            $item->save();
            Toastr::warning('In use by ' . $item->usage_count . ' pharmacies — retired instead of deleted, so their items keep working.');
            return back();
        }

        $item->delete();
        Toastr::success('Removed from the pool.');
        return back();
    }

    // ── Import ──────────────────────────────────────────────────────────────

    /**
     * Bulk-load the pool from a sheet of name / brand / strength / type.
     *
     * Every row goes through the same upsert as everything else, so re-importing a corrected file
     * updates rather than duplicating, and a file with the same medicine twice yields one record.
     */
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xls,xlsx|max:10240']);
        CatalogPool::ensureSchema();

        $domain = $request->get('domain', CatalogPool::DOMAIN_PHARMACY);

        try {
            $rows = IOFactory::load($request->file('file')->getPathname())->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            Toastr::error('Could not read the file: ' . $e->getMessage());
            return back();
        }

        if (count($rows) < 2) {
            Toastr::error('The file has no data rows.');
            return back();
        }

        $header = array_map(fn($h) => strtolower(trim((string) $h)), array_shift($rows));
        $get = function ($row, array $aliases) use ($header) {
            foreach ($aliases as $a) {
                $i = array_search($a, $header, true);
                if ($i !== false && isset($row[$i])) {
                    return trim((string) $row[$i]);
                }
            }
            return null;
        };

        $added = 0; $existing = 0; $skipped = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $name = $get($row, ['name', 'medicine_name', 'medicine name', 'item_name', 'medicine', 'generic', 'generic name', 'generic_name', 'composition']);
            if ($name === null || $name === '') {
                $skipped++;
                continue;
            }

            $before = CatalogItem::where('domain', $domain)->count();

            CatalogPool::upsert([
                'name'          => $name,
                'brand'         => $get($row, ['brand', 'brand_example', 'brand example', 'brand name', 'brand_name', 'trade name', 'trade_name']),
                'strength_text' => $get($row, ['strength', 'mg', 'dosage', 'power', 'strength_text']),
                'form'          => $get($row, ['form', 'type', 'dosage_form', 'dosage form', 'pack', 'pack_size']),
            ], $domain, 'import');

            CatalogItem::where('domain', $domain)->count() > $before ? $added++ : $existing++;
        }

        Toastr::success("Pool import complete — {$added} added, {$existing} already present, {$skipped} rows skipped.");
        return back();
    }

    // ── Suggestions queue ───────────────────────────────────────────────────

    public function suggestions(Request $request)
    {
        CatalogPool::ensureSchema();

        $status = $request->get('status', 'open');
        $domain = $request->get('domain', CatalogPool::DOMAIN_PHARMACY);

        $suggestions = CatalogSuggestion::with(['match', 'store'])
            ->where('domain', $domain)
            ->when($status === 'open', fn($q) => $q->whereIn('status', CatalogSuggestion::OPEN_STATUSES))
            ->when($status !== 'open' && $status !== 'all', fn($q) => $q->where('status', $status))
            ->orderByDesc('request_count')
            ->orderByDesc('id')
            ->paginate(40)
            ->appends($request->query());

        $tallies = CatalogSuggestion::where('domain', $domain)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $forms = CatalogPool::FORMS;

        return view('admin-views.catalog.suggestions', compact('suggestions', 'status', 'domain', 'tallies', 'forms'));
    }

    /**
     * Accept a suggestion into the pool, and link the store item that asked for it.
     */
    public function approve(Request $request, $id)
    {
        CatalogPool::ensureSchema();

        $suggestion = CatalogSuggestion::findOrFail($id);

        $item = CatalogPool::upsert([
            'name'          => $request->input('name', $suggestion->raw_name),
            'brand'         => $request->input('brand', $suggestion->raw_brand),
            'strength_text' => $request->input('strength', $suggestion->raw_strength),
            'form'          => $request->input('form', $suggestion->raw_form),
        ], $suggestion->domain, 'vendor');

        $this->close($suggestion, CatalogSuggestion::STATUS_APPROVED, $item);

        Toastr::success('"' . $item->label . '" added to the pool.');
        return back();
    }

    /**
     * The suggestion was a spelling of something already pooled — link it there instead.
     */
    public function mergeSuggestion(Request $request, $id)
    {
        CatalogPool::ensureSchema();

        $request->validate(['catalog_item_id' => 'required|integer']);

        $suggestion = CatalogSuggestion::findOrFail($id);
        $item = CatalogItem::findOrFail($request->catalog_item_id)->resolved();

        $this->close($suggestion, CatalogSuggestion::STATUS_MERGED, $item);

        Toastr::success('Filed under "' . $item->label . '".');
        return back();
    }

    public function reject($id)
    {
        CatalogPool::ensureSchema();

        $suggestion = CatalogSuggestion::findOrFail($id);
        $this->close($suggestion, CatalogSuggestion::STATUS_REJECTED, null);

        Toastr::success('Rejected — it will not be suggested again.');
        return back();
    }

    /** Re-run the AI pass over whatever is still unsorted. */
    public function verify(Request $request)
    {
        CatalogPool::ensureSchema();

        $ids = CatalogSuggestion::where('domain', $request->get('domain', CatalogPool::DOMAIN_PHARMACY))
            ->where('status', CatalogSuggestion::STATUS_PENDING)
            ->limit(200)
            ->pluck('id')
            ->all();

        if (!$ids) {
            Toastr::info('Nothing waiting to be checked.');
            return back();
        }

        VerifyCatalogSuggestions::dispatchAfterResponse($ids);
        Toastr::success(count($ids) . ' entries queued for checking — refresh in a moment.');

        return back();
    }

    /**
     * Settle a suggestion and, when it resolved to a pool record, link the store item that
     * raised it so the hospital that asked gets the benefit immediately.
     */
    private function close(CatalogSuggestion $suggestion, string $status, ?CatalogItem $item): void
    {
        CatalogPool::settle($suggestion, $status, $item, auth('admin')->id());
    }
}
