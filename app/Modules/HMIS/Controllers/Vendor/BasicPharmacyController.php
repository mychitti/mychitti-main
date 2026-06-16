<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\CentralLogics\Helpers;
use App\Models\InventoryItem;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\PharmacyBannedItem;
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
        $this->ensureBannedTable();
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

        $bannedNames = PharmacyBannedItem::activeNames($storeId);

        return view('hmis::vendor.pharmacy.medicines', compact('items', 'stats', 'search', 'bannedNames'));
    }

    // ── Banned / Blocked Items ──────────────────────────────────────────────
    // Store-maintained list of medicines that should NOT be sold/dispensed (govt-banned or
    // store-blocked). Surfaced as warnings (warn-but-allow) wherever a medicine is entered.
    private function ensureBannedTable(): void
    {
        if (!Schema::hasTable('pharmacy_banned_items')) {
            DB::statement("CREATE TABLE IF NOT EXISTS `pharmacy_banned_items` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NOT NULL,
                `name` VARCHAR(200) NOT NULL,
                `reason` VARCHAR(500) NULL,
                `source` VARCHAR(20) NOT NULL DEFAULT 'store',
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `pbi_store_idx` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function bannedItems(Request $request)
    {
        $this->ensurePharmacyPermission();
        $this->ensureBannedTable();
        $storeId = $this->storeId();

        $search = trim($request->get('search', ''));
        $banned = PharmacyBannedItem::where('store_id', $storeId)
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();

        // Items currently in stock that match a banned name — flagged so the pharmacist notices.
        $bannedNames = $banned->where('status', 1)->pluck('name')->map(fn($n) => mb_strtolower(trim($n)));
        $stockedBanned = InventoryItem::where('store_id', $storeId)
            ->where('item_type', 'product')
            ->get()
            ->filter(fn($i) => $bannedNames->contains(mb_strtolower(trim($i->item_name))));

        return view('hmis::vendor.pharmacy.banned_items', compact('banned', 'search', 'stockedBanned'));
    }

    public function saveBannedItem(Request $request)
    {
        $this->ensureBannedTable();
        $request->validate([
            'name'   => 'required|string|max:200',
            'reason' => 'nullable|string|max:500',
            'source' => 'nullable|in:govt,store',
        ]);

        PharmacyBannedItem::create([
            'store_id' => $this->storeId(),
            'name'     => trim($request->name),
            'reason'   => $request->reason,
            'source'   => $request->source ?: 'store',
            'status'   => 1,
        ]);

        Toastr::success('Item added to the banned/blocked list.');
        return back();
    }

    public function toggleBannedItem($id)
    {
        $this->ensureBannedTable();
        $item = PharmacyBannedItem::where('store_id', $this->storeId())->findOrFail($id);
        $item->status = $item->status ? 0 : 1;
        $item->save();

        Toastr::success('Banned item ' . ($item->status ? 'activated' : 'paused') . '.');
        return back();
    }

    public function deleteBannedItem($id)
    {
        $this->ensureBannedTable();
        $item = PharmacyBannedItem::where('store_id', $this->storeId())->findOrFail($id);
        $item->delete();

        Toastr::success('Removed from the banned/blocked list.');
        return back();
    }

    // ── Walk-in Sale ────────────────────────────────────────────────────────
    public function walkin(Request $request)
    {
        $this->ensurePharmacyColumns();
        $this->ensurePharmacyPermission();
        $this->ensureBannedTable();
        $storeId = $this->storeId();

        $items = InventoryItem::where('store_id', $storeId)
            ->where('item_type', 'product')
            ->orderBy('item_name')
            ->get(['id', 'item_name', 'brand', 'sku_id', 'selling_price', 'mrp', 'stock']);

        $bannedNames = PharmacyBannedItem::activeNames($storeId);

        return view('hmis::vendor.pharmacy.walkin', compact('items', 'bannedNames'));
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
            'transaction_id'    => 'required_if:payment_mode,online,card,upi|nullable|string|max:100',
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
