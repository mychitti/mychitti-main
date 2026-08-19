<?php

namespace App\Modules\HMIS\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OpdClinicalTerm;
use App\Models\OpdTermCatalogue;
use App\Models\StoreConfig;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

/**
 * The platform's diagnosis and treatment lists, per hospital category.
 *
 * Every hospital reads these live, so an edit here lands on the next consultation screen that
 * opens — including hospitals that have been running for months. Terms are deactivated rather
 * than deleted wherever a visit might already name them.
 */
class OpdTermCatalogueController extends Controller
{
    /** Category keys admin can edit: the shared list, then every hospital category. */
    private function categories(): array
    {
        return [OpdTermCatalogue::CATEGORY_COMMON => 'Common — shown to every hospital']
            + StoreConfig::HOSPITAL_CATEGORIES;
    }

    public function index(Request $request)
    {
        OpdTermCatalogue::ensureTable();

        $categories = $this->categories();
        $category   = $request->get('category', OpdTermCatalogue::CATEGORY_COMMON);
        if (!isset($categories[$category])) {
            $category = OpdTermCatalogue::CATEGORY_COMMON;
        }

        $type = $request->get('type', OpdClinicalTerm::TYPE_DIAGNOSIS);
        if (!in_array($type, OpdClinicalTerm::TYPES, true)) {
            $type = OpdClinicalTerm::TYPE_DIAGNOSIS;
        }

        $terms = OpdTermCatalogue::where('category', $category)->where('type', $type)
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        // How many hospitals this list actually reaches, so admin can see the blast radius of an
        // edit before making one. The common list reaches every hospital, category or not.
        $storeCount = $category === OpdTermCatalogue::CATEGORY_COMMON
            ? \Illuminate\Support\Facades\DB::table('stores')->whereRaw('LOWER(business_type) = ?', ['hospital'])->count()
            : \Illuminate\Support\Facades\DB::table('store_configs')->where('hospital_category', $category)->count();

        return view('hmis::admin.opd-terms.index', compact('categories', 'category', 'type', 'terms', 'storeCount'));
    }

    /**
     * Add one term or a pasted block of them — a consultant handing over two hundred dental
     * diagnoses should not be typed in one at a time.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:40',
            'type'     => 'required|in:' . implode(',', OpdClinicalTerm::TYPES),
            'names'    => 'required|string',
        ]);

        if (!isset($this->categories()[$request->category])) {
            Toastr::error('Unknown category.');
            return back();
        }

        OpdTermCatalogue::ensureTable();

        $names = collect(preg_split('/\r\n|\r|\n|,/', $request->names))
            ->map(fn($n) => trim($n))
            ->filter()
            ->unique(fn($n) => mb_strtolower($n))
            ->take(500);

        if ($names->isEmpty()) {
            Toastr::error('Nothing to add.');
            return back();
        }

        $next = (int) OpdTermCatalogue::where('category', $request->category)
            ->where('type', $request->type)->max('sort_order');

        $now  = now();
        $rows = $names->values()->map(fn($name, $i) => [
            'category'   => $request->category,
            'type'       => $request->type,
            'name'       => $name,
            'sort_order' => $next + $i + 1,
            'active'     => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        OpdTermCatalogue::insertOrIgnore($rows);

        Toastr::success($names->count() === 1
            ? 'Term added.'
            : $names->count() . ' terms added (duplicates skipped).');

        return back();
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'   => 'required|string|max:150',
            'active' => 'nullable|boolean',
        ]);

        OpdTermCatalogue::ensureTable();
        $term = OpdTermCatalogue::findOrFail($id);

        $term->name   = trim($request->name);
        $term->active = $request->boolean('active');
        $term->save();

        Toastr::success('Term updated.');
        return back();
    }

    /** Turn a term off without losing it — a visit already recorded against it still reads back. */
    public function toggle($id)
    {
        OpdTermCatalogue::ensureTable();
        $term = OpdTermCatalogue::findOrFail($id);
        $term->active = !$term->active;
        $term->save();

        Toastr::success($term->active ? 'Term switched on.' : 'Term switched off.');
        return back();
    }

    /**
     * Removed outright. Only safe because nothing references a catalogue row by id — a visit
     * stores the term as text, so deleting the row cannot orphan a record.
     */
    public function destroy($id)
    {
        OpdTermCatalogue::ensureTable();
        OpdTermCatalogue::findOrFail($id)->delete();

        Toastr::success('Term removed.');
        return back();
    }
}
