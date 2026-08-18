<?php

namespace App\Modules\PosRetail\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\StoreCustomer;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use App\Exports\PosTopItemsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class RetailPosController extends Controller
{
    // Role-grid sub-features (master_module `pos_retail`). Self-healing seed — no migration files.
    public const FEATURES = [
        'pos_billing'       => ['New Sale', ['create', 'price_override', 'hold', 'resume']],
        'pos_bill_discount' => ['Bill Discount', ['apply']],
        'pos_bills'         => ['Bills', ['view', 'void', 'print', 'delete']],
        'pos_gst_report'    => ['GST Report', ['view']],
        'pos_branch'        => ['Branches', ['view', 'create', 'delete']],
        'pos_counter'       => ['Counters', ['create', 'delete']],
        'pos_branch_stock'  => ['Branch Stock', ['view', 'edit']],
        'pos_gatepass'      => ['Stock Transfer (Gatepass)', ['view', 'create', 'delete']],
        'pos_writeoff'      => ['Damaged / Theft Stock', ['view', 'create', 'delete']],
        'pos_cash'          => ['Cash Flow', ['view', 'manage']],
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
        ['slug' => 'retail_offers',       'name' => 'Offers',              'route' => 'vendor.retail-pos.offer.index'],
        ['slug' => 'retail_branches',     'name' => 'Branches & Counters', 'route' => 'vendor.retail-pos.terminals'],
        ['slug' => 'retail_branch_stock', 'name' => 'Branch Stock',        'route' => 'vendor.retail-pos.branch-stock'],
        ['slug' => 'retail_gatepass',     'name' => 'Stock Transfer',      'route' => 'vendor.retail-pos.gatepass'],
        ['slug' => 'retail_writeoff',     'name' => 'Damaged / Theft',     'route' => 'vendor.retail-pos.writeoff'],
        ['slug' => 'retail_cash_flow',    'name' => 'Cash Flow',           'route' => 'vendor.retail-pos.cash-flow'],
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

            // pos_retail also gets basic Inventory free (free-by-business-type). Seed the Inventory
            // menu visible ONCE if the store has no row for it yet — independent of the Retail POS
            // "seed once" guard below, so stores that already had retail menus seeded still get it.
            // A later manual hide on the Menu Preference page creates the row and is preserved.
            $hasInventoryRow = DB::table('store_menu_visibility')
                ->where('store_id', $storeId)->where('menu_type', 'sidebar')
                ->where('menu_key', 'inventory_manage')->exists();
            if (!$hasInventoryRow) {
                DB::table('store_menu_visibility')->insert([
                    'store_id' => $storeId, 'menu_type' => 'sidebar', 'menu_key' => 'inventory_manage', 'is_visible' => 1,
                ]);
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

    // Bump this whenever ensureSchema() changes, to force the one-time introspection to re-run.
    private const SCHEMA_VERSION = 3;

    private function ensureSchema(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        // The DDL introspection below is expensive against the remote DB and runs on hot paths
        // (billing, printing). Run it once, then cache — so normal sales skip dozens of
        // information_schema queries. Cleared by bumping SCHEMA_VERSION on any change here.
        $schemaFlag = 'pos_retail_schema_v' . self::SCHEMA_VERSION;
        try {
            if (\Illuminate\Support\Facades\Cache::get($schemaFlag)) {
                return;
            }
        } catch (\Throwable $e) {
            // cache unavailable → fall through and just run the checks
        }

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
        // Print the "Saved Rs. X/- On MRP" line. Defaults to 1 so every store that already prints
        // it keeps doing so — a NULL here means "never chose", not "turn it off".
        if (Schema::hasTable('stores') && !Schema::hasColumn('stores', 'pos_show_mrp_saving')) {
            DB::statement("ALTER TABLE `stores` ADD COLUMN `pos_show_mrp_saving` TINYINT(1) NOT NULL DEFAULT 1");
        }
        // Tag each bill with the branch + counter it was billed at.
        if (Schema::hasTable('manual_invoices')) {
            if (!Schema::hasColumn('manual_invoices', 'pos_branch_id')) {
                DB::statement("ALTER TABLE `manual_invoices` ADD COLUMN `pos_branch_id` BIGINT NULL");
            }
            if (!Schema::hasColumn('manual_invoices', 'pos_terminal_id')) {
                DB::statement("ALTER TABLE `manual_invoices` ADD COLUMN `pos_terminal_id` BIGINT NULL");
            }
            // The staff who actually billed (captured at sale time). The counter's staff
            // changes per shift, so staff-wise sales must be attributed on the bill itself.
            if (!Schema::hasColumn('manual_invoices', 'pos_staff_id')) {
                DB::statement("ALTER TABLE `manual_invoices` ADD COLUMN `pos_staff_id` BIGINT NULL");
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
            // When on, the counter's active staff is auto-picked from its roster by shift time.
            if (!Schema::hasColumn('pos_terminals', 'auto_shift')) {
                DB::statement("ALTER TABLE `pos_terminals` ADD COLUMN `auto_shift` TINYINT(1) NOT NULL DEFAULT 0");
            }
        }
        // Roster of staff who man a counter across shifts (used when auto_shift is on).
        if (!Schema::hasTable('pos_counter_staff')) {
            DB::statement("CREATE TABLE `pos_counter_staff` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NULL,
                `terminal_id` BIGINT NULL,
                `staff_id` BIGINT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `pos_counter_staff_terminal_idx` (`terminal_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // Cash-flow request ledger: shift-to-shift cash accountability per counter / branch.
        if (!Schema::hasTable('pos_cash_handovers')) {
            DB::statement("CREATE TABLE `pos_cash_handovers` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NULL,
                `terminal_id` BIGINT NULL,
                `branch_id` BIGINT NULL,
                `request_no` VARCHAR(20) NULL,
                `type` VARCHAR(20) NOT NULL DEFAULT 'handover',
                `purpose` VARCHAR(30) NULL,
                `purpose_other` VARCHAR(120) NULL,
                `payment_mode` VARCHAR(20) NOT NULL DEFAULT 'cash',
                `from_role` VARCHAR(10) NULL,
                `from_id` BIGINT NULL,
                `to_role` VARCHAR(10) NULL,
                `to_id` BIGINT NULL,
                `requested_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `cash_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `upi_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `denominations` TEXT NULL,
                `expected_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `counted_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `variance` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `status` VARCHAR(12) NOT NULL DEFAULT 'pending',
                `note` VARCHAR(500) NULL,
                `attachment` VARCHAR(255) NULL,
                `approved_by` BIGINT NULL,
                `approved_by_role` VARCHAR(10) NULL,
                `approved_at` TIMESTAMP NULL,
                `from_label` VARCHAR(40) NULL,
                `to_label` VARCHAR(40) NULL,
                `raised_at` TIMESTAMP NULL,
                `responded_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `pos_cash_handovers_term_idx` (`terminal_id`),
                KEY `pos_cash_handovers_status_idx` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            foreach ([
                'branch_id' => "BIGINT NULL", 'request_no' => "VARCHAR(20) NULL",
                'purpose' => "VARCHAR(30) NULL", 'purpose_other' => "VARCHAR(120) NULL",
                'payment_mode' => "VARCHAR(20) NOT NULL DEFAULT 'cash'",
                'requested_amount' => "DECIMAL(12,2) NOT NULL DEFAULT 0",
                'cash_amount' => "DECIMAL(12,2) NOT NULL DEFAULT 0",
                'upi_amount' => "DECIMAL(12,2) NOT NULL DEFAULT 0",
                'coupon_amount' => "DECIMAL(12,2) NOT NULL DEFAULT 0",
                'denominations' => "TEXT NULL", 'attachment' => "VARCHAR(255) NULL",
                'approved_by' => "BIGINT NULL", 'approved_by_role' => "VARCHAR(10) NULL",
                'approved_at' => "TIMESTAMP NULL",
                'from_label' => "VARCHAR(40) NULL", 'to_label' => "VARCHAR(40) NULL",
            ] as $col => $def) {
                if (!Schema::hasColumn('pos_cash_handovers', $col)) {
                    DB::statement("ALTER TABLE `pos_cash_handovers` ADD COLUMN `$col` $def");
                }
            }
            if (Schema::hasColumn('pos_cash_handovers', 'note')) {
                try { DB::statement("ALTER TABLE `pos_cash_handovers` MODIFY `note` VARCHAR(500) NULL"); } catch (\Throwable $e) {}
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
        if (Schema::hasTable('pos_stock_gatepass_items') && !Schema::hasColumn('pos_stock_gatepass_items', 'variation_type')) {
            try {
                DB::statement("ALTER TABLE `pos_stock_gatepass_items` ADD `variation_type` VARCHAR(100) NULL AFTER `inventory_item_id`");
            } catch (\Throwable $e) {}
        }
        // Where the stock came from. NULL is the main store, which is what every gatepass written
        // before branch-to-branch transfers existed was, so the column reads correctly on old rows
        // without a backfill.
        if (Schema::hasTable('pos_stock_gatepass') && !Schema::hasColumn('pos_stock_gatepass', 'from_branch_id')) {
            try {
                DB::statement("ALTER TABLE `pos_stock_gatepass` ADD `from_branch_id` BIGINT NULL AFTER `store_id`");
                DB::statement("ALTER TABLE `pos_stock_gatepass` ADD INDEX `psg_from_branch_idx` (`from_branch_id`)");
            } catch (\Throwable $e) {}
        }

        // Stock write-off: damaged / theft removed from main store or a branch (audited, reversible).
        if (!Schema::hasTable('pos_stock_writeoff')) {
            DB::statement("CREATE TABLE `pos_stock_writeoff` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL,
                `branch_id` BIGINT NULL,
                `inventory_item_id` BIGINT NULL,
                `type` VARCHAR(20) NOT NULL,
                `qty` DECIMAL(12,3) NOT NULL DEFAULT 0,
                `note` VARCHAR(255) NULL,
                `created_by` BIGINT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `psw_store_idx` (`store_id`),
                KEY `psw_item_idx` (`inventory_item_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        // Damaged/Theft approval workflow columns.
        if (Schema::hasTable('pos_stock_writeoff')) {
            foreach ([
                'status'       => "VARCHAR(20) NOT NULL DEFAULT 'pending'",
                'manager_note' => "VARCHAR(500) NULL",
                'decided_by'   => "BIGINT NULL",
                'decided_by_role' => "VARCHAR(20) NULL",
                'decided_at'   => "TIMESTAMP NULL",
            ] as $col => $def) {
                if (!Schema::hasColumn('pos_stock_writeoff', $col)) {
                    DB::statement("ALTER TABLE `pos_stock_writeoff` ADD COLUMN `$col` $def");
                }
            }
        }
        // Disposition rows (split: return to supplier / resell / scrap) for an accepted write-off.
        if (!Schema::hasTable('pos_writeoff_dispositions')) {
            DB::statement("CREATE TABLE `pos_writeoff_dispositions` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `writeoff_id` BIGINT NULL,
                `disposition` VARCHAR(20) NOT NULL,
                `qty` DECIMAL(12,3) NOT NULL DEFAULT 0,
                `damage_category` VARCHAR(120) NULL,
                `reason` VARCHAR(500) NULL,
                `attachment` VARCHAR(255) NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `pwd_writeoff_idx` (`writeoff_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        // Branch manager (a staff) who approves write-offs raised at that branch.
        if (Schema::hasTable('branches') && !Schema::hasColumn('branches', 'branch_manager_id')) {
            DB::statement("ALTER TABLE `branches` ADD COLUMN `branch_manager_id` BIGINT NULL");
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

        try { \Illuminate\Support\Facades\Cache::forever($schemaFlag, 1); } catch (\Throwable $e) {}
    }

    // 1 loyalty point earned per ₹100 of bill value; wallet redeems 1:1 (₹).
    private const LOYALTY_EARN_PER = 100;
    // Max discount % a cashier may apply without manager approval (§4.1).
    private const DISCOUNT_CAP = 5;

    // A line above this is a keying slip, not a sale — 999999999999 typed into the grams box
    // comes through as 999999999.999 kg. Hard cap, deliberately not clearable by the OOS
    // override: the totals it produces overflow the money columns (a tender past
    // pos_payment_legs.amount DECIMAL(12,2) is silently truncated by MySQL, so the receipt
    // then prints a "Tendered" figure the customer never handed over) and the bill is unusable.
    // Mirrored client-side as MAX_LINE_QTY in the New Sale screen.
    private const MAX_LINE_QTY = 10000;

    // Ceiling of pos_payment_legs.amount DECIMAL(12,2) — reject rather than store a wrong number.
    private const MAX_PAYMENT_LEG = 9999999999.99;

    // ── Cash Flow: shift-to-shift cash handover with raise / accept ──────────────

    private function isCashManager(): bool
    {
        return auth('vendor')->check() || hasPermission('pos_cash', 'manage');
    }
    private function meRole(): string
    {
        return auth('vendor')->check() ? 'owner' : 'staff';
    }
    private function meId()
    {
        return auth('vendor')->id() ?? auth('vendor_employee')->id();
    }

    public const CASH_PURPOSES = [
        'opening_cash'    => 'Opening Cash',
        'shift_handover'  => 'Shift Handover',
        'change_request'  => 'Change Request',
        'cash_deposit'    => 'Cash Deposit',
        'expense_request' => 'Expense Request',
        'cash_collection' => 'Cash Collection',
        'other'           => 'Other',
    ];
    public const CASH_DENOMS = [500, 200, 100, 50, 20, 10];

    private function purposeType($purpose): string
    {
        return ['opening_cash' => 'open', 'shift_handover' => 'handover', 'cash_deposit' => 'close'][$purpose] ?? 'other';
    }

    // "owner:0" | "staff:5" → ['staff', 5]
    private function parsePerson($val): array
    {
        $p = explode(':', (string) $val);
        $role = in_array($p[0] ?? '', ['owner', 'staff', 'manager'], true) ? $p[0] : 'staff';
        return [$role, (int) ($p[1] ?? 0)];
    }

    // Current open cash session for a counter, derived from its last *received* open/handover.
    private function cashState($counterId): array
    {
        if (!$counterId) {
            return ['open' => false, 'holder_id' => null, 'opening' => 0.0, 'since' => null];
        }
        $last = DB::table('pos_cash_handovers')->where('terminal_id', $counterId)
            ->where('status', 'received')->whereIn('type', ['open', 'handover', 'close'])
            ->orderByDesc('responded_at')->orderByDesc('id')->first();
        if (!$last || $last->type === 'close') {
            return ['open' => false, 'holder_id' => null, 'opening' => 0.0, 'since' => null];
        }
        return ['open' => true, 'holder_id' => (int) $last->to_id, 'opening' => (float) $last->cash_amount, 'since' => $last->responded_at];
    }

    // Cash collected (cash leg of bills) by a holder on a counter since they took over.
    private function cashSalesSince($counterId, $holderId, $since): float
    {
        if (!$holderId || !$since) {
            return 0.0;
        }
        return (float) ManualInvoice::where('vendor_id', $this->storeId())
            ->where('pos_terminal_id', $counterId)->where('pos_staff_id', $holderId)
            ->where('pos_status', 'final')->where('created_at', '>=', $since)->sum('cash_amount');
    }

    // Build the common dataset (people, counters, name maps) the cash views need.
    private function cashViewData($storeId): array
    {
        $branches = Branch::where('store_id', $storeId)->orderBy('name')->get();
        $counters = DB::table('pos_terminals')->where('store_id', $storeId)->orderBy('branch_id')->orderBy('id')->get();
        $staff    = VendorEmployee::where('store_id', $storeId)->with('role')->orderBy('f_name')->get(['id', 'f_name', 'l_name', 'employee_role_id']);
        $staffNames = $staff->mapWithKeys(fn($s) => [(int) $s->id => trim($s->f_name . ' ' . $s->l_name)]);
        $counterNames = $counters->mapWithKeys(fn($c) => [(int) $c->id => $c->name]);
        $branchNames = $branches->mapWithKeys(fn($b) => [(int) $b->id => $b->name]);
        return compact('branches', 'counters', 'staff', 'staffNames', 'counterNames', 'branchNames');
    } 

    private function personLabel($role, $id, $staffNames): string
    {
        if ($role === 'owner' || $role === 'manager') {
            return $role === 'owner' ? 'Owner' : 'Owner / Manager';
        }
        return $staffNames[(int) $id] ?? 'Staff';
    }

    // Cash Flow list — requests + counter cash status.
    public function cashFlow(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $this->syncCounterShiftStaff($storeId);

        $d = $this->cashViewData($storeId);
        $meId = (int) $this->meId();
        $meRole = $this->meRole();
        $isManager = $this->isCashManager();

        // Per-counter live cash status.
        $rows = [];
        foreach ($d['counters'] as $c) {
            $state = $this->cashState($c->id);
            $cashSales = $state['open'] ? $this->cashSalesSince($c->id, $state['holder_id'], $state['since']) : 0.0;
            $rows[] = [
                'counter' => $c, 'branch' => $d['branchNames'][(int) $c->branch_id] ?? 'Main Store',
                'state' => $state, 'cash_sales' => $cashSales, 'expected' => $state['opening'] + $cashSales,
            ];
        }

        // Requests: the owner/manager sees every request for the store (all branches); a staff member
        // sees the full history — all statuses — of every request they raised OR were the recipient of.
        $q = DB::table('pos_cash_handovers')->where('store_id', $storeId)->whereIn('status', ['draft', 'pending', 'approved', 'received', 'rejected', 'closed']);
        if (!$isManager) {
            $q->where(function ($w) use ($meId, $meRole) {
                $w->where(function ($x) use ($meId, $meRole) { $x->where('from_id', $meId)->where('from_role', $meRole); })
                    ->orWhere(function ($x) use ($meId, $meRole) { $x->where('to_id', $meId)->where('to_role', $meRole); });
            });
        }
        $requests = $q->orderByDesc('id')->paginate(40)->withQueryString();

        return view('posretail::vendor.retail-pos.cash-flow', array_merge($d, [
            'rows' => $rows, 'requests' => $requests, 'isManager' => $isManager, 'meId' => $meId, 'meRole' => $meRole,
        ]));
    }

    // Show the detailed cash request form (create, or view an existing request).
    public function cashRequestForm(Request $request, $id = null)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $d = $this->cashViewData($storeId);

        $req = null;
        if ($id) {
            $req = DB::table('pos_cash_handovers')->where('store_id', $storeId)->where('id', $id)->first();
            if (!$req) {
                Toastr::error('Cash request not found');
                return redirect()->route('vendor.retail-pos.cash-flow');
            }
        }

        $meId = (int) $this->meId();
        $meRole = $this->meRole();
        $isManager = $this->isCashManager();
        $myName = $meRole === 'owner'
            ? (Helpers::get_store_data()->name ?? 'Owner')
            : ($d['staffNames'][$meId] ?? 'Me');

        // A staff (not owner/manager) raising a NEW request is locked to the branch & counter
        // of the till they man — show only those in the dropdowns.
        $branchLocked = false;
        if (!$isManager && !$req) {
            $myCounter = $this->currentCounter();
            if ($myCounter) {
                $branchLocked = true;
                $d['counters'] = collect([$myCounter]);
                $d['branches'] = $myCounter->branch_id
                    ? $d['branches']->where('id', $myCounter->branch_id)->values()
                    : collect();
            }
        }

        // Next request number preview for a new form.
        $nextId = ((int) DB::table('pos_cash_handovers')->max('id')) + 1;
        $nextNo = 'CF-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);

        return view('posretail::vendor.retail-pos.cash-request-form', array_merge($d, [
            'req' => $req, 'purposes' => self::CASH_PURPOSES, 'denoms' => self::CASH_DENOMS,
            'isManager' => $isManager, 'meId' => $meId, 'meRole' => $meRole, 'myName' => $myName, 'nextNo' => $nextNo,
            'branchLocked' => $branchLocked,
        ]));
    }

    // Create / save a cash request (draft or submit).
    public function cashRequestSave(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $meId = (int) $this->meId();
        $meRole = $this->meRole();

        $purpose = $request->input('purpose');
        if (!array_key_exists($purpose, self::CASH_PURPOSES)) {
            Toastr::error('Select a valid purpose');
            return back()->withInput();
        }
        [$toRole, $toId] = $this->parsePerson($request->input('requested_to'));
        if ($toRole === 'staff' && !$toId) {
            Toastr::error('Select who the request is to');
            return back()->withInput();
        }
 
        [$fromRole, $fromId] = $this->parsePerson($request->input('requested_by', $meRole . ':' . $meId));
        if (!$fromRole) {
            $fromRole = $meRole;
            $fromId = $meId;
        }

        // Denomination breakdown → cash total cross-check.
        $denoms = [];
        $denomCash = 0.0;
        foreach (self::CASH_DENOMS as $dv) {
            $qty = max(0, (int) $request->input("denom.$dv", 0));
            $denoms[(string) $dv] = $qty;
            $denomCash += $dv * $qty;
        }
        $coins = round((float) $request->input('coins', 0), 2);
        $denoms['coins'] = $coins;
        $denomCash += $coins;

        $mode = in_array($request->input('payment_mode'), ['cash', 'upi', 'bank_transfer', 'mixed', 'coupon'], true)
            ? $request->input('payment_mode') : 'cash';
        $cashAmount = round((float) $request->input('cash_amount', $denomCash), 2);
        $upiAmount  = round((float) $request->input('upi_amount', 0), 2);
        $couponAmount = round((float) $request->input('coupon_amount', 0), 2);
        $requested  = round((float) $request->input('requested_amount', $cashAmount + $upiAmount + $couponAmount), 2);

        $type = $this->purposeType($purpose);
        $counterId = (int) $request->input('terminal_id') ?: null;
        $branchId  = (int) $request->input('branch_id') ?: null;

        // Auto expected/variance for shift handover & close (from the holder's running cash).
        $expected = $requested;
        $variance = 0.0;
        if (in_array($type, ['handover', 'close'], true) && $counterId) {
            $state = $this->cashState($counterId);
            if ($state['open']) {
                $expected = round($state['opening'] + $this->cashSalesSince($counterId, $state['holder_id'], $state['since']), 2);
                $variance = round($cashAmount - $expected, 2);
            }
        }

        $attachment = null;
        if ($request->hasFile('attachment')) {
            $f = $request->file('attachment');
            $attachment = Helpers::upload('cash-flow/', $f->getClientOriginalExtension(), $f);
        }

        $status = $request->input('save_mode') === 'draft' ? 'draft' : 'pending';

        $fields = [
            'terminal_id' => $counterId, 'branch_id' => $branchId,
            'type' => $type, 'purpose' => $purpose, 'purpose_other' => $request->input('purpose_other'),
            'payment_mode' => $mode,
            'to_role' => $toRole, 'to_id' => $toId ?: null, 'to_label' => $request->input('to_label'),
            'from_label' => $request->input('from_label'),
            'requested_amount' => $requested, 'cash_amount' => $cashAmount, 'upi_amount' => $upiAmount, 'coupon_amount' => $couponAmount,
            'denominations' => json_encode($denoms), 'expected_amount' => $expected, 'counted_amount' => $cashAmount,
            'variance' => $variance, 'status' => $status, 'note' => $request->input('note'),
            'updated_at' => now(),
        ];
        if ($attachment) {
            $fields['attachment'] = $attachment;
        }

        // Editing an existing draft (only the requester, only while still a draft).
        $editId = (int) $request->input('id');
        if ($editId) {
            $existing = DB::table('pos_cash_handovers')->where('store_id', $storeId)->where('id', $editId)->first();
            if (!$existing || $existing->status !== 'draft' || (int) $existing->from_id !== $meId || $existing->from_role !== $meRole) {
                Toastr::error('This request can no longer be edited');
                return back();
            }
            if ($status === 'pending') {
                $fields['raised_at'] = now();
            }
            DB::table('pos_cash_handovers')->where('id', $editId)->update($fields);
            $this->logAudit('cash_request_' . $status, $existing->request_no, '₹' . number_format($requested, 2));
            Toastr::success($status === 'draft' ? 'Draft updated' : 'Cash request submitted');
            return redirect()->route('vendor.retail-pos.cash-flow.show', $editId);
        }
 
        $id = DB::table('pos_cash_handovers')->insertGetId(array_merge($fields, [
            'store_id' => $storeId, 'from_role' => $fromRole, 'from_id' => $fromId ?: $meId,
            'attachment' => $attachment, 'raised_at' => now(), 'created_at' => now(),
        ])); 
        DB::table('pos_cash_handovers')->where('id', $id)->update(['request_no' => 'CF-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT)]);

        $this->logAudit('cash_request_' . $status, 'CF-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT), '₹' . number_format($requested, 2));
        Toastr::success($status === 'draft' ? 'Cash request saved as draft' : 'Cash request submitted');
        return redirect()->route('vendor.retail-pos.cash-flow.show', $id);
    }

    // Printable handover slip — denominations, both parties, expected vs counted, variance.
    public function cashSlip(Request $request, $id)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $req = DB::table('pos_cash_handovers')->where('store_id', $storeId)->where('id', $id)->first();
        if (!$req) {
            Toastr::error('Cash request not found');
            return redirect()->route('vendor.retail-pos.cash-flow');
        }
        $d = $this->cashViewData($storeId);
        $store = Helpers::get_store_data();
        $den = $req->denominations ? (json_decode($req->denominations, true) ?: []) : [];

        $fromName = $req->from_role === 'owner' ? 'Owner' : ($d['staffNames'][(int) $req->from_id] ?? 'Staff');
        $toName = $req->to_role === 'manager' ? 'Owner / Manager' : ($req->to_id ? ($d['staffNames'][(int) $req->to_id] ?? 'Staff') : 'Owner / Manager');
        $approvedName = $req->approved_by ? ($req->approved_by_role === 'owner' ? 'Owner' : ($d['staffNames'][(int) $req->approved_by] ?? 'Manager')) : null;
        $counterName = $req->terminal_id ? ($d['counterNames'][(int) $req->terminal_id] ?? null) : null;
        $branchName = $req->branch_id ? ($d['branchNames'][(int) $req->branch_id] ?? null) : 'Main Store';

        return view('posretail::vendor.retail-pos.cash-slip', compact('req', 'store', 'den', 'fromName', 'toName', 'approvedName', 'counterName', 'branchName'));
    }

    // Approve / Receive Cash / Reject / Close a request.
    public function cashRequestAction(Request $request, $id)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $action = $request->input('action'); // submit | approve | receive | reject | close
        $row = DB::table('pos_cash_handovers')->where('store_id', $storeId)->where('id', $id)->first();
        if (!$row) {
            Toastr::error('Cash request not found');
            return back();
        }

        $meId = (int) $this->meId();
        $meRole = $this->meRole();
        $isManager = $this->isCashManager();
        $isRecipient = ((int) $row->to_id === $meId && $row->to_role === $meRole)
            || ($row->to_role === 'manager' && $isManager);
        $isRequester = ((int) $row->from_id === $meId && $row->from_role === $meRole);

        $upd = ['updated_at' => now()];
        if ($action === 'submit' && $row->status === 'draft' && $isRequester) {
            $upd['status'] = 'pending';
            $upd['raised_at'] = now();
        } elseif ($action === 'approve' && in_array($row->status, ['pending'], true) && ($isManager || $isRecipient)) {
            $upd['status'] = 'approved';
            $upd['approved_by'] = $meId;
            $upd['approved_by_role'] = $meRole;
            $upd['approved_at'] = now();
        } elseif ($action === 'receive' && in_array($row->status, ['pending', 'approved'], true) && $isRecipient) {
            // Physical cash confirmed by the recipient — this is the accountability transfer.
            $upd['status'] = 'received';
            $upd['responded_at'] = now();
            if (!$row->approved_at) {
                $upd['approved_by'] = $meId;
                $upd['approved_by_role'] = $meRole;
                $upd['approved_at'] = now();
            }
        } elseif ($action === 'reject' && in_array($row->status, ['pending', 'approved'], true) && ($isRecipient || $isManager)) {
            $upd['status'] = 'rejected';
            $upd['responded_at'] = now();
        } elseif ($action === 'close' && in_array($row->status, ['received', 'approved'], true) && $isManager) {
            $upd['status'] = 'closed';
        } else {
            Toastr::error('This action is not allowed in the current state');
            return back();
        }

        DB::table('pos_cash_handovers')->where('id', $id)->update($upd);
        $this->logAudit('cash_' . $action, $row->request_no, '₹' . number_format((float) $row->cash_amount, 2));
        Toastr::success('Cash request ' . $upd['status']);
        return back();
    }

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

        $quickItems = InventoryItem::with('itemunit')->where('store_id', $storeId)->where('item_type', 'product')
            ->when(!empty($topIds), fn($q) => $q->whereIn('id', $topIds)
                ->orderByRaw('FIELD(id,' . implode(',', $topIds) . ') ')
            , fn($q) => $q->orderByDesc('updated_at'))
            ->limit(24)->get();

        $store = Helpers::get_store_data();
        $upiId = $store->pos_upi_id ?? null;
        $storeName = $store->name ?? 'Store';
        // Read the chosen template straight from the row (avoids any cached store object).
        $uiTemplate = DB::table('stores')->where('id', $storeId)->value('pos_ui_template');
        $uiTemplate = in_array($uiTemplate, ['classic', 'compact', 'modern', 'search'], true) ? $uiTemplate : 'classic';
        $heldBills = $this->heldBillsData($storeId);

        // Branch context: staff are locked to their counter's branch; owner picks one
        // (defaulting to the last branch they billed from, else the first branch).
        $branches    = Branch::where('store_id', $storeId)->orderBy('name')->get();
        $counter     = $this->currentCounter();
        // Staff sell from their allotted branch only — the counter's if they are signed in at
        // one, else the branch on their staff record. The picker is hidden for them either way
        // (branchLocked), so a cashier cannot bill against another branch's stock. Matches
        // billingBranchId(), which enforces the same thing server-side at checkout.
        $employeeId  = auth('vendor_employee')->id();
        $staffBranch = $employeeId ? VendorEmployee::where('id', $employeeId)->value('branch_id') : null;
        $myBranchId  = $counter->branch_id ?? ($staffBranch ? (int) $staffBranch : null);
        $branchLocked = (bool) ($employeeId || ($counter && $counter->branch_id));
        // Staff with no branch have no stock to sell from — say so on the screen rather than
        // letting them build a cart and be refused at checkout. finalize() blocks it either way.
        $noBranchAllotted = (bool) ($employeeId && !$myBranchId);
        $savedBranch = DB::table('stores')->where('id', $storeId)->value('pos_default_branch_id');
        // Default to the staff counter's branch, else the owner's last-used branch; otherwise
        // fall back to Main Store (no branch) rather than forcing the first branch.
        $defaultBranchId = $myBranchId ?: ($savedBranch ?: null);

        // "On shift now" indicator for the cashier: shows whether the logged-in staff is the
        // active staff on a counter right now (and the shift window), so they can confirm.
        $shiftStatus = null;
        $empId = auth('vendor_employee')->id();
        if ($empId) {
            $emp = VendorEmployee::with('storeShift:id,name,start_time,end_time')->find($empId);
            $shiftTxt = null;
            if ($emp && $emp->storeShift) {
                $t = fn($v) => $v ? substr($v, 0, 5) : '';
                $shiftTxt = trim(($emp->storeShift->name ? $emp->storeShift->name . ' ' : '')
                    . $t($emp->storeShift->start_time) . '–' . $t($emp->storeShift->end_time));
            }
            $shiftStatus = [
                'on'      => (bool) $counter,
                'staff'   => $emp ? trim($emp->f_name . ' ' . $emp->l_name) : null,
                'counter' => $counter->name ?? null,
                'shift'   => $shiftTxt,
                'auto'    => (bool) ($counter->auto_shift ?? false),
            ];
        }

        $gstStore = Helpers::get_store_data();
        $gstOn = empty($gstStore->gst) || (bool) (json_decode($gstStore->gst)->status ?? 0);

        return view('posretail::vendor.retail-pos.index', compact(
            'categories', 'quickItems', 'upiId', 'storeName', 'heldBills', 'uiTemplate',
            'branches', 'myBranchId', 'branchLocked', 'defaultBranchId', 'shiftStatus', 'gstOn',
            'noBranchAllotted'
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
        $branch = $this->posBranchFilter($request);
        $branches = Branch::where('store_id', $storeId)->orderBy('name')->get();

        $base = ManualInvoice::where('vendor_id', $storeId)->where('type', 'manual')
            ->whereNotNull('pos_status')
            ->where($this->posBranchScope($branch))
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

        $hasVarCol = Schema::hasColumn('invoice_items', 'variation_type');
        $topItems = DB::table('invoice_items as ii')
            ->join('manual_invoices as mi', 'mi.id', '=', 'ii.manual_invoice_id')
            ->where('mi.vendor_id', $storeId)->where('mi.pos_status', 'final')
            ->where($this->posBranchScope($branch, 'mi.pos_branch_id'))
            ->whereBetween(DB::raw('DATE(mi.created_at)'), [$from, $to])
            ->selectRaw('ii.name, MAX(ii.inv_id) as inv_id, SUM(ii.qty) as qty, SUM(ii.price*ii.qty) as amount'
                . ($hasVarCol ? ', MAX(ii.variation_type) as variation_type' : ''))
            ->groupBy('ii.name')->orderByDesc('amount')->limit(8)->get();
        $topItems = $this->decorateTopItemUnits($topItems);

        // Last 14 days trend (for the line chart) — independent of the selected range.
        $trend = ManualInvoice::where('vendor_id', $storeId)->where('type', 'manual')
            ->where('pos_status', 'final')
            ->where($this->posBranchScope($branch))
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as d, SUM(total_amount) as total')
            ->groupBy('d')->orderBy('d')->pluck('total', 'd');

        // Payment-mode breakdown (from the recorded legs).
        $payModes = DB::table('pos_payment_legs as pl')
            ->join('manual_invoices as mi', 'mi.id', '=', 'pl.manual_invoice_id')
            ->where('mi.vendor_id', $storeId)->where('mi.pos_status', 'final')
            ->where($this->posBranchScope($branch, 'mi.pos_branch_id'))
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

    /**
     * Full top-selling list behind the dashboard's "Top items" card — same filters, no limit.
     * `export=excel` streams the very rows on screen, so the sheet always matches what was seen.
     */
    public function topItems(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
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
        // Presets hand back Carbon instances — flattened to plain dates so they survive a round
        // trip through the filter links and cannot put a colon in the download filename.
        $from = \Carbon\Carbon::parse($from)->toDateString();
        $to   = \Carbon\Carbon::parse($to)->toDateString();
        $branch = $this->posBranchFilter($request);
        $branches = Branch::where('store_id', $storeId)->orderBy('name')->get();
        // Sales by default: qty cannot rank rows against each other once they are counted in
        // different units — 64 bananas over 14.1 kg of onion is not a comparison.
        $sort = $request->get('sort') === 'qty' ? 'qty' : 'amount';

        $hasVarCol = Schema::hasColumn('invoice_items', 'variation_type');
        $rows = DB::table('invoice_items as ii')
            ->join('manual_invoices as mi', 'mi.id', '=', 'ii.manual_invoice_id')
            ->where('mi.vendor_id', $storeId)->where('mi.type', 'manual')->where('mi.pos_status', 'final')
            ->where($this->posBranchScope($branch, 'mi.pos_branch_id'))
            ->whereBetween(DB::raw('DATE(mi.created_at)'), [$from, $to])
            ->selectRaw('ii.name, MAX(ii.inv_id) as inv_id, SUM(ii.qty) as qty,
                COUNT(DISTINCT ii.manual_invoice_id) as bills, SUM(ii.price*ii.qty) as amount'
                . ($hasVarCol ? ', MAX(ii.variation_type) as variation_type' : ''))
            ->groupBy('ii.name')->orderByDesc($sort)->get();

        $rows = $this->decorateTopItemUnits($rows);

        // Deliberately no combined qty total — kg, pieces and packs share no scale.
        $totalAmount = (float) $rows->sum('amount');

        if ($request->get('export') === 'excel') {
            $data = [];
            foreach ($rows as $key => $r) {
                $data[] = [
                    $key + 1,
                    $r->name,
                    $r->sku,
                    (float) $r->qty,
                    $r->unit_label . ($r->pack_note ? ' (' . $r->pack_note . ')' : ''),
                    (int) $r->bills,
                    round((float) $r->amount, 2),
                    $totalAmount > 0 ? round($r->amount / $totalAmount * 100, 2) : 0,
                ];
            }
            $headings = ['#', 'Item', 'SKU', 'Qty Sold', 'Unit', 'Bills', 'Sales', 'Share %'];
            $file = 'top-selling-items_' . $from . '_' . $to . '.xlsx';
            return Excel::download(new PosTopItemsExport($data, $headings), $file);
        }

        return view('posretail::vendor.retail-pos.top-items', compact(
            'rows', 'totalAmount', 'from', 'to',
            'branches', 'branch', 'preset', 'custom', 'sort'
        ));
    }

    /**
     * Unit each top-selling row is counted in, plus its SKU.
     *
     * A plain or loose line is counted in the item's own unit — the POS keeps loose weights in
     * stock units, its g/kg toggle being display-only (`entryToQty`). A measured-pack variation
     * line counts PACKS, so borrowing the item's unit there would read "3 kg" where three 100gm
     * packs were sold; those rows are labelled by the pack instead. Rows never mix the two:
     * `invoice_items.name` carries the variation in brackets, so each group is one sold form.
     */
    private function decorateTopItemUnits($rows)
    {
        $items = InventoryItem::whereIn('id', $rows->pluck('inv_id')->filter()->unique())->get()->keyBy('id');

        foreach ($rows as $row) {
            $item = $items[$row->inv_id] ?? null;
            $row->sku = $item->sku_id ?? '';
            $row->unit_label = $item ? _unitLabelFor($item->unit) : '';
            $row->pack_note = null;

            $varType = $row->variation_type ?? null;
            if ($item && $varType && ($var = _variationRow($item, $varType)) && _variationMode($item) === 'measured') {
                $pack = _variationPack($item, $var);
                if ($pack) {
                    $row->unit_label = 'pack';
                    $row->pack_note = rtrim(rtrim(number_format((float) $pack['qty'], 3), '0'), '.') . ' ' . $pack['code'] . ' each';
                }
            }
        }

        return $rows;
    }

    // Live product search / barcode-SKU scan. Returns JSON for the billing screen.
    public function products(Request $request)
    {
        $storeId = $this->storeId();
        $term = trim((string) $request->get('q', ''));
        $exact = $request->boolean('exact'); // scanner sends exact barcode/SKU
        $category = $request->get('category'); // browse a category's items (tab click)

        $query = InventoryItem::with('itemunit')->where('store_id', $storeId)->where('item_type', 'product');

        // Variation SKUs live in inv_item_variation_details (not on the item row), so a scan of a
        // variation barcode/SKU must be matched there and mapped back to its parent item.
        $matchedVarByItem = []; // item_id => variation type (the specific variation that was scanned)
        if ($term !== '') {
            $varRows = DB::table('inv_item_variation_details as v')
                ->join('inventory_items as ii', 'ii.id', '=', 'v.item_id')
                ->where('ii.store_id', $storeId)
                ->where('v.sku', $term)
                ->get(['v.item_id', 'v.type']);
            foreach ($varRows as $vr) {
                $matchedVarByItem[$vr->item_id] = $vr->type;
            }
        }

        if ($exact && $term !== '') {
            $query->where(function ($q) use ($term, $matchedVarByItem) {
                $q->where('barcode', $term)->orWhere('sku_id', $term);
                if (!empty($matchedVarByItem)) {
                    $q->orWhereIn('id', array_keys($matchedVarByItem));
                }
            });
        } elseif ($term !== '') {
            $query->where(function ($q) use ($term, $matchedVarByItem) {
                $q->where('item_name', 'like', "%{$term}%")
                    ->orWhere('sku_id', 'like', "%{$term}%")
                    ->orWhere('barcode', $term);
                if (!empty($matchedVarByItem)) {
                    $q->orWhereIn('id', array_keys($matchedVarByItem));
                }
            });
        }
        if ($category && $category !== 'all') {
            $query->where('category_id', $category);
        }

        $limit = $category === 'all' ? 200 : ($category ? 60 : 30);
        $rows = $query->orderBy('item_name')->limit($limit)->get();

        // Stock shown for the branch this sale will actually bill against: the staff counter's
        // branch, else the staff member's own allotted branch, else the owner-selected one.
        // Staff never fall through to the query parameter — the figures on screen have to be the
        // stock billingBranchId() will draw the sale down from, or the cashier is shown one
        // branch's availability and sells out of another's.
        $branchId = $this->currentCounter()->branch_id ?? null;
        if (!$branchId && ($employeeId = auth('vendor_employee')->id())) {
            $branchId = (int) VendorEmployee::where('id', $employeeId)->value('branch_id') ?: null;
        } elseif (!$branchId) {
            $branchId = (int) $request->get('branch') ?: null;
        }
        $branchStock = $branchId
            ? DB::table('pos_branch_stock')->where('branch_id', $branchId)
                ->whereIn('inventory_item_id', $rows->pluck('id'))->pluck('stock', 'inventory_item_id')
            : collect();  

        $items = $rows->map(function ($i) use ($branchId, $branchStock, $matchedVarByItem) {
            $stock = $branchId ? (float) ($branchStock[$i->id] ?? 0) : (float) $i->stock;
            return [
                'id'         => (string) $i->id,
                'name'       => $i->item_name,
                'sku'        => $i->sku_id,
                // Set when the search term matched a variation's SKU — the front-end adds
                // that specific variation straight to the cart (skips the pick-variation modal).
                'matched_variation' => $matchedVarByItem[$i->id] ?? null,
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
                'stock_type' => _stockTypeOf($i),
                'variations' => $this->decorateVariations($i),
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
        // Null branch = sell directly from main-store stock (no branch). Staff remain
        // locked to their counter's branch via billingBranchId().
        $billingBranchId = $this->billingBranchId($request);

        // A staff member with no branch on record has no stock to sell from — billing them
        // through would draw the sale down from main-store stock, which is not theirs to move.
        // Blocked here rather than in the UI alone, so it holds for a replayed request too.
        if (!$billingBranchId && auth('vendor_employee')->id()) {
            return response()->json([
                'status' => false,
                'msg'    => 'You have not been allotted a branch, so you cannot bill yet. Ask the store owner to assign you to a branch or a counter.',
            ], 422);
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

        // Store-level GST toggle — when off, no GST is charged or shown on the bill/receipt.
        $gstOn = empty($store->gst) || (bool) (json_decode($store->gst)->status ?? 0);

        foreach ($cart as $row) {
            $idParts = explode('-var-', $row['id'] ?? '');
            $itemId = $idParts[0];
            $varType = $idParts[1] ?? null;

            $item = InventoryItem::where('id', $itemId)->where('store_id', $storeId)->first();
            if (!$item) {
                continue;
            }

            // Once a product has variations the parent is not sellable on its own — the line has
            // to say which one, or the stock has no single place to come from.
            if ($varErr = _variationSelectionError($item, $varType)) {
                return response()->json(['status' => false, 'msg' => $varErr], 422);
            }

            $qty = max(0, (float) ($row['qty'] ?? 1));

            // Absurd quantity — a slipped key in the weight box, never a real sale. Blocked here
            // as well as in the UI so a replayed or hand-built request can't get through either.
            if ($qty > self::MAX_LINE_QTY) {
                $displayName = $varType ? "{$item->item_name} ({$varType})" : $item->item_name;
                $qtyTxt = rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.');
                return response()->json([
                    'status' => false,
                    'msg'    => "Quantity on \"{$displayName}\" looks wrong ({$qtyTxt}). Maximum " . self::MAX_LINE_QTY . " per line — check the weight box.",
                ], 422);
            }

            $basePrice = (float) ($item->selling_price ?? 0);
            $avail = $billingBranchId ? $this->branchItemStock($billingBranchId, $item->id) : (float) $item->stock;

            if ($varType && ($var = _variationRow($item, $varType))) {
                $basePrice = (float) ($var['price'] ?? $item->selling_price ?? 0);

                if (_variationMode($item) === 'measured') {
                    // The variation holds no stock of its own — how many 100gm packs are available
                    // is the pool divided by one pack, not a counter on the variation.
                    $pack = _variationPack($item, $var);
                    $perPack = $pack ? _variationQtyInItemUnit($item, $pack, 1) : 0;
                    $avail = $perPack > 0 ? floor($avail / $perPack) : $avail;
                } else {
                    $varStock = (float) ($var['stock'] ?? 0);
                    $avail = $billingBranchId ? min($varStock, $avail) : $varStock;
                }
            }

            $price = (float) ($row['price'] ?? $basePrice);
            $lineDiscount = (float) ($row['discount'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            // Price override (§4.1) — only Owner/Manager may change the unit price.
            if (round($price, 2) !== round($basePrice, 2)) {
                if (!$canOverride) {
                    $displayName = $varType ? "{$item->item_name} ({$varType})" : $item->item_name;
                    return response()->json(['status' => false, 'msg' => "Price override on \"{$displayName}\" needs manager approval"], 422);
                }
                $displayName = $varType ? "{$item->item_name} ({$varType})" : $item->item_name;
                $auditNotes[] = "Price {$displayName}: ₹{$basePrice}→₹{$price}";
            }

            // Item discount cap for cashiers (§4.1). Above the cap needs manager override.
            if ($lineDiscount > 0) {
                $discPct = ($price * $qty) > 0 ? ($lineDiscount / ($price * $qty)) * 100 : 0;
                if ($discPct > self::DISCOUNT_CAP && !$canOverride) {
                    $displayName = $varType ? "{$item->item_name} ({$varType})" : $item->item_name;
                    return response()->json(['status' => false, 'msg' => "Discount above " . self::DISCOUNT_CAP . "% on \"{$displayName}\" needs manager approval"], 422);
                }
            }

            // Out-of-stock (§4.1) — checked against branch stock when billing at a branch.
            if ($qty > $avail) {
                if (!$allowOos) {
                    $displayName = $varType ? "{$item->item_name} ({$varType})" : $item->item_name;
                    return response()->json(['status' => false, 'msg' => "Insufficient stock for \"{$displayName}\" (have " . $avail . "). Manager approval required.", 'oos' => true], 422);
                }
                $displayName = $varType ? "{$item->item_name} ({$varType})" : $item->item_name;
                $auditNotes[] = "OOS {$displayName}: sold {$qty}, stock {$avail}";
            }

            $gross = ($price * $qty) - $lineDiscount;
            $gross = max(0, $gross);
            $rate = $gstOn ? (float) ($item->gst_rate ?? 0) : 0;
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
            $piecesIn = (int) ($row['pieces'] ?? 0);
            $pieces = ((int) ($item->sell_loose ?? 0) === 1 && $piecesIn > 0)
                ? min($piecesIn, self::MAX_LINE_QTY) : null;

            $lines[] = [
                'item'       => $item,
                'qty'        => $qty,
                'pieces'     => $pieces,
                'price'      => $price,
                // Carried through to the invoice line. It comes off the bill total above, so
                // dropping it here left the line holding a price the customer never paid.
                'discount'   => $lineDiscount,
                'rate'       => $rate,
                'gst_status' => $status,
                'hsn'        => $item->hsn,
                'var_type'   => $varType,
            ];
        }

        if (empty($lines)) {
            return response()->json(['status' => false, 'msg' => 'No valid items'], 422);
        }

        $customer = $customerId ? StoreCustomer::find($customerId) : null;

        // Item offers (Buy X Get Y / discounts / bundles) — free reward lines are added
        // at ₹0 and any offer discount is applied at bill level, alongside the manual one.
        $selectedOfferIds = json_decode($request->input('offer_ids', '[]'), true) ?: [];
        $offerEngine = app(\App\Modules\PosRetail\Services\PosOfferEngine::class);
        $offerResult = $offerEngine->apply($lines, [
            'store_id'  => $storeId,
            'branch_id' => $billingBranchId,
            'customer'  => $customer,
            'subtotal'  => $subtotal,
        ], $selectedOfferIds);
        $offerDiscount = (float) $offerResult['discount'];
        foreach ($offerResult['free_lines'] as $freeLine) {
            $lines[] = $freeLine;
        }

        // Coupon — validated server-side, discount adjusted off the bill total.
        $couponCode = trim((string) $request->input('coupon_code', ''));
        $couponDiscount = 0.0;
        $couponModel = null;
        $serviceCoupon = null;
        if ($couponCode !== '') {
            if (\Illuminate\Support\Facades\Schema::hasTable('coupons')) {
                $couponModel = \App\Models\Coupon::where('store_id', $storeId)
                    ->whereRaw('LOWER(code) = ?', [strtolower($couponCode)])->first();
                if ($couponModel) {
                    $couponDiscount = (float) ($this->posCouponDiscount($couponModel, $subtotal, $customer) ?? 0);
                }
            }
            if ($couponDiscount <= 0 && \Illuminate\Support\Facades\Schema::hasTable('service_coupons')) {
                $couponModel = null;
                $serviceCoupon = \App\Models\ServiceCoupon::where('user_type', 'store')->where('user_type_id', $storeId)
                    ->whereRaw('LOWER(code) = ?', [strtolower($couponCode)])->first();
                if ($serviceCoupon) {
                    $couponDiscount = (float) ($this->serviceCouponDiscount($serviceCoupon, $subtotal) ?? 0);
                }
            }
        }

        $grandTotal = max(0, $subtotal + $taxTotal - $billDiscount - $offerDiscount - $couponDiscount);
        $roundOff = round($grandTotal) - $grandTotal;
        $grandTotal = round($grandTotal);

        $taxType = $hasGst ? 'gst' : 'non-gst';

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
            // Past the column's ceiling MySQL stores the maximum instead of failing, so the
            // receipt would print a tendered amount nobody paid. Refuse the sale instead.
            if ($amt > self::MAX_PAYMENT_LEG) {
                return response()->json(['status' => false, 'msg' => 'Payment amount is too large — check the tendered figure'], 422);
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
        $invoice->discount_amount = $billDiscount + $offerDiscount + $couponDiscount;
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
        // Billing staff: the logged-in employee, else the counter's assigned staff (owner billing = null).
        $invoice->pos_staff_id = auth('vendor_employee')->id() ?? ($counter->staff_id ?? null);
        $invoice->save();

        if (!empty($auditNotes)) {
            $this->logAudit('override', $invoice->invoice_id, implode('; ', $auditNotes));
        }

        _ensureInvoiceItemVariationColumn();
        _ensureInvoiceItemDiscountColumn();
        $hasLineDiscountColumn = Schema::hasColumn('invoice_items', 'discount');

        foreach ($lines as $line) {
            $ii = new InvoiceItem();
            $ii->rand_invoice_id = $invoice->invoice_id;
            $ii->manual_invoice_id = $invoice->id;
            $ii->inv_id = $line['item']->id;
            $ii->name = $line['var_type'] ? $line['item']->item_name . ' (' . $line['var_type'] . ')' : $line['item']->item_name;
            if (!empty($line['is_offer'])) {
                $ii->name .= ' (Offer Free)';
            }
            $ii->qty = $line['qty'];
            $ii->price = $line['price'];
            if ($hasLineDiscountColumn) {
                $ii->discount = (float) ($line['discount'] ?? 0);
            }
            $ii->tax = $line['rate'];
            $ii->hsn = $line['hsn'];
            $ii->gst_status = $line['gst_status'];
            $ii->pieces = $line['pieces'];
            if (Schema::hasColumn('invoice_items', 'variation_type')) {
                $ii->variation_type = $line['var_type'];
            }
            $ii->save();

            // One call, whatever the item's stock type. The variation decides where the stock
            // comes from: a measured pack (100gm) draws its converted weight from the main pool,
            // a countable variation (Red) takes it off its own count and the main figure follows
            // as the sum. Deducting from both — which is what happened here before — took the
            // quantity out twice.
            _updateInventoryStock($line['item']->id, $line['qty'], $line['item']->unit, $line['var_type']);

            // Decrement the branch's stock too (availability at the counter's branch). Branch
            // stock is held in the item's own unit, so a measured pack takes its converted
            // weight — one 100gm pack off a kg item is 0.1, not 1.
            //
            // No floor at zero: an approved oversell must show the branch short (-2), matching
            // what the main pool now records. Flooring it here left the branch reading 0 while
            // the store's own figure was negative, and the two never reconciled.
            if ($billingBranchId) {
                $branchQty = _stockQtyForLine($line['item'], $line['qty'], $line['item']->unit, $line['var_type']);
                $affected = DB::table('pos_branch_stock')
                    ->where('branch_id', $billingBranchId)->where('inventory_item_id', $line['item']->id)
                    ->update(['stock' => DB::raw('stock - ' . $branchQty), 'updated_at' => now()]);
                // An item never transferred to this branch has no row at all, so the update above
                // matched nothing and the oversell vanished. Open the row at the negative figure.
                if (!$affected) {
                    DB::table('pos_branch_stock')->insert([
                        'store_id'          => $storeId,
                        'branch_id'         => $billingBranchId,
                        'inventory_item_id' => $line['item']->id,
                        'stock'             => -$branchQty,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }
            }
        }

        // Record offer redemptions so per-day / per-customer / campaign limits hold next sale.
        $offerEngine->logRedemptions($offerResult['applied'], $storeId, $invoice->id, $customerId ?: null);

        // Record coupon usage + stamp it on the invoice.
        if ($couponDiscount > 0 && ($couponModel || $serviceCoupon)) {
            if ($couponModel) {
                $couponModel->increment('total_uses');
            } else {
                $serviceCoupon->increment('used_count');
            }
            $invoice->meta = array_merge((array) $invoice->meta, [
                'coupon_code'     => $couponModel->code ?? $serviceCoupon->code,
                'coupon_discount' => round($couponDiscount, 2),
            ]);
            $invoice->save();
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

        // Generate the A4 PDF at sale time (here — not lazily), but AFTER the response is flushed,
        // so the heavy DomPDF render (~8–9s) doesn't delay the thermal print. Runs in-process on
        // PHP-FPM termination; no queue worker needed.
        dispatch(function () use ($invoice) {
            try {
                $pdf = _createBillPdf($invoice, 'vendor');
                $invoice->update(['pdf' => $pdf['pdf']]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Retail finalize PDF generation failed (invoice ' . $invoice->id . '): ' . $e->getMessage());
            }
        })->afterResponse();
        $pdfUrl = null;

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
            'offer_discount' => round($offerDiscount, 2),
            'coupon_discount' => round($couponDiscount, 2),
            'applied_offers' => array_map(fn($a) => [
                'label'    => $a['label'],
                'free_qty' => $a['free_qty'],
                'discount' => $a['discount'],
            ], $offerResult['applied']),
            'pdf_url'        => $pdfUrl,
            'thermal_url'    => route('vendor.retail-pos.thermal', $invoice->id),
            // Inlined receipt so the browser prints immediately (no second round-trip).
            'receipt_html'   => $this->buildThermalHtml($invoice),
        ]);
    }

    // ── Offers: list all + match against the current cart (preview, not applied) ──
    public function offersAll(Request $request)
    {
        $storeId = $this->storeId();
        if (!\Illuminate\Support\Facades\Schema::hasTable('inventory_offers')) {
            return response()->json(['offers' => []]);
        }
        $today = date('Y-m-d');
        $offers = \App\Models\InventoryOffer::where('store_id', $storeId)->orderByDesc('id')->get();

        return response()->json(['offers' => $offers->map(fn($o) => [
            'id'     => $o->id,
            'name'   => $o->offer_name,
            'code'   => $o->offer_code,
            'type'   => ucwords(str_replace('_', ' ', $o->offer_type)),
            'start'  => $o->start_date,
            'end'    => $o->end_date,
            'status' => $o->status,
            'active' => $o->status === 'published' && $o->start_date <= $today && $o->end_date >= $today,
        ])]);
    }

    public function offersMatch(Request $request)
    {
        $store = Helpers::get_store_data();
        $storeId = $store->id;

        $cart = json_decode($request->input('items', '[]'), true) ?: [];
        if (empty($cart)) {
            return response()->json(['offers' => []]);
        }

        $billingBranchId = $this->billingBranchId($request);
        $customerId = (int) $request->input('customer_id', 0);
        $customer = $customerId ? StoreCustomer::find($customerId) : null;

        [$lines, $subtotal] = $this->buildOfferLines($cart, $storeId);
        if (empty($lines)) {
            return response()->json(['offers' => []]);
        }

        $matches = app(\App\Modules\PosRetail\Services\PosOfferEngine::class)->matches($lines, [
            'store_id'  => $storeId,
            'branch_id' => $billingBranchId,
            'customer'  => $customer,
            'subtotal'  => $subtotal,
        ]);

        return response()->json(['offers' => array_map(fn($m) => [
            'id'       => $m['offer']->id,
            'label'    => $m['label'],
            'type'     => $m['offer']->offer_type,
            'summary'  => $m['summary'],
            'discount' => $m['discount'],
            'free_qty' => $m['free_qty'],
        ], $matches)]);
    }

    public function offersApplyCode(Request $request)
    {
        $store = Helpers::get_store_data();
        $storeId = $store->id;

        $code = trim((string) $request->input('code', ''));
        if ($code === '') {
            return response()->json(['status' => false, 'msg' => 'Enter an offer code']);
        }

        $cart = json_decode($request->input('items', '[]'), true) ?: [];
        if (empty($cart)) {
            return response()->json(['status' => false, 'msg' => 'Cart is empty']);
        }

        $billingBranchId = $this->billingBranchId($request);
        $customerId = (int) $request->input('customer_id', 0);
        $customer = $customerId ? StoreCustomer::find($customerId) : null;

        [$lines, $subtotal] = $this->buildOfferLines($cart, $storeId);

        $matches = app(\App\Modules\PosRetail\Services\PosOfferEngine::class)->matches($lines, [
            'store_id'  => $storeId,
            'branch_id' => $billingBranchId,
            'customer'  => $customer,
            'subtotal'  => $subtotal,
        ]);

        foreach ($matches as $m) {
            if (strcasecmp($m['offer']->offer_code, $code) === 0) {
                return response()->json(['status' => true, 'offer' => [
                    'id'       => $m['offer']->id,
                    'label'    => $m['label'],
                    'type'     => $m['offer']->offer_type,
                    'summary'  => $m['summary'],
                    'discount' => $m['discount'],
                    'free_qty' => $m['free_qty'],
                ]]);
            }
        }

        $exists = \Illuminate\Support\Facades\Schema::hasTable('inventory_offers')
            && \App\Models\InventoryOffer::where('store_id', $storeId)
                ->whereRaw('LOWER(offer_code) = ?', [strtolower($code)])->exists();

        return response()->json([
            'status' => false,
            'msg'    => $exists ? 'Offer not applicable to this cart' : 'Invalid offer code',
        ]);
    }

    // ── Coupons: list the customer's applicable coupons + validate a code (preview) ──
    public function couponsForCart(Request $request)
    {
        $storeId = $this->storeId();
        if (!\Illuminate\Support\Facades\Schema::hasTable('coupons')) {
            return response()->json(['coupons' => []]);
        }

        $cart = json_decode($request->input('items', '[]'), true) ?: [];
        [, $subtotal] = $this->buildOfferLines($cart, $storeId);
        $customerId = (int) $request->input('customer_id', 0);
        $customer = $customerId ? StoreCustomer::find($customerId) : null;

        $today = date('Y-m-d');
        $coupons = \App\Models\Coupon::where('store_id', $storeId)
            ->where('status', 1)
            ->whereDate('expire_date', '>=', $today)
            ->orderByDesc('id')
            ->get();

        $out = [];
        foreach ($coupons as $c) {
            $disc = $this->posCouponDiscount($c, $subtotal, $customer);
            if ($disc === null) {
                continue;
            }
            $out[] = $this->couponPayload($c, $disc);
        }

        // Store service-coupons (admin/platform issued to this store).
        if (\Illuminate\Support\Facades\Schema::hasTable('service_coupons')) {
            $serviceCoupons = \App\Models\ServiceCoupon::where('user_type', 'store')
                ->where('user_type_id', $storeId)->get();
            foreach ($serviceCoupons as $sc) {
                $disc = $this->serviceCouponDiscount($sc, $subtotal);
                if ($disc === null) {
                    continue;
                }
                $out[] = $this->serviceCouponPayload($sc, $disc);
            }
        }

        return response()->json(['coupons' => $out]);
    }

    public function applyCoupon(Request $request)
    {
        $storeId = $this->storeId();
        $code = trim((string) $request->input('code', ''));
        if ($code === '') {
            return response()->json(['status' => false, 'msg' => 'Enter a coupon code']);
        }
        if (!\Illuminate\Support\Facades\Schema::hasTable('coupons')) {
            return response()->json(['status' => false, 'msg' => 'Coupons unavailable']);
        }

        $cart = json_decode($request->input('items', '[]'), true) ?: [];
        [, $subtotal] = $this->buildOfferLines($cart, $storeId);
        $customerId = (int) $request->input('customer_id', 0);
        $customer = $customerId ? StoreCustomer::find($customerId) : null;

        $found = false;

        $coupon = \App\Models\Coupon::where('store_id', $storeId)
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])->first();
        if ($coupon) {
            $found = true;
            $disc = $this->posCouponDiscount($coupon, $subtotal, $customer);
            if ($disc !== null) {
                return response()->json(['status' => true, 'coupon' => $this->couponPayload($coupon, $disc)]);
            }
        }

        // Fall back to a store service-coupon with this code.
        if (\Illuminate\Support\Facades\Schema::hasTable('service_coupons')) {
            $sc = \App\Models\ServiceCoupon::where('user_type', 'store')->where('user_type_id', $storeId)
                ->whereRaw('LOWER(code) = ?', [strtolower($code)])->first();
            if ($sc) {
                $found = true;
                $disc = $this->serviceCouponDiscount($sc, $subtotal);
                if ($disc !== null) {
                    return response()->json(['status' => true, 'coupon' => $this->serviceCouponPayload($sc, $disc)]);
                }
            }
        }

        return response()->json([
            'status' => false,
            'msg'    => $found ? 'Coupon not applicable to this bill' : 'Invalid coupon code',
        ]);
    }

    private function couponPayload($coupon, float $disc): array
    {
        return [
            'code'     => $coupon->code,
            'title'    => $coupon->title,
            'discount' => $disc,
            'source'   => 'coupon',
            'label'    => $coupon->discount_type === 'percent'
                ? (rtrim(rtrim((string) $coupon->discount, '0'), '.') . '% off (₹' . $disc . ')')
                : ('₹' . $disc . ' off'),
        ];
    }

    private function serviceCouponPayload($sc, float $disc): array
    {
        return [
            'code'     => $sc->code,
            'title'    => $sc->code,
            'discount' => $disc,
            'source'   => 'service',
            'label'    => '₹' . $disc . ' off',
        ];
    }

    /** Validate a store service-coupon (flat amount, limited by use_limit vs used_count). */
    private function serviceCouponDiscount($sc, float $amount): ?float
    {
        if ($sc->use_limit !== null && (int) ($sc->used_count ?? 0) >= (int) $sc->use_limit) {
            return null;
        }
        $disc = (float) $sc->amount;
        if ($disc <= 0) {
            return null;
        }
        $disc = min($disc, $amount);

        return round(max(0, $disc), 2);
    }

    /** Validate a coupon for the POS bill and return the discount amount, or null if not applicable. */
    private function posCouponDiscount($coupon, float $amount, $customer): ?float
    {
        if ((int) $coupon->status !== 1) {
            return null;
        }
        $today = date('Y-m-d');
        if ($coupon->start_date && \Illuminate\Support\Carbon::parse($coupon->start_date)->format('Y-m-d') > $today) {
            return null;
        }
        if ($coupon->expire_date && \Illuminate\Support\Carbon::parse($coupon->expire_date)->format('Y-m-d') < $today) {
            return null;
        }
        if ($coupon->min_purchase && $amount < (float) $coupon->min_purchase) {
            return null;
        }

        // Customer targeting: customer_id is a JSON array of ids, or ["all"].
        $custIds = json_decode($coupon->customer_id, true) ?: [];
        if (!empty($custIds) && !in_array('all', $custIds, true)) {
            if (!$customer || !in_array((string) $customer->id, array_map('strval', $custIds), true)) {
                return null;
            }
        }

        // Global usage limit.
        if ($coupon->limit && (int) $coupon->total_uses >= (int) $coupon->limit) {
            return null;
        }

        $disc = $coupon->discount_type === 'percent'
            ? $amount * ((float) $coupon->discount / 100)
            : (float) $coupon->discount;
        if ($coupon->max_discount > 0) {
            $disc = min($disc, (float) $coupon->max_discount);
        }
        $disc = min($disc, $amount);

        return round(max(0, $disc), 2);
    }

    /** Lightweight cart → lines for offer matching (no stock/price-override validation). */
    private function buildOfferLines(array $cart, int $storeId): array
    {
        $lines = [];
        $subtotal = 0.0;
        foreach ($cart as $row) {
            $idParts = explode('-var-', $row['id'] ?? '');
            $item = InventoryItem::where('id', $idParts[0])->where('store_id', $storeId)->first();
            if (!$item) {
                continue;
            }
            $varType = $idParts[1] ?? null;
            $qty = max(0, (float) ($row['qty'] ?? 1));
            if ($qty <= 0) {
                continue;
            }
            $basePrice = (float) ($item->selling_price ?? 0);
            if ($varType) {
                foreach (json_decode($item->variations, true) ?: [] as $var) {
                    if (($var['type'] ?? null) === $varType) {
                        $basePrice = (float) ($var['price'] ?? $basePrice);
                        break;
                    }
                }
            }
            $price = (float) ($row['price'] ?? $basePrice);
            $gross = max(0, $price * $qty - (float) ($row['discount'] ?? 0));
            $rate = (float) ($item->gst_rate ?? 0);
            $status = $item->gst_status ?? 'excluding';
            $subtotal += $status === 'including' ? ($rate > 0 ? $gross / (1 + $rate / 100) : $gross) : $gross;

            $lines[] = [
                'item'       => $item,
                'qty'        => $qty,
                'price'      => $price,
                'rate'       => $rate,
                'gst_status' => $status,
                'hsn'        => $item->hsn,
                'var_type'   => $varType,
            ];
        }
        return [$lines, $subtotal];
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

        $branch = $this->posBranchFilter($request);
        $staff = (int) $request->get('staff') ?: null;
        $terminal = (int) $request->get('terminal') ?: null;
        $branches = Branch::where('store_id', $storeId)->orderBy('name')->get();

        // A logged-in staff member only ever sees their own sales ("My Sales").
        $isStaff = (bool) auth('vendor_employee')->id();

        // "My Billing" — only what the person signed in rang up themselves. A staff member is
        // already held to their own sales, so for them it is the whole page and the tab is just
        // what it is called. For the owner it means the bills with nobody else against them:
        // finalize() stamps pos_staff_id with the employee who billed, or the counter's assigned
        // staff, and leaves it null when the owner bills directly.
        $mine = $request->boolean('mine');
        $myBranchName = null;
        if ($isStaff) {
            $staff = auth('vendor_employee')->id();
            $terminal = null;
            $mine = true;
            // Filtering one person's own bills by branch is a choice with no answers: they billed
            // from the counter they are assigned to, so the filter can only narrow to their own
            // branch or to nothing. Dropped, and the branch is shown as context instead. Left
            // unset rather than pinned, so a staff member moved between branches still sees
            // everything they rang up.
            $branch = null;
            $counter = $this->currentCounter();
            $myBranchName = $counter && ($counter->branch_id ?? null)
                ? optional($branches->firstWhere('id', $counter->branch_id))->name
                : null;
        }

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
            ->where($this->posBranchScope($branch))
            ->when($staff, fn($q) => $q->where('pos_staff_id', $staff))
            ->when(!$isStaff && $mine, fn($q) => $q->whereNull('pos_staff_id'))
            ->when($terminal, fn($q) => $q->where('pos_terminal_id', $terminal))
            ->orderByDesc('id')
            ->get();

        // Email/WhatsApp need a linked customer — walk-in bills have none. Resolve in one query so
        // the view can disable those actions instead of failing on submit.
        $custIds = $bills->pluck('bill_to')->filter()->unique()->values();
        $customers = $custIds->isNotEmpty()
            ? StoreCustomer::whereIn('id', $custIds)->get(['id', 'f_name', 'email', 'phone'])->keyBy('id')
            : collect();

        // Filter sources + display-name maps for the Staff / Counter columns.
        $staffList = VendorEmployee::where('store_id', $storeId)->orderBy('f_name')->get(['id', 'f_name', 'l_name']);
        $counters = DB::table('pos_terminals')->where('store_id', $storeId)->orderBy('name')->get();
        $staffNames = $staffList->mapWithKeys(fn($s) => [$s->id => trim($s->f_name . ' ' . $s->l_name)]);
        $counterNames = $counters->mapWithKeys(fn($c) => [$c->id => $c->name]);

        return view('posretail::vendor.retail-pos.today', compact('bills', 'branches', 'branch', 'from', 'to', 'preset', 'custom', 'customers', 'staff', 'terminal', 'staffList', 'counters', 'staffNames', 'counterNames', 'isStaff', 'mine', 'myBranchName'));
    }

    // ── Void (counter-entry; never deleted) ───────────────────────────────────
    public function voidBill(Request $request, $id)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $invoice = ManualInvoice::where('vendor_id', $storeId)->findOrFail($id);

        if (!$this->performVoid($invoice, $request->input('reason'), $storeId)) {
            Toastr::info('Invoice already voided');
            return back();
        }

        Toastr::success('Invoice voided');
        return back();
    }

    // Void several bills at once from Today's Bills. Same effect as voiding each individually —
    // stock is restored and every void is audited. Already-void bills in the selection are skipped.
    public function bulkVoid(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);
        $storeId = $this->storeId();

        $invoices = ManualInvoice::where('vendor_id', $storeId)
            ->whereIn('id', $request->ids)
            ->get();

        $voided = 0;
        foreach ($invoices as $invoice) {
            if ($this->performVoid($invoice, $request->input('reason') ?: 'Bulk void from Today\'s Bills', $storeId)) {
                $voided++;
            }
        }

        if ($voided === 0) {
            Toastr::info('Nothing to void — the selected bills were already voided.');
        } else {
            Toastr::success($voided . ' bill' . ($voided > 1 ? 's' : '') . ' voided, stock restored.');
        }
        return back();
    }

    public function deleteBill(Request $request, $id)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $invoice = ManualInvoice::where('vendor_id', $storeId)->findOrFail($id);

        $this->performDelete($invoice, $storeId);
        Toastr::success('Bill deleted');
        return back();
    }

    // Permanently delete several bills from Today's Bills. Unlike void (which keeps the record),
    // this removes the invoice, its lines, payment legs and inventory-order mirror outright.
    public function bulkDelete(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);
        $storeId = $this->storeId();

        $invoices = ManualInvoice::where('vendor_id', $storeId)
            ->whereIn('id', $request->ids)
            ->get();

        $deleted = 0;
        foreach ($invoices as $invoice) {
            $this->performDelete($invoice, $storeId);
            $deleted++;
        }

        if ($deleted === 0) {
            Toastr::info('No bills deleted.');
        } else {
            Toastr::success($deleted . ' bill' . ($deleted > 1 ? 's' : '') . ' deleted.');
        }
        return back();
    }

    // Permanently removes one bill and everything a sale created for it: inventory-order mirror,
    // payment legs, invoice lines, then the invoice. Stock is restored only if the bill was NOT
    // already void — a voided bill already had its stock returned, so restoring again would
    // double-count. All in one transaction.
    private function performDelete(ManualInvoice $invoice, int $storeId): void
    {
        DB::transaction(function () use ($invoice, $storeId) {
            $lines = InvoiceItem::where('manual_invoice_id', $invoice->id)->get();

            // A live (non-void) sale still holds its stock as sold — return it on delete so the
            // catalog stays accurate. A void bill already restored stock; leave it alone.
            if ($invoice->pos_status !== 'void') {
                foreach ($lines->whereNotNull('inv_id') as $line) {
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
            }

            // Inventory-order mirror created by Helpers::_placeInventoryOrder (linked by invoice_id).
            $orderIds = InventoryOrder::where('store_id', $storeId)
                ->where('invoice_id', $invoice->invoice_id)->pluck('order_id');
            if ($orderIds->isNotEmpty()) {
                InventoryOrderDetail::whereIn('order_id', $orderIds)->delete();
                InventoryOrder::where('store_id', $storeId)->where('invoice_id', $invoice->invoice_id)->delete();
            }

            DB::table('pos_payment_legs')->where('manual_invoice_id', $invoice->id)->delete();
            InvoiceItem::where('manual_invoice_id', $invoice->id)->delete();

            $this->logAudit('delete', $invoice->invoice_id, $invoice->pos_status === 'void' ? 'Deleted (was void)' : 'Deleted');
            $invoice->delete();
        });
    }

    // Marks one invoice void and restores its stock (global + branch). Returns false if it was
    // already void. Shared by voidBill and bulkVoid so both behave identically.
    private function performVoid(ManualInvoice $invoice, ?string $reason, int $storeId): bool
    {
        if ($invoice->pos_status === 'void') {
            return false;
        }

        $invoice->pos_status = 'void';
        $invoice->void_reason = $reason;
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
        return true;
    }

    // ── 80mm thermal receipt (browser print) ───────────────────────────────────
    public function thermal(Request $request, $id)
    {
        // No ensureSchema here — read-only render on the print hot path; schema is ensured elsewhere.
        $storeId = $this->storeId();
        $invoice = ManualInvoice::where('vendor_id', $storeId)->findOrFail($id);
        return response($this->buildThermalHtml($invoice));
    }

    // Renders the thermal receipt to an HTML string — used by thermal() and inlined into the
    // finalize response so the browser prints without a second server round-trip.
    private function buildThermalHtml(ManualInvoice $invoice): string
    {
        $storeId = $this->storeId();
        $items = InvoiceItem::with('item.itemunit')->where('manual_invoice_id', $invoice->id)->get();
        $this->decorateReceiptLines($items);
        $store = Helpers::get_store_data();
        $legs = DB::table('pos_payment_legs')->where('manual_invoice_id', $invoice->id)->get();
        $customer = $invoice->bill_to ? StoreCustomer::find($invoice->bill_to) : null;

        // Branch sale — show the branch's name/address on the receipt instead of the main store's.
        if ($invoice->pos_branch_id) {
            $branch = Branch::where('store_id', $storeId)->find($invoice->pos_branch_id);
            if ($branch) {
                if (!empty($branch->address)) {
                    $store->address = $branch->address;
                }
                $store->branch_label = $branch->name;
            }
        }

        $receiptTemplate = DB::table('stores')->where('id', $storeId)->value('pos_receipt_template') ?: 'standard';
        $receiptTemplate = in_array($receiptTemplate, ['standard', 'modern', 'elegant'], true) ? $receiptTemplate : 'standard';

        // Tendered (sum of payment legs) → over-tender shows change, short-tender shows balance due.
        $tendered = (float) $legs->sum('amount');
        $changeReturn = $tendered > 0 ? max(0, round($tendered - (float) $invoice->total_amount, 2)) : 0;
        $balanceDue = $tendered > 0 ? max(0, round((float) $invoice->total_amount - $tendered, 2)) : 0;

        // Customer savings vs MRP: Σ(mrp − selling price)·qty per line, plus bill discount + coupon.
        $mrpSaving = 0;
        foreach ($items as $it) {
            $mrp = (float) ($it->line_mrp ?? 0);
            $price = (float) ($it->price ?? 0);
            $qty = (float) ($it->qty ?? 0);
            if ($mrp > $price) {
                $mrpSaving += ($mrp - $price) * $qty;
            }
        }
        $savedAmount = round($mrpSaving + (float) ($invoice->discount_amount ?? 0) + (float) ($invoice->coupon_amount ?? 0), 2);

        // Some stores would rather not advertise the gap between MRP and what they charge.
        // Zeroed rather than passed as a flag so all three receipt templates hide it through the
        // "> 0" test they already apply — one switch, no chance of one layout missing it.
        if (!$this->showsMrpSaving($storeId)) {
            $savedAmount = 0;
        }

        return view('posretail::vendor.retail-pos.thermal', compact('invoice', 'items', 'store', 'legs', 'customer', 'receiptTemplate', 'tendered', 'changeReturn', 'balanceDue', 'savedAmount'))->render();
    }

    /**
     * Qty text and comparable MRP for every receipt line.
     *
     * A measured-pack line is billed in PACKS: nine 500gm packs are qty 9 at ₹23 a pack. The
     * receipts read "loose" off the ITEM (sugar is sold loose) and then printed that 9 with the
     * item's own unit — "9 kg" for 4.5 kg of sugar — and valued the saving against the per-kg
     * MRP, so the bill also claimed roughly twice the discount actually given. Pack lines print
     * as "9 × 500gm" and compare against the MRP of one pack; every other line is unchanged.
     */
    private function decorateReceiptLines($items): void
    {
        $trim = fn($n, $dp) => rtrim(rtrim(number_format((float) $n, $dp, '.', ''), '0'), '.') ?: '0';

        foreach ($items as $it) {
            $item = $it->item;
            $qty  = (float) $it->qty;
            $mrp  = (float) (optional($item)->mrp ?? 0);
            $pack = null;
            $varType = trim((string) ($it->variation_type ?? ''));

            if ($item && $varType !== '' && _variationMode($item) === 'measured' && ($var = _variationRow($item, $varType))) {
                $pack = _variationPack($item, $var);
            }

            if ($pack) {
                $it->qty_label = $trim($qty, 3) . ' × ' . $varType;
                // mrp is held per item unit, so one pack is worth that much of it.
                $it->line_mrp = $mrp * _variationQtyInItemUnit($item, $pack, 1);
                continue;
            }

            $it->line_mrp = $mrp;
            if (!empty(optional($item)->sell_loose) || !empty($it->pieces)) {
                // Weighed line — show the weight with its unit, not the piece count.
                $unitTxt = optional(optional($item)->itemunit)->unit;
                $it->qty_label = $trim($qty, 3) . ($unitTxt ? ' ' . $unitTxt : '');
            } else {
                $it->qty_label = $trim($qty, 2);
            }
        }
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

        // Brand the message with the vendor's store name (Phase 1: global number, vendor identity in content).
        $storeName = optional(Helpers::get_store_data())->name;
        $caption = ($storeName ? $storeName . ' — ' : '') . 'Invoice ' . $invoice->invoice_id;
        $ctx = 'Invoice ' . $invoice->invoice_id;
        $res = $pdfUrl
            ? $wa->sendDocument($phone, $pdfUrl, 'Invoice-' . $invoice->invoice_id . '.pdf', $caption, $ctx)
            : $wa->sendText($phone, $caption, true, $ctx);

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
        $branch = $this->posBranchFilter($request);
        $branches = Branch::where('store_id', $storeId)->orderBy('name')->get();

        $rows = DB::table('invoice_items as ii')
            ->join('manual_invoices as mi', 'mi.id', '=', 'ii.manual_invoice_id')
            ->where('mi.vendor_id', $storeId)
            ->where('mi.type', 'manual')
            ->where('mi.pos_status', 'final')
            ->where($this->posBranchScope($branch, 'mi.pos_branch_id'))
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

        // Apply shift-based staff rotation so the page shows who is actually on each counter now.
        $this->syncCounterShiftStaff($storeId);

        $branches = Branch::where('store_id', $storeId)->orderBy('name')->get();
        $counters = DB::table('pos_terminals')->where('store_id', $storeId)->orderBy('branch_id')->orderBy('id')->get();
        // Include each staff's shift so the owner can swap counter staff per shift timing.
        $staff    = VendorEmployee::with('storeShift:id,name,start_time,end_time')
            ->where('store_id', $storeId)->orderBy('f_name')
            ->get(['id', 'f_name', 'l_name', 'branch_id', 'store_shift_id']);
        // counter_id => [staff_id,...] roster for the auto-shift UI.
        $rosterMap = DB::table('pos_counter_staff')->where('store_id', $storeId)
            ->get(['terminal_id', 'staff_id'])->groupBy('terminal_id')
            ->map(fn($g) => $g->pluck('staff_id')->map(fn($v) => (int) $v)->all());
        $upiId    = DB::table('stores')->where('id', $storeId)->value('pos_upi_id');
        $uiTemplate = DB::table('stores')->where('id', $storeId)->value('pos_ui_template') ?: 'classic';

        return view('posretail::vendor.retail-pos.terminals', compact('branches', 'counters', 'staff', 'rosterMap', 'upiId', 'uiTemplate'));
    }

    /** Does this store print the MRP saving on its receipts? On unless it has been turned off. */
    private function showsMrpSaving(int $storeId): bool
    {
        if (!Schema::hasColumn('stores', 'pos_show_mrp_saving')) {
            return true;
        }
        $value = DB::table('stores')->where('id', $storeId)->value('pos_show_mrp_saving');
        return $value === null || (int) $value === 1;
    }

    // Store-level Retail POS settings page (UPI, New Sale UI template).
    public function settings(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $upiId      = DB::table('stores')->where('id', $storeId)->value('pos_upi_id');
        $uiTemplate = DB::table('stores')->where('id', $storeId)->value('pos_ui_template') ?: 'classic';
        $receiptTemplate = DB::table('stores')->where('id', $storeId)->value('pos_receipt_template') ?: 'standard';
        $showMrpSaving = $this->showsMrpSaving($storeId);
        return view('posretail::vendor.retail-pos.settings', compact('upiId', 'uiTemplate', 'receiptTemplate', 'showMrpSaving'));
    }

    // Print the "Saved Rs. X/- On MRP" line, or don't.
    public function saveMrpSaving(Request $request)
    {
        $this->ensureSchema();
        DB::table('stores')->where('id', $this->storeId())
            ->update(['pos_show_mrp_saving' => $request->boolean('pos_show_mrp_saving') ? 1 : 0]);
        Toastr::success($request->boolean('pos_show_mrp_saving')
            ? 'Receipts will show the MRP saving.'
            : 'Receipts will no longer show the MRP saving.');
        return back();
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
        if (!in_array($tpl, ['classic', 'compact', 'modern', 'search'], true)) {
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
        $branch->branch_manager_id = (int) $request->input('branch_manager_id') ?: null;
        $branch->save();
        Toastr::success('Branch added');
        return back();
    }

    // Assign / change the Branch Manager (a staff) who approves write-offs for a branch.
    public function branchAssignManager(Request $request, $id)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $managerId = (int) $request->input('branch_manager_id') ?: null;
        if ($managerId && !VendorEmployee::where('store_id', $storeId)->where('id', $managerId)->exists()) {
            Toastr::error('Select a valid staff');
            return back();
        }
        Branch::where('store_id', $storeId)->where('id', $id)->update(['branch_manager_id' => $managerId]);
        Toastr::success($managerId ? 'Branch Manager assigned.' : 'Branch Manager cleared.');
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
        $branchId = (int) $request->input('branch_id') ?: null;
        // One counter per staff: clear any existing assignment for this staff first.
        if ($staffId) {
            DB::table('pos_terminals')->where('store_id', $storeId)->where('staff_id', $staffId)->update(['staff_id' => null]);
        }
        DB::table('pos_terminals')->insert([
            'store_id'   => $storeId,
            'branch_id'  => $branchId,
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
        // Keep the staff's saved (HR) branch in sync with the counter they now man.
        if ($staffId && $branchId) {
            VendorEmployee::where('store_id', $storeId)->where('id', $staffId)->update(['branch_id' => $branchId]);
        }
        Toastr::success('Counter added' . ($staffId && $branchId ? " — staff's branch updated to match." : ''));
        return back();
    }

    public function terminalDelete(Request $request, $id)
    {
        DB::table('pos_terminals')->where('id', $id)->where('store_id', $this->storeId())->delete();
        Toastr::success('Counter removed');
        return back();
    }

    // Swap the staff manning a counter (e.g. at shift change). One counter per staff.
    public function terminalAssignStaff(Request $request, $id)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $counter = DB::table('pos_terminals')->where('store_id', $storeId)->where('id', $id)->first();
        if (!$counter) {
            Toastr::error('Counter not found');
            return back();
        }
        $staffId = (int) $request->input('staff_id') ?: null;
        if ($staffId && !VendorEmployee::where('store_id', $storeId)->where('id', $staffId)->exists()) {
            Toastr::error('Select a valid staff');
            return back();
        }
        // One counter per staff: detach this staff from any other counter first.
        if ($staffId) {
            DB::table('pos_terminals')->where('store_id', $storeId)->where('staff_id', $staffId)
                ->where('id', '!=', $id)->update(['staff_id' => null, 'updated_at' => now()]);
        }
        DB::table('pos_terminals')->where('id', $id)->where('store_id', $storeId)
            ->update(['staff_id' => $staffId, 'updated_at' => now()]);
        // Keep the staff's saved (HR) branch in sync with the counter they now man.
        if ($staffId && $counter->branch_id) {
            VendorEmployee::where('store_id', $storeId)->where('id', $staffId)->update(['branch_id' => $counter->branch_id]);
        }
        Toastr::success($staffId
            ? 'Counter staff updated' . ($counter->branch_id ? " — staff's branch updated to match." : '')
            : 'Counter staff cleared');
        return back();
    }

    // The counter assigned to the current actor (staff). Owner has no fixed counter.
    private function currentCounter()
    {
        // Refresh shift-based counter staff once per request before resolving.
        static $synced = false;
        if (!$synced) {
            $this->syncCounterShiftStaff($this->storeId());
            $synced = true;
        }
        $empId = auth('vendor_employee')->id();
        if (!$empId) {
            return null;
        }
        return DB::table('pos_terminals')->where('store_id', $this->storeId())->where('staff_id', $empId)->first();
    }

    // Auto-rotate counter staff by shift: for every counter with auto_shift on, set its
    // active staff_id to the rostered staff whose shift covers the current time (null if
    // nobody is on shift). Runs lazily on POS page loads / billing, so no cron is needed.
    private function syncCounterShiftStaff($storeId): void
    {
        if (!Schema::hasTable('pos_counter_staff') || !Schema::hasColumn('pos_terminals', 'auto_shift')) {
            return;
        }
        $autoCounters = DB::table('pos_terminals')->where('store_id', $storeId)->where('auto_shift', 1)->get();
        if ($autoCounters->isEmpty()) {
            return;
        }
        $nowT = now()->format('H:i:s');
        foreach ($autoCounters as $counter) {
            $rosterIds = DB::table('pos_counter_staff')->where('terminal_id', $counter->id)->pluck('staff_id')->filter()->all();
            $active = null;
            if (!empty($rosterIds)) {
                $emps = VendorEmployee::with('storeShift:id,start_time,end_time')->whereIn('id', $rosterIds)->get();
                foreach ($emps as $emp) {
                    $sh = $emp->storeShift;
                    if (!$sh || !$sh->start_time || !$sh->end_time) {
                        continue;
                    }
                    $start = substr($sh->start_time, 0, 8);
                    $end   = substr($sh->end_time, 0, 8);
                    // Normal shift: start <= now < end. Overnight shift (start > end) wraps midnight.
                    $covers = ($start <= $end)
                        ? ($nowT >= $start && $nowT < $end)
                        : ($nowT >= $start || $nowT < $end);
                    if ($covers) {
                        $active = (int) $emp->id;
                        break;
                    }
                }
            }
            if ((int) $counter->staff_id !== (int) $active) {
                // Keep one-counter-per-staff: free this staff from any other counter first.
                if ($active) {
                    DB::table('pos_terminals')->where('store_id', $storeId)->where('staff_id', $active)
                        ->where('id', '!=', $counter->id)->update(['staff_id' => null]);
                }
                DB::table('pos_terminals')->where('id', $counter->id)->update(['staff_id' => $active, 'updated_at' => now()]);
            }
        }
    }

    // Save a counter's auto-shift toggle + its staff roster.
    public function terminalRoster(Request $request, $id)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $counter = DB::table('pos_terminals')->where('store_id', $storeId)->where('id', $id)->first();
        if (!$counter) {
            Toastr::error('Counter not found');
            return back();
        }

        $auto = $request->boolean('auto_shift');
        $roster = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('roster', [])))));
        // Only keep staff that belong to this store.
        if (!empty($roster)) {
            $roster = VendorEmployee::where('store_id', $storeId)->whereIn('id', $roster)->pluck('id')->all();
        }

        DB::table('pos_terminals')->where('id', $id)->update(['auto_shift' => $auto ? 1 : 0, 'updated_at' => now()]);

        DB::table('pos_counter_staff')->where('terminal_id', $id)->delete();
        if ($auto && !empty($roster)) {
            $rows = array_map(fn($sid) => [
                'store_id' => $storeId, 'terminal_id' => (int) $id, 'staff_id' => (int) $sid,
                'created_at' => now(), 'updated_at' => now(),
            ], $roster);
            DB::table('pos_counter_staff')->insert($rows);
        }

        // Apply immediately so the page reflects the current on-shift staff.
        $this->syncCounterShiftStaff($storeId);

        Toastr::success($auto ? 'Auto shift roster saved' : 'Auto shift turned off');
        return back();
    }

    /**
     * The branch a sale is billed from, and therefore whose stock it draws down.
     *
     * Staff sell from the branch they are allotted to, and only that one: the counter they are
     * signed in at first, else the branch on their own staff record. A posted `branch_id` is
     * ignored for them outright — the branch picker is not shown to staff, so anything arriving
     * in that field came from a hand-crafted request, and honouring it would let a cashier bill
     * against another branch's stock.
     *
     * The owner has no counter and no staff record, so they keep the posted value — that is what
     * the picker on their own screen sets.
     */
    private function billingBranchId(Request $request = null): ?int
    {
        $counter = $this->currentCounter();
        if ($counter && $counter->branch_id) {
            return (int) $counter->branch_id;
        }

        if ($employeeId = auth('vendor_employee')->id()) {
            $branchId = VendorEmployee::where('id', $employeeId)->value('branch_id');
            return $branchId ? (int) $branchId : null;
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

    /**
     * Variations as the counter needs them: each one told whether it is a fixed pack and, if so,
     * how much of the item's stock one of it consumes.
     *
     * Without this the cart cannot tell "150gm" (a fixed pack — one tap is 150 g) from "Red"
     * (a countable SKU sold by the piece), and a pack on a loose item inherits the weight box and
     * defaults to a full unit.
     */
    private function decorateVariations($item): array
    {
        $measured = _variationMode($item) === 'measured';

        return array_map(function ($var) use ($item, $measured) {
            $pack = $measured ? _variationPack($item, $var) : null;
            $var['is_pack']  = (bool) $pack;
            $var['pack_qty'] = $pack['qty'] ?? null;
            $var['pack_unit'] = $pack['code'] ?? null;
            // What one pack takes out of stock, in the item's own unit — 150gm of a kg item = 0.15.
            $var['pack_in_item_unit'] = $pack ? _variationQtyInItemUnit($item, $pack, 1) : null;
            return $var;
        }, _itemVariations($item));
    }

    // Item IDs (this store) that own a variation whose SKU matches $term. Variation SKUs live in
    // inv_item_variation_details, so this lets a scan/search of a variation SKU surface its parent item.
    private function itemIdsByVariationSku(int $storeId, string $term, bool $like = false): array
    {
        if ($term === '') {
            return [];
        }
        $q = DB::table('inv_item_variation_details as v')
            ->join('inventory_items as ii', 'ii.id', '=', 'v.item_id')
            ->where('ii.store_id', $storeId);
        $like ? $q->where('v.sku', 'like', "%{$term}%") : $q->where('v.sku', $term);
        return $q->distinct()->pluck('v.item_id')->all();
    }

    // ── Per-branch stock management ──────────────────────────────────────────────
    public function branchStock(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $branches = Branch::where('store_id', $storeId)->orderBy('name')->get();
        $search = trim((string) $request->get('q', ''));

        // Location selector (chosen via the summary cards): 'all' → matrix overview;
        // 'main' → main-store breakdown; <branch id> → that branch's breakdown.
        $default  = $branches->count() ? 'all' : 'main';
        $sel      = $request->get('branch', $default);
        $allMode  = ($sel === 'all') && $branches->count();
        $mainMode = ($sel === 'main') || ($sel === 'all' && !$branches->count());
        $branchId = (!$allMode && !$mainMode) ? (int) $sel : null;
        if ($branchId !== null && !$branches->firstWhere('id', $branchId)) {
            $branchId = null;
            $allMode  = (bool) $branches->count();
            $mainMode = !$branches->count();
        }
        $detailMode = $mainMode || $branchId !== null;
        $sel = $allMode ? 'all' : ($mainMode ? 'main' : (string) $branchId);

        $varItemIds = $this->itemIdsByVariationSku($storeId, $search, true);
        $items = InventoryItem::where('store_id', $storeId)->where('item_type', 'product')
            ->when($search, fn($q) => $q->where(fn($w) => $w->where('item_name', 'like', "%{$search}%")
                ->orWhere('sku_id', 'like', "%{$search}%")
                ->when(!empty($varItemIds), fn($ww) => $ww->orWhereIn('id', $varItemIds))))
            ->orderBy('item_name')->limit(300)->get(['id', 'item_name', 'sku_id', 'stock']);
        $itemIds = $items->pluck('id');

        // All-branches matrix: current stock keyed [item_id][branch_id].
        $matrix = [];
        if ($allMode && $branches->count() && $items->count()) {
            $rows = DB::table('pos_branch_stock')
                ->whereIn('branch_id', $branches->pluck('id'))
                ->whereIn('inventory_item_id', $itemIds)
                ->get(['inventory_item_id', 'branch_id', 'stock']);
            foreach ($rows as $r) {
                $matrix[$r->inventory_item_id][$r->branch_id] = (float) $r->stock;
            }
        }

        // Per-item breakdown for one location: total / sold / damaged / theft / remaining.
        $detail = [];
        if ($detailMode && $items->count()) {
            // Remaining = current on-hand (branch pool, else main store).
            $remaining = [];
            if ($branchId) {
                $bs = DB::table('pos_branch_stock')->where('branch_id', $branchId)
                    ->whereIn('inventory_item_id', $itemIds)->pluck('stock', 'inventory_item_id');
                foreach ($items as $it) { $remaining[$it->id] = (float) ($bs[$it->id] ?? 0); }
            } else {
                foreach ($items as $it) { $remaining[$it->id] = (float) $it->stock; }
            }

            // Sold = finalized POS sale qty at this location (branch sales vs main-store/no-branch).
            $sold = DB::table('invoice_items as ii')
                ->join('manual_invoices as mi', 'mi.id', '=', 'ii.manual_invoice_id')
                ->where('mi.vendor_id', $storeId)->where('mi.pos_status', 'final')->whereNotNull('ii.inv_id')
                ->when($branchId, fn($q) => $q->where('mi.pos_branch_id', $branchId), fn($q) => $q->whereNull('mi.pos_branch_id'))
                ->whereIn('ii.inv_id', $itemIds)
                ->groupBy('ii.inv_id')->selectRaw('ii.inv_id as id, SUM(ii.qty) as qty')->pluck('qty', 'id');

            // Damaged / theft write-offs at this location.
            $damaged = [];
            $theft = [];
            $wo = DB::table('pos_stock_writeoff')->where('store_id', $storeId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
                ->whereIn('inventory_item_id', $itemIds)
                ->groupBy('inventory_item_id', 'type')->selectRaw('inventory_item_id as id, type, SUM(qty) as qty')->get();
            foreach ($wo as $r) {
                if ($r->type === 'theft') { $theft[$r->id] = (float) $r->qty; }
                else { $damaged[$r->id] = (float) $r->qty; }
            }

            foreach ($items as $it) {
                $rem = $remaining[$it->id] ?? 0;
                $so  = (float) ($sold[$it->id] ?? 0);
                $dm  = $damaged[$it->id] ?? 0;
                $th  = $theft[$it->id] ?? 0;
                $detail[$it->id] = [
                    'total'     => $rem + $so + $dm + $th,
                    'sold'      => $so,
                    'damaged'   => $dm,
                    'theft'     => $th,
                    'remaining' => $rem,
                ];
            }
        }

        $locationName = $mainMode ? 'Main Store'
            : ($branchId ? (optional($branches->firstWhere('id', $branchId))->name ?? 'Branch') : null);

        // ── Summary cards (whole-location totals, independent of the search filter) ──
        // Remaining per location.
        $mainRemaining = (float) InventoryItem::where('store_id', $storeId)->where('item_type', 'product')->sum('stock');
        $branchRemaining = $branches->count()
            ? DB::table('pos_branch_stock')->whereIn('branch_id', $branches->pluck('id'))
                ->groupBy('branch_id')->selectRaw('branch_id, SUM(stock) as s')->pluck('s', 'branch_id')
            : collect();

        // Sold per location (bid 0 = main store / no branch).
        $soldByLoc = [];
        foreach (DB::table('invoice_items as ii')
            ->join('manual_invoices as mi', 'mi.id', '=', 'ii.manual_invoice_id')
            ->where('mi.vendor_id', $storeId)->where('mi.pos_status', 'final')->whereNotNull('ii.inv_id')
            ->groupBy('mi.pos_branch_id')->selectRaw('mi.pos_branch_id as bid, SUM(ii.qty) as s')->get() as $r) {
            $soldByLoc[(int) ($r->bid ?? 0)] = (float) $r->s;
        }

        // Damaged / theft per location.
        $dmgByLoc = [];
        $thfByLoc = [];
        foreach (DB::table('pos_stock_writeoff')->where('store_id', $storeId)
            ->groupBy('branch_id', 'type')->selectRaw('branch_id as bid, type, SUM(qty) as s')->get() as $r) {
            $bid = (int) ($r->bid ?? 0);
            if ($r->type === 'theft') { $thfByLoc[$bid] = (float) $r->s; }
            else { $dmgByLoc[$bid] = (float) $r->s; }
        }

        $mk = fn($key, $name, $rem, $active) => [
            'key' => $key, 'name' => $name, 'active' => $active,
            'remaining' => $rem,
            'sold'      => $soldByLoc[$key === 'main' ? 0 : (int) $key] ?? 0,
            'damaged'   => $dmgByLoc[$key === 'main' ? 0 : (int) $key] ?? 0,
            'theft'     => $thfByLoc[$key === 'main' ? 0 : (int) $key] ?? 0,
        ];

        $cards = [];
        if ($branches->count()) {
            $cards[] = [
                'key' => 'all', 'name' => 'All Branches', 'active' => $allMode,
                'remaining' => $mainRemaining + (float) $branchRemaining->sum(),
                'sold'      => array_sum($soldByLoc),
                'damaged'   => array_sum($dmgByLoc),
                'theft'     => array_sum($thfByLoc),
            ];
        }
        $cards[] = $mk('main', 'Main Store', $mainRemaining, $mainMode);
        foreach ($branches as $b) {
            $cards[] = $mk((string) $b->id, $b->name, (float) ($branchRemaining[$b->id] ?? 0), $branchId == $b->id);
        }

        return view('posretail::vendor.retail-pos.branch-stock', compact(
            'branches', 'branchId', 'allMode', 'mainMode', 'detailMode', 'items', 'matrix', 'detail',
            'search', 'locationName', 'cards', 'sel'
        ));
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

        // No item list up front. A transfer is a handful of lines picked deliberately, the same
        // way a bill is built — rendering the whole catalogue with a quantity box on every row
        // made the operator hunt through hundreds of items to fill in three.
        $hasSource = $this->gatepassHasSourceColumn();
        $gatepasses = DB::table('pos_stock_gatepass as g')
            ->leftJoin('branches as b', 'b.id', '=', 'g.branch_id')
            ->when($hasSource, fn($q) => $q->leftJoin('branches as fb', 'fb.id', '=', 'g.from_branch_id'))
            ->where('g.store_id', $storeId)
            ->orderByDesc('g.id')->limit(100)
            ->get(array_merge(
                ['g.id', 'g.gatepass_no', 'g.note', 'g.created_at', 'b.name as branch_name'],
                $hasSource ? ['fb.name as from_branch_name'] : []
            ));

        return view('posretail::vendor.retail-pos.gatepass', compact('branches', 'gatepasses', 'hasSource'));
    }

    /** Item picker for the transfer form — name or SKU, including variation SKUs. */
    public function gatepassSearch(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();

        $search = trim((string) $request->get('q', ''));
        $varItemIds = $this->itemIdsByVariationSku($storeId, $search, true);

        $fromBranch = $this->gatepassSourceBranch($request, $storeId);

        $dbItems = InventoryItem::where('store_id', $storeId)->where('item_type', 'product')
            ->when($search, fn($q) => $q->where(fn($w) => $w->where('item_name', 'like', "%{$search}%")
                ->orWhere('sku_id', 'like', "%{$search}%")
                ->when(!empty($varItemIds), fn($ww) => $ww->orWhereIn('id', $varItemIds))))
            ->with('itemunit')->orderBy('item_name')->limit(25)
            ->get(['id', 'item_name', 'sku_id', 'stock', 'unit', 'variations']);

        // Sending from a branch means the ceiling is that branch's pool, not the main store's, and
        // an item the branch has never received simply isn't offered — a picker that lists the
        // whole catalogue against a branch that holds three products is only a way to fail
        // validation later.
        if ($fromBranch) {
            $pool = DB::table('pos_branch_stock')->where('branch_id', $fromBranch->id)
                ->whereIn('inventory_item_id', $dbItems->pluck('id'))
                ->pluck('stock', 'inventory_item_id');

            $dbItems = $dbItems->filter(fn($it) => (float) ($pool[$it->id] ?? 0) > 0)
                ->each(function ($it) use ($pool) {
                    $it->stock = (float) ($pool[$it->id] ?? 0);
                    // A branch holds one flat quantity per item, so there is no variation pool to
                    // choose between — the source dropdown would be a lie. Empty JSON rather than
                    // null so the payload builder's json_decode still gets a string.
                    $it->variations = '[]';
                })
                ->values();
        }

        $lastMoved = $this->gatepassLastTransfers($storeId, $dbItems->pluck('id')->all());

        return response()->json([
            'results' => $dbItems->map(fn($it) => $this->gatepassItemPayload($it, $lastMoved[$it->id] ?? null))->all(),
        ]);
    }

    /**
     * The branch a report page is filtered to: null = every location, 0 = the main store, >0 = a branch.
     *
     * "Main store" needs a sentinel of its own because the reading these pages used —
     * `(int) $request->get('branch') ?: null` — folds 0 back into null, so a plain 0 in the URL is
     * indistinguishable from no filter at all. 'main' is the same token the inventory reports use.
     */
    private function posBranchFilter(Request $request)
    {
        $b = $request->get('branch');
        if ($b === null || $b === '' || $b === 'all') {
            return null;
        }
        if ($b === 'main' || (string) $b === '0') {
            return 0;
        }
        return (int) $b ?: null;
    }

    /**
     * That filter as a where-closure, so it drops into any query the same way.
     *
     * Bills rung up with no counter — the owner billing directly — carry a null pos_branch_id,
     * which is exactly what "main store" means here. Adds nothing when no branch is selected;
     * Laravel skips an empty nested group rather than emitting invalid SQL.
     */
    private function posBranchScope($branch, string $column = 'pos_branch_id'): \Closure
    {
        return function ($q) use ($branch, $column) {
            if ($branch === null) {
                return;
            }
            $branch === 0 ? $q->whereNull($column) : $q->where($column, $branch);
        };
    }

    /**
     * Whether pos_stock_gatepass can record where a transfer came from.
     *
     * The column is added by ensureSchema(), whose DDL is best-effort — a swallowed failure there
     * would otherwise take the whole gatepass page down with an "unknown column" error, breaking
     * main-store transfers that have nothing to do with this feature. Checked once per request.
     */
    private function gatepassHasSourceColumn(): bool
    {
        static $has = null;
        if ($has === null) {
            try {
                $has = Schema::hasColumn('pos_stock_gatepass', 'from_branch_id');
            } catch (\Throwable $e) {
                $has = false;
            }
        }
        return $has;
    }

    /**
     * The branch a transfer is being sent FROM, or null for the main store.
     *
     * Resolved against this store's own branches, so a posted id can only ever name a branch the
     * caller already owns.
     */
    private function gatepassSourceBranch(Request $request, int $storeId)
    {
        if (!$this->gatepassHasSourceColumn()) {
            return null;
        }

        $id = (int) $request->input('from_branch_id');
        if ($id <= 0) {
            return null;
        }

        return Branch::where('store_id', $storeId)->where('id', $id)->first();
    }

    /**
     * When each of these items last went out on a gatepass, and to where.
     *
     * Answers the question the operator actually has while filling the form — "didn't I already
     * send this to Andheri on Tuesday?" — which is what stops the same stock being transferred
     * twice. One grouped query for the whole result set rather than a lookup per row.
     */
    private function gatepassLastTransfers(int $storeId, array $itemIds): array
    {
        if (empty($itemIds)) {
            return [];
        }

        try {
            return DB::table('pos_stock_gatepass_items as i')
                ->join('pos_stock_gatepass as g', 'g.id', '=', 'i.gatepass_id')
                ->leftJoin('branches as b', 'b.id', '=', 'g.branch_id')
                ->where('g.store_id', $storeId)
                ->whereIn('i.inventory_item_id', $itemIds)
                ->groupBy('i.inventory_item_id')
                ->select(
                    'i.inventory_item_id as item_id',
                    DB::raw('MAX(g.created_at) as last_at'),
                    // The branch on the most recent gatepass, not just any of them. Separated on
                    // '||' rather than a comma because "Andheri, West" is a perfectly ordinary
                    // branch name and the default separator would cut it in half.
                    DB::raw("SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(b.name, '') ORDER BY g.created_at DESC SEPARATOR '||'), '||', 1) as last_branch")
                )
                ->get()
                ->keyBy('item_id')
                ->all();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gatepass last-transfer lookup skipped: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * One transfer row's worth of data.
     *
     * One row per product — a branch holds stock per item, not per variation (pos_branch_stock is
     * unique on branch_id + inventory_item_id), so what moves is always the main product.
     * Variations ride along only as the pool the qty is deducted from.
     */
    private function gatepassItemPayload($it, $lastMoved = null): array
    {
        $mode = _variationMode($it);
        $vars = [];

        foreach (json_decode($it->variations, true) ?: [] as $var) {
            if (empty($var['type'])) {
                continue;
            }

            // What the quantity box means depends on which pool is picked, so each option carries
            // its own ceiling in ITS OWN units. A measured pack is entered in packs — 3 × 100gm
            // draws 0.3 kg — so capping the box at the item's kg stock would refuse quantities the
            // transfer can happily fulfil.
            $max = (float) ($var['stock'] ?? 0);
            $label = '';
            if ($mode === 'measured') {
                $pack = _variationPack($it, $var);
                $perPack = $pack ? _variationQtyInItemUnit($it, $pack, 1) : 0;
                if ($perPack > 0) {
                    $max = floor((float) $it->stock / $perPack);
                    $label = 'packs of ' . rtrim(rtrim(number_format($perPack, 3), '0'), '.')
                        . ' ' . _unitLabelFor($it->unit);
                } else {
                    $max = (float) $it->stock;
                }
            }

            $vars[] = [
                'type'  => $var['type'],
                'stock' => (float) ($var['stock'] ?? 0),
                'max'   => $max,
                'hint'  => $label,
            ];
        }

        $lastText = null;
        if ($lastMoved && !empty($lastMoved->last_at)) {
            $lastText = 'Last sent ' . \Carbon\Carbon::parse($lastMoved->last_at)->format('d M Y, h:i A')
                . (trim((string) ($lastMoved->last_branch ?? '')) !== '' ? ' → ' . $lastMoved->last_branch : '');
        }

        return [
            'id'         => (string) $it->id,
            'item_name'  => $it->item_name,
            'sku_id'     => (string) $it->sku_id,
            'stock'      => (float) $it->stock,
            'stock_text' => rtrim(rtrim(number_format((float) $it->stock, 3), '0'), '.'),
            'unit'       => _unitLabelFor($it->unit),
            'var_mode'   => $mode,
            'variations' => $vars,
            'last_sent'  => $lastText,
        ];
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

        $fromBranch = $this->gatepassSourceBranch($request, $storeId);
        if ($fromBranch && $fromBranch->id == $branchId) {
            Toastr::error('Source and destination branch cannot be the same');
            return back();
        }

        // The form posts qty[{itemId}] alongside source[{itemId}], which is '' for the main
        // stock pool or a variation type. The legacy "{itemId}-var-{type}" qty key is still
        // honoured so a stale open tab keeps working.
        $sources = (array) $request->input('source', []);
        $lines = [];
        foreach ((array) $request->input('qty', []) as $idStr => $val) {
            $qty = (float) $val;
            if ($qty <= 0) {
                continue;
            }
            $idParts = explode('-var-', (string) $idStr);
            $varType = $idParts[1] ?? null;
            if ($varType === null) {
                $src = trim((string) ($sources[$idStr] ?? ''));
                $varType = $src !== '' ? $src : null;
            }
            $lines[] = ['item_id' => (int) $idParts[0], 'var_type' => $varType, 'qty' => $qty];
        }
        if (empty($lines)) {
            Toastr::error('Enter a transfer quantity for at least one item');
            return back();
        }

        // A branch pool has no variation breakdown, so a variation posted by a stale tab would be
        // recorded on the gatepass and printed on the note while the deduction ignored it.
        if ($fromBranch) {
            $lines = array_map(fn($l) => ['item_id' => $l['item_id'], 'var_type' => null, 'qty' => $l['qty']], $lines);
        }

        // Validate what is being sent against the pool it is coming out of. From the main store a
        // countable variation is checked against its own count and a measured pack against what it
        // actually draws — 3 × 100gm needs 0.3 kg, not 3 kg, so the raw line quantity is the wrong
        // thing to compare.
        $items = InventoryItem::where('store_id', $storeId)
            ->whereIn('id', array_unique(array_column($lines, 'item_id')))->get()->keyBy('id');

        // Sending from a branch is a different sum entirely: the branch holds one flat quantity per
        // item in the item's own unit, with no variation breakdown, so the line quantity is checked
        // straight against that pool and the main store is not consulted at all.
        if ($fromBranch) {
            $pool = DB::table('pos_branch_stock')->where('branch_id', $fromBranch->id)
                ->whereIn('inventory_item_id', array_column($lines, 'item_id'))
                ->pluck('stock', 'inventory_item_id');

            $wanted = [];
            foreach ($lines as $line) {
                $item = $items->get($line['item_id']);
                if (!$item) {
                    Toastr::error('Item not found');
                    return back();
                }
                $wanted[$line['item_id']] = ($wanted[$line['item_id']] ?? 0) + $line['qty'];
            }

            foreach ($wanted as $itemId => $qty) {
                $have = (float) ($pool[$itemId] ?? 0);
                if ($qty > $have) {
                    $name = optional($items->get($itemId))->item_name ?? 'Item';
                    Toastr::error("Insufficient stock at {$fromBranch->name} for \"{$name}\" (have "
                        . rtrim(rtrim(number_format($have, 3), '0'), '.') . ')');
                    return back();
                }
            }
        }

        if (!$fromBranch) {
            foreach ($lines as $line) {
                $item = $items->get($line['item_id']);
                if (!$item) {
                    Toastr::error('Item not found');
                    return back();
                }

                if ($varErr = _variationSelectionError($item, $line['var_type'])) {
                    Toastr::error($varErr);
                    return back();
                }

                if (_variationMode($item) === 'countable') {
                    $var = _variationRow($item, $line['var_type']);
                    $have = (float) ($var['stock'] ?? 0);
                    if ($line['qty'] > $have) {
                        Toastr::error("Insufficient stock for \"{$item->item_name} ({$line['var_type']})\" (have " . rtrim(rtrim(number_format($have, 3), '0'), '.') . ')');
                        return back();
                    }
                    continue;
                }

                $needed = _stockQtyForLine($item, $line['qty'], $item->unit, $line['var_type']);
                if ($needed > (float) $item->stock) {
                    Toastr::error("Insufficient main-store stock for \"{$item->item_name}\" (have " . rtrim(rtrim(number_format((float) $item->stock, 3), '0'), '.') . ')');
                    return back();
                }
            }
        }

        DB::beginTransaction();
        try {
            $serial = DB::table('pos_stock_gatepass')->where('store_id', $storeId)->count() + 1;
            $gpNo = 'GP-' . str_pad((string) $serial, 4, '0', STR_PAD_LEFT);
            $header = [
                'store_id'    => $storeId,
                'branch_id'   => $branchId,
                'gatepass_no' => $gpNo,
                'note'        => $request->input('note'),
                'created_by'  => auth('vendor')->id() ?? auth('vendor_employee')->id(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
            if ($this->gatepassHasSourceColumn()) {
                $header['from_branch_id'] = $fromBranch?->id;
            }
            $gatepassId = DB::table('pos_stock_gatepass')->insertGetId($header);

            foreach ($lines as $line) {
                $itemId = $line['item_id'];
                $varType = $line['var_type'];
                $qty = $line['qty'];

                $item = InventoryItem::where('id', $itemId)->where('store_id', $storeId)->first();

                if ($fromBranch) {
                    // Branch to branch moves stock sideways: the store's total is unchanged, so
                    // inventory_items.stock must not be touched. The quantity is already in the
                    // item's own unit — a branch holds no variations to convert from.
                    $branchQty = $qty;
                    DB::table('pos_branch_stock')
                        ->where('branch_id', $fromBranch->id)->where('inventory_item_id', $itemId)
                        ->update(['stock' => DB::raw('GREATEST(stock - ' . $branchQty . ', 0)'), 'updated_at' => now()]);
                } else {
                    // Deduct from main store through the shared helper, so a transfer takes exactly
                    // what the same line would take on a sale — one deduction, in the right place for
                    // the item's stock type. Deducting the variation and the umbrella separately, as
                    // this did before, took the quantity out twice.
                    _decrementInventoryStock($itemId, $qty, $item->unit, $varType);

                    // …and add to the branch. Branch stock is held in the item's own unit, so a
                    // measured pack moves its converted weight, not its pack count.
                    $branchQty = _stockQtyForLine($item, $qty, $item->unit, $varType);
                }

                $existing = DB::table('pos_branch_stock')->where('branch_id', $branchId)->where('inventory_item_id', $itemId)->first();
                if ($existing) {
                    DB::table('pos_branch_stock')->where('id', $existing->id)->update(['stock' => DB::raw('stock + ' . $branchQty), 'updated_at' => now()]);
                } else {
                    DB::table('pos_branch_stock')->insert(['branch_id' => $branchId, 'inventory_item_id' => $itemId, 'store_id' => $storeId, 'stock' => $branchQty, 'created_at' => now(), 'updated_at' => now()]);
                }

                DB::table('pos_stock_gatepass_items')->insert([
                    'gatepass_id' => $gatepassId,
                    'inventory_item_id' => $itemId,
                    'variation_type' => $varType,
                    'qty' => $qty,
                    'created_at' => now()
                ]);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Toastr::error('Transfer failed: ' . $th->getMessage());
            return back();
        }

        Toastr::success('Stock transferred from ' . ($fromBranch->name ?? 'Main Store')
            . ' to ' . $branch->name . ' (' . $gpNo . ')');
        return redirect()->route('vendor.retail-pos.gatepass.print', $gatepassId);
    }

    // Bulk-delete gatepasses. Each delete reverses its transfer: stock returns to the main
    // store and is deducted back from the branch (clamped at 0).
    public function gatepassDelete(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();

        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        if (empty($ids)) {
            Toastr::error('Select at least one gatepass to delete');
            return back();
        }

        $gatepasses = DB::table('pos_stock_gatepass')->where('store_id', $storeId)->whereIn('id', $ids)->get();
        if ($gatepasses->isEmpty()) {
            Toastr::error('No matching gatepass found');
            return back();
        }

        DB::beginTransaction();
        try {
            foreach ($gatepasses as $gp) {
                $lines = DB::table('pos_stock_gatepass_items')->where('gatepass_id', $gp->id)->get();
                foreach ($lines as $line) {
                    $qty = (float) $line->qty;
                    $itemId = $line->inventory_item_id;
                    $varType = $line->variation_type ?? null;

                    $item = InventoryItem::where('id', $itemId)->where('store_id', $storeId)->first();
                    $fromBranchId = $gp->from_branch_id ?? null;

                    if ($fromBranchId) {
                        // A branch-to-branch transfer never touched the main store, so reversing it
                        // must not either — the quantity simply goes back the way it came, in the
                        // item's own unit.
                        $branchQty = $qty;
                        $existing = DB::table('pos_branch_stock')
                            ->where('branch_id', $fromBranchId)->where('inventory_item_id', $itemId)->first();
                        if ($existing) {
                            DB::table('pos_branch_stock')->where('id', $existing->id)
                                ->update(['stock' => DB::raw('stock + ' . $branchQty), 'updated_at' => now()]);
                        } else {
                            DB::table('pos_branch_stock')->insert([
                                'branch_id' => $fromBranchId, 'inventory_item_id' => $itemId,
                                'store_id' => $storeId, 'stock' => $branchQty,
                                'created_at' => now(), 'updated_at' => now(),
                            ]);
                        }
                    } else {
                        // Return to main store — the exact mirror of the transfer, through the same
                        // helper, so what comes back is what went out.
                        if ($item) {
                            _incrementInventoryStock($itemId, $qty, $item->unit, $varType);
                        }
                        $branchQty = $item ? _stockQtyForLine($item, $qty, $item->unit, $varType) : $qty;
                    }

                    // …and pull it back out of the destination branch, in the unit the branch holds.
                    DB::table('pos_branch_stock')
                        ->where('branch_id', $gp->branch_id)->where('inventory_item_id', $itemId)
                        ->update(['stock' => DB::raw('GREATEST(stock - ' . $branchQty . ', 0)'), 'updated_at' => now()]);
                }
                DB::table('pos_stock_gatepass_items')->where('gatepass_id', $gp->id)->delete();
                DB::table('pos_stock_gatepass')->where('id', $gp->id)->where('store_id', $storeId)->delete();
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Toastr::error('Delete failed: ' . $th->getMessage());
            return back();
        }

        Toastr::success(count($gatepasses) . ' gatepass(es) deleted and stock reversed');
        return back();
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
        $fromBranch = !empty($gatepass->from_branch_id) ? Branch::find($gatepass->from_branch_id) : null;
        $items = DB::table('pos_stock_gatepass_items as gi')
            ->leftJoin('inventory_items as ii', 'ii.id', '=', 'gi.inventory_item_id')
            ->leftJoin('units as u', 'u.id', '=', 'ii.unit')
            ->where('gi.gatepass_id', $id)
            ->get(['gi.inventory_item_id', 'gi.qty', 'gi.variation_type', 'ii.item_name', 'ii.sku_id', 'u.unit as unit_label']);

        // The stored qty is in whatever the line was entered in — pack counts for a measured
        // variation. Pairing that number with the item's base unit read as "4 kg" for 4 × 100gm,
        // which is four times what actually moved. Resolve each line to its real quantity here.
        $lineItems = InventoryItem::whereIn('id', $items->pluck('inventory_item_id')->filter()->unique())
            ->get()->keyBy('id');

        foreach ($items as $row) {
            $inv = $lineItems->get($row->inventory_item_id);
            $row->qty_label = rtrim(rtrim(number_format((float) $row->qty, 3), '0'), '.') . ' ' . $row->unit_label;

            if (!$inv || !$row->variation_type) {
                continue;
            }

            if (_variationMode($inv) === 'countable') {
                // Countable variations are units in their own right, not a weight.
                $row->qty_label = rtrim(rtrim(number_format((float) $row->qty, 3), '0'), '.') . ' × ' . $row->variation_type;
                continue;
            }

            $base = _stockQtyForLine($inv, (float) $row->qty, $inv->unit, $row->variation_type);
            if (abs($base - (float) $row->qty) > 0.0001) {
                $row->qty_label = rtrim(rtrim(number_format((float) $row->qty, 3), '0'), '.')
                    . ' × ' . $row->variation_type
                    . ' (' . rtrim(rtrim(number_format($base, 3), '0'), '.') . ' ' . $row->unit_label . ')';
            }
        }

        $store = Helpers::get_store_data();
        return view('posretail::vendor.retail-pos.gatepass-print', compact('gatepass', 'branch', 'fromBranch', 'items', 'store'));
    }

    // ── Damaged / Theft stock write-off ─────────────────────────────────────────
    public function writeoff(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $branches = Branch::where('store_id', $storeId)->orderBy('name')->get();

        $records = DB::table('pos_stock_writeoff as w')
            ->leftJoin('inventory_items as ii', 'ii.id', '=', 'w.inventory_item_id')
            ->leftJoin('branches as b', 'b.id', '=', 'w.branch_id')
            ->where('w.store_id', $storeId)
            ->orderByDesc('w.id')->limit(100)
            ->get(['w.id', 'w.type', 'w.qty', 'w.note', 'w.status', 'w.manager_note', 'w.branch_id', 'w.created_at', 'ii.item_name', 'ii.sku_id', 'b.name as branch_name']);

        $dispositions = DB::table('pos_writeoff_dispositions')
            ->whereIn('writeoff_id', $records->pluck('id'))->get()->groupBy('writeoff_id');

        // "Select or type" suggestions for damage category (previously used values).
        $damageCategories = DB::table('pos_writeoff_dispositions')
            ->where('disposition', 'return_supplier')->whereNotNull('damage_category')
            ->where('damage_category', '!=', '')->distinct()->pluck('damage_category');

        foreach ($records as $r) {
            $r->can_approve = $r->status === 'pending' && $this->canApproveWriteoff($r->branch_id);
        }

        return view('posretail::vendor.retail-pos.writeoff', compact('branches', 'records', 'dispositions', 'damageCategories'));
    }

    // select2 AJAX source for the write-off item picker. Stock reflects the chosen
    // location (a branch's pool, else the main store).
    public function writeoffItems(Request $request)
    {
        $storeId = $this->storeId();
        $term = trim((string) $request->get('q', ''));
        $branchId = (int) $request->get('branch_id') ?: null;

        $varItemIds = $this->itemIdsByVariationSku($storeId, $term, true);
        $items = InventoryItem::with('itemunit')->where('store_id', $storeId)->where('item_type', 'product')
            ->when($term !== '', fn($q) => $q->where(fn($w) => $w
                ->where('item_name', 'like', "%{$term}%")
                ->orWhere('sku_id', 'like', "%{$term}%")
                ->orWhere('barcode', $term)
                ->when(!empty($varItemIds), fn($ww) => $ww->orWhereIn('id', $varItemIds))))
            ->orderBy('item_name')->limit(30)->get(['id', 'item_name', 'sku_id', 'stock', 'unit']);

        $branchStock = collect();
        $loc = 'main store';
        if ($branchId) {
            $branchStock = DB::table('pos_branch_stock')->where('branch_id', $branchId)
                ->whereIn('inventory_item_id', $items->pluck('id'))->pluck('stock', 'inventory_item_id');
            $loc = optional(Branch::where('store_id', $storeId)->find($branchId))->name ?? 'branch';
        }

        $fmt = fn($n) => rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.');
        return response()->json([
            'results' => $items->map(function ($i) use ($branchId, $branchStock, $fmt, $loc) {
                $stock = $branchId ? (float) ($branchStock[$i->id] ?? 0) : (float) $i->stock;
                return [
                    'id'        => $i->id,
                    'name'      => $i->item_name . ($i->sku_id ? ' (' . $i->sku_id . ')' : ''),
                    'stock'     => $fmt($stock),
                    'stock_num' => $stock,
                    'unit'      => optional($i->itemunit)->unit ?? '',
                    'loc'       => $loc,
                ];
            }),
        ]);
    }

    public function writeoffStore(Request $request)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();

        $itemId = (int) $request->input('inventory_item_id');
        $branchId = (int) $request->input('branch_id') ?: null;
        $type = in_array($request->input('type'), ['theft', 'leaked'], true) ? $request->input('type') : 'damaged';
        $qty = (float) $request->input('qty');

        $item = InventoryItem::where('id', $itemId)->where('store_id', $storeId)->first();
        if (!$item) {
            Toastr::error('Select a valid item');
            return back();
        }
        if ($qty <= 0) {
            Toastr::error('Enter a quantity greater than 0');
            return back();
        }
        if ($branchId && !Branch::where('store_id', $storeId)->where('id', $branchId)->exists()) {
            Toastr::error('Select a valid branch');
            return back();
        }

        // Available stock at the chosen location (branch pool, else main store).
        $available = $branchId ? $this->branchItemStock($branchId, $itemId) : (float) $item->stock;
        if ($qty > $available + 0.0001) {
            Toastr::error('Quantity exceeds available stock (have ' . rtrim(rtrim(number_format($available, 3), '0'), '.') . ')');
            return back();
        }

        DB::beginTransaction();
        try {
            if ($branchId) {
                DB::table('pos_branch_stock')
                    ->where('branch_id', $branchId)->where('inventory_item_id', $itemId)
                    ->update(['stock' => DB::raw('GREATEST(stock - ' . $qty . ', 0)'), 'updated_at' => now()]);
            } else {
                InventoryItem::where('id', $itemId)->where('store_id', $storeId)
                    ->update(['stock' => DB::raw('GREATEST(stock - ' . $qty . ', 0)')]);
            }

            DB::table('pos_stock_writeoff')->insert([
                'store_id'          => $storeId,
                'branch_id'         => $branchId,
                'inventory_item_id' => $itemId,
                'type'              => $type,
                'qty'               => $qty,
                'note'              => $request->input('note'),
                'status'            => 'pending',
                'created_by'        => auth('vendor')->id() ?? auth('vendor_employee')->id(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Toastr::error('Write-off failed: ' . $th->getMessage());
            return back();
        }

        $this->logAudit('writeoff', $item->item_name, ucfirst($type) . ' ' . rtrim(rtrim(number_format($qty, 3), '0'), '.') . ($branchId ? ' (branch)' : ' (main store)'));
        Toastr::success(ucfirst($type) . ' request submitted for manager approval.');
        return back();
    }

    // Owner / the branch's assigned Branch Manager can approve write-off requests.
    private function canApproveWriteoff($branchId): bool
    {
        if (auth('vendor')->check()) {
            return true; // owner
        }
        if (!$branchId) {
            return false; // main-store write-offs → owner only
        }
        $managerId = (int) (Branch::where('store_id', $this->storeId())->where('id', $branchId)->value('branch_manager_id') ?? 0);
        return $managerId && $managerId === (int) auth('vendor_employee')->id();
    }

    private function restoreWriteoffStock($storeId, $branchId, $itemId, $qty): void
    {
        if ($qty <= 0) {
            return;
        }
        if ($branchId) {
            DB::table('pos_branch_stock')->where('branch_id', $branchId)->where('inventory_item_id', $itemId)
                ->update(['stock' => DB::raw('stock + ' . $qty), 'updated_at' => now()]);
        } else {
            // Mass update bypasses model events, so the stock_base dual-write would be skipped.
            // Go through the model instead — one row, and the saving hook keeps both in step.
            $item = InventoryItem::where('id', $itemId)->where('store_id', $storeId)->first();
            if ($item) {
                $item->stock = (float) $item->stock + $qty;
                $item->save();
            }
        }
    }

    // Manager decision on a pending write-off: reject (restore stock) or accept (split dispositions).
    public function writeoffDecide(Request $request, $id)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $rec = DB::table('pos_stock_writeoff')->where('store_id', $storeId)->where('id', $id)->first();
        if (!$rec) {
            Toastr::error('Request not found');
            return back();
        }
        if ($rec->status !== 'pending') {
            Toastr::error('This request has already been decided.');
            return back();
        }
        if (!$this->canApproveWriteoff($rec->branch_id)) {
            Toastr::error('Only the Owner or the Branch Manager can approve this request.');
            return back();
        }

        $action = $request->input('action');
        $deciderId = auth('vendor')->id() ?? auth('vendor_employee')->id();
        $deciderRole = auth('vendor')->check() ? 'owner' : 'branch_manager';

        if ($action === 'reject') {
            DB::beginTransaction();
            try {
                // Deducted at request time — return it to normal inventory.
                $this->restoreWriteoffStock($storeId, $rec->branch_id, $rec->inventory_item_id, (float) $rec->qty);
                DB::table('pos_stock_writeoff')->where('id', $id)->update([
                    'status' => 'rejected', 'manager_note' => $request->input('manager_note'),
                    'decided_by' => $deciderId, 'decided_by_role' => $deciderRole, 'decided_at' => now(), 'updated_at' => now(),
                ]);
                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                Toastr::error('Reject failed: ' . $th->getMessage());
                return back();
            }
            Toastr::success('Request rejected — stock returned to inventory.');
            return back();
        }

        // ACCEPT — validate split dispositions sum to the request qty.
        $dispositions = $request->input('disp', []); // [['type'=>..,'qty'=>..,'damage_category'=>..,'reason'=>..], ...]
        $rows = [];
        $sum = 0.0;
        foreach ((array) $dispositions as $i => $d) {
            $dtype = $d['type'] ?? null;
            $dqty  = (float) ($d['qty'] ?? 0);
            if (!in_array($dtype, ['return_supplier', 'resell', 'scrap'], true) || $dqty <= 0) {
                continue;
            }
            $sum += $dqty;
            $rows[$i] = ['type' => $dtype, 'qty' => $dqty,
                'damage_category' => $d['damage_category'] ?? null, 'reason' => $d['reason'] ?? null];
        }
        if (!count($rows)) {
            Toastr::error('Add at least one disposition.');
            return back();
        }
        if (abs($sum - (float) $rec->qty) > 0.001) {
            Toastr::error('Disposition quantities must total ' . rtrim(rtrim(number_format((float) $rec->qty, 3), '0'), '.') . '.');
            return back();
        }

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $r) {
                $attachment = null;
                if ($request->hasFile("disp_file.$i")) {
                    $f = $request->file("disp_file.$i");
                    $attachment = Helpers::upload('writeoff/', $f->getClientOriginalExtension(), $f);
                }
                // Convert to resell → stock goes back to inventory; supplier/scrap stay out.
                if ($r['type'] === 'resell') {
                    $this->restoreWriteoffStock($storeId, $rec->branch_id, $rec->inventory_item_id, $r['qty']);
                }
                DB::table('pos_writeoff_dispositions')->insert([
                    'writeoff_id' => $id, 'disposition' => $r['type'], 'qty' => $r['qty'],
                    'damage_category' => $r['type'] === 'return_supplier' ? $r['damage_category'] : null,
                    'reason' => $r['reason'], 'attachment' => $attachment,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('pos_stock_writeoff')->where('id', $id)->update([
                'status' => 'accepted', 'manager_note' => $request->input('manager_note'),
                'decided_by' => $deciderId, 'decided_by_role' => $deciderRole, 'decided_at' => now(), 'updated_at' => now(),
            ]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Toastr::error('Approval failed: ' . $th->getMessage());
            return back();
        }
        Toastr::success('Request accepted and dispositions recorded.');
        return back();
    }

    // Deleting a write-off restores the removed stock (corrects a mistaken entry).
    public function writeoffDelete(Request $request, $id)
    {
        $this->ensureSchema();
        $storeId = $this->storeId();
        $rec = DB::table('pos_stock_writeoff')->where('store_id', $storeId)->where('id', $id)->first();
        if (!$rec) {
            Toastr::error('Record not found');
            return back();
        }
        if (($rec->status ?? 'pending') !== 'pending') {
            Toastr::error('Only pending requests can be deleted.');
            return back();
        }

        DB::beginTransaction();
        try {
            $qty = (float) $rec->qty;
            if ($rec->branch_id) {
                DB::table('pos_branch_stock')
                    ->where('branch_id', $rec->branch_id)->where('inventory_item_id', $rec->inventory_item_id)
                    ->update(['stock' => DB::raw('stock + ' . $qty), 'updated_at' => now()]);
            } else {
                InventoryItem::where('id', $rec->inventory_item_id)->where('store_id', $storeId)
                    ->update(['stock' => DB::raw('stock + ' . $qty)]);
            }
            DB::table('pos_stock_writeoff')->where('id', $id)->where('store_id', $storeId)->delete();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Toastr::error('Delete failed: ' . $th->getMessage());
            return back();
        }

        Toastr::success('Write-off reversed and stock restored');
        return back();
    }
}
