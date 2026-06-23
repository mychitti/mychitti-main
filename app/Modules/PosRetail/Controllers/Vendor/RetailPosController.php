<?php

namespace App\Modules\PosRetail\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\StoreCustomer;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RetailPosController extends Controller
{
    // Role-grid sub-features (master_module `pos_retail`). Self-healing seed — no migration files.
    public const FEATURES = [
        'pos_billing'       => ['New Sale', ['create', 'price_override', 'hold', 'resume']],
        'pos_bill_discount' => ['Bill Discount', ['apply']],
        'pos_bills'         => ['Bills', ['view', 'void', 'print']],
        'pos_gst_report'    => ['GST Report', ['view']],
        'pos_branch'        => ['Branches', ['view', 'create', 'delete']],
        'pos_counter'       => ['Counters', ['create', 'delete']],
        'pos_branch_stock'  => ['Branch Stock', ['view', 'edit']],
    ];

    // Eager-seedable so the role grid shows it without first opening New Sale.
    public static function seedPermissions(): void
    {
        try { (new self())->ensurePermissions(); } catch (\Throwable $th) {}
    }

    // Retail POS sidebar menu masterdata — all under the single "pos_retail" group, so the items
    // appear (and are toggleable) on the Menu Preference page for pos_retail stores, like HMIS /
    // School. Self-healing & schema-defensive (writes only columns that exist), runs once per
    // request, never throws.
    public const MENU_GROUP = 'pos_retail';
    public const MENU_MASTERDATA = [
        ['slug' => 'retail_dashboard',    'name' => 'Dashboard',           'route' => 'vendor.retail-pos.dashboard'],
        ['slug' => 'retail_new_sale',     'name' => 'New Sale',            'route' => 'vendor.retail-pos.index'],
        ['slug' => 'retail_bills',        'name' => "Today's Bills",       'route' => 'vendor.retail-pos.today'],
        ['slug' => 'retail_gst_report',   'name' => 'GST Report',          'route' => 'vendor.retail-pos.gst-report'],
        ['slug' => 'retail_branches',     'name' => 'Branches & Counters', 'route' => 'vendor.retail-pos.terminals'],
        ['slug' => 'retail_branch_stock', 'name' => 'Branch Stock',        'route' => 'vendor.retail-pos.branch-stock'],
        ['slug' => 'retail_gatepass',     'name' => 'Stock Transfer',      'route' => 'vendor.retail-pos.gatepass'],
    ];

