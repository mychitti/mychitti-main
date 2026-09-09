<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\BrandPool;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Item;
use App\Services\AiServiceClient;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * CRUD for the shared brand pool — admin curates, vendors pick, SEO consumes.
 *
 * Modelled after CatalogPoolController: one table, one simple page with modals,
 * search + pagination + bulk import.
 */
class BrandPoolController extends Controller
{
    public function __construct()
    {
        $this->ensureSchema();
    }

    // ── List ────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search = trim($request->get('search', ''));
        $status = $request->get('status', '');

        $brands = BrandPool::query()
            ->with(['categories'])
            ->when(Schema::hasTable('brand_pool_service'), fn($q) => $q->with('services'))
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderByDesc('usage_count')
            ->orderBy('name')
            ->paginate(30)
            ->appends($request->query());

        $counts = [
            'total'    => BrandPool::count(),
            'active'   => BrandPool::where('status', 'active')->count(),
            'inactive' => BrandPool::where('status', 'inactive')->count(),
            'in_use'   => BrandPool::where('usage_count', '>', 0)->count(),
        ];

        // Only primary categories (position = 0, no subcategories).
        $categories = Category::whereNull('added_by')
            ->where('status', 1)
            ->where('position', 0)
            ->where(function ($q) {
                $q->where('parent_id', 0)->orWhereNull('parent_id');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        // Specific services (items with module_id = 6, e.g. AC Repair)
        // Using DB::table to bypass Eloquent global scopes (ZoneScope / StoreScope)
        $services = DB::table('items')
            ->where('module_id', 6)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin-views.brand-pool.index', compact('brands', 'search', 'status', 'counts', 'categories', 'services'));
    }

    // ── Create ──────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:200',
            'categories'   => 'nullable|array',
            'targets'      => 'nullable|array',
        ]);

        $existing = BrandPool::where('slug', BrandPool::makeSlug($request->name))->first();
        if ($existing) {
            Toastr::warning('Brand "' . $existing->name . '" already exists in the pool.');
            return back();
        }

        $brand = BrandPool::create([
            'name'   => $request->name,
            'slug'   => BrandPool::makeSlug($request->name),
            'status' => 'active',
        ]);

        $rawTargets = (array) ($request->input('targets') ?? $request->input('categories') ?? []);
        $catIds = [];
        $srvIds = [];

        foreach ($rawTargets as $t) {
            $t = trim((string) $t);
            if (str_starts_with($t, 'srv_')) {
                $srvIds[] = (int) substr($t, 4);
            } elseif (str_starts_with($t, 'cat_')) {
                $catIds[] = (int) substr($t, 4);
            } elseif (is_numeric($t)) {
                $intVal = (int) $t;
                if (DB::table('items')->where('module_id', 6)->where('id', $intVal)->whereNull('deleted_at')->exists()) {
                    $srvIds[] = $intVal;
                } else {
                    $catIds[] = $intVal;
                }
            }
        }

        if (!empty($catIds)) {
            $validCatIds = Category::whereIn('id', $catIds)->where('position', 0)->pluck('id')->toArray();
            $brand->categories()->sync($validCatIds);
        } else {
            $brand->categories()->sync([]);
        }

        if (Schema::hasTable('brand_pool_service')) {
            if (!empty($srvIds)) {
                $validSrvIds = DB::table('items')->whereIn('id', $srvIds)->where('module_id', 6)->whereNull('deleted_at')->pluck('id')->toArray();
                $brand->services()->sync($validSrvIds);
            } else {
                $brand->services()->sync([]);
            }
        }

        Toastr::success('Brand "' . $brand->name . '" added to the pool.');
        return back();
    }

    // ── Update ──────────────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'         => 'required|string|max:200',
            'categories'   => 'nullable|array',
            'targets'      => 'nullable|array',
        ]);

        $brand = BrandPool::findOrFail($id);

        $newSlug = BrandPool::makeSlug($request->name, $brand->id);
        $clash = BrandPool::where('slug', $newSlug)->where('id', '!=', $brand->id)->first();
        if ($clash) {
            Toastr::error('A brand with that name already exists ("' . $clash->name . '").');
            return back();
        }

        $brand->name = $request->name;
        $brand->slug = $newSlug;
        $brand->save();

        $rawTargets = (array) ($request->input('targets') ?? $request->input('categories') ?? []);
        $catIds = [];
        $srvIds = [];

        foreach ($rawTargets as $t) {
            $t = trim((string) $t);
            if (str_starts_with($t, 'srv_')) {
                $srvIds[] = (int) substr($t, 4);
            } elseif (str_starts_with($t, 'cat_')) {
                $catIds[] = (int) substr($t, 4);
            } elseif (is_numeric($t)) {
                $intVal = (int) $t;
                if (DB::table('items')->where('module_id', 6)->where('id', $intVal)->whereNull('deleted_at')->exists()) {
                    $srvIds[] = $intVal;
                } else {
                    $catIds[] = $intVal;
                }
            }
        }

        if (!empty($catIds)) {
            $validCatIds = Category::whereIn('id', $catIds)->where('position', 0)->pluck('id')->toArray();
            $brand->categories()->sync($validCatIds);
        } else {
            $brand->categories()->sync([]);
        }

        if (Schema::hasTable('brand_pool_service')) {
            if (!empty($srvIds)) {
                $validSrvIds = DB::table('items')->whereIn('id', $srvIds)->where('module_id', 6)->whereNull('deleted_at')->pluck('id')->toArray();
                $brand->services()->sync($validSrvIds);
            } else {
                $brand->services()->sync([]);
            }
        }

        // Update the text brand column on inventory items that use this brand.
        InventoryItem::where('brand_pool_id', $brand->id)->update(['brand' => $brand->name]);

        Toastr::success('Brand updated.');
        return back();
    }

    // ── Delete / retire ─────────────────────────────────────────────────

    public function destroy($id)
    {
        $brand = BrandPool::findOrFail($id);

        if (InventoryItem::where('brand_pool_id', $brand->id)->exists()) {
            $brand->status = 'inactive';
            $brand->save();
            Toastr::warning('In use by ' . $brand->usage_count . ' items — deactivated instead of deleted.');
            return back();
        }

        $brand->categories()->detach();
        if (Schema::hasTable('brand_pool_service')) {
            $brand->services()->detach();
        }
        $brand->delete();
        Toastr::success('Brand removed from the pool.');
        return back();
    }

    // ── Toggle status ───────────────────────────────────────────────────

    public function toggleStatus($id)
    {
        $brand = BrandPool::findOrFail($id);
        $brand->status = $brand->status === 'active' ? 'inactive' : 'active';
        $brand->save();

        Toastr::success('Brand is now ' . $brand->status . '.');
        return back();
    }

    // ── Import ──────────────────────────────────────────────────────────

    /**
     * Bulk-load brands from a CSV / Excel. Columns: name, category (optional).
     * Duplicates are skipped; a file can be re-imported safely.
     */
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xls,xlsx|max:10240']);

        try {
            $rows = IOFactory::load($request->file('file')->getPathname())
                ->getActiveSheet()->toArray(null, true, true, false);
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

        // Pre-load primary categories (position = 0, no subcategories).
        $categoryMap = Category::whereNull('added_by')
            ->where('status', 1)
            ->where('position', 0)
            ->pluck('id', 'name')
            ->mapWithKeys(fn($id, $name) => [strtolower($name) => $id]);

        // Pre-load services (items where module_id = 6).
        $serviceMap = DB::table('items')->where('module_id', 6)
            ->whereNull('deleted_at')
            ->pluck('id', 'name')
            ->mapWithKeys(fn($id, $name) => [strtolower($name) => $id]);

        foreach ($rows as $row) {
            if (!is_array($row)) continue;

            $name = $get($row, ['name', 'brand_name', 'brand name', 'brand']);
            if ($name === null || $name === '') { $skipped++; continue; }

            $slug = BrandPool::makeSlug($name);
            $brand = BrandPool::where('slug', $slug)->first();

            if ($brand) {
                $existing++;
            } else {
                $brand = BrandPool::create([
                    'name'   => $name,
                    'slug'   => $slug,
                    'status' => 'active',
                ]);
                $added++;
            }

            // Attach to service or category if column present.
            $targetName = $get($row, ['service', 'category', 'service_name', 'category_name']);
            if ($targetName) {
                $lowerTarget = strtolower($targetName);
                if (isset($serviceMap[$lowerTarget]) && Schema::hasTable('brand_pool_service')) {
                    $srvId = $serviceMap[$lowerTarget];
                    if (!$brand->services()->where('item_id', $srvId)->exists()) {
                        $brand->services()->attach($srvId);
                    }
                } elseif (isset($categoryMap[$lowerTarget])) {
                    $catId = $categoryMap[$lowerTarget];
                    if (!$brand->categories()->where('category_id', $catId)->exists()) {
                        $brand->categories()->attach($catId);
                    }
                }
            }
        }

        Toastr::success("Import complete — {$added} added, {$existing} already present, {$skipped} skipped.");
        return back();
    }

    // ── AJAX search (for vendor Select2) ────────────────────────────────

    /**
     * Used by vendor-side autocomplete. Returns JSON.
     * Route is registered separately in vendor routes.
     */
    public static function ajaxSearch(Request $request)
    {
        try {
            $q = trim($request->get('q', ''));

            $brands = BrandPool::active()
                ->when($q, fn($query) => $query->where('name', 'like', "%{$q}%"))
                ->orderBy('name')
                ->limit(30)
                ->get(['id', 'name']);

            return response()->json($brands);
        } catch (\Throwable $e) {
            return response()->json([]);
        }
    }

    // ── AI Brand Generation ─────────────────────────────────────────────

    /**
     * AI generation endpoint: queries LLM for a list of reputable brands
     * based on category and/or user prompt. Returns JSON list with status
     * of whether each brand already exists in the pool.
     */
    public function aiGenerate(Request $request)
    {
        $target = $request->input('target') ?? $request->input('category_id');
        $targetName = '';
        $targetType = 'category';

        if ($target) {
            $t = trim((string) $target);
            if (str_starts_with($t, 'srv_')) {
                $item = DB::table('items')->where('module_id', 6)->whereNull('deleted_at')->find((int) substr($t, 4));
                if ($item) {
                    $targetName = $item->name;
                    $targetType = 'service';
                }
            } elseif (str_starts_with($t, 'cat_')) {
                $cat = Category::where('position', 0)->find((int) substr($t, 4));
                if ($cat) {
                    $targetName = $cat->name;
                    $targetType = 'category';
                }
            } elseif (is_numeric($t)) {
                $item = DB::table('items')->where('module_id', 6)->whereNull('deleted_at')->find((int) $t);
                if ($item) {
                    $targetName = $item->name;
                    $targetType = 'service';
                } else {
                    $cat = Category::where('position', 0)->find((int) $t);
                    if ($cat) {
                        $targetName = $cat->name;
                        $targetType = 'category';
                    }
                }
            }
        }

        $promptInput = trim((string) $request->input('prompt', ''));
        $count = (int) $request->input('count', 15);
        if ($count < 5) $count = 5;
        if ($count > 50) $count = 50;

        if (!$targetName && !$promptInput) {
            return response()->json([
                'success' => false,
                'message' => 'Please choose a service or category, or provide a prompt for the AI.'
            ], 422);
        }

        $systemPrompt = "You are an expert brand catalog directory assistant. "
            . "Your job is to provide accurate, real-world manufacturer and service brand names in India and globally. "
            . "Return ONLY a valid JSON array of brand name strings. No markdown backticks, no explanatory text, no extra properties. "
            . "Example format: [\"Daikin\", \"Voltas\", \"Blue Star\", \"LG\", \"Samsung\", \"Hitachi\"]";

        $userPrompt = "Generate a curated list of {$count} popular, recognized brand names";
        if ($targetName) {
            if ($targetType === 'service') {
                $userPrompt .= " that are serviced, repaired, or manufactured for the service '{$targetName}' (e.g. major brands for {$targetName})";
            } else {
                $userPrompt .= " for the industry/category '{$targetName}'";
            }
        }
        if ($promptInput) {
            $userPrompt .= " with the following instructions: '{$promptInput}'";
        }
        $userPrompt .= ". Do not include duplicate names. Return only the JSON array of strings.";

        $rawText = null;

        // 1. Try AiServiceClient (internal proxy / AI service)
        try {
            if (class_exists(AiServiceClient::class)) {
                $aiClient = app(AiServiceClient::class);
                $res = $aiClient->chat(
                    0,
                    'admin',
                    $userPrompt,
                    systemPrompt: $systemPrompt,
                    modelConfig: ['ai_provider' => 'openai', 'ai_model' => 'gpt-4o-mini']
                );
                if (!empty($res['success']) && !empty($res['message'])) {
                    $rawText = $res['message'];
                }
            }
        } catch (\Throwable $e) {
            Log::info('BrandPool AI generation via AiServiceClient failed, trying fallbacks: ' . $e->getMessage());
        }

        // 2. Direct OpenAI fallback
        if (empty($rawText) && config('services.openai.key')) {
            try {
                $response = Http::withToken(config('services.openai.key'))
                    ->timeout(20)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model'       => config('services.openai.model', 'gpt-4o-mini'),
                        'messages'    => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $userPrompt],
                        ],
                        'temperature' => 0.4,
                    ]);
                if ($response->successful()) {
                    $rawText = $response->json('choices.0.message.content');
                }
            } catch (\Throwable $e) {
                Log::info('BrandPool AI direct OpenAI call failed: ' . $e->getMessage());
            }
        }

        // 3. Direct Gemini fallback
        if (empty($rawText) && config('services.gemini.key')) {
            try {
                $apiKey = config('services.gemini.key');
                $response = Http::timeout(20)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                        'contents' => [
                            ['parts' => [['text' => $systemPrompt . "\n\nUser request: " . $userPrompt]]]
                        ]
                    ]);
                if ($response->successful()) {
                    $rawText = $response->json('candidates.0.content.parts.0.text');
                }
            } catch (\Throwable $e) {
                Log::info('BrandPool AI direct Gemini call failed: ' . $e->getMessage());
            }
        }

        $brandNames = [];
        if (!empty($rawText)) {
            // Strip markdown code fences if model enclosed in ```json ... ```
            $clean = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($rawText));
            $decoded = json_decode($clean, true);
            if (is_array($decoded)) {
                foreach ($decoded as $item) {
                    if (is_string($item) && trim($item) !== '') {
                        $brandNames[] = trim($item);
                    } elseif (is_array($item) && !empty($item['name'])) {
                        $brandNames[] = trim($item['name']);
                    }
                }
            }
        }

        // If no AI response could be retrieved (e.g. offline or no API keys), provide intelligent fallbacks
        if (empty($brandNames)) {
            $brandNames = $this->getCategoryFallbackBrands($targetName . ' ' . $promptInput);
            $brandNames = array_slice($brandNames, 0, $count);
        }

        // Deduplicate
        $brandNames = array_values(array_unique($brandNames));

        // Check which ones already exist in database
        $existingSlugs = [];
        if (!empty($brandNames)) {
            try {
                $slugs = array_map(fn($n) => Str::slug($n), $brandNames);
                $existingSlugs = BrandPool::whereIn('slug', $slugs)->pluck('slug')->toArray();
            } catch (\Throwable $e) {}
        }

        $resultList = [];
        foreach ($brandNames as $name) {
            $slug = Str::slug($name);
            $exists = in_array($slug, $existingSlugs);
            $resultList[] = [
                'name'   => $name,
                'exists' => $exists,
            ];
        }

        return response()->json([
            'success'  => true,
            'category' => $targetName,
            'brands'   => $resultList,
            'total'    => count($resultList),
        ]);
    }

    /**
     * Batch save brands selected from AI generator into BrandPool,
     * associating them with selected categories.
     */
    public function aiSave(Request $request)
    {
        $brands = (array) $request->input('brands', []);
        $rawTargets = (array) ($request->input('targets') ?? $request->input('categories') ?? []);

        if (empty($brands)) {
            return response()->json([
                'success' => false,
                'message' => 'No brands selected to save.'
            ], 422);
        }

        $catIds = [];
        $srvIds = [];

        foreach ($rawTargets as $t) {
            $t = trim((string) $t);
            if (str_starts_with($t, 'srv_')) {
                $srvIds[] = (int) substr($t, 4);
            } elseif (str_starts_with($t, 'cat_')) {
                $catIds[] = (int) substr($t, 4);
            } elseif (is_numeric($t)) {
                $intVal = (int) $t;
                if (DB::table('items')->where('module_id', 6)->where('id', $intVal)->whereNull('deleted_at')->exists()) {
                    $srvIds[] = $intVal;
                } else {
                    $catIds[] = $intVal;
                }
            }
        }

        $validCats = !empty($catIds) ? Category::whereIn('id', $catIds)->where('position', 0)->pluck('id')->toArray() : [];
        $validSrvs = (!empty($srvIds) && Schema::hasTable('brand_pool_service')) ? DB::table('items')->whereIn('id', $srvIds)->where('module_id', 6)->whereNull('deleted_at')->pluck('id')->toArray() : [];

        $added = 0;
        $updated = 0;

        foreach ($brands as $brandName) {
            $brandName = trim((string) $brandName);
            if ($brandName === '') continue;

            $slug = BrandPool::makeSlug($brandName);
            $brand = BrandPool::where('slug', Str::slug($brandName))->first();

            if (!$brand) {
                $brand = BrandPool::create([
                    'name'   => $brandName,
                    'slug'   => $slug,
                    'status' => 'active',
                ]);
                $added++;
            } else {
                $updated++;
            }

            if (!empty($validCats)) {
                $brand->categories()->syncWithoutDetaching($validCats);
            }
            if (!empty($validSrvs)) {
                $brand->services()->syncWithoutDetaching($validSrvs);
            }
        }

        return response()->json([
            'success' => true,
            'added'   => $added,
            'updated' => $updated,
            'message' => "Successfully processed: {$added} new brand(s) added to pool, {$updated} existing updated with linked services/categories."
        ]);
    }

    /**
     * Curated category fallback brands if external AI service is unreachable.
     */
    private function getCategoryFallbackBrands(string $query): array
    {
        $q = strtolower($query);

        if (str_contains($q, 'ac') || str_contains($q, 'air condition') || str_contains($q, 'cooling')) {
            return ['Daikin', 'Voltas', 'Blue Star', 'LG', 'Samsung', 'Hitachi', 'Mitsubishi Electric', 'Carrier', 'Godrej', 'Whirlpool', 'Panasonic', 'Lloyd', 'Haier', 'O General', 'Toshiba', 'TCL', 'IFB'];
        }

        if (str_contains($q, 'wash') || str_contains($q, 'laundry') || str_contains($q, 'dryer')) {
            return ['LG', 'Samsung', 'IFB', 'Bosch', 'Whirlpool', 'Haier', 'Godrej', 'Panasonic', 'Siemens', 'Voltas Beko', 'BPL', 'Onida'];
        }

        if (str_contains($q, 'refrigerat') || str_contains($q, 'fridge') || str_contains($q, 'deep freeze')) {
            return ['LG', 'Samsung', 'Whirlpool', 'Haier', 'Godrej', 'Bosch', 'Hitachi', 'Panasonic', 'Voltas Beko', 'Liebherr', 'Kelvinator'];
        }

        if (str_contains($q, 'plumb') || str_contains($q, 'pipe') || str_contains($q, 'sanitary') || str_contains($q, 'bath')) {
            return ['Jaquar', 'Kohler', 'Hindware', 'Cera', 'Parryware', 'Grohe', 'Astral', 'Ashirvad', 'Supreme', 'Prince Pipes', 'Finolex', 'Essco', 'Vectus'];
        }

        if (str_contains($q, 'electric') || str_contains($q, 'wiring') || str_contains($q, 'switch') || str_contains($q, 'fan')) {
            return ['Havells', 'Anchor by Panasonic', 'Legrand', 'Schneider Electric', 'Polycab', 'Finolex', 'Crompton', 'Orient Electric', 'Usha', 'L&T', 'Philips', 'Syska', 'Wipro'];
        }

        if (str_contains($q, 'mobile') || str_contains($q, 'phone') || str_contains($q, 'smartphone')) {
            return ['Apple', 'Samsung', 'OnePlus', 'Xiaomi', 'Vivo', 'Oppo', 'Realme', 'Motorola', 'Google Pixel', 'Nothing', 'Infinix', 'Tecno', 'Poco', 'IQOO'];
        }

        if (str_contains($q, 'laptop') || str_contains($q, 'computer') || str_contains($q, 'pc')) {
            return ['HP', 'Dell', 'Lenovo', 'Apple', 'Asus', 'Acer', 'MSI', 'Samsung', 'Microsoft Surface', 'LG Gram'];
        }

        if (str_contains($q, 'tv') || str_contains($q, 'television') || str_contains($q, 'audio')) {
            return ['Sony', 'Samsung', 'LG', 'TCL', 'Xiaomi', 'OnePlus', 'Vu', 'Hisense', 'Panasonic', 'Toshiba', 'Philips', 'Bose', 'JBL'];
        }

        if (str_contains($q, 'car') || str_contains($q, 'auto') || str_contains($q, 'bike') || str_contains($q, 'vehicle')) {
            return ['Maruti Suzuki', 'Hyundai', 'Tata Motors', 'Mahindra', 'Toyota', 'Honda', 'Kia', 'Volkswagen', 'Skoda', 'Hero MotoCorp', 'Bajaj', 'TVS', 'Royal Enfield', 'Yamaha'];
        }

        if (str_contains($q, 'grocery') || str_contains($q, 'food') || str_contains($q, 'fmcg')) {
            return ['Nestle', 'Amul', 'Britannia', 'Tata Consumer', 'Hindustan Unilever', 'ITC', 'Parle', 'Haldiram', 'Mother Dairy', 'Dabur', 'Marico', 'Fortune', 'Patanjali'];
        }

        return ['LG', 'Samsung', 'Sony', 'Panasonic', 'Philips', 'Bosch', 'Godrej', 'Haier', 'Whirlpool', 'Havells', 'Voltas', 'Tata'];
    }

    // ── Schema guard ────────────────────────────────────────────────────

    private function ensureSchema(): void
    {
        try {
            if (!Schema::hasTable('brand_pool')) {
                \Artisan::call('migrate', ['--force' => true]);
            }
            if (!Schema::hasTable('brand_pool_service')) {
                DB::statement("CREATE TABLE IF NOT EXISTS `brand_pool_service` (
                    `brand_pool_id` bigint unsigned NOT NULL,
                    `item_id` bigint unsigned NOT NULL,
                    PRIMARY KEY (`brand_pool_id`, `item_id`),
                    KEY `bps_item_id_index` (`item_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            }
            if (!Schema::hasTable('brand_pool_category')) {
                DB::statement("CREATE TABLE IF NOT EXISTS `brand_pool_category` (
                    `brand_pool_id` bigint unsigned NOT NULL,
                    `category_id` bigint unsigned NOT NULL,
                    PRIMARY KEY (`brand_pool_id`, `category_id`),
                    KEY `bpc_category_id_index` (`category_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            }
        } catch (\Throwable $e) {
            // Silently fail — DB may not be connected or table already exists.
        }
    }
}
