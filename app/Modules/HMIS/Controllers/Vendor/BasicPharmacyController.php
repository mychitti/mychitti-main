<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\CentralLogics\Helpers;
use App\Models\InventoryItem;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        foreach (['list', 'add', 'edit', 'delete', 'dispense'] as $action) {
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

        return view('hmis::vendor.pharmacy.medicines', compact('items', 'stats', 'search'));
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

            foreach ($lines as $ln) {
                InvoiceItem::create([
                    'rand_invoice_id'   => $invoiceId,
                    'manual_invoice_id' => $manual->id,
                    'name'              => $ln['item']->item_name,
                    'qty'               => $ln['qty'],
                    'price'             => $ln['price'],
                    'tax'               => 0,
                    'gst_status'        => 'excluding',
                ]);

                // Decrement pharmacy stock.
                $ln['item']->stock = max(0, (float) $ln['item']->stock - $ln['qty']);
                $ln['item']->save();
            }

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

    public function saveMedicine(Request $request)
    {
        $this->ensurePharmacyColumns();

        $request->validate([
            'item_name' => 'required|string|max:200',
            'unit'      => 'required|string|max:50',
            'mrp'       => 'required|numeric|min:0',
            'stock'     => 'nullable|numeric|min:0',
        ]);

        $storeId = $this->storeId();

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

        Toastr::success('Medicine added to the pharmacy.');
        return back();
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
}