    public static function ensureMenuMasterdata(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            if (!Schema::hasTable('menu')) {
                return;
            }
            $cols = Schema::getColumnListing('menu');
            foreach (self::MENU_MASTERDATA as $item) {
                $existing = DB::table('menu')->where('slug', $item['slug'])->where('menu_type', 'sidebar')->first();
                if ($existing) {
                    // Correct the group / business type on an already-seeded row (only writes when
                    // something is actually off, so there are no per-request updates).
                    $upd = [];
                    if (($existing->group ?? null) !== self::MENU_GROUP) $upd['group'] = self::MENU_GROUP;
                    if (strtolower($existing->business_type ?? '') !== 'pos_retail') $upd['business_type'] = 'pos_retail';
                    if ($upd) {
                        $upd['updated_at'] = now();
                        DB::table('menu')->where('slug', $item['slug'])->where('menu_type', 'sidebar')->update($upd);
                    }
                    continue;
                }
                $row = array_merge([
                    'menu_type'         => 'sidebar',
                    'business_type'     => 'pos_retail',
                    'group'             => self::MENU_GROUP,
                    'status'            => 1,
                    'default'           => 1,
                    'free'              => 1,
                    'under_development' => 0,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ], $item);
                DB::table('menu')->insert(array_intersect_key($row, array_flip($cols)));
            }
        } catch (\Throwable $th) {
            // best-effort — never block a page render
        }
    }

    // Pre-select every Retail POS sidebar item for a store (used when the POS Retail subscription
    // is purchased) so the menus appear immediately. Only acts when the store already has saved
    // sidebar preferences — otherwise the menu defaults already show these items, and writing a
    // partial set would flip selected_menu() into "explicit" mode and hide the rest.
    public static function selectAllMenus($storeId): void
    {
        try {
            if (!$storeId || !Schema::hasTable('store_menu_visibility')) {
                return;
            }
            $hasPrefs = DB::table('store_menu_visibility')
                ->where('store_id', $storeId)->where('menu_type', 'sidebar')->exists();
            if (!$hasPrefs) {
                return;
            }
            foreach (self::MENU_MASTERDATA as $item) {
                $where = ['store_id' => $storeId, 'menu_type' => 'sidebar', 'menu_key' => $item['slug']];
                if (DB::table('store_menu_visibility')->where($where)->exists()) {
                    DB::table('store_menu_visibility')->where($where)->update(['is_visible' => 1]);
                } else {
                    DB::table('store_menu_visibility')->insert($where + ['is_visible' => 1]);
                }
            }
        } catch (\Throwable $th) {
            // best-effort
        }
    }

    // Self-healing variant for the sidebar: when an active POS Retail subscription exists but the
    // store already saved menu prefs (so selected_menu() is in "explicit" mode), seed every Retail
    // POS item ONCE as visible. Runs only while no retail record exists yet, so any later manual
    // hide/show the vendor sets on the Menu Preference page is preserved. Covers every purchase
    // path (free trial, direct buy, temp-purchase finalize) without patching each one.
    public static function syncMenusOnSubscription($storeId): void
    {
        try {
            if (!$storeId || !Schema::hasTable('store_menu_visibility')) {
                return;
            }
            $hasPrefs = DB::table('store_menu_visibility')
                ->where('store_id', $storeId)->where('menu_type', 'sidebar')->exists();
            if (!$hasPrefs) {
                return; // no prefs at all → menu defaults already show the Retail POS items
            }
            $slugs = array_column(self::MENU_MASTERDATA, 'slug');
            $alreadySeeded = DB::table('store_menu_visibility')
                ->where('store_id', $storeId)->where('menu_type', 'sidebar')
                ->whereIn('menu_key', $slugs)->exists();
            if ($alreadySeeded) {
                return; // seeded before → respect whatever the vendor has since chosen
            }
            $rows = [];
            foreach ($slugs as $slug) {
                $rows[] = ['store_id' => $storeId, 'menu_type' => 'sidebar', 'menu_key' => $slug, 'is_visible' => 1];
            }
            DB::table('store_menu_visibility')->insert($rows);
        } catch (\Throwable $th) {
            // best-effort — never block a page render
        }
    }

    private function ensurePermissions(): void
    {
        if (!Schema::hasTable('features') || !Schema::hasTable('feature_permissions')) {
            return;
        }
        foreach (self::FEATURES as $name => [$display, $actions]) {
            $fid = DB::table('features')->where('name', $name)->value('id');
            if (!$fid) {
                $fid = DB::table('features')->insertGetId([
                    'name' => $name, 'display_name' => $display, 'master_module' => 'pos_retail',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            } else {
                // Keep it in its own Retail POS group with the current label.
                DB::table('features')->where('id', $fid)
                    ->update(['master_module' => 'pos_retail', 'display_name' => $display]);
            }
            foreach ($actions as $a) {
                if (!DB::table('feature_permissions')->where('feature_id', $fid)->where('action', $a)->exists()) {
                    // free=1: enforcement is at the route (planwise); this just enables role grants.
                    DB::table('feature_permissions')->insert(['feature_id' => $fid, 'action' => $a, 'free' => 1]);
                }
            }
            DB::table('feature_permissions')->where('feature_id', $fid)->where('free', 0)->update(['free' => 1]);

            // Drop legacy actions no longer part of this feature — the original single
            // `pos_billing` feature carried all nine operations as actions.
            $stale = DB::table('feature_permissions')->where('feature_id', $fid)
                ->whereNotIn('action', $actions)->pluck('id');
            if ($stale->count()) {
                if (Schema::hasTable('role_feature_permissions')) {
                    DB::table('role_feature_permissions')->whereIn('feature_permission_id', $stale)->delete();
                }
                DB::table('feature_permissions')->whereIn('id', $stale)->delete();
            }
        }

        // Remove features that were folded into actions, renamed, or dropped
        // (pos_void, pos_hold_resume, pos_price_override, pos_split_partial, pos_print_share, pos_reports).
        $orphans = DB::table('features')->where('master_module', 'pos_retail')
            ->whereNotIn('name', array_keys(self::FEATURES))->pluck('id');
        if ($orphans->count()) {
            $pids = DB::table('feature_permissions')->whereIn('feature_id', $orphans)->pluck('id');
            if ($pids->count() && Schema::hasTable('role_feature_permissions')) {
                DB::table('role_feature_permissions')->whereIn('feature_permission_id', $pids)->delete();
            }
            DB::table('feature_permissions')->whereIn('feature_id', $orphans)->delete();
            DB::table('features')->whereIn('id', $orphans)->delete();
        }
    }

    private function ensureSchema(): void
    {
        if (Schema::hasTable('manual_invoices')) {
            if (!Schema::hasColumn('manual_invoices', 'pos_status')) {
                DB::statement("ALTER TABLE `manual_invoices` ADD COLUMN `pos_status` VARCHAR(20) NULL");
            }
            if (!Schema::hasColumn('manual_invoices', 'void_reason')) {
                DB::statement("ALTER TABLE `manual_invoices` ADD COLUMN `void_reason` VARCHAR(255) NULL");
            }
            if (!Schema::hasColumn('manual_invoices', 'voided_by')) {
                DB::statement("ALTER TABLE `manual_invoices` ADD COLUMN `voided_by` BIGINT NULL");
            }
        }
        // Store UPI ID — used to render the "scan to pay" QR for UPI payments (§4.2).
        if (Schema::hasTable('stores') && !Schema::hasColumn('stores', 'pos_upi_id')) {
            DB::statement("ALTER TABLE `stores` ADD COLUMN `pos_upi_id` VARCHAR(120) NULL");
        }
        // Owner's last-used billing branch (remembered for the next sale).
        if (Schema::hasTable('stores') && !Schema::hasColumn('stores', 'pos_default_branch_id')) {
            DB::statement("ALTER TABLE `stores` ADD COLUMN `pos_default_branch_id` BIGINT NULL");
        }
        // Chosen New Sale UI template (classic | compact | modern).
        if (Schema::hasTable('stores') && !Schema::hasColumn('stores', 'pos_ui_template')) {
            DB::statement("ALTER TABLE `stores` ADD COLUMN `pos_ui_template` VARCHAR(20) NULL");
        }
        // Chosen printed receipt template (standard | modern | elegant).
        if (Schema::hasTable('stores') && !Schema::hasColumn('stores', 'pos_receipt_template')) {
            DB::statement("ALTER TABLE `stores` ADD COLUMN `pos_receipt_template` VARCHAR(20) NULL");
        }
        // Tag each bill with the branch + counter it was billed at.
        if (Schema::hasTable('manual_invoices')) {
            if (!Schema::hasColumn('manual_invoices', 'pos_branch_id')) {
                DB::statement("ALTER TABLE `manual_invoices` ADD COLUMN `pos_branch_id` BIGINT NULL");
            }
            if (!Schema::hasColumn('manual_invoices', 'pos_terminal_id')) {
                DB::statement("ALTER TABLE `manual_invoices` ADD COLUMN `pos_terminal_id` BIGINT NULL");
            }
        }
        if (!Schema::hasTable('pos_payment_legs')) {
            DB::statement("CREATE TABLE `pos_payment_legs` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `manual_invoice_id` BIGINT UNSIGNED NULL,
                `mode` VARCHAR(30) NOT NULL,
                `sub_type` VARCHAR(50) NULL,
                `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `reference` VARCHAR(100) NULL,
                `approval_code` VARCHAR(50) NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `pos_payment_legs_invoice_idx` (`manual_invoice_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // Hold & Resume (§4.4)
        if (!Schema::hasTable('pos_held_bills')) {
            DB::statement("CREATE TABLE `pos_held_bills` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL,
                `hold_code` VARCHAR(20) NULL,
                `cashier_id` BIGINT NULL,
                `customer_id` BIGINT NULL,
                `cart_json` LONGTEXT NULL,
                `meta` TEXT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'held',
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `pos_held_bills_store_idx` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // Loyalty + store credit on the customer (§4.2/4.3/4.5)
        if (Schema::hasTable('store_customers')) {
            foreach ([
                'loyalty_points' => "INT NOT NULL DEFAULT 0",
                'wallet_balance' => "DECIMAL(12,2) NOT NULL DEFAULT 0",
                'credit_limit'   => "DECIMAL(12,2) NOT NULL DEFAULT 2000",
                'credit_balance' => "DECIMAL(12,2) NOT NULL DEFAULT 0",
            ] as $col => $def) {
                if (!Schema::hasColumn('store_customers', $col)) {
                    DB::statement("ALTER TABLE `store_customers` ADD COLUMN `$col` $def");
                }
            }
        }

        // Loyalty ledger (earn/redeem audit)
        if (!Schema::hasTable('pos_loyalty_ledger')) {
            DB::statement("CREATE TABLE `pos_loyalty_ledger` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL,
                `customer_id` BIGINT NULL,
                `manual_invoice_id` BIGINT UNSIGNED NULL,
                `type` VARCHAR(20) NOT NULL,
                `points` INT NOT NULL DEFAULT 0,
                `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `note` VARCHAR(150) NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `pos_loyalty_ledger_cust_idx` (`customer_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // Counters (terminals) — each belongs to a branch and is assigned to one staff (§3.2)
        if (!Schema::hasTable('pos_terminals')) {
            DB::statement("CREATE TABLE `pos_terminals` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL,
                `branch_id` BIGINT NULL,
                `staff_id` BIGINT NULL,
                `name` VARCHAR(80) NOT NULL,
                `code` VARCHAR(40) NULL,
                `hardware` TEXT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `pos_terminals_store_idx` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (Schema::hasTable('pos_terminals')) {
            if (!Schema::hasColumn('pos_terminals', 'branch_id')) {
                DB::statement("ALTER TABLE `pos_terminals` ADD COLUMN `branch_id` BIGINT NULL");
            }
            if (!Schema::hasColumn('pos_terminals', 'staff_id')) {
                DB::statement("ALTER TABLE `pos_terminals` ADD COLUMN `staff_id` BIGINT NULL");
            }
        }

        // Per-branch stock — stock physically available at each branch.
        if (!Schema::hasTable('pos_branch_stock')) {
            DB::statement("CREATE TABLE `pos_branch_stock` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL,
                `branch_id` BIGINT NULL,
                `inventory_item_id` BIGINT NULL,
                `stock` DECIMAL(12,3) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `pbs_branch_item` (`branch_id`,`inventory_item_id`),
                KEY `pbs_store_idx` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // Stock-transfer gatepass: stock reaches a branch only via a gatepass that deducts the
        // main-store stock and adds it to the branch (immediate). The header + line items also
        // back the printable gatepass document.
        if (!Schema::hasTable('pos_stock_gatepass')) {
            DB::statement("CREATE TABLE `pos_stock_gatepass` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL,
                `branch_id` BIGINT NULL,
                `gatepass_no` VARCHAR(40) NULL,
                `note` VARCHAR(255) NULL,
                `created_by` BIGINT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `psg_store_idx` (`store_id`),
                KEY `psg_branch_idx` (`branch_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('pos_stock_gatepass_items')) {
            DB::statement("CREATE TABLE `pos_stock_gatepass_items` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `gatepass_id` BIGINT UNSIGNED NULL,
                `inventory_item_id` BIGINT NULL,
                `qty` DECIMAL(12,3) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `psgi_gp_idx` (`gatepass_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // Audit trail (price overrides, voids, out-of-stock overrides) — immutable.
        if (!Schema::hasTable('pos_audit_log')) {
            DB::statement("CREATE TABLE `pos_audit_log` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL,
                `action` VARCHAR(40) NOT NULL,
                `reference` VARCHAR(60) NULL,
                `detail` VARCHAR(255) NULL,
                `user_id` BIGINT NULL,
                `user_type` VARCHAR(20) NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `pos_audit_store_idx` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // Loose selling — item weighed on the scale at sale time (e.g. apples sold by weight).
        // Billed as the weighed quantity × per-unit price, like any other fractional-qty line.
        if (Schema::hasTable('inventory_items') && !Schema::hasColumn('inventory_items', 'sell_loose')) {
            DB::statement("ALTER TABLE `inventory_items` ADD COLUMN `sell_loose` TINYINT(1) NOT NULL DEFAULT 0");
        }
        // Optional piece count recorded on a loose line (e.g. "4 apples" sold by weight).
        if (Schema::hasTable('invoice_items') && !Schema::hasColumn('invoice_items', 'pieces')) {
            DB::statement("ALTER TABLE `invoice_items` ADD COLUMN `pieces` INT NULL");
        }
        // Loose items sell fractional weights (0.7 kg), so qty must be decimal. If the column is
        // still an integer type it rounds 0.7 -> 1; widen it once (idempotent — skipped after).
        if (Schema::hasTable('invoice_items') && Schema::hasColumn('invoice_items', 'qty')) {
            try {
                $t = DB::selectOne(
                    "SELECT DATA_TYPE dt FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoice_items' AND COLUMN_NAME = 'qty'"
                );
                if ($t && in_array(strtolower($t->dt), ['int', 'tinyint', 'smallint', 'mediumint', 'bigint'])) {
                    DB::statement("ALTER TABLE `invoice_items` MODIFY `qty` DECIMAL(12,3) NOT NULL DEFAULT 0");
                }
            } catch (\Throwable $th) {
                // best-effort
            }
        }
    }

    // 1 loyalty point earned per ₹100 of bill value; wallet redeems 1:1 (₹).
    private const LOYALTY_EARN_PER = 100;
    // Max discount % a cashier may apply without manager approval (§4.1).
    private const DISCOUNT_CAP = 5;

    private function storeId()
    {
        return Helpers::get_store_id();
    }

    private function logAudit(string $action, ?string $reference, ?string $detail): void
    {
        try {
            DB::table('pos_audit_log')->insert([
                'store_id'   => $this->storeId(),
                'action'     => $action,
                'reference'  => $reference,
                'detail'     => $detail,
                'user_id'    => auth('vendor')->id() ?? auth('vendor_employee')->id(),
                'user_type'  => auth('vendor')->check() ? 'owner' : 'staff',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (\Throwable $th) {
            // audit is best-effort
        }
    }

    // ── Billing screen ──────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $this->ensurePermissions();
        $this->ensureSchema();

        $storeId = $this->storeId();
        // Only categories this store actually has products in.
        $catIds = InventoryItem::where('store_id', $storeId)->where('item_type', 'product')
            ->whereNotNull('category_id')->distinct()->pluck('category_id')->all();
        $categories = Category::where('status', 1)->whereIn('id', $catIds)
            ->orderBy('name')->get(['id', 'name']);

        // Quick-access grid: frequently-sold products (pinned), falling back to recent.
        $topIds = DB::table('invoice_items as ii')
            ->join('manual_invoices as mi', 'mi.id', '=', 'ii.manual_invoice_id')
            ->where('mi.vendor_id', $storeId)
            ->whereNotNull('ii.inv_id')
            ->where('mi.created_at', '>=', now()->subDays(30))
            ->select('ii.inv_id', DB::raw('SUM(ii.qty) as sold'))
            ->groupBy('ii.inv_id')->orderByDesc('sold')->limit(24)->pluck('ii.inv_id')->all();

        $quickItems = InventoryItem::where('store_id', $storeId)->where('item_type', 'product')
            ->when(!empty($topIds), fn($q) => $q->whereIn('id', $topIds)
                ->orderByRaw('FIELD(id,' . implode(',', $topIds) . ') ')
            , fn($q) => $q->orderByDesc('updated_at'))
            ->limit(24)->get();

        $store = Helpers::get_store_data();
        $upiId = $store->pos_upi_id ?? null;
        $storeName = $store->name ?? 'Store';
        // Read the chosen template straight from the row (avoids any cached store object).
        $uiTemplate = DB::table('stores')->where('id', $storeId)->value('pos_ui_template');
        $uiTemplate = in_array($uiTemplate, ['classic', 'compact', 'modern'], true) ? $uiTemplate : 'classic';
        $heldBills = $this->heldBillsData($storeId);

        // Branch context: staff are locked to their counter's branch; owner picks one
        // (defaulting to the last branch they billed from, else the first branch).
        $branches    = Branch::where('store_id', $storeId)->orderBy('name')->get();
        $counter     = $this->currentCounter();
        $myBranchId  = $counter->branch_id ?? null;
        $branchLocked = (bool) ($counter && $counter->branch_id);
        $savedBranch = DB::table('stores')->where('id', $storeId)->value('pos_default_branch_id');
        $defaultBranchId = $myBranchId ?: ($savedBranch ?: ($branches->first()->id ?? null));

        return view('posretail::vendor.retail-pos.index', compact(
            'categories', 'quickItems', 'upiId', 'storeName', 'heldBills', 'uiTemplate',
            'branches', 'myBranchId', 'branchLocked', 'defaultBranchId'
        ));
    }

    // ── Retail sales dashboard ────────────────────────────────────────────────
    public function dashboard(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        // Shared date-range picker (presets + custom).
        $preset = $request->get('date_range', 'today');
        $custom = $request->get('custom_date_range');
        if ($request->filled('from') && $request->filled('to')) {
            $from = $request->get('from');
            $to   = $request->get('to');
        } else {
            $range = Helpers::calculatePresetDates($preset, $custom);
            $from = $range['start'];
            $to   = $range['end'];
        }
        $branch = (int) $request->get('branch') ?: null;
        $branches = Branch::where('store_id', $storeId)->orderBy('name')->get();

        $base = ManualInvoice::where('vendor_id', $storeId)->where('type', 'manual')
            ->whereNotNull('pos_status')
            ->when($branch, fn($q) => $q->where('pos_branch_id', $branch))
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]);

        $final = (clone $base)->where('pos_status', 'final');

        $stats = [
            'sales'   => (clone $final)->sum('total_amount'),
            'bills'   => (clone $final)->count(),
            'cash'    => (clone $final)->sum('cash_amount'),
            'online'  => (clone $final)->sum('online_amount'),
            'tax'     => (clone $final)->sum('final_tax'),
            'voids'   => (clone $base)->where('pos_status', 'void')->count(),
        ];
        $stats['avg'] = $stats['bills'] ? $stats['sales'] / $stats['bills'] : 0;

        $topItems = DB::table('invoice_items as ii')
            ->join('manual_invoices as mi', 'mi.id', '=', 'ii.manual_invoice_id')
            ->where('mi.vendor_id', $storeId)->where('mi.pos_status', 'final')
            ->when($branch, fn($q) => $q->where('mi.pos_branch_id', $branch))
            ->whereBetween(DB::raw('DATE(mi.created_at)'), [$from, $to])
            ->selectRaw('ii.name, SUM(ii.qty) as qty, SUM(ii.price*ii.qty) as amount')
            ->groupBy('ii.name')->orderByDesc('qty')->limit(8)->get();

        // Last 14 days trend (for the line chart) — independent of the selected range.
        $trend = ManualInvoice::where('vendor_id', $storeId)->where('type', 'manual')
            ->where('pos_status', 'final')
            ->when($branch, fn($q) => $q->where('pos_branch_id', $branch))
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as d, SUM(total_amount) as total')
            ->groupBy('d')->orderBy('d')->pluck('total', 'd');

        // Payment-mode breakdown (from the recorded legs).
        $payModes = DB::table('pos_payment_legs as pl')
            ->join('manual_invoices as mi', 'mi.id', '=', 'pl.manual_invoice_id')
            ->where('mi.vendor_id', $storeId)->where('mi.pos_status', 'final')
            ->when($branch, fn($q) => $q->where('mi.pos_branch_id', $branch))
            ->whereBetween(DB::raw('DATE(mi.created_at)'), [$from, $to])
            ->selectRaw('pl.mode, SUM(pl.amount) as amount')
            ->groupBy('pl.mode')->pluck('amount', 'mode');

        // Sales by branch (within the selected range).
        $branchSales = collect();
        if ($branches->count()) {
            $byId = DB::table('manual_invoices')->where('vendor_id', $storeId)->where('type', 'manual')
                ->where('pos_status', 'final')->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                ->selectRaw('pos_branch_id, SUM(total_amount) as total, COUNT(*) as bills')
                ->groupBy('pos_branch_id')->get()->keyBy('pos_branch_id');
            $branchSales = $branches->map(fn($b) => [
                'name'  => $b->name,
                'total' => (float) ($byId[$b->id]->total ?? 0),
                'bills' => (int) ($byId[$b->id]->bills ?? 0),
            ])->sortByDesc('total')->values();
        }

        // Inventory health — branch-scoped (against pos_branch_stock) when a branch is selected.
        $soon = now()->addDays(30)->toDateString();
        if ($branch) {
            $bs = DB::table('pos_branch_stock as bs')->join('inventory_items as ii', 'ii.id', '=', 'bs.inventory_item_id')
                ->where('bs.branch_id', $branch)->where('ii.store_id', $storeId)->where('ii.item_type', 'product');
            $inv = [
                'out'      => (clone $bs)->where('bs.stock', '<=', 0)->count(),
                'low'      => (clone $bs)->whereColumn('bs.stock', '<=', 'ii.reorder_level')->where('bs.stock', '>', 0)->count(),
                'expiring' => (clone $bs)->whereNotNull('ii.expiry_date')->whereDate('ii.expiry_date', '<=', $soon)->count(),
            ];
        } else {
            $inv = [
                'out'      => InventoryItem::where('store_id', $storeId)->where('item_type', 'product')->where('stock', '<=', 0)->count(),
                'low'      => InventoryItem::where('store_id', $storeId)->where('item_type', 'product')
                    ->whereColumn('stock', '<=', 'reorder_level')->where('stock', '>', 0)->count(),
                'expiring' => InventoryItem::where('store_id', $storeId)->where('item_type', 'product')
                    ->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', $soon)->count(),
            ];
        }

        // Outstanding customer credit (store-wide).
        $creditOutstanding = Schema::hasColumn('store_customers', 'credit_balance')
            ? (float) StoreCustomer::where('store_id', $storeId)->sum('credit_balance') : 0;

        // Recent bills. Customer name resolves from store_customers via bill_to (no name column on
        // manual_invoices); walk-ins (bill_to 0/null) show "Walk-in".
        $recent = (clone $final)->orderByDesc('id')->limit(8)
            ->get(['invoice_id', 'bill_to', 'total_amount', 'payment_method', 'created_at']);
        $recentNames = StoreCustomer::whereIn('id', $recent->pluck('bill_to')->filter()->unique())
            ->pluck('f_name', 'id');

        return view('posretail::vendor.retail-pos.dashboard', compact(
            'stats', 'topItems', 'trend', 'payModes', 'inv', 'creditOutstanding', 'recent', 'recentNames',
            'from', 'to', 'branches', 'branch', 'preset', 'custom', 'branchSales'
        ));
    }

    // Live product search / barcode-SKU scan. Returns JSON for the billing screen.
    public function products(Request $request)
    {
        $storeId = $this->storeId();
        $term = trim((string) $request->get('q', ''));
        $exact = $request->boolean('exact'); // scanner sends exact barcode/SKU
        $category = $request->get('category'); // browse a category's items (tab click)

        $query = InventoryItem::with('itemunit')->where('store_id', $storeId)->where('item_type', 'product');

        if ($exact && $term !== '') {
            $query->where(fn($q) => $q->where('barcode', $term)->orWhere('sku_id', $term));
        } elseif ($term !== '') {
            $query->where(fn($q) => $q->where('item_name', 'like', "%{$term}%")
                ->orWhere('sku_id', 'like', "%{$term}%")
                ->orWhere('barcode', $term));
        }
        if ($category && $category !== 'all') {
            $query->where('category_id', $category);
        }

        $limit = $category === 'all' ? 200 : ($category ? 60 : 30);
        $rows = $query->orderBy('item_name')->limit($limit)->get();

        // Stock shown for the billing branch: staff counter's branch, else owner-selected branch.
        $branchId = $this->currentCounter()->branch_id ?? ((int) $request->get('branch') ?: null);
        $branchStock = $branchId
            ? DB::table('pos_branch_stock')->where('branch_id', $branchId)
                ->whereIn('inventory_item_id', $rows->pluck('id'))->pluck('stock', 'inventory_item_id')
            : collect();

        $items = $rows->map(function ($i) use ($branchId, $branchStock) {
            $stock = $branchId ? (float) ($branchStock[$i->id] ?? 0) : (float) $i->stock;
            return [
                'id'         => $i->id,
                'name'       => $i->item_name,
                'sku'        => $i->sku_id,
                'barcode'    => $i->barcode,
                'image'      => $i->image ? asset('storage/app/public/inventory-item/' . $i->image) : '',
                'price'      => (float) ($i->selling_price ?? 0),
                'mrp'        => (float) ($i->mrp ?? 0),
                'hsn'        => $i->hsn,
                'gst_rate'   => (float) ($i->gst_rate ?? 0),
                'gst_status' => $i->gst_status ?? 'excluding',
                'stock'      => $stock,
                'unit'       => optional($i->itemunit)->unit ?? '',
                'expiry'     => $i->expiry_date,
                'expiry_warn'=> $i->expiry_date && $i->expiry_date <= now()->addDays(30)->toDateString(),
                'low_stock'  => $stock <= 0,
                'sell_loose' => (bool) ($i->sell_loose ?? false),
            ];
        });

        return response()->json(['items' => $items]);
    }

    // ── Finalize a sale → GST ManualInvoice ─────────────────────────────────
    public function finalize(Request $request)
    {
        $this->ensureSchema();
        $store = Helpers::get_store_data();
        $storeId = $store->id;

        $cart = json_decode($request->input('items', '[]'), true) ?: [];
        if (empty($cart)) {
            return response()->json(['status' => false, 'msg' => 'Cart is empty'], 422);
        }

        if (count($cart) > 500) {
            return response()->json(['status' => false, 'msg' => 'Maximum 500 line items per bill'], 422);
        }

        $payments = json_decode($request->input('payments', '[]'), true) ?: [];
        $billDiscount = (float) $request->input('bill_discount', 0);
        $customerId = (int) $request->input('customer_id', 0);
        $billingBranchId = $this->billingBranchId($request);

        // When the store has branches, every sale must be billed against one.
        if (!$billingBranchId && Branch::where('store_id', $storeId)->exists()) {
            return response()->json(['status' => false, 'msg' => 'Please select a branch for this sale'], 422);
        }

        // Actor capabilities (owner always passes; staff via role).
        $canOverride   = hasPermission('pos_billing', 'price_override');
        $canBillDisc   = hasPermission('pos_bill_discount', 'apply');
        $allowOos      = $request->boolean('allow_oos') && $canOverride;
        $auditNotes    = [];

        // Bill-level discount needs manager rights.
        if ($billDiscount > 0 && !$canBillDisc) {
            return response()->json(['status' => false, 'msg' => 'Bill discount needs manager approval'], 422);
        }

        $lines = [];
        $subtotal = 0.0;
        $taxTotal = 0.0;
        $hasGst = false;

        foreach ($cart as $row) {
            $item = InventoryItem::where('id', $row['id'] ?? 0)->where('store_id', $storeId)->first();
            if (!$item) {
                continue;
            }
            $qty = max(0, (float) ($row['qty'] ?? 1));
            $price = (float) ($row['price'] ?? $item->selling_price ?? 0);
            $lineDiscount = (float) ($row['discount'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            // Price override (§4.1) — only Owner/Manager may change the unit price.
            $basePrice = (float) ($item->selling_price ?? 0);
            if (round($price, 2) !== round($basePrice, 2)) {
                if (!$canOverride) {
                    return response()->json(['status' => false, 'msg' => "Price override on \"{$item->item_name}\" needs manager approval"], 422);
                }
                $auditNotes[] = "Price {$item->item_name}: ₹{$basePrice}→₹{$price}";
            }

            // Item discount cap for cashiers (§4.1). Above the cap needs manager override.
            if ($lineDiscount > 0) {
                $discPct = ($price * $qty) > 0 ? ($lineDiscount / ($price * $qty)) * 100 : 0;
                if ($discPct > self::DISCOUNT_CAP && !$canOverride) {
                    return response()->json(['status' => false, 'msg' => "Discount above " . self::DISCOUNT_CAP . "% on \"{$item->item_name}\" needs manager approval"], 422);
                }
            }

            // Out-of-stock (§4.1) — checked against branch stock when billing at a branch.
            $avail = $billingBranchId ? $this->branchItemStock($billingBranchId, $item->id) : (float) $item->stock;
            if ($qty > $avail) {
                if (!$allowOos) {
                    return response()->json(['status' => false, 'msg' => "Insufficient stock for \"{$item->item_name}\" (have " . $avail . "). Manager approval required.", 'oos' => true], 422);
                }
                $auditNotes[] = "OOS {$item->item_name}: sold {$qty}, stock {$avail}";
            }

            $gross = ($price * $qty) - $lineDiscount;
            $gross = max(0, $gross);
            $rate = (float) ($item->gst_rate ?? 0);
            $status = $item->gst_status ?? 'excluding';

            if ($rate > 0) {
                $hasGst = true;
            }
            if ($status === 'including') {
                $taxable = $rate > 0 ? $gross / (1 + $rate / 100) : $gross;
                $tax = $gross - $taxable;
            } else {
                $taxable = $gross;
                $tax = $taxable * $rate / 100;
            }

            $subtotal += $taxable;
            $taxTotal += $tax;

            // Piece count is only meaningful for a loose (weighed) line.
            $pieces = ((int) ($item->sell_loose ?? 0) === 1 && (int) ($row['pieces'] ?? 0) > 0)
                ? (int) $row['pieces'] : null;

            $lines[] = [
                'item'       => $item,
                'qty'        => $qty,
                'pieces'     => $pieces,
                'price'      => $price,
                'rate'       => $rate,
                'gst_status' => $status,
                'hsn'        => $item->hsn,
            ];
        }

        if (empty($lines)) {
            return response()->json(['status' => false, 'msg' => 'No valid items'], 422);
        }

        $grandTotal = max(0, $subtotal + $taxTotal - $billDiscount);
        $roundOff = round($grandTotal) - $grandTotal;
        $grandTotal = round($grandTotal);

        $taxType = $hasGst ? 'gst' : 'non-gst';

        $customer = $customerId ? StoreCustomer::find($customerId) : null;

        $cashAmount = 0.0;
        $onlineAmount = 0.0;
        $walletAmount = 0.0;
        $modes = [];
        foreach ($payments as $p) {
            $amt = (float) ($p['amount'] ?? 0);
            $mode = strtolower($p['mode'] ?? 'cash');
            if ($amt <= 0) {
                continue;
            }
            $modes[] = $mode;
            if ($mode === 'cash') {
                $cashAmount += $amt;
            } elseif ($mode === 'wallet') {
                $walletAmount += $amt;
            } else {
                $onlineAmount += $amt;
            }
        }
        if (empty($modes)) {
            $cashAmount = $grandTotal;
            $modes = ['cash'];
        }

        // Wallet redemption must be backed by the customer's balance.
        if ($walletAmount > 0) {
            if (!$customer) {
                return response()->json(['status' => false, 'msg' => 'Wallet payment needs a linked customer'], 422);
            }
            if ($walletAmount > (float) $customer->wallet_balance + 0.001) {
                return response()->json(['status' => false, 'msg' => 'Insufficient wallet balance'], 422);
            }
        }

        $paid = round($cashAmount + $onlineAmount + $walletAmount, 2);
        $due = round($grandTotal - $paid, 2);

        // Partial / credit sale (§4.5): balance carried to customer account.
        if ($due > 0.01) {
            if (!$customer) {
                return response()->json(['status' => false, 'msg' => 'Partial payment needs a linked customer (no walk-in credit)'], 422);
            }
            $newCredit = (float) $customer->credit_balance + $due;
            if ($newCredit > (float) $customer->credit_limit + 0.001) {
                return response()->json(['status' => false, 'msg' => 'Credit limit exceeded (₹' . number_format((float) $customer->credit_limit, 2) . ')'], 422);
            }
            $modes[] = 'credit';
        }
        // Over-tender (cash) → change; record only the applied cash on the invoice.
        $change = $due < 0 ? -$due : 0;
        $cashApplied = max(0, $cashAmount - $change);

        $paymentMethod = count(array_unique($modes)) > 1 ? 'split' : ($modes[0] ?? 'cash');
        $paymentStatus = $due > 0.01 ? 'Partial' : 'Paid';

        $invoiceId = Helpers::generateInvoiceId('M', true, null, $taxType, $store);

        $invoice = new ManualInvoice();
        $invoice->invoice_id = $invoiceId;
        $invoice->invoice_serial = (int) substr($invoiceId, strrpos($invoiceId, '_') + 1);
        $invoice->financial_year = _currentFinancialYear();
        $invoice->vendor_id = $storeId;
        $invoice->module_id = $store->module_id;
        $invoice->bill_to = $customerId ?: 0;
        $invoice->bill_to_type = 'user';
        $invoice->user_type = 'store_user';
        $invoice->total_amount = $grandTotal;
        $invoice->subtotal_amount = round($subtotal, 2);
        $invoice->taxable_amount = round($subtotal, 2);
        $invoice->final_tax = round($taxTotal, 2);
        $invoice->discount_amount = $billDiscount;
        $invoice->round_off = round($roundOff, 2);
        $invoice->tax_type = $taxType;
        $invoice->type = 'manual';
        $invoice->payment_method = $paymentMethod;
        $invoice->payment_status = $paymentStatus;
        $invoice->payment_date = $due > 0.01 ? null : date('Y-m-d');
        $invoice->cash_amount = $cashApplied;
        $invoice->online_amount = $onlineAmount + $walletAmount;
        $invoice->pos_status = 'final';
        // Branch / counter context — staff bill from their assigned counter; owner may pass one.
        $counter = $this->currentCounter();
        $invoice->pos_terminal_id = $counter->id ?? ((int) $request->input('terminal_id') ?: null);
        $invoice->pos_branch_id = $counter->branch_id ?? ((int) $request->input('branch_id') ?: null);
        $invoice->save();

        if (!empty($auditNotes)) {
            $this->logAudit('override', $invoice->invoice_id, implode('; ', $auditNotes));
        }

        foreach ($lines as $line) {
            $ii = new InvoiceItem();
            $ii->rand_invoice_id = $invoice->invoice_id;
            $ii->manual_invoice_id = $invoice->id;
            $ii->inv_id = $line['item']->id;
            $ii->name = $line['item']->item_name;
            $ii->qty = $line['qty'];
            $ii->price = $line['price'];
            $ii->tax = $line['rate'];
            $ii->hsn = $line['hsn'];
            $ii->gst_status = $line['gst_status'];
            $ii->pieces = $line['pieces'];
            $ii->save();

            _updateInventoryStock($line['item']->id, $line['qty'], $line['item']->unit);

            // Decrement the branch's stock too (availability at the counter's branch).
            if ($billingBranchId) {
                DB::table('pos_branch_stock')
                    ->where('branch_id', $billingBranchId)->where('inventory_item_id', $line['item']->id)
                    ->update(['stock' => DB::raw('GREATEST(stock - ' . (float) $line['qty'] . ', 0)'), 'updated_at' => now()]);
            }
        }

        // Sale Order (inventory) from the invoice lines.
        Helpers::_placeInventoryOrder($invoice);

        // Payment legs for reconciliation.
        foreach ($payments as $p) {
            DB::table('pos_payment_legs')->insert([
                'manual_invoice_id' => $invoice->id,
                'mode'              => strtolower($p['mode'] ?? 'cash'),
                'sub_type'          => $p['sub_type'] ?? null,
                'amount'            => (float) ($p['amount'] ?? 0),
                'reference'         => $p['reference'] ?? null,
                'approval_code'     => $p['approval_code'] ?? null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        // Wallet redemption, store credit, and loyalty accrual on the customer.
        $earned = 0;
        if ($customer) {
            if ($walletAmount > 0) {
                $customer->wallet_balance = max(0, (float) $customer->wallet_balance - $walletAmount);
                DB::table('pos_loyalty_ledger')->insert([
                    'store_id' => $storeId, 'customer_id' => $customer->id, 'manual_invoice_id' => $invoice->id,
                    'type' => 'redeem', 'points' => 0, 'amount' => $walletAmount, 'note' => 'Wallet redeemed',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            if ($due > 0.01) {
                $customer->credit_balance = (float) $customer->credit_balance + $due;
            }
            $earned = (int) floor($grandTotal / self::LOYALTY_EARN_PER);
            if ($earned > 0) {
                $customer->loyalty_points = (int) $customer->loyalty_points + $earned;
                DB::table('pos_loyalty_ledger')->insert([
                    'store_id' => $storeId, 'customer_id' => $customer->id, 'manual_invoice_id' => $invoice->id,
                    'type' => 'earn', 'points' => $earned, 'amount' => 0, 'note' => 'Sale ' . $invoice->invoice_id,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $customer->save();

            try {
                $credit = Helpers::ensureSalesAccount();
                $debit = Helpers::ensureCustomerLedger($customer);
                _masterLedgerEntry([
                    'date'         => now(),
                    'amount'       => $grandTotal,
                    'invoice_id'   => 'manual-' . $invoice->id,
                    'voucher_type' => 'Sales',
                    'status'       => $due > 0.01 ? 'pending' : 'approved',
                    'description'  => 'POS Retail Sale',
                ], $credit, $debit, 'customer', 'store', null, null);
            } catch (\Throwable $th) {
                // ledger is best-effort; billing must not fail on accounts
            }
        }

        // Clear the held bill once it's been finalized.
        if ($request->filled('hold_id')) {
            DB::table('pos_held_bills')->where('id', $request->input('hold_id'))
                ->where('store_id', $storeId)->update(['status' => 'billed', 'updated_at' => now()]);
        }

        try {
            $pdf = _createBillPdf($invoice, 'vendor');
            $invoice->update(['pdf' => $pdf['pdf']]);
            $pdfUrl = $pdf['url'];
        } catch (\Throwable $th) {
            $pdfUrl = null;
        }

        return response()->json([
            'status'         => true,
            'invoice_id'     => $invoice->invoice_id,
            'id'             => $invoice->id,
            'total'          => $grandTotal,
            'paid'           => $paid,
            'due'            => max(0, $due),
            'change'         => $due < 0 ? abs($due) : 0,
            'points_earned'  => $earned,
            'payment_status' => $paymentStatus,
            'pdf_url'        => $pdfUrl,
            'thermal_url'    => route('vendor.retail-pos.thermal', $invoice->id),
        ]);
    }

    // ── Customer search (for linking + loyalty/credit) ────────────────────────
    public function customers(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $term = trim((string) $request->get('q', ''));

        $rows = StoreCustomer::where('store_id', $storeId)
            ->when($term !== '', fn($q) => $q->where(fn($w) => $w
                ->where('f_name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('id', $term)
                ->orWhere('id_number', 'like', "%{$term}%")))
            ->orderBy('f_name')->limit(20)->get();

        return response()->json(['customers' => $rows->map(fn($c) => [
            'id'             => $c->id,
            'name'           => $c->f_name,
            'phone'          => $c->phone,
            'loyalty_points' => (int) ($c->loyalty_points ?? 0),
            'wallet_balance' => (float) ($c->wallet_balance ?? 0),
            'credit_limit'   => (float) ($c->credit_limit ?? 0),
            'credit_balance' => (float) ($c->credit_balance ?? 0),
        ])]);
    }

    // ── Hold & Resume (§4.4) ───────────────────────────────────────────────────
    public function holdBill(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();

        $cart = $request->input('items', '[]');
        if (json_decode($cart, true) === null || $cart === '[]') {
            return response()->json(['status' => false, 'msg' => 'Nothing to hold'], 422);
        }

        $open = DB::table('pos_held_bills')->where('store_id', $storeId)->where('status', 'held')->count();
        if ($open >= 10) {
            return response()->json(['status' => false, 'msg' => 'Max 10 bills can be on hold'], 422);
        }

        $seq = DB::table('pos_held_bills')->where('store_id', $storeId)->count() + 1;
        $holdCode = 'H-' . str_pad($seq, 3, '0', STR_PAD_LEFT);

        $id = DB::table('pos_held_bills')->insertGetId([
            'store_id'    => $storeId,
            'hold_code'   => $holdCode,
            'cashier_id'  => auth('vendor')->id() ?? auth('vendor_employee')->id(),
            'customer_id' => (int) $request->input('customer_id', 0) ?: null,
            'cart_json'   => $cart,
            'meta'        => json_encode(['bill_discount' => (float) $request->input('bill_discount', 0)]),
            'status'      => 'held',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return response()->json(['status' => true, 'hold_code' => $holdCode, 'id' => $id]);
    }

    // Shared held-bills list (used for server-render on load + the AJAX refresh).
    private function heldBillsData($storeId)
    {
        return DB::table('pos_held_bills')->where('store_id', $storeId)->where('status', 'held')
            ->orderByDesc('id')->get()->map(function ($h) {
                $items = json_decode($h->cart_json, true) ?: [];
                $cust = $h->customer_id ? StoreCustomer::find($h->customer_id) : null;
                return [
                    'id'         => $h->id,
                    'hold_code'  => $h->hold_code,
                    'customer'   => $cust->f_name ?? 'Walk-in',
                    'item_count' => count($items),
                    'held_at'    => \Illuminate\Support\Carbon::parse($h->created_at)->format('h:i A'),
                ];
            })->values();
    }

    public function heldBills(Request $request)
    {
        $this->ensureSchema();
        return response()->json(['held' => $this->heldBillsData($this->storeId())]);
    }

    public function resumeBill(Request $request, $id)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $hold = DB::table('pos_held_bills')->where('id', $id)->where('store_id', $storeId)->first();
        if (!$hold) {
            return response()->json(['status' => false, 'msg' => 'Held bill not found'], 404);
        }
        return response()->json([
            'status'      => true,
            'hold_id'     => $hold->id,
            'items'       => json_decode($hold->cart_json, true) ?: [],
            'customer_id' => $hold->customer_id,
            'meta'        => json_decode($hold->meta, true) ?: [],
        ]);
    }

    public function deleteHeld(Request $request, $id)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        DB::table('pos_held_bills')->where('id', $id)->where('store_id', $storeId)
            ->update(['status' => 'cancelled', 'updated_at' => now()]);
        return response()->json(['status' => true]);
    }

    // ── Today's bills ────────────────────────────────────────────────────────
    public function todaysBills(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();

        $branch = (int) $request->get('branch') ?: null;
        $branches = Branch::where('store_id', $storeId)->orderBy('name')->get();

        // Date-range picker (defaults to today).
        $preset = $request->get('date_range', 'today');
        $custom = $request->get('custom_date_range');
        if ($request->filled('from') && $request->filled('to')) {
            $from = $request->get('from');
            $to   = $request->get('to');
        } else {
            $range = Helpers::calculatePresetDates($preset, $custom);
            $from = $range['start'];
            $to   = $range['end'];
        }

        $bills = ManualInvoice::where('vendor_id', $storeId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->where('type', 'manual')
            ->whereNotNull('pos_status')
            ->when($branch, fn($q) => $q->where('pos_branch_id', $branch))
            ->orderByDesc('id')
            ->get();

        // Email/WhatsApp need a linked customer — walk-in bills have none. Resolve in one query so
        // the view can disable those actions instead of failing on submit.
        $custIds = $bills->pluck('bill_to')->filter()->unique()->values();
        $customers = $custIds->isNotEmpty()
            ? StoreCustomer::whereIn('id', $custIds)->get(['id', 'f_name', 'email', 'phone'])->keyBy('id')
            : collect();

        return view('posretail::vendor.retail-pos.today', compact('bills', 'branches', 'branch', 'from', 'to', 'preset', 'custom', 'customers'));
    }

    // ── Void (counter-entry; never deleted) ───────────────────────────────────
    public function voidBill(Request $request, $id)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $invoice = ManualInvoice::where('vendor_id', $storeId)->findOrFail($id);

        if ($invoice->pos_status === 'void') {
            Toastr::info('Invoice already voided');
            return back();
        }

        $invoice->pos_status = 'void';
        $invoice->void_reason = $request->input('reason');
        $invoice->voided_by = auth('vendor')->id() ?? auth('vendor_employee')->id();
        $invoice->save();

        // Restore stock for each line (global + branch, if the bill was branch-tagged).
        foreach (InvoiceItem::where('manual_invoice_id', $invoice->id)->whereNotNull('inv_id')->get() as $line) {
            $item = InventoryItem::where('id', $line->inv_id)->where('store_id', $storeId)->first();
            if ($item) {
                $item->stock = (float) $item->stock + (float) $line->qty;
                $item->save();
            }
            if ($invoice->pos_branch_id) {
                DB::table('pos_branch_stock')
                    ->where('branch_id', $invoice->pos_branch_id)->where('inventory_item_id', $line->inv_id)
                    ->update(['stock' => DB::raw('stock + ' . (float) $line->qty), 'updated_at' => now()]);
            }
        }

        $this->logAudit('void', $invoice->invoice_id, $invoice->void_reason);
        Toastr::success('Invoice voided');
        return back();
    }

    // ── 80mm thermal receipt (browser print) ───────────────────────────────────
    public function thermal(Request $request, $id)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $invoice = ManualInvoice::where('vendor_id', $storeId)->findOrFail($id);
        $items = InvoiceItem::with('item.itemunit')->where('manual_invoice_id', $invoice->id)->get();
        $store = Helpers::get_store_data();
        $legs = DB::table('pos_payment_legs')->where('manual_invoice_id', $invoice->id)->get();
        $customer = $invoice->bill_to ? StoreCustomer::find($invoice->bill_to) : null;

        $receiptTemplate = DB::table('stores')->where('id', $storeId)->value('pos_receipt_template') ?: 'standard';
        $receiptTemplate = in_array($receiptTemplate, ['standard', 'modern', 'elegant'], true) ? $receiptTemplate : 'standard';

        return view('posretail::vendor.retail-pos.thermal', compact('invoice', 'items', 'store', 'legs', 'customer', 'receiptTemplate'));
    }

    // ── Email invoice (§4.3) ────────────────────────────────────────────────────
    public function emailInvoice(Request $request, $id)
    {
        $invoice = ManualInvoice::where('vendor_id', $this->storeId())->findOrFail($id);
        $customer = $invoice->bill_to ? StoreCustomer::find($invoice->bill_to) : null;
        $email = $request->input('email') ?: ($customer->email ?? null);
        if (!$email) {
            Toastr::error('No customer email on file');
            return back();
        }
        if (!$invoice->pdf) {
            try { $pdf = _createBillPdf($invoice, 'vendor'); $invoice->update(['pdf' => $pdf['pdf']]); } catch (\Throwable $th) {}
        }
        $path = storage_path('app/public/invoice/' . $invoice->pdf);
        try {
            \Illuminate\Support\Facades\Mail::raw('Please find your invoice ' . $invoice->invoice_id . ' attached. Thank you for shopping with us.', function ($m) use ($email, $invoice, $path) {
                $m->to($email)->subject('Invoice ' . $invoice->invoice_id);
                if (is_file($path)) { $m->attach($path); }
            });
            $this->logAudit('email', $invoice->invoice_id, $email);
            Toastr::success('Invoice emailed to ' . $email);
        } catch (\Throwable $th) {
            Toastr::error('Email failed (check SMTP settings)');
        }
        return back();
    }

    // ── WhatsApp invoice + SMS fallback (§4.3, config-gated) ─────────────────────
    public function whatsappInvoice(Request $request, $id)
    {
        $invoice = ManualInvoice::where('vendor_id', $this->storeId())->findOrFail($id);
        $customer = $invoice->bill_to ? StoreCustomer::find($invoice->bill_to) : null;
        $phone = $request->input('phone') ?: ($customer->phone ?? null);
        if (!$phone) {
            Toastr::error('No customer phone on file');
            return back();
        }
        // Make sure there's a PDF to attach.
        if (!$invoice->pdf) {
            try { $pdf = _createBillPdf($invoice, 'vendor'); $invoice->update(['pdf' => $pdf['pdf']]); } catch (\Throwable $th) {}
        }
        $pdfUrl = $invoice->pdf ? asset('storage/app/public/invoice/' . $invoice->pdf) : null;

        $wa = \App\Services\WhatsAppService::make($this->storeId());
        if (!$wa->isConfigured()) {
            Toastr::warning('WhatsApp API not configured. Invoice link: ' . ($pdfUrl ?: '—'));
            return back();
        }

        $caption = 'Invoice ' . $invoice->invoice_id;
        $res = $pdfUrl
            ? $wa->sendDocument($phone, $pdfUrl, 'Invoice-' . $invoice->invoice_id . '.pdf', $caption)
            : $wa->sendText($phone, $caption);

        if ($res['success']) {
            $this->logAudit('whatsapp', $invoice->invoice_id, $phone);
            Toastr::success('Invoice sent via WhatsApp to ' . $phone);
        } else {
            Toastr::error('WhatsApp send failed: ' . $res['error']);
        }
        return back();
    }

    // ── GST reports — GSTR-1 / GSTR-3B summary by slab (§7) ───────────────────────
    public function gstReport(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        // Use the panel's shared date-range picker (presets + custom).
        $preset = $request->get('date_range', 'this_month');
        $custom = $request->get('custom_date_range');
        if ($request->filled('from') && $request->filled('to')) {
            $from = $request->get('from');
            $to   = $request->get('to');
        } else {
            $range = Helpers::calculatePresetDates($preset, $custom);
            $from = $range['start'];
            $to   = $range['end'];
        }
        $branch = (int) $request->get('branch') ?: null;
        $branches = Branch::where('store_id', $storeId)->orderBy('name')->get();

        $rows = DB::table('invoice_items as ii')
            ->join('manual_invoices as mi', 'mi.id', '=', 'ii.manual_invoice_id')
            ->where('mi.vendor_id', $storeId)
            ->where('mi.type', 'manual')
            ->where('mi.pos_status', 'final')
            ->when($branch, fn($q) => $q->where('mi.pos_branch_id', $branch))
            ->whereBetween(DB::raw('DATE(mi.created_at)'), [$from, $to])
            ->selectRaw('ii.tax as rate,
                SUM(CASE WHEN ii.gst_status = "including" AND ii.tax > 0
                        THEN (ii.price*ii.qty)/(1+ii.tax/100) ELSE ii.price*ii.qty END) as taxable,
                SUM(CASE WHEN ii.gst_status = "including" AND ii.tax > 0
                        THEN (ii.price*ii.qty) - (ii.price*ii.qty)/(1+ii.tax/100)
                        ELSE (ii.price*ii.qty)*ii.tax/100 END) as tax')
            ->groupBy('ii.tax')->orderBy('ii.tax')->get();

        if ($request->get('export') === 'csv') {
            $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=GST_{$from}_{$to}.csv"];
            return response()->stream(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['GST Rate %', 'Taxable Value', 'CGST', 'SGST', 'Total Tax', 'Invoice Value']);
                foreach ($rows as $r) {
                    $tax = round($r->tax, 2);
                    fputcsv($out, [$r->rate, round($r->taxable, 2), round($tax / 2, 2), round($tax / 2, 2), $tax, round($r->taxable + $tax, 2)]);
                }
                fclose($out);
            }, 200, $headers);
        }

        return view('posretail::vendor.retail-pos.gst-report', compact('rows', 'from', 'to', 'branches', 'branch', 'preset', 'custom'));
    }

    // ── Branches, Counters (terminals) + staff assignment (§3.2) ─────────────────
    public function terminals(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();

        $branches = Branch::where('store_id', $storeId)->orderBy('name')->get();
        $counters = DB::table('pos_terminals')->where('store_id', $storeId)->orderBy('branch_id')->orderBy('id')->get();
        $staff    = VendorEmployee::where('store_id', $storeId)->orderBy('f_name')
            ->get(['id', 'f_name', 'l_name', 'branch_id']);
        $upiId    = DB::table('stores')->where('id', $storeId)->value('pos_upi_id');
        $uiTemplate = DB::table('stores')->where('id', $storeId)->value('pos_ui_template') ?: 'classic';

        return view('posretail::vendor.retail-pos.terminals', compact('branches', 'counters', 'staff', 'upiId', 'uiTemplate'));
    }

    // Store-level Retail POS settings page (UPI, New Sale UI template).
    public function settings(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $upiId      = DB::table('stores')->where('id', $storeId)->value('pos_upi_id');
        $uiTemplate = DB::table('stores')->where('id', $storeId)->value('pos_ui_template') ?: 'classic';
        $receiptTemplate = DB::table('stores')->where('id', $storeId)->value('pos_receipt_template') ?: 'standard';
        return view('posretail::vendor.retail-pos.settings', compact('upiId', 'uiTemplate', 'receiptTemplate'));
    }

    public function saveUpi(Request $request)
    {
        $this->ensureSchema();
        DB::table('stores')->where('id', $this->storeId())
            ->update(['pos_upi_id' => trim((string) $request->input('upi_id')) ?: null]);
        Toastr::success('UPI ID saved');
        return back();
    }

    // Persist the vendor's chosen New Sale UI template.
    public function saveUiTemplate(Request $request)
    {
        $this->ensureSchema();
        $tpl = $request->input('pos_ui_template');
        if (!in_array($tpl, ['classic', 'compact', 'modern'], true)) {
            $tpl = 'classic';
        }
        DB::table('stores')->where('id', $this->storeId())->update(['pos_ui_template' => $tpl]);
        Toastr::success('New Sale UI template updated');
        return back();
    }

    // Persist the vendor's chosen printed receipt template.
    public function saveReceiptTemplate(Request $request)
    {
        $this->ensureSchema();
        $tpl = $request->input('pos_receipt_template');
        if (!in_array($tpl, ['standard', 'modern', 'elegant'], true)) {
            $tpl = 'standard';
        }
        DB::table('stores')->where('id', $this->storeId())->update(['pos_receipt_template' => $tpl]);
        Toastr::success('Receipt template updated');
        return back();
    }

    // Remember the owner's chosen billing branch for next time.
    public function saveDefaultBranch(Request $request)
    {
        $this->ensureSchema();
        DB::table('stores')->where('id', $this->storeId())
            ->update(['pos_default_branch_id' => (int) $request->input('branch_id') ?: null]);
        return response()->json(['status' => true]);
    }

    public function branchStore(Request $request)
    {
        $this->ensureSchema();
        $name = trim((string) $request->input('name'));
        if ($name === '') {
            Toastr::error('Branch name required');
            return back();
        }
        $branch = new Branch();
        $branch->name = $name;
        $branch->address = $request->input('address');
        $branch->store_id = $this->storeId();
        $branch->save();
        Toastr::success('Branch added');
        return back();
    }

    public function branchDelete(Request $request, $id)
    {
        $storeId = $this->storeId();
        // Detach counters from the branch before removing it.
        DB::table('pos_terminals')->where('store_id', $storeId)->where('branch_id', $id)->update(['branch_id' => null]);
        Branch::where('id', $id)->where('store_id', $storeId)->delete();
        Toastr::success('Branch removed');
        return back();
    }

    public function terminalStore(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $name = trim((string) $request->input('name'));
        if ($name === '') {
            Toastr::error('Counter name required');
            return back();
        }
        $staffId = (int) $request->input('staff_id') ?: null;
        // One counter per staff: clear any existing assignment for this staff first.
        if ($staffId) {
            DB::table('pos_terminals')->where('store_id', $storeId)->where('staff_id', $staffId)->update(['staff_id' => null]);
        }
        DB::table('pos_terminals')->insert([
            'store_id'   => $storeId,
            'branch_id'  => (int) $request->input('branch_id') ?: null,
            'staff_id'   => $staffId,
            'name'       => $name,
            'code'       => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 6)) . '-' . rand(10, 99),
            'hardware'   => json_encode([
                'thermal' => $request->input('thermal'),
                'scale'   => $request->input('scale'),
                'drawer'  => $request->boolean('drawer'),
            ]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Toastr::success('Counter added');
        return back();
    }

    public function terminalDelete(Request $request, $id)
    {
        DB::table('pos_terminals')->where('id', $id)->where('store_id', $this->storeId())->delete();
        Toastr::success('Counter removed');
        return back();
    }

    // The counter assigned to the current actor (staff). Owner has no fixed counter.
    private function currentCounter()
    {
        $empId = auth('vendor_employee')->id();
        if (!$empId) {
            return null;
        }
        return DB::table('pos_terminals')->where('store_id', $this->storeId())->where('staff_id', $empId)->first();
    }

    // The branch a sale is billed from: the staff's counter branch, else a posted branch_id.
    private function billingBranchId(Request $request = null): ?int
    {
        $counter = $this->currentCounter();
        if ($counter && $counter->branch_id) {
            return (int) $counter->branch_id;
        }
        if ($request && $request->input('branch_id')) {
            return (int) $request->input('branch_id');
        }
        return null;
    }

    private function branchItemStock(int $branchId, int $itemId): float
    {
        return (float) DB::table('pos_branch_stock')->where('branch_id', $branchId)
            ->where('inventory_item_id', $itemId)->value('stock');
    }

    // ── Per-branch stock management ──────────────────────────────────────────────
    public function branchStock(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $branches = Branch::where('store_id', $storeId)->orderBy('name')->get();
        $branchId = (int) $request->get('branch') ?: ($branches->first()->id ?? null);
        $search = trim((string) $request->get('q', ''));

        $items = InventoryItem::where('store_id', $storeId)->where('item_type', 'product')
            ->when($search, fn($q) => $q->where(fn($w) => $w->where('item_name', 'like', "%{$search}%")->orWhere('sku_id', 'like', "%{$search}%")))
            ->orderBy('item_name')->limit(300)->get(['id', 'item_name', 'sku_id', 'stock']);

        $branchStock = $branchId
            ? DB::table('pos_branch_stock')->where('branch_id', $branchId)
                ->pluck('stock', 'inventory_item_id')
            : collect();

        return view('posretail::vendor.retail-pos.branch-stock', compact('branches', 'branchId', 'items', 'branchStock', 'search'));
    }

    public function branchStockSave(Request $request)
    {
        // Direct editing is disabled — branch stock now moves in only via a Stock Transfer gatepass.
        Toastr::info('Branch stock is managed through Stock Transfer (Gatepass).');
        return redirect()->route('vendor.retail-pos.gatepass');
    }

    // ── Stock Transfer Gatepass (main store → branch) ───────────────────────────
    public function gatepass(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $branches = Branch::where('store_id', $storeId)->orderBy('name')->get();

        $search = trim((string) $request->get('q', ''));
        $items = InventoryItem::where('store_id', $storeId)->where('item_type', 'product')
            ->when($search, fn($q) => $q->where(fn($w) => $w->where('item_name', 'like', "%{$search}%")->orWhere('sku_id', 'like', "%{$search}%")))
            ->with('itemunit')->orderBy('item_name')->limit(300)->get(['id', 'item_name', 'sku_id', 'stock', 'unit']);

        $gatepasses = DB::table('pos_stock_gatepass as g')
            ->leftJoin('branches as b', 'b.id', '=', 'g.branch_id')
            ->where('g.store_id', $storeId)
            ->orderByDesc('g.id')->limit(100)
            ->get(['g.id', 'g.gatepass_no', 'g.note', 'g.created_at', 'b.name as branch_name']);

        return view('posretail::vendor.retail-pos.gatepass', compact('branches', 'items', 'gatepasses', 'search'));
    }

    public function gatepassStore(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $branchId = (int) $request->input('branch_id');
        $branch = Branch::where('store_id', $storeId)->where('id', $branchId)->first();
        if (!$branch) {
            Toastr::error('Select a valid branch');
            return back();
        }

        $lines = [];
        foreach ((array) $request->input('qty', []) as $itemId => $val) {
            $qty = (float) $val;
            if ($qty > 0) {
                $lines[(int) $itemId] = $qty;
            }
        }
        if (empty($lines)) {
            Toastr::error('Enter a transfer quantity for at least one item');
            return back();
        }

        // Validate availability against main-store stock before moving anything.
        $items = InventoryItem::where('store_id', $storeId)->whereIn('id', array_keys($lines))->get()->keyBy('id');
        foreach ($lines as $itemId => $qty) {
            $item = $items->get($itemId);
            if (!$item) {
                Toastr::error('Item not found');
                return back();
            }
            if ($qty > (float) $item->stock) {
                Toastr::error("Insufficient main-store stock for \"{$item->item_name}\" (have " . rtrim(rtrim(number_format((float) $item->stock, 3), '0'), '.') . ')');
                return back();
            }
        }

        DB::beginTransaction();
        try {
            $serial = DB::table('pos_stock_gatepass')->where('store_id', $storeId)->count() + 1;
            $gpNo = 'GP-' . str_pad((string) $serial, 4, '0', STR_PAD_LEFT);
            $gatepassId = DB::table('pos_stock_gatepass')->insertGetId([
                'store_id'    => $storeId,
                'branch_id'   => $branchId,
                'gatepass_no' => $gpNo,
                'note'        => $request->input('note'),
                'created_by'  => auth('vendor')->id() ?? auth('vendor_employee')->id(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            foreach ($lines as $itemId => $qty) {
                // Deduct from main store…
                InventoryItem::where('id', $itemId)->update(['stock' => DB::raw('GREATEST(stock - ' . $qty . ', 0)')]);
                // …and add to the branch (increment if a row already exists).
                $existing = DB::table('pos_branch_stock')->where('branch_id', $branchId)->where('inventory_item_id', $itemId)->first();
                if ($existing) {
                    DB::table('pos_branch_stock')->where('id', $existing->id)->update(['stock' => DB::raw('stock + ' . $qty), 'updated_at' => now()]);
                } else {
                    DB::table('pos_branch_stock')->insert(['branch_id' => $branchId, 'inventory_item_id' => $itemId, 'store_id' => $storeId, 'stock' => $qty, 'created_at' => now(), 'updated_at' => now()]);
                }
                DB::table('pos_stock_gatepass_items')->insert(['gatepass_id' => $gatepassId, 'inventory_item_id' => $itemId, 'qty' => $qty, 'created_at' => now()]);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Toastr::error('Transfer failed: ' . $th->getMessage());
            return back();
        }

        Toastr::success('Stock transferred to ' . $branch->name . ' (' . $gpNo . ')');
        return redirect()->route('vendor.retail-pos.gatepass.print', $gatepassId);
    }

    public function gatepassPrint(Request $request, $id)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $gatepass = DB::table('pos_stock_gatepass')->where('store_id', $storeId)->where('id', $id)->first();
        if (!$gatepass) {
            abort(404);
        }
        $branch = Branch::find($gatepass->branch_id);
        $items = DB::table('pos_stock_gatepass_items as gi')
            ->leftJoin('inventory_items as ii', 'ii.id', '=', 'gi.inventory_item_id')
            ->leftJoin('units as u', 'u.id', '=', 'ii.unit')
            ->where('gi.gatepass_id', $id)
            ->get(['gi.qty', 'ii.item_name', 'ii.sku_id', 'u.unit as unit_label']);
        $store = Helpers::get_store_data();
        return view('posretail::vendor.retail-pos.gatepass-print', compact('gatepass', 'branch', 'items', 'store'));
    }
}
