<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateStoreMeta;
use App\Models\Store;
use App\Models\Zone;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

/**
 * Admin screen for store SEO meta — see which stores are missing meta_title / meta_description
 * and fill them via AI, one at a time or in bulk.
 */
class StoreMetaController extends Controller
{
    public function index(Request $request)
    {
        $counts = [
            'total'   => (clone $this->baseQuery())->count(),
            'missing' => (clone $this->baseQuery())->missingMeta()->count(),
        ];
        $counts['filled'] = $counts['total'] - $counts['missing'];

        $stores = $this->applyFilters($request)
            ->paginate(20)->withQueryString();

        $zones = Zone::orderBy('name')->get(['id', 'name']);

        return view('admin-views.store-meta.index', compact('stores', 'counts', 'zones'));
    }

    public function generate($id)
    {
        $store = Store::withoutGlobalScopes()->findOrFail($id);

        // Call handle() directly rather than dispatchSync(): for a ShouldQueue job, dispatchSync()
        // routes through the sync queue connection and returns a queue-push result, not handle()'s
        // return value — so we would lose the real outcome.
        $outcome = (new GenerateStoreMeta($store->id, true))->handle();

        if (!empty($outcome['success'])) {
            Toastr::success('Generated meta for ' . $store->name . '.');
        } else {
            Toastr::error('Generation failed: ' . ($outcome['message'] ?? 'unknown error'));
        }

        return back();
    }

    /** Stores generated per click — each one is an AI call, and this runs inside the request. */
    const BATCH_SIZE = 10;

    /**
     * Generate meta for the stores matching the current filter, a batch at a time.
     *
     * Deliberately inline and capped rather than queued: nothing consumes the 'seo' queue, and
     * this panel runs with QUEUE_DRIVER=sync anyway, so dispatching would either do nothing or
     * run every match in one request and time out. For a large backfill use the CLI:
     * `php artisan store:generate-meta`.
     */
    public function generateMissing(Request $request)
    {
        $pending = $this->applyFilters($request, true)->count();

        if ($pending === 0) {
            Toastr::info('Nothing to generate — every store matching this filter already has meta.');
            return back();
        }

        $ids = $this->applyFilters($request, true)->limit(self::BATCH_SIZE)->pluck('stores.id');

        $ok = 0;
        $failed = 0;
        foreach ($ids as $id) {
            $outcome = (new GenerateStoreMeta((int) $id))->handle();
            empty($outcome['success']) ? $failed++ : $ok++;
        }

        $remaining = max(0, $pending - $ok);
        $message = "Generated {$ok} store(s)." . ($failed ? " {$failed} failed — see logs." : '');
        $message .= $remaining
            ? " {$remaining} still missing meta — click again for the next " . self::BATCH_SIZE . ', or run "php artisan store:generate-meta" for all of them.'
            : ' Every store matching this filter now has meta.';

        $failed ? Toastr::warning($message) : Toastr::success($message);
        return back();
    }

    private function baseQuery()
    {
        return Store::withoutGlobalScopes();
    }

    /**
     * @param bool $missingOnly Force the "missing meta" filter regardless of the request.
     */
    private function applyFilters(Request $request, bool $missingOnly = false)
    {
        $query = $this->baseQuery()->select('stores.*');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('stores.name', 'like', "%{$search}%")
                    ->orWhere('stores.address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('zone_id')) {
            $query->where('stores.zone_id', (int) $request->input('zone_id'));
        }

        $status = $missingOnly ? 'missing' : $request->input('status');
        if ($status === 'missing') {
            $query->missingMeta();
        } elseif ($status === 'filled') {
            $query->whereNotNull('stores.meta_title')->where('stores.meta_title', '!=', '')
                ->whereNotNull('stores.meta_description')->where('stores.meta_description', '!=', '');
        }

        return $query->orderByDesc('stores.id');
    }
}
