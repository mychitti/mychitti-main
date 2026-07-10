<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateServiceZoneSeo;
use App\Models\ServiceZoneSeo;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeoPageController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceZoneSeo::query()
            ->join('categories', 'categories.id', '=', 'service_zone_seo.category_id')
            ->join('zones', 'zones.id', '=', 'service_zone_seo.zone_id')
            ->leftJoin('items', 'items.id', '=', 'service_zone_seo.item_id')
            ->select('service_zone_seo.*', 'categories.name as category_name', 'zones.name as zone_name', 'items.name as item_name');

        if ($request->filled('status')) {
            $query->where('service_zone_seo.status', $request->status);
        }
        if ($request->filled('zone_id')) {
            $query->where('service_zone_seo.zone_id', $request->zone_id);
        }
        if ($request->filled('category_id')) {
            $query->where('service_zone_seo.category_id', $request->category_id);
        }
        if ($request->filled('level')) {
            $request->level === 'item'
                ? $query->where('service_zone_seo.item_id', '>', 0)
                : $query->where('service_zone_seo.item_id', 0);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('categories.name', 'like', "%{$search}%")
                    ->orWhere('zones.name', 'like', "%{$search}%")
                    ->orWhere('items.name', 'like', "%{$search}%");
            });
        }

        $combos = $query->orderByDesc('service_zone_seo.store_count')
            ->paginate(20)->withQueryString();

        $counts = ServiceZoneSeo::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status');
        $categories = DB::table('categories')->whereNull('added_by')->orderBy('name')->get(['id', 'name']);
        $zones = DB::table('zones')->orderBy('name')->get(['id', 'name']);

        return view('admin-views.seo-page.index', compact('combos', 'counts', 'categories', 'zones'));
    }

    public function generate($id)
    {
        ServiceZoneSeo::findOrFail($id);
        // Call handle() directly rather than dispatchSync(): for a ShouldQueue job, dispatchSync()
        // routes through the sync QUEUE CONNECTION (job serialization + CallQueuedHandler), which
        // returns a queue-push result — NOT handle()'s return value. Instantiating and calling
        // handle() directly bypasses that and gives us the real ['success','message'] outcome.
        $outcome = (new GenerateServiceZoneSeo($id))->handle();

        if (!empty($outcome['success'])) {
            Toastr::success('Generated SEO content.');
        } else {
            Toastr::error('Generation failed: ' . ($outcome['message'] ?? 'unknown error'));
        }
        return back();
    }

    public function edit($id)
    {
        $combo = ServiceZoneSeo::with(['zone', 'category'])->findOrFail($id);
        return view('admin-views.seo-page.edit', compact('combo'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'meta_title'       => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:300',
            'h1'               => 'nullable|string|max:160',
            'intro_paragraph'  => 'nullable|string',
            'keywords'         => 'nullable|string',
            'status'           => 'required|in:draft,published,unpublished',
        ]);

        $combo = ServiceZoneSeo::findOrFail($id);

        $keywords = array_values(array_filter(array_map(
            fn($k) => strtolower(trim($k)),
            preg_split('/[\r\n,]+/', (string) $request->keywords)
        )));

        $faqs = [];
        foreach ((array) $request->input('faq_q', []) as $i => $q) {
            $a = $request->input('faq_a')[$i] ?? '';
            if (trim($q) !== '' && trim($a) !== '') {
                $faqs[] = ['q' => trim($q), 'a' => trim($a)];
            }
        }

        $combo->update([
            'meta_title'       => $request->meta_title,
            'meta_description' => $request->meta_description,
            'h1'               => $request->h1,
            'intro_paragraph'  => $request->intro_paragraph,
            'keywords'         => $keywords,
            'faqs'             => $faqs ?: null,
            'status'           => $request->status,
        ]);

        Toastr::success('SEO page updated.');
        return redirect()->route('admin.seo-pages.index');
    }
}
