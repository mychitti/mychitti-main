<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\CentralLogics\Helpers;
use App\Jobs\VerifyCatalogSuggestions;
use App\Models\CatalogItem;
use App\Models\InventoryItem;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Services\CatalogPool;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BasicPharmacyController extends Controller
{
    private function storeId()
    {
        return Helpers::get_store_id();
    }

    // Basic pharmacy uses the SAME inventory_items table as the premium Inventory module.
    // We only need two extra light columns (reorder level + a simple item-level expiry) so the
    // free pharmacy can show low-stock / expiry alerts. Guarded ALTER — no migration files.
    private function ensurePharmacyColumns(): void
    {
        if (!Schema::hasColumn('inventory_items', 'reorder_level')) {
            DB::statement('ALTER TABLE `inventory_items` ADD COLUMN `reorder_level` INT NULL DEFAULT 0');
        }
        if (!Schema::hasColumn('inventory_items', 'expiry_date')) {
            DB::statement('ALTER TABLE `inventory_items` ADD COLUMN `expiry_date` DATE NULL');
        }
        // Banned/blocked flag lives on the inventory item itself (per-item, exact match).
        if (!Schema::hasColumn('inventory_items', 'is_banned')) {
            DB::statement('ALTER TABLE `inventory_items` ADD COLUMN `is_banned` TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (!Schema::hasColumn('inventory_items', 'banned_reason')) {
            DB::statement('ALTER TABLE `inventory_items` ADD COLUMN `banned_reason` VARCHAR(500) NULL');
        }
        if (!Schema::hasColumn('inventory_items', 'banned_source')) {
            DB::statement("ALTER TABLE `inventory_items` ADD COLUMN `banned_source` VARCHAR(20) NULL");
        }
    }
 
    // Basic Pharmacy is a FREE feature. Seed the `pharmacy` feature + its actions with free=1
    // so the vendor OWNER passes hasPermission() without the premium plan, and staff can be
    // granted it via roles. Idempotent (insert-if-missing) — same self-healing pattern as the
    // guarded columns above, so no manual SQL is needed on each hospital DB.
    public function ensurePharmacyPermission(): void
    {
        if (!Schema::hasTable('features') || !Schema::hasTable('feature_permissions')) {
            return;
        }

        $featureId = DB::table('features')->where('name', 'pharmacy')->value('id');
        if (!$featureId) {
            $featureId = DB::table('features')->insertGetId([
                'name'          => 'pharmacy',
                'display_name'  => 'Pharmacy (Basic)',
                'master_module' => 'hospital_manage',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        foreach (['view', 'add', 'edit', 'delete', 'dispense'] as $action) {
            $exists = DB::table('feature_permissions')
                ->where('feature_id', $featureId)
                ->where('action', $action)
                ->exists();
            if (!$exists) {
                DB::table('feature_permissions')->insert([
                    'feature_id' => $featureId,
                    'action'     => $action,
                    'free'       => 1,
                ]);
            }
        }

        // Legacy: the read permission used to be 'list'. Migrate any existing role grants
        // from 'list' → 'view' and drop the old 'list' action so the grid shows a single
        // "View" column for basic pharmacy.
        if (Schema::hasTable('role_feature_permissions')) {
            $listPerm = DB::table('feature_permissions')
                ->where('feature_id', $featureId)->where('action', 'list')->first();
            $viewPerm = DB::table('feature_permissions')
                ->where('feature_id', $featureId)->where('action', 'view')->first();
            if ($listPerm && $viewPerm) {
                $roleIds = DB::table('role_feature_permissions')
                    ->where('feature_permission_id', $listPerm->id)->pluck('role_id');
                foreach ($roleIds as $rid) {
                    $already = DB::table('role_feature_permissions')
                        ->where('role_id', $rid)
                        ->where('feature_permission_id', $viewPerm->id)->exists();
                    if (!$already) {
                        DB::table('role_feature_permissions')->insert([
                            'role_id'               => $rid,
                            'feature_permission_id' => $viewPerm->id,
                            'created_at'            => now(),
                            'updated_at'            => now(),
                        ]);
                    }
                }
                DB::table('role_feature_permissions')->where('feature_permission_id', $listPerm->id)->delete();
                DB::table('feature_permissions')->where('id', $listPerm->id)->delete();
            }
        }
    }

    public function medicines(Request $request)
    {
        $this->ensurePharmacyColumns();
        $this->ensurePharmacyPermission();
        $storeId = $this->storeId();

        $search = trim($request->get('search', ''));

        $items = InventoryItem::where('store_id', $storeId)
            ->where('item_type', 'product')
            ->when($search, fn($q) => $q->where('item_name', 'like', "%{$search}%")->orWhere('sku_id', 'like', "%{$search}%"))
            ->orderBy('item_name')
            ->get();

        $soon = now()->addDays(60)->toDateString();
        $stats = [
            'total'     => $items->count(),
            'low'       => $items->filter(fn($i) => ($i->reorder_level ?? 0) > 0 && (int) $i->stock <= (int) $i->reorder_level && (int) $i->stock > 0)->count(),
            'out'       => $items->filter(fn($i) => (int) $i->stock <= 0)->count(),
            'expiring'  => $items->filter(fn($i) => $i->expiry_date && $i->expiry_date <= $soon)->count(),
            'stock_val' => $items->sum(fn($i) => (float) $i->stock * (float) ($i->selling_price ?? 0)),
        ];

        // Unit suggestions for the Add/Edit Medicine dropdown: the units already in the catalog,
        // merged with common pharmacy defaults so a fresh hospital still gets a useful list.
        // The field stays free-typeable — a new unit is created on save via _saveUnitIfNotExist.
        $defaultUnits = ['Tablet', 'Strip', 'Capsule', 'Bottle', 'Vial', 'Ampoule', 'Sachet', 'Tube', 'ml', 'mg', 'gm', 'Unit', 'Box', 'Piece'];
        $units = \App\Models\Unit::orderBy('unit')->pluck('unit')
            ->merge($defaultUnits)
            ->map(fn($u) => trim((string) $u))
            ->filter()
            ->unique(fn($u) => mb_strtolower($u))
            ->values();

        $forms = CatalogPool::FORMS;

        return view('hmis::vendor.pharmacy.medicines', compact('items', 'stats', 'search', 'units', 'forms'));
    }

    // ── Banned / Blocked Items ──────────────────────────────────────────────
    // A medicine is banned/blocked by flagging the inventory item itself (is_banned).
    // Surfaced as warnings (warn-but-allow) wherever the medicine is entered.
    public function bannedItems(Request $request)
    {
        $this->ensurePharmacyColumns();
        $this->ensurePharmacyPermission();
        $storeId = $this->storeId();

        $search = trim($request->get('search', ''));

        // Currently banned medicines.
        $banned = InventoryItem::where('store_id', $storeId)
            ->where('item_type', 'product')
            ->where('is_banned', 1)
            ->when($search, fn($q) => $q->where('item_name', 'like', "%{$search}%"))
            ->orderBy('item_name')
            ->get();

        // Items available to ban (not yet banned) — for the picker.
        $available = InventoryItem::where('store_id', $storeId)
            ->where('item_type', 'product')
            ->where(fn($q) => $q->whereNull('is_banned')->orWhere('is_banned', 0))
            ->orderBy('item_name')
            ->get(['id', 'item_name', 'brand', 'sku_id']);

        return view('hmis::vendor.pharmacy.banned_items', compact('banned', 'available', 'search'));
    }

    public function saveBannedItem(Request $request)
    {
        $this->ensurePharmacyColumns();
        $request->validate([
            'item_id'   => 'nullable|integer',
            'item_name' => 'required_without:item_id|nullable|string|max:200',
            'brand'     => 'nullable|string|max:200',
            'sku_id'    => 'nullable|string|max:100',
            'unit'      => 'nullable|string|max:50',
            'reason'    => 'nullable|string|max:500',
            'source'    => 'nullable|in:govt,store',
        ]);

        $storeId = $this->storeId();

        // Path 1: an existing medicine was selected from the pharmacy.
        if ($request->filled('item_id')) {
            $item = InventoryItem::where('store_id', $storeId)->findOrFail($request->item_id);
            $item->is_banned     = 1;
            $item->banned_reason = $request->reason;
            $item->banned_source = $request->source ?: 'store';
            $item->save();

            Toastr::success($item->item_name . ' marked as banned/blocked.');
            return back();
        }

        // Path 2: a name was typed. Flag a matching medicine if it exists, else create it as banned.
        $item = InventoryItem::where('store_id', $storeId)
            ->where('item_type', 'product')
            ->whereRaw('LOWER(item_name) = ?', [mb_strtolower(trim($request->item_name))])
            ->first();

        if (!$item) {
            $item = new InventoryItem();
            $item->store_id          = $storeId;
            $item->item_type         = 'product';
            $item->item_name         = trim($request->item_name);
            $item->brand             = $request->brand;
            $item->sku_id            = $request->sku_id;
            $item->unit              = _saveUnitIfNotExist($request->unit ?: 'Unit');
            $item->category_id       = Helpers::_saveCategoryIfNotExists('Medicine');
            $item->module_id         = Helpers::get_store_data()->module_id ?? null;
            $item->mrp               = 0;
            $item->selling_price     = 0;
            $item->stock             = 0;
            $item->reorder_level     = 0;
            $item->gst_rate          = 0;
            $item->gst_status        = 'excluding';
            $item->show_on_store_page = 0;
        }

        $item->is_banned     = 1;
        $item->banned_reason = $request->reason;
        $item->banned_source = $request->source ?: 'store';
        $item->save();

        Toastr::success($item->item_name . ' added as a banned/blocked item.');
        return back();
    }

    public function deleteBannedItem($id)
    {
        $this->ensurePharmacyColumns();
        $item = InventoryItem::where('store_id', $this->storeId())->findOrFail($id);
        $item->is_banned     = 0;
        $item->banned_reason = null;
        $item->banned_source = null;
        $item->save();

        Toastr::success($item->item_name . ' removed from the banned list.');
        return back();
    }

    // ── Walk-in Sale ────────────────────────────────────────────────────────
    public function walkin(Request $request)
    {
        $this->ensurePharmacyColumns();
        $this->ensurePharmacyPermission();
        $storeId = $this->storeId();

        $items = InventoryItem::where('store_id', $storeId)
            ->where('item_type', 'product')
            ->orderBy('item_name')
            ->get(['id', 'item_name', 'brand', 'sku_id', 'selling_price', 'mrp', 'stock', 'is_banned']);

        $dispenseToBearer = Schema::hasColumn('store_configs', 'pharmacy_dispense_to_bearer')
            ? (int) (\App\Models\StoreConfig::where('store_id', $storeId)->value('pharmacy_dispense_to_bearer') ?? 0)
            : 0;

        return view('hmis::vendor.pharmacy.walkin', compact('items', 'dispenseToBearer'));
    }

    public function walkinStore(Request $request)
    {
        $this->ensurePharmacyColumns();
        $storeId = $this->storeId();

        $request->validate([
            'items'             => 'required|array|min:1',
            'items.*.id'        => 'required|integer',
            'items.*.qty'       => 'required|numeric|min:0.01',
            'items.*.price'     => 'required|numeric|min:0',
            'payment_mode'      => 'required|string|max:30',
            'transaction_id'    => 'required_if:payment_mode,Online,Card,UPI|nullable|string|max:100',
            'customer_name'     => 'nullable|string|max:150',
            'customer_phone'    => 'nullable|string|max:20',
            'rx_doctor'         => 'nullable|string|max:150',
            'rx_number'         => 'nullable|string|max:100',
            'rx_notes'          => 'nullable|string|max:1000',
            'discount'          => 'nullable|numeric|min:0',
        ]);

        $lines = collect($request->items)->map(function ($row) use ($storeId) {
            $item = InventoryItem::where('store_id', $storeId)->find($row['id']);
            if (!$item) return null;
            $qty = (float) $row['qty'];
            $price = (float) $row['price'];
            return ['item' => $item, 'qty' => $qty, 'price' => $price, 'amount' => $qty * $price];
        })->filter()->values();

        if ($lines->isEmpty()) {
            Toastr::error('No valid medicines in the cart.');
            return back()->withInput();
        }

        $subtotal = $lines->sum('amount');
        $discount = (float) ($request->discount ?: 0);
        $payable = max(0, $subtotal - $discount);
        $mode = strtolower($request->payment_mode);
        $isOnline = in_array($mode, ['online', 'card', 'upi']);
        $taxType = 'non-gst';

        DB::beginTransaction();
        try {
            $invoiceId = Helpers::generateInvoiceId('H', true, null, $taxType);

            $manual = ManualInvoice::create([
                'invoice_id'     => $invoiceId,
                'invoice_serial' => (int) substr($invoiceId, strrpos($invoiceId, '_') + 1),
                'financial_year' => _currentFinancialYear(),
                'bill_to'        => 0,
                'bill_to_type'   => 'walkin',
                'user_type'      => 'hospital_patient',
                'vendor_id'      => $storeId,
                'total_amount'   => $payable,
                'subtotal_amount' => $subtotal,
                'payment_status' => 'Paid',
                'payment_method' => $request->payment_mode,
                'payment_date'   => now()->toDateString(),
                'invoice_date'   => now()->toDateString(),
                'tax_type'       => $taxType,
                'cash_amount'    => $isOnline ? 0 : $payable,
                'online_amount'  => $isOnline ? $payable : 0,
                'reference_number' => $isOnline ? ['transaction_id' => $request->transaction_id] : [],
                'meta'           => [
                    'source'          => 'pharmacy_walkin',
                    'customer_name'   => $request->customer_name,
                    'customer_phone'  => $request->customer_phone,
                    'rx_doctor'       => $request->rx_doctor,
                    'rx_number'       => $request->rx_number,
                    'rx_notes'        => $request->rx_notes,
                    'payment_mode'    => $request->payment_mode,
                    'transaction_id'  => $isOnline ? $request->transaction_id : null,
                    'discount'        => $discount,
                ],
            ]);

            // Stock may go negative below, which a legacy UNSIGNED column rejects under strict mode.
            _ensureDecimalStockColumns();

            foreach ($lines as $ln) {
                InvoiceItem::create([
                    'rand_invoice_id'   => $invoiceId,
                    'manual_invoice_id' => $manual->id,
                    'name'              => $ln['item']->item_name,
                    'qty'               => $ln['qty'],
                    'price'             => $ln['price'],
                    'tax'               => 0,
                    'gst_status'        => 'excluding',
                    'inv_id'            => $ln['item']->id, // link to inventory item so it appears in Sale Orders
                ]);

                // Decrement pharmacy stock. Not floored at zero — dispensing past what is on the
                // shelf is allowed, so the shortfall has to stay visible instead of reading 0.
                $ln['item']->stock = (float) $ln['item']->stock - $ln['qty'];
                $ln['item']->save();
            }

            // Record this walk-in as an inventory Sale Order (InventoryOrder + details).
            Helpers::_placeInventoryOrder($manual);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Toastr::error('Could not complete the sale: ' . $e->getMessage());
            return back()->withInput();
        }

        try {
            $data = _createBillPdf($manual, 'vendor');
            $manual->update(['pdf' => $data['pdf']]);
        } catch (\Throwable $e) {
            // PDF is non-fatal — the sale is already recorded.
        }

        Toastr::success('Walk-in sale ' . $invoiceId . ' completed.');
        return redirect()->route('vendor.pharmacy.walkin');
    }

    /**
     * Type-ahead over the shared medicine pool, for the pool-first Add Medicine box.
     *
     * Flags what the store already stocks so a pharmacist is not walked into adding a second
     * copy of something already on their shelf.
     */
    public function searchPool(Request $request)
    {
        $this->ensurePharmacyPermission();

        $matches = CatalogPool::search($request->get('q', ''), CatalogPool::DOMAIN_PHARMACY);
        if ($matches->isEmpty()) {
            return response()->json([]);
        }

        $owned = InventoryItem::where('store_id', $this->storeId())
            ->whereIn('catalog_item_id', $matches->pluck('id'))
            ->pluck('id', 'catalog_item_id');

        return response()->json($matches->map(fn(CatalogItem $c) => [
            'id'        => $c->id,
            'name'      => $c->name,
            'brand'     => $c->brand,
            'strength'  => $c->strength_text,
            'form'      => $c->form,
            'label'     => $c->label,
            'meta'      => $c->meta_label,
            'image'     => $c->image_url,
            'in_stock'  => isset($owned[$c->id]),
        ])->values());
    }

    /**
     * Browse the shared catalog and take what you need into your own pharmacy.
     *
     * This is the answer to ten hospitals all stocking Pantoprazole 40 mg: they adopt the one
     * pooled record instead of each typing their own version of it.
     */
    public function catalogBrowse(Request $request)
    {
        $this->ensurePharmacyPermission();
        CatalogPool::ensureSchema();

        $storeId = $this->storeId();
        $search  = trim($request->get('search', ''));
        $form    = trim($request->get('form', ''));
        $hide    = $request->boolean('hide_stocked');

        $stocked = InventoryItem::where('store_id', $storeId)
            ->whereNotNull('catalog_item_id')
            ->pluck('catalog_item_id', 'catalog_item_id');

        $items = CatalogItem::where('domain', CatalogPool::DOMAIN_PHARMACY)
            ->where('status', CatalogItem::STATUS_ACTIVE)
            ->when($search, fn($q) => $q->where(fn($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%")))
            ->when($form, fn($q) => $q->where('form', $form))
            ->when($hide && $stocked->isNotEmpty(), fn($q) => $q->whereNotIn('id', $stocked->keys()))
            ->orderByDesc('usage_count')
            ->orderBy('name')
            ->paginate(40)
            ->appends($request->query());

        // Every unit a suggested default can resolve to has to be in the list, or the row would
        // silently fall back to whatever option happens to be first.
        $units = \App\Models\Unit::orderBy('unit')->pluck('unit')
            ->merge(['Tablet', 'Strip', 'Capsule', 'Bottle', 'Vial', 'Ampoule', 'Sachet', 'Tube', 'ml', 'mg', 'gm', 'Unit', 'Box', 'Piece'])
            ->merge(array_values(CatalogPool::UNIT_BY_FORM))
            ->map(fn($u) => trim((string) $u))->filter()->unique(fn($u) => mb_strtolower($u))->sort()->values();

        $forms = CatalogPool::FORMS;

        return view('hmis::vendor.pharmacy.catalog', compact('items', 'stocked', 'search', 'form', 'hide', 'units', 'forms'));
    }

    /**
     * Take a batch of pooled medicines into this store's pharmacy in one go.
     *
     * Each becomes the store's own priced, stocked row pointing back at the single pooled record.
     * Anything the store already stocks is skipped rather than duplicated.
     */
    public function catalogAdopt(Request $request)
    {
        $this->ensurePharmacyColumns();
        CatalogPool::ensureSchema();

        $request->validate([
            'adopt'      => 'required|array|min:1',
            'adopt.*'    => 'integer',
            'mrp'           => 'array',
            'selling_price' => 'array',
            'stock'         => 'array',
            'unit'       => 'array',
            'pack_unit'  => 'array',
            'pack_qty'   => 'array',
            'stock_unit' => 'array',
        ]);

        $storeId  = $this->storeId();
        $moduleId = Helpers::get_store_data()->module_id ?? null;
        $category = Helpers::_saveCategoryIfNotExists('Medicine');

        // Units are resolved once per distinct name, not once per row: _saveUnitIfNotExist writes
        // to the shared units table, and adopting 60 tablets would otherwise hit it 60 times.
        $unitIds = [];

        $added = 0; $skipped = 0;

        foreach ($request->input('adopt', []) as $catalogItemId) {
            $catalogItem = CatalogItem::find((int) $catalogItemId);
            if (!$catalogItem) {
                continue;
            }
            $catalogItem = $catalogItem->resolved();

            if (CatalogPool::stockedItem($storeId, $catalogItem->id)) {
                $skipped++;
                continue;
            }

            $mrp = (float) ($request->input('mrp.' . $catalogItemId) ?? 0);

            // Left blank, the shelf price is the MRP — the same rule the Add Medicine form uses.
            $sellingInput = $request->input('selling_price.' . $catalogItemId);
            $selling      = ($sellingInput === null || trim((string) $sellingInput) === '')
                ? $mrp
                : (float) $sellingInput;

            // The base unit the row is counted in, falling back to what this dosage form is
            // normally counted in — stock, reorder level and MRP are all read in it.
            $unitName = trim((string) $request->input('unit.' . $catalogItemId))
                ?: CatalogPool::defaultUnitFor($catalogItem->form);

            if (!isset($unitIds[$unitName])) {
                $unitIds[$unitName] = _saveUnitIfNotExist($unitName);
            }

            // Multi-UOM, stored the way the Inventory module stores it: the alternate unit plus
            // how many base units it holds, with secondary_qty normalised to 1. That is what
            // _convertQtyToItemUnit() reads when a box is dispensed as strips.
            $packUnitName = trim((string) $request->input('pack_unit.' . $catalogItemId));
            $packQty      = (float) ($request->input('pack_qty.' . $catalogItemId) ?? 0);
            $hasPack      = $packUnitName !== '' && $packQty > 0 && strcasecmp($packUnitName, $unitName) !== 0;

            if ($hasPack && !isset($unitIds[$packUnitName])) {
                $unitIds[$packUnitName] = _saveUnitIfNotExist($packUnitName);
            }

            // Opening stock is always stored in the base unit; entering it in packs is a
            // convenience, so 3 boxes of 20 lands as 60 strips.
            $qty = (float) ($request->input('stock.' . $catalogItemId) ?? 0);
            if ($hasPack && $request->input('stock_unit.' . $catalogItemId) === 'pack') {
                $qty *= $packQty;
            }

            $item = new InventoryItem();
            $item->store_id           = $storeId;
            $item->item_type          = 'product';
            $item->item_name          = $catalogItem->label;
            $item->brand              = $catalogItem->brand;
            $item->unit               = $unitIds[$unitName];
            $item->category_id        = $category;
            $item->module_id          = $moduleId;
            $item->mrp                = $mrp;
            $item->selling_price      = $selling;
            $item->stock              = $qty;
            $item->reorder_level      = 0;

            if ($hasPack) {
                $item->secondary_unit = $unitIds[$packUnitName];
                $item->secondary_qty  = 1;
                $item->primary_qty    = $packQty;
            }

            $item->gst_rate           = 0;
            $item->gst_status         = 'excluding';
            $item->show_on_store_page = 0;
            $item->save();

            CatalogPool::link($item, $catalogItem);
            $added++;
        }

        $note = $skipped ? " {$skipped} were already in your pharmacy." : '';
        Toastr::success("{$added} medicine(s) added from the shared catalog.{$note}");

        return redirect()->route('vendor.pharmacy.medicines');
    }

    public function saveMedicine(Request $request)
    {
        $this->ensurePharmacyColumns();

        $request->validate([
            'item_name' => 'required|string|max:200',
            'unit'      => 'required|string|max:50',
            'mrp'       => 'required|numeric|min:0',
            'stock'     => 'nullable|numeric|min:0',
            'strength'  => 'nullable|string|max:100',
            'form'      => 'nullable|string|max:50',
            'catalog_item_id' => 'nullable|integer',
        ]);

        $storeId = $this->storeId();

        // Picked from the catalog but already on this store's shelf: adding it again would put the
        // duplication back in the one place the pool cannot prevent it.
        if ($request->filled('catalog_item_id')) {
            $existing = CatalogPool::stockedItem($storeId, (int) $request->input('catalog_item_id'));
            if ($existing) {
                Toastr::warning('"' . $existing->item_name . '" is already in your pharmacy - use Stock Entry to add more.');
                return back();
            }
        }

        $item = new InventoryItem();
        $item->store_id       = $storeId;
        $item->item_type      = 'product'; // required so it shows in Rx search + premium inventory
        $item->item_name      = $request->item_name;
        $item->brand          = $request->brand;
        $item->sku_id         = $request->sku_id;
        $item->unit           = _saveUnitIfNotExist($request->unit);
        $item->category_id    = Helpers::_saveCategoryIfNotExists($request->category ?: 'Medicine');
        $item->module_id      = Helpers::get_store_data()->module_id ?? null;
        $item->mrp            = $request->mrp;
        $item->selling_price  = $request->filled('selling_price') ? $request->selling_price : $request->mrp;
        $item->stock          = $request->stock ?? 0;
        $item->reorder_level  = $request->reorder_level ?? 0;
        $item->expiry_date    = $request->expiry_date ?: null;
        $item->gst_rate       = 0;
        $item->gst_status     = 'excluding';
        $item->show_on_store_page = 0;
        $item->save();

        $this->attachToPool($item, $request, (int) $request->input('catalog_item_id'));

        Toastr::success('Medicine added to the pharmacy.');
        return back();
    }

    /**
     * Bind a freshly saved medicine to the shared pool.
     *
     * Picked from the pool → link it. Typed freehand → try to resolve it anyway (the same product
     * spelt the same way is a link, not a new record), and only file a suggestion when the pool
     * genuinely does not have it. The store's row is already saved either way: pooling is a
     * background nicety and must never be able to fail a pharmacist's save.
     */
    private function attachToPool(InventoryItem $item, Request $request, ?int $catalogItemId = null): void
    {
        try {
            CatalogPool::ensureSchema();

            $catalogItem = $catalogItemId ? CatalogItem::find($catalogItemId) : null;

            if (!$catalogItem) {
                $catalogItem = CatalogPool::resolve(
                    CatalogPool::DOMAIN_PHARMACY,
                    $item->item_name,
                    $item->brand,
                    $request->input('strength'),
                    $request->input('form')
                );
            }

            if ($catalogItem) {
                CatalogPool::link($item, $catalogItem);
                return;
            }

            $suggestion = CatalogPool::suggest([
                'name'     => $item->item_name,
                'brand'    => $item->brand,
                'strength' => $request->input('strength'),
                'form'     => $request->input('form'),
            ], $item->store_id, $item->id);

            if ($suggestion && $suggestion->status === \App\Models\CatalogSuggestion::STATUS_PENDING) {
                VerifyCatalogSuggestions::dispatchAfterResponse([$suggestion->id]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function updateMedicine(Request $request, $id)
    {
        $this->ensurePharmacyColumns();

        $request->validate([
            'item_name' => 'required|string|max:200',
            'unit'      => 'required|string|max:50',
            'mrp'       => 'required|numeric|min:0',
        ]);

        $item = InventoryItem::where('store_id', $this->storeId())->findOrFail($id);
        $item->item_name     = $request->item_name;
        $item->brand         = $request->brand;
        $item->sku_id        = $request->sku_id;
        $item->unit          = _saveUnitIfNotExist($request->unit);
        $item->mrp           = $request->mrp;
        $item->selling_price = $request->filled('selling_price') ? $request->selling_price : $request->mrp;
        $item->reorder_level = $request->reorder_level ?? 0;
        $item->expiry_date   = $request->expiry_date ?: null;
        $item->save();

        Toastr::success('Medicine updated.');
        return back();
    }

    public function addStock(Request $request, $id)
    {
        $request->validate(['quantity' => 'required|numeric|min:0.01']);

        $item = InventoryItem::where('store_id', $this->storeId())->findOrFail($id);
        $item->stock = (float) $item->stock + (float) $request->quantity;
        if ($request->filled('expiry_date')) {
            $item->expiry_date = $request->expiry_date;
        }
        $item->save();

        Toastr::success('Stock added — new balance: ' . $item->stock . '.');
        return back();
    }

    public function deleteMedicine($id)
    {
        $item = InventoryItem::where('store_id', $this->storeId())->findOrFail($id);
        $item->delete();
        Toastr::success('Medicine removed.');
        return back();
    }

    // ── Import / Export ─────────────────────────────────────────────────────
    private const IMPORT_COLUMNS = ['item_name', 'brand', 'sku_id', 'unit', 'mrp', 'selling_price', 'stock', 'reorder_level', 'expiry_date'];

    public function exportMedicines()
    {
        $this->ensurePharmacyColumns();
        $items = InventoryItem::where('store_id', $this->storeId())
            ->where('item_type', 'product')
            ->with('itemunit')
            ->orderBy('item_name')
            ->get();

        $columns = self::IMPORT_COLUMNS;
        $callback = function () use ($items, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            foreach ($items as $i) {
                fputcsv($out, [
                    $i->item_name, $i->brand, $i->sku_id,
                    $i->itemunit->unit ?? '',
                    $i->mrp, $i->selling_price, (int) $i->stock,
                    (int) ($i->reorder_level ?? 0),
                    $i->expiry_date ? \Carbon\Carbon::parse($i->expiry_date)->toDateString() : '',
                ]);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, 'pharmacy_medicines_' . date('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function importMedicines(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xls,xlsx|max:5120']);
        $this->ensurePharmacyColumns();
        $storeId = $this->storeId();

        try {
            $sheet = IOFactory::load($request->file('file')->getPathname())->getActiveSheet();
            $rows  = $sheet->toArray(null, true, true, false); // 0-indexed columns
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

        $created = 0; $updated = 0;
        $pooled = 0; $newSuggestions = [];
        CatalogPool::ensureSchema();
        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                if (!is_array($row)) continue;
                $name = $get($row, ['item_name', 'medicine_name', 'medicine name', 'name', 'medicine', 'generic', 'generic_name']);
                if ($name === null || $name === '') continue;

                $sku  = $get($row, ['sku_id', 'sku', 'code']);
                $item = InventoryItem::where('store_id', $storeId)->where('item_type', 'product')
                    ->when($sku, fn($q) => $q->where('sku_id', $sku))
                    ->when(!$sku, fn($q) => $q->whereRaw('LOWER(item_name) = ?', [mb_strtolower($name)]))
                    ->first();

                $isNew = !$item;
                if ($isNew) {
                    $item = new InventoryItem();
                    $item->store_id          = $storeId;
                    $item->item_type         = 'product';
                    $item->module_id         = Helpers::get_store_data()->module_id ?? null;
                    $item->category_id       = Helpers::_saveCategoryIfNotExists($get($row, ['category']) ?: 'Medicine');
                    $item->gst_rate          = 0;
                    $item->gst_status        = 'excluding';
                    $item->show_on_store_page = 0;
                }

                $item->item_name = $name;
                if (($v = $get($row, ['brand', 'brand_example', 'brand example', 'brand_name'])) !== null && $v !== '') $item->brand = $v;
                if ($sku !== null && $sku !== '')                        $item->sku_id = $sku;
                // Supplier sheets rarely carry a "unit" column, but their pack column says it:
                // "10 tablets" sells as a Tablet, "20 ml vial" as a Vial, "60 ml" as ml. Falling
                // back to a literal "Unit" for all of them makes every stock figure meaningless.
                $pack = $get($row, ['pack', 'pack_size', 'dosage_form', 'dosage form', 'form', 'type']);
                if (($v = $get($row, ['unit'])) !== null && $v !== '') {
                    $item->unit = _saveUnitIfNotExist($v);
                } elseif (($derived = $this->unitFromPack($pack)) !== null) {
                    $item->unit = _saveUnitIfNotExist($derived);
                } elseif ($isNew) {
                    $item->unit = _saveUnitIfNotExist('Unit');
                }

                $mrp     = $get($row, ['mrp']);
                $selling = $get($row, ['selling_price', 'price', 'selling']);
                $stock   = $get($row, ['stock', 'qty', 'quantity']);
                $reorder = $get($row, ['reorder_level', 'reorder', 'min_level']);
                $expiry  = $get($row, ['expiry_date', 'expiry', 'expiry date']);

                if (is_numeric($mrp))     $item->mrp           = (float) $mrp;
                if (is_numeric($selling)) $item->selling_price = (float) $selling;
                elseif ($isNew)           $item->selling_price = is_numeric($mrp) ? (float) $mrp : 0;
                if (is_numeric($stock))   $item->stock         = (float) $stock;
                elseif ($isNew)           $item->stock         = 0;
                if (is_numeric($reorder)) $item->reorder_level = (int) $reorder;
                if ($expiry) {
                    $item->expiry_date = $this->parseSheetDate($expiry) ?? $item->expiry_date;
                }

                $item->save();
                $isNew ? $created++ : $updated++;

                // Match every imported row against the shared pool: a hit costs the store nothing
                // and earns it a clean name and a picture; a miss is queued for curation rather
                // than failing the import.
                $strength = $get($row, ['strength', 'mg', 'dosage', 'power']);
                $form     = $pack;
                $catalogItem = CatalogPool::resolve(CatalogPool::DOMAIN_PHARMACY, $item->item_name, $item->brand, $strength, $form);

                if ($catalogItem) {
                    CatalogPool::link($item, $catalogItem);
                    $pooled++;
                } else {
                    $suggestion = CatalogPool::suggest([
                        'name'     => $item->item_name,
                        'brand'    => $item->brand,
                        'strength' => $strength,
                        'form'     => $form,
                    ], $storeId, $item->id);

                    if ($suggestion && $suggestion->status === \App\Models\CatalogSuggestion::STATUS_PENDING) {
                        $newSuggestions[] = $suggestion->id;
                    }
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Toastr::error('Import failed: ' . $e->getMessage());
            return back();
        }

        // One batched AI pass for everything the import could not match, after the response so
        // the vendor is not left watching a spinner while it runs.
        if ($newSuggestions) {
            VerifyCatalogSuggestions::dispatchAfterResponse(array_slice($newSuggestions, 0, 200));
        }

        $poolNote = $pooled ? " {$pooled} matched the shared catalog." : '';
        Toastr::success("Import complete — {$created} added, {$updated} updated.{$poolNote}");
        return back();
    }

    /**
     * The selling unit a pack description implies: "10 tablets" → Tablet, "20 ml vial" → Vial,
     * "60 ml" → ml, "1.7 ml cartridge" → Cartridge.
     *
     * Kept separate from CatalogPool::normaliseForm(), which answers a different question — that
     * one wants the dosage form for the shared catalog and returns nothing for a pure measurement,
     * while a store still has to sell that syrup in millilitres.
     */
    private function unitFromPack(?string $pack): ?string
    {
        $pack = trim(mb_strtolower((string) $pack));
        if ($pack === '') {
            return null;
        }

        // Drop the leading count: the pack size is not the unit.
        $pack = trim(preg_replace('/^\d+(?:\.\d+)?\s*(x\s*\d+(?:\.\d+)?\s*)?/i', '', $pack));
        if ($pack === '') {
            return null;
        }

        $last = str_contains($pack, ' ') ? substr($pack, strrpos($pack, ' ') + 1) : $pack;
        $last = rtrim(trim($last), 's');

        if ($last === '') {
            return null;
        }

        // Measurements stay lowercase the way a pharmacist writes them; anything else is a noun.
        return in_array($last, ['ml', 'mg', 'g', 'gm', 'mcg', 'l', 'iu'], true) ? $last : ucfirst($last);
    }

    /**
     * A date from a spreadsheet cell, which is either text or an Excel serial number.
     *
     * PhpSpreadsheet hands back the raw serial (46477) for a real date cell, and Carbon reads that
     * as a year — silently filing medicines with expiry dates 40,000 years out.
     */
    private function parseSheetDate($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value) && (float) $value > 25569 && (float) $value < 60000) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        try {
            // Dashes are read day-first by PHP, which is what these sheets mean by 31-03-2027.
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ── Sale Orders (free pharmacy view of inventory sale orders) ───────────
    /**
     * What the pharmacy actually sold, over a window.
     *
     * "A sale" here is any invoice LINE that points at an inventory item (inv_id), whichever
     * screen raised it — the walk-in counter, a prescription dispensed at the window, or medicines
     * put on a hospital bill. All three decrement the same stock and take the same money, so a
     * sales figure that counted only one of them would be quietly wrong every day.
     *
     * Revenue is summed from qty x price on the LINES rather than from the invoice totals: an
     * invoice can carry a consultation fee and a bed charge alongside the medicines, and those are
     * not pharmacy takings.
     */
    public function sales(Request $request)
    {
        $this->ensurePharmacyPermission();
        $storeId = $this->storeId();

        // Default to the last 30 days — long enough to show a trend, short enough that a busy
        // counter's figures still mean something.
        $preset = $request->get('range', '30d');
        $to     = $request->filled('to')   ? \Carbon\Carbon::parse($request->get('to'))->endOfDay()     : now()->endOfDay();
        $from   = $request->filled('from') ? \Carbon\Carbon::parse($request->get('from'))->startOfDay() : match ($preset) {
            'today' => now()->startOfDay(),
            '7d'    => now()->subDays(6)->startOfDay(),
            '90d'   => now()->subDays(89)->startOfDay(),
            default => now()->subDays(29)->startOfDay(),
        };

        $lines = fn() => DB::table('invoice_items as ii')
            ->join('manual_invoices as mi', 'mi.id', '=', 'ii.manual_invoice_id')
            ->where('mi.vendor_id', $storeId)
            ->whereNotNull('ii.inv_id')
            ->whereBetween('mi.invoice_date', [$from->toDateString(), $to->toDateString()]);

        $totals = $lines()
            ->selectRaw('COALESCE(SUM(ii.qty), 0) as items_sold')
            ->selectRaw('COALESCE(SUM(ii.qty * ii.price), 0) as revenue')
            ->selectRaw('COUNT(DISTINCT ii.manual_invoice_id) as sale_count')
            ->first();

        $itemsSold = (float) ($totals->items_sold ?? 0);
        $revenue   = (float) ($totals->revenue ?? 0);
        $saleCount = (int)   ($totals->sale_count ?? 0);

        $stats = [
            'revenue'    => $revenue,
            'items_sold' => $itemsSold,
            'sales'      => $saleCount,
            'avg_sale'   => $saleCount > 0 ? round($revenue / $saleCount, 2) : 0.0,
        ];

        // Grouped by the inventory item, not the typed name — the same medicine spelled two ways
        // on two invoices is one product, and splitting it would understate the top seller.
        $topItems = $lines()
            ->select('ii.inv_id')
            ->selectRaw('MAX(ii.name) as name')
            ->selectRaw('SUM(ii.qty) as qty')
            ->selectRaw('SUM(ii.qty * ii.price) as amount')
            ->groupBy('ii.inv_id')
            ->orderByDesc('amount')
            ->limit(10)
            ->get();

        $daily = $lines()
            ->selectRaw('mi.invoice_date as d')
            ->selectRaw('SUM(ii.qty * ii.price) as amount')
            ->selectRaw('SUM(ii.qty) as qty')
            ->groupBy('mi.invoice_date')
            ->orderBy('mi.invoice_date')
            ->get();

        $recent = $lines()
            ->select('mi.id', 'mi.invoice_id', 'mi.invoice_date', 'mi.payment_status', 'mi.meta')
            ->selectRaw('SUM(ii.qty) as qty')
            ->selectRaw('SUM(ii.qty * ii.price) as amount')
            // Grouped by the primary key alone. The other selected columns are functionally
            // dependent on it, which ONLY_FULL_GROUP_BY accepts — and listing `meta` in the GROUP BY
            // would make MySQL group on a JSON/TEXT column, which is needless work at best.
            ->groupBy('mi.id')
            ->orderByDesc('mi.invoice_date')
            ->orderByDesc('mi.id')
            ->limit(25)
            ->get();

        return view('hmis::vendor.pharmacy.sales', compact(
            'stats', 'topItems', 'daily', 'recent', 'from', 'to', 'preset'
        ));
    }

    public function saleOrders(Request $request)
    {
        $this->ensurePharmacyPermission();
        $storeId = $this->storeId();
        $search  = trim($request->get('search', ''));

        $orders = \App\Models\InventoryOrder::where('store_id', $storeId)
            ->with(['details.item', 'invoice'])
            ->when($search, fn($q) => $q->where('order_id', 'like', "%{$search}%")->orWhere('invoice_id', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate(25);

        return view('hmis::vendor.pharmacy.sale_orders', compact('orders', 'search'));
    }
}
