<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\CentralLogics\Helpers;
use App\Models\DoctorProfile;
use App\Models\LabInvoice;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabOrderResult;
use App\Models\LabReagent;
use App\Models\LabTest;
use App\Models\LabTestParameter;
use App\Models\ManualInvoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Services\HmisWhatsAppShare;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LabController extends Controller
{
    private function storeId()
    {
        return Helpers::get_store_id();
    }

    private function actor(): array
    {
        $emp = auth('vendor_employee')->user();
        if ($emp) {
            return [$emp->id, 'vendor_employee', trim(($emp->f_name ?? '') . ' ' . ($emp->l_name ?? ''))];
        }
        $v = auth('vendor')->user();
        return [auth('vendor')->id(), 'vendor', trim(($v->f_name ?? 'Lab') . ' ' . ($v->l_name ?? 'Staff'))];
    }

    // ── Schema (guarded, idempotent — no migration files) ──────────────────
    private function ensureLabSchema(): void
    {
        if (!Schema::hasTable('lab_tests')) {
            DB::statement("CREATE TABLE `lab_tests` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL,
                `name` VARCHAR(200) NOT NULL,
                `code` VARCHAR(60) NULL,
                `department` VARCHAR(80) NULL,
                `sample_type` VARCHAR(80) NULL,
                `price` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `tat_text` VARCHAR(60) NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `lab_tests_store_idx` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); 
        } else {
            // Patch columns that may be missing in an older version of the table.
            if (!Schema::hasColumn('lab_tests', 'name'))
                DB::statement("ALTER TABLE `lab_tests` ADD COLUMN `name` VARCHAR(200) NOT NULL DEFAULT '' AFTER `store_id`");
            if (!Schema::hasColumn('lab_tests', 'code'))
                DB::statement("ALTER TABLE `lab_tests` ADD COLUMN `code` VARCHAR(60) NULL");
            if (!Schema::hasColumn('lab_tests', 'department'))
                DB::statement("ALTER TABLE `lab_tests` ADD COLUMN `department` VARCHAR(80) NULL");
            if (!Schema::hasColumn('lab_tests', 'sample_type'))
                DB::statement("ALTER TABLE `lab_tests` ADD COLUMN `sample_type` VARCHAR(80) NULL");
            if (!Schema::hasColumn('lab_tests', 'price'))
                DB::statement("ALTER TABLE `lab_tests` ADD COLUMN `price` DECIMAL(12,2) NOT NULL DEFAULT 0");
            if (!Schema::hasColumn('lab_tests', 'tat_text'))
                DB::statement("ALTER TABLE `lab_tests` ADD COLUMN `tat_text` VARCHAR(60) NULL");
            if (!Schema::hasColumn('lab_tests', 'is_active'))
                DB::statement("ALTER TABLE `lab_tests` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1");
            // The standing note printed under this test's results — what the values mean, what
            // raises or lowers them, what to correlate against. Written once against the test
            // rather than retyped on every report, which is how it stays consistent between them.
            if (!Schema::hasColumn('lab_tests', 'interpretation'))
                DB::statement("ALTER TABLE `lab_tests` ADD COLUMN `interpretation` TEXT NULL");
            if (!Schema::hasColumn('lab_tests', 'created_at'))
                DB::statement("ALTER TABLE `lab_tests` ADD COLUMN `created_at` TIMESTAMP NULL");
            if (!Schema::hasColumn('lab_tests', 'updated_at'))
            DB::statement("ALTER TABLE `lab_tests` ADD COLUMN `updated_at` TIMESTAMP NULL");
        }

        // The copy that actually prints, taken from the catalogue when the order is placed so a
        // later edit to the catalogue never rewrites a report that has already gone out, and so a
        // pathologist can tailor the wording to one patient.
        if (Schema::hasTable('lab_order_items') && !Schema::hasColumn('lab_order_items', 'interpretation')) {
            DB::statement("ALTER TABLE `lab_order_items` ADD COLUMN `interpretation` TEXT NULL");
        }

        if (!Schema::hasTable('lab_test_parameters')) {
            DB::statement("CREATE TABLE `lab_test_parameters` ( 
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `lab_test_id` BIGINT UNSIGNED NOT NULL,
                `name` VARCHAR(160) NOT NULL,
                `unit` VARCHAR(40) NULL,
                `normal_low` DECIMAL(12,3) NULL,
                `normal_high` DECIMAL(12,3) NULL,
                `ref_range_text` VARCHAR(120) NULL,
                `critical_low` DECIMAL(12,3) NULL,
                `critical_high` DECIMAL(12,3) NULL,
                `sort_order` INT NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `lab_param_test_idx` (`lab_test_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            if (!Schema::hasColumn('lab_test_parameters', 'name'))        DB::statement("ALTER TABLE `lab_test_parameters` ADD COLUMN `name` VARCHAR(160) NOT NULL DEFAULT ''");
            if (!Schema::hasColumn('lab_test_parameters', 'unit'))         DB::statement("ALTER TABLE `lab_test_parameters` ADD COLUMN `unit` VARCHAR(40) NULL");
            if (!Schema::hasColumn('lab_test_parameters', 'normal_low'))   DB::statement("ALTER TABLE `lab_test_parameters` ADD COLUMN `normal_low` DECIMAL(12,3) NULL");
            if (!Schema::hasColumn('lab_test_parameters', 'normal_high'))  DB::statement("ALTER TABLE `lab_test_parameters` ADD COLUMN `normal_high` DECIMAL(12,3) NULL");
            if (!Schema::hasColumn('lab_test_parameters', 'ref_range_text')) DB::statement("ALTER TABLE `lab_test_parameters` ADD COLUMN `ref_range_text` VARCHAR(120) NULL");
            if (!Schema::hasColumn('lab_test_parameters', 'critical_low')) DB::statement("ALTER TABLE `lab_test_parameters` ADD COLUMN `critical_low` DECIMAL(12,3) NULL");
            if (!Schema::hasColumn('lab_test_parameters', 'critical_high')) DB::statement("ALTER TABLE `lab_test_parameters` ADD COLUMN `critical_high` DECIMAL(12,3) NULL");
            if (!Schema::hasColumn('lab_test_parameters', 'sort_order'))   DB::statement("ALTER TABLE `lab_test_parameters` ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0");
            if (!Schema::hasColumn('lab_test_parameters', 'created_at'))   DB::statement("ALTER TABLE `lab_test_parameters` ADD COLUMN `created_at` TIMESTAMP NULL");
            if (!Schema::hasColumn('lab_test_parameters', 'updated_at'))   DB::statement("ALTER TABLE `lab_test_parameters` ADD COLUMN `updated_at` TIMESTAMP NULL");
        }
 
        if (!Schema::hasTable('lab_orders')) {
            DB::statement("CREATE TABLE `lab_orders` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL,
                `order_no` VARCHAR(40) NULL,
                `patient_id` BIGINT UNSIGNED NULL,
                `doctor_profile_id` BIGINT UNSIGNED NULL,
                `prescription_id` BIGINT UNSIGNED NULL,
                `opd_id` BIGINT UNSIGNED NULL,
                `source` VARCHAR(20) NULL,
                `department` VARCHAR(30) NULL,
                `priority` VARCHAR(20) NOT NULL DEFAULT 'routine',
                `status` VARCHAR(20) NOT NULL DEFAULT 'ordered',
                `sample_type` VARCHAR(80) NULL,
                `clinical_notes` TEXT NULL,
                `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `referred_by` VARCHAR(150) NULL,
                `technician_notes` TEXT NULL,
                `analysed_by` VARCHAR(120) NULL,
                `verified_by_name` VARCHAR(120) NULL,
                `collected_at` TIMESTAMP NULL,
                `reported_at` TIMESTAMP NULL,
                `created_by` BIGINT UNSIGNED NULL,
                `created_by_type` VARCHAR(30) NULL,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `lab_orders_store_idx` (`store_id`), KEY `lab_orders_status_idx` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            if (!Schema::hasColumn('lab_orders', 'order_no'))          DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `order_no` VARCHAR(40) NULL");
            if (!Schema::hasColumn('lab_orders', 'prescription_id'))   DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `prescription_id` BIGINT UNSIGNED NULL");
            if (!Schema::hasColumn('lab_orders', 'opd_id'))            DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `opd_id` BIGINT UNSIGNED NULL");
            if (!Schema::hasColumn('lab_orders', 'source'))            DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `source` VARCHAR(20) NULL");
            if (!Schema::hasColumn('lab_orders', 'department'))        DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `department` VARCHAR(30) NULL");
            if (!Schema::hasColumn('lab_orders', 'priority'))          DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `priority` VARCHAR(20) NOT NULL DEFAULT 'routine'");
            if (!Schema::hasColumn('lab_orders', 'status'))            DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'ordered'");
            if (!Schema::hasColumn('lab_orders', 'sample_type'))       DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `sample_type` VARCHAR(80) NULL");
            // An order can need several samples (e.g. blood + urine) — stored comma-separated,
            // so widen the original single-value column.
            if (Schema::hasColumn('lab_orders', 'sample_type')) {
                $sampleLen = DB::table('information_schema.columns')
                    ->where('table_schema', DB::getDatabaseName())
                    ->where('table_name', 'lab_orders')
                    ->where('column_name', 'sample_type')
                    ->value('character_maximum_length');
                if ($sampleLen !== null && (int) $sampleLen < 255) {
                    DB::statement("ALTER TABLE `lab_orders` MODIFY `sample_type` VARCHAR(255) NULL");
                }
            }
            if (!Schema::hasColumn('lab_orders', 'clinical_notes'))    DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `clinical_notes` TEXT NULL");
            if (!Schema::hasColumn('lab_orders', 'total_amount'))      DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0");
            if (!Schema::hasColumn('lab_orders', 'referred_by'))       DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `referred_by` VARCHAR(150) NULL");
            if (!Schema::hasColumn('lab_orders', 'technician_notes'))  DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `technician_notes` TEXT NULL");
            if (!Schema::hasColumn('lab_orders', 'analysed_by'))       DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `analysed_by` VARCHAR(120) NULL");
            if (!Schema::hasColumn('lab_orders', 'verified_by_name'))  DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `verified_by_name` VARCHAR(120) NULL");
            if (!Schema::hasColumn('lab_orders', 'collected_at'))      DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `collected_at` TIMESTAMP NULL");
            if (!Schema::hasColumn('lab_orders', 'reported_at'))       DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `reported_at` TIMESTAMP NULL");
            if (!Schema::hasColumn('lab_orders', 'created_by'))        DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `created_by` BIGINT UNSIGNED NULL");
            if (!Schema::hasColumn('lab_orders', 'created_by_type'))   DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `created_by_type` VARCHAR(30) NULL");
            if (!Schema::hasColumn('lab_orders', 'created_at'))        DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `created_at` TIMESTAMP NULL");
            if (!Schema::hasColumn('lab_orders', 'updated_at'))        DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `updated_at` TIMESTAMP NULL");
        }

        // Referral labs. The table was written on the assumption that every order runs on the
        // bench here, which is why these arrive as a patch rather than in the CREATE above — and
        // why they default to not-outsourced: every order that predates them was run in-house.
        //
        // The phone matters more than it looks. It is the number a handover verification code is
        // sent to, so it is the number that decides whether a stranger at the counter can be
        // caught, and it is read from here on the server rather than from anything typed at the
        // time of the handover.
        if (Schema::hasTable('lab_orders')) {
            if (!Schema::hasColumn('lab_orders', 'external_lab_id'))    DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `external_lab_id` BIGINT UNSIGNED NULL");
            if (!Schema::hasColumn('lab_orders', 'external_lab_name'))  DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `external_lab_name` VARCHAR(190) NULL");
            if (!Schema::hasColumn('lab_orders', 'external_lab_phone')) DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `external_lab_phone` VARCHAR(40) NULL");
            if (!Schema::hasColumn('lab_orders', 'is_outsourced'))      DB::statement("ALTER TABLE `lab_orders` ADD COLUMN `is_outsourced` TINYINT(1) NOT NULL DEFAULT 0");
        }

        if (!Schema::hasTable('lab_order_items')) {
            DB::statement("CREATE TABLE `lab_order_items` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `lab_order_id` BIGINT UNSIGNED NOT NULL,
                `lab_test_id` BIGINT UNSIGNED NULL,
                `test_name` VARCHAR(200) NOT NULL,
                `department` VARCHAR(80) NULL,
                `price` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `lab_order_items_order_idx` (`lab_order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            if (!Schema::hasColumn('lab_order_items', 'lab_test_id'))  DB::statement("ALTER TABLE `lab_order_items` ADD COLUMN `lab_test_id` BIGINT UNSIGNED NULL");
            if (!Schema::hasColumn('lab_order_items', 'test_name'))    DB::statement("ALTER TABLE `lab_order_items` ADD COLUMN `test_name` VARCHAR(200) NOT NULL DEFAULT ''");
            if (!Schema::hasColumn('lab_order_items', 'department'))   DB::statement("ALTER TABLE `lab_order_items` ADD COLUMN `department` VARCHAR(80) NULL");
            if (!Schema::hasColumn('lab_order_items', 'price'))        DB::statement("ALTER TABLE `lab_order_items` ADD COLUMN `price` DECIMAL(12,2) NOT NULL DEFAULT 0");
            if (!Schema::hasColumn('lab_order_items', 'status'))       DB::statement("ALTER TABLE `lab_order_items` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'pending'");
            if (!Schema::hasColumn('lab_order_items', 'created_at'))   DB::statement("ALTER TABLE `lab_order_items` ADD COLUMN `created_at` TIMESTAMP NULL");
            if (!Schema::hasColumn('lab_order_items', 'updated_at'))   DB::statement("ALTER TABLE `lab_order_items` ADD COLUMN `updated_at` TIMESTAMP NULL");
        }

        if (!Schema::hasTable('lab_order_results')) {
            DB::statement("CREATE TABLE `lab_order_results` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `lab_order_id` BIGINT UNSIGNED NOT NULL,
                `lab_order_item_id` BIGINT UNSIGNED NOT NULL,
                `parameter_name` VARCHAR(160) NOT NULL,
                `unit` VARCHAR(40) NULL,
                `normal_low` DECIMAL(12,3) NULL,
                `normal_high` DECIMAL(12,3) NULL,
                `ref_range_text` VARCHAR(120) NULL,
                `critical_low` DECIMAL(12,3) NULL,
                `critical_high` DECIMAL(12,3) NULL,
                `result_value` VARCHAR(120) NULL,
                `result_flag` VARCHAR(10) NULL,
                `is_critical` TINYINT(1) NOT NULL DEFAULT 0,
                `critical_notified_at` TIMESTAMP NULL,
                `critical_notified_to` VARCHAR(120) NULL,
                `sort_order` INT NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `lab_results_order_idx` (`lab_order_id`), KEY `lab_results_item_idx` (`lab_order_item_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            if (!Schema::hasColumn('lab_order_results', 'lab_order_item_id'))   DB::statement("ALTER TABLE `lab_order_results` ADD COLUMN `lab_order_item_id` BIGINT UNSIGNED NOT NULL DEFAULT 0");
            if (!Schema::hasColumn('lab_order_results', 'parameter_name'))      DB::statement("ALTER TABLE `lab_order_results` ADD COLUMN `parameter_name` VARCHAR(160) NOT NULL DEFAULT ''");
            if (!Schema::hasColumn('lab_order_results', 'unit'))                DB::statement("ALTER TABLE `lab_order_results` ADD COLUMN `unit` VARCHAR(40) NULL");
            if (!Schema::hasColumn('lab_order_results', 'normal_low'))          DB::statement("ALTER TABLE `lab_order_results` ADD COLUMN `normal_low` DECIMAL(12,3) NULL");
            if (!Schema::hasColumn('lab_order_results', 'normal_high'))         DB::statement("ALTER TABLE `lab_order_results` ADD COLUMN `normal_high` DECIMAL(12,3) NULL");
            if (!Schema::hasColumn('lab_order_results', 'ref_range_text'))      DB::statement("ALTER TABLE `lab_order_results` ADD COLUMN `ref_range_text` VARCHAR(120) NULL");
            if (!Schema::hasColumn('lab_order_results', 'critical_low'))        DB::statement("ALTER TABLE `lab_order_results` ADD COLUMN `critical_low` DECIMAL(12,3) NULL");
            if (!Schema::hasColumn('lab_order_results', 'critical_high'))       DB::statement("ALTER TABLE `lab_order_results` ADD COLUMN `critical_high` DECIMAL(12,3) NULL");
            if (!Schema::hasColumn('lab_order_results', 'result_value'))        DB::statement("ALTER TABLE `lab_order_results` ADD COLUMN `result_value` VARCHAR(120) NULL");
            if (!Schema::hasColumn('lab_order_results', 'result_flag'))         DB::statement("ALTER TABLE `lab_order_results` ADD COLUMN `result_flag` VARCHAR(10) NULL");
            if (!Schema::hasColumn('lab_order_results', 'is_critical'))         DB::statement("ALTER TABLE `lab_order_results` ADD COLUMN `is_critical` TINYINT(1) NOT NULL DEFAULT 0");
            if (!Schema::hasColumn('lab_order_results', 'critical_notified_at')) DB::statement("ALTER TABLE `lab_order_results` ADD COLUMN `critical_notified_at` TIMESTAMP NULL");
            if (!Schema::hasColumn('lab_order_results', 'critical_notified_to')) DB::statement("ALTER TABLE `lab_order_results` ADD COLUMN `critical_notified_to` VARCHAR(120) NULL");
            if (!Schema::hasColumn('lab_order_results', 'sort_order'))          DB::statement("ALTER TABLE `lab_order_results` ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0");
            if (!Schema::hasColumn('lab_order_results', 'created_at'))          DB::statement("ALTER TABLE `lab_order_results` ADD COLUMN `created_at` TIMESTAMP NULL");
            if (!Schema::hasColumn('lab_order_results', 'updated_at'))          DB::statement("ALTER TABLE `lab_order_results` ADD COLUMN `updated_at` TIMESTAMP NULL");
        }

        if (!Schema::hasTable('lab_reagents')) {
            DB::statement("CREATE TABLE `lab_reagents` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL,
                `name` VARCHAR(160) NOT NULL,
                `machine` VARCHAR(120) NULL,
                `for_test` VARCHAR(120) NULL,
                `expiry_date` DATE NULL,
                `stock` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `min_level` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `unit_label` VARCHAR(40) NULL DEFAULT 'tests',
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `lab_reagents_store_idx` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            if (!Schema::hasColumn('lab_reagents', 'name'))        DB::statement("ALTER TABLE `lab_reagents` ADD COLUMN `name` VARCHAR(160) NOT NULL DEFAULT ''");
            if (!Schema::hasColumn('lab_reagents', 'machine'))     DB::statement("ALTER TABLE `lab_reagents` ADD COLUMN `machine` VARCHAR(120) NULL");
            if (!Schema::hasColumn('lab_reagents', 'for_test'))    DB::statement("ALTER TABLE `lab_reagents` ADD COLUMN `for_test` VARCHAR(120) NULL");
            if (!Schema::hasColumn('lab_reagents', 'expiry_date')) DB::statement("ALTER TABLE `lab_reagents` ADD COLUMN `expiry_date` DATE NULL");
            if (!Schema::hasColumn('lab_reagents', 'stock'))       DB::statement("ALTER TABLE `lab_reagents` ADD COLUMN `stock` DECIMAL(12,2) NOT NULL DEFAULT 0");
            if (!Schema::hasColumn('lab_reagents', 'min_level'))   DB::statement("ALTER TABLE `lab_reagents` ADD COLUMN `min_level` DECIMAL(12,2) NOT NULL DEFAULT 0");
            if (!Schema::hasColumn('lab_reagents', 'unit_label'))  DB::statement("ALTER TABLE `lab_reagents` ADD COLUMN `unit_label` VARCHAR(40) NULL DEFAULT 'tests'");
            if (!Schema::hasColumn('lab_reagents', 'created_at'))  DB::statement("ALTER TABLE `lab_reagents` ADD COLUMN `created_at` TIMESTAMP NULL");
            if (!Schema::hasColumn('lab_reagents', 'updated_at'))  DB::statement("ALTER TABLE `lab_reagents` ADD COLUMN `updated_at` TIMESTAMP NULL");
        }

        if (!Schema::hasTable('lab_invoices')) {
            DB::statement("CREATE TABLE `lab_invoices` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NULL,
                `lab_order_id` BIGINT UNSIGNED NULL,
                `invoice_no` VARCHAR(50) NULL,
                `patient_id` BIGINT UNSIGNED NULL,
                `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `insurance_provider` VARCHAR(120) NULL,
                `insurance_covered` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `discount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `payable` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `payment_mode` VARCHAR(40) NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'finalized',
                `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`), KEY `lab_invoices_store_idx` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            if (!Schema::hasColumn('lab_invoices', 'lab_order_id'))        DB::statement("ALTER TABLE `lab_invoices` ADD COLUMN `lab_order_id` BIGINT UNSIGNED NULL");
            if (!Schema::hasColumn('lab_invoices', 'invoice_no'))          DB::statement("ALTER TABLE `lab_invoices` ADD COLUMN `invoice_no` VARCHAR(50) NULL");
            if (!Schema::hasColumn('lab_invoices', 'patient_id'))          DB::statement("ALTER TABLE `lab_invoices` ADD COLUMN `patient_id` BIGINT UNSIGNED NULL");
            if (!Schema::hasColumn('lab_invoices', 'subtotal'))            DB::statement("ALTER TABLE `lab_invoices` ADD COLUMN `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0");
            if (!Schema::hasColumn('lab_invoices', 'insurance_provider'))  DB::statement("ALTER TABLE `lab_invoices` ADD COLUMN `insurance_provider` VARCHAR(120) NULL");
            if (!Schema::hasColumn('lab_invoices', 'insurance_covered'))   DB::statement("ALTER TABLE `lab_invoices` ADD COLUMN `insurance_covered` DECIMAL(12,2) NOT NULL DEFAULT 0");
            if (!Schema::hasColumn('lab_invoices', 'discount'))            DB::statement("ALTER TABLE `lab_invoices` ADD COLUMN `discount` DECIMAL(12,2) NOT NULL DEFAULT 0");
            if (!Schema::hasColumn('lab_invoices', 'payable'))             DB::statement("ALTER TABLE `lab_invoices` ADD COLUMN `payable` DECIMAL(12,2) NOT NULL DEFAULT 0");
            if (!Schema::hasColumn('lab_invoices', 'payment_mode'))        DB::statement("ALTER TABLE `lab_invoices` ADD COLUMN `payment_mode` VARCHAR(40) NULL");
            if (!Schema::hasColumn('lab_invoices', 'status'))              DB::statement("ALTER TABLE `lab_invoices` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'finalized'");
            if (!Schema::hasColumn('lab_invoices', 'created_at'))          DB::statement("ALTER TABLE `lab_invoices` ADD COLUMN `created_at` TIMESTAMP NULL");
            if (!Schema::hasColumn('lab_invoices', 'updated_at'))          DB::statement("ALTER TABLE `lab_invoices` ADD COLUMN `updated_at` TIMESTAMP NULL");
        }
    }

    public const FEATURES = [
        'lab_worklist' => ['Lab Worklist', ['view', 'edit']],
        'lab_result'   => ['Lab Result Entry', ['view', 'edit']],
        'lab_report'   => ['Lab Reports', ['view', 'send']],
        'lab_critical' => ['Lab Critical Values', ['view', 'notify']],
        'lab_order'    => ['Lab Order Test', ['view', 'add']],
        'lab_reagent'  => ['Lab Reagents', ['view', 'add', 'edit', 'delete']],
        'lab_history'  => ['Lab Test History', ['view']],
        'lab_billing'  => ['Lab Billing', ['view', 'add']],
        'lab_catalog'  => ['Lab Test Catalog', ['view', 'add', 'edit', 'delete']],
    ];
 
    public function ensureLabPermission(): void
    {
        if (!Schema::hasTable('features') || !Schema::hasTable('feature_permissions')) {
            return;
        }
        foreach (self::FEATURES as $name => [$display, $actions]) {
            $fid = DB::table('features')->where('name', $name)->value('id');
            if (!$fid) {
                $fid = DB::table('features')->insertGetId([
                    'name' => $name, 'display_name' => $display, 'master_module' => 'hospital_manage',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            foreach ($actions as $a) {
                if (!DB::table('feature_permissions')->where('feature_id', $fid)->where('action', $a)->exists()) {
                    DB::table('feature_permissions')->insert(['feature_id' => $fid, 'action' => $a, 'free' => 0]);
                }
            }
        }
        $legacy = DB::table('features')->where('name', 'lab')->value('id');
        if ($legacy) {
            $pids = DB::table('feature_permissions')->where('feature_id', $legacy)->pluck('id');
            if ($pids->count() && Schema::hasTable('role_feature_permissions')) {
                DB::table('role_feature_permissions')->whereIn('feature_permission_id', $pids)->delete();
            }
            DB::table('feature_permissions')->where('feature_id', $legacy)->delete();
            DB::table('features')->where('id', $legacy)->delete();
        }
    }

    // Seed a default test catalog + reagents on first use so the lab is usable out of the box.
    private function seedDefaults($storeId): void
    {
        if (LabTest::where('store_id', $storeId)->exists()) {
            return;
        }

        $catalog = [
            ['CBC', 'CBC', 'Haematology', 'Venous Blood', 220, '30–60 min', [
                ['Haemoglobin', 'g/dL', 12, 16, null, 6, null],
                ['Total WBC Count', 'cells/µL', 4000, 11000, null, 2000, 30000],
                ['Platelet Count', '/µL', 150000, 410000, null, 20000, null],
                ['RBC Count', 'million/µL', 4.5, 5.9, null, null, null],
                ['PCV / Haematocrit', '%', 40, 50, null, null, null],
            ]],
            ['Lipid Profile', 'LIPID', 'Biochemistry', 'Venous Blood (Fasting)', 450, '1–2 hours', [
                ['Total Cholesterol', 'mg/dL', null, 200, '< 200', null, null],
                ['HDL Cholesterol', 'mg/dL', 40, 60, '40 – 60', null, null],
                ['LDL Cholesterol', 'mg/dL', null, 100, '< 100', null, null],
                ['Triglycerides', 'mg/dL', null, 150, '< 150', null, null],
                ['VLDL', 'mg/dL', 5, 40, '5 – 40', null, null],
            ]],
            ['Renal Function Test', 'RFT', 'Biochemistry', 'Venous Blood', 380, '1–2 hours', [
                ['Serum Urea', 'mg/dL', 15, 45, null, null, null],
                ['Serum Creatinine', 'mg/dL', 0.7, 1.2, null, null, 8],
                ['eGFR', 'mL/min', 60, 120, null, null, null],
                ['Uric Acid', 'mg/dL', 3.5, 7.2, null, null, null],
            ]],
            ['HbA1c', 'HBA1C', 'Biochemistry', 'Venous Blood', 320, '2–4 hours', [
                ['HbA1c', '%', 4.0, 6.4, '4.0 – 6.4', null, null],
            ]],
            ['Liver Function Test', 'LFT', 'Biochemistry', 'Venous Blood', 420, '2–4 hours', [
                ['Total Bilirubin', 'mg/dL', 0.2, 1.2, null, null, null],
                ['SGOT (AST)', 'U/L', 5, 40, null, null, null],
                ['SGPT (ALT)', 'U/L', 5, 45, null, null, null],
                ['Alkaline Phosphatase', 'U/L', 40, 130, null, null, null],
                ['Serum Albumin', 'g/dL', 3.5, 5.2, null, null, null],
            ]],
            ['Thyroid Profile', 'THYROID', 'Endocrinology', 'Venous Blood', 380, '2–4 hours', [
                ['TSH', 'µIU/mL', 0.4, 4.0, '0.4 – 4.0', null, null],
                ['T3 (Total)', 'ng/dL', 80, 200, null, null, null],
                ['T4 (Total)', 'µg/dL', 5.1, 14.1, null, null, null],
            ]],
            ['Electrolytes', 'ELEC', 'Biochemistry', 'Venous Blood', 280, '1 hour', [
                ['Sodium (Na+)', 'mEq/L', 135, 145, null, 120, 160],
                ['Potassium (K+)', 'mEq/L', 3.5, 5.0, null, 2.5, 6.0],
                ['Chloride (Cl-)', 'mEq/L', 98, 107, null, null, null],
            ]],
            ['Fasting Blood Glucose', 'FBS', 'Biochemistry', 'Venous Blood (Fasting)', 120, '30 min', [
                ['Fasting Glucose', 'mg/dL', 70, 100, null, 40, 450],
            ]],
            ['Urine Routine', 'URINE', 'Microbiology', 'Urine', 150, '30–45 min', [
                ['Colour', '', null, null, 'Pale Yellow', null, null],
                ['pH', '', 5.0, 8.0, '5.0 – 8.0', null, null],
                ['Protein', '', null, null, 'Negative', null, null],
                ['Glucose', '', null, null, 'Negative', null, null],
                ['Pus Cells', '/hpf', null, 5, '0 – 5', null, null],
            ]],
            ['ESR', 'ESR', 'Haematology', 'Venous Blood', 80, '1 hour', [
                ['ESR', 'mm/hr', 0, 20, '0 – 20', null, null],
            ]],
        ];

        foreach ($catalog as $row) {
            [$name, $code, $dept, $sample, $price, $tat, $params] = $row;
            $test = LabTest::create([
                'store_id' => $storeId, 'name' => $name, 'code' => $code, 'department' => $dept,
                'sample_type' => $sample, 'price' => $price, 'tat_text' => $tat, 'is_active' => 1,
            ]);
            foreach ($params as $i => $p) {
                LabTestParameter::create([
                    'lab_test_id' => $test->id, 'name' => $p[0], 'unit' => $p[1],
                    'normal_low' => $p[2], 'normal_high' => $p[3], 'ref_range_text' => $p[4],
                    'critical_low' => $p[5], 'critical_high' => $p[6], 'sort_order' => $i,
                ]);
            }
        }

        $reagents = [
            ['HbA1c Reagent Kit', 'Menarini HA-8180', 'HbA1c', '2026-06-30', 2, 10],
            ['Lipid Profile Reagent', 'Agappe Mispa i2', 'Lipid Panel', '2026-09-30', 8, 15],
            ['CBC Reagent Pack', 'Sysmex XN-L', 'CBC', '2026-12-31', 84, 20],
            ['LFT Reagent Kit', 'Beckman Coulter AU', 'Liver Function', '2026-11-30', 42, 10],
            ['Urine Dipstick Strips', 'Siemens Multistix', 'Urine Routine', '2027-03-31', 200, 50],
            ['Blood Culture Bottles', 'BD BACTEC', 'Culture', '2026-08-31', 18, 10],
        ];
        foreach ($reagents as $r) {
            LabReagent::create([
                'store_id' => $storeId, 'name' => $r[0], 'machine' => $r[1], 'for_test' => $r[2],
                'expiry_date' => $r[3], 'stock' => $r[4], 'min_level' => $r[5], 'unit_label' => 'tests',
            ]);
        }
    }

    private function boot(): void
    {
        $this->ensureLabSchema();
        $this->ensureLabPermission();
        $this->seedDefaults($this->storeId());
    }

    // ── Chrome (KPI strip + critical banner data shared by every tab) ──────
    private function chrome(): array
    {
        $storeId = $this->storeId();
        $today = today();

        $testsPending = LabOrderItem::whereHas('order', fn($q) => $q->where('store_id', $storeId)->whereDate('created_at', $today))
            ->where('status', 'pending')->count();
        $inProgress = LabOrder::where('store_id', $storeId)->where('status', 'in_progress')->count();
        $completed = LabOrder::where('store_id', $storeId)->whereIn('status', ['verified', 'sent'])->whereDate('updated_at', $today)->count();
        $criticalOpen = LabOrderResult::where('is_critical', 1)->whereNull('critical_notified_at')
            ->whereHas('order', fn($q) => $q->where('store_id', $storeId))->count();
        $totalToday = LabOrder::where('store_id', $storeId)->whereDate('created_at', $today)->count();
        $patientsToday = LabOrder::where('store_id', $storeId)->whereDate('created_at', $today)->distinct('patient_id')->count('patient_id');
        $revenueToday = LabOrder::where('store_id', $storeId)->whereDate('created_at', $today)->sum('total_amount');

        $criticalAlerts = LabOrderResult::with('order.patient')
            ->where('is_critical', 1)->whereNull('critical_notified_at')
            ->whereHas('order', fn($q) => $q->where('store_id', $storeId))
            ->latest()->take(5)->get();

        return [
            'labStats' => compact('testsPending', 'inProgress', 'completed', 'criticalOpen', 'totalToday', 'patientsToday', 'revenueToday'),
            'criticalAlerts' => $criticalAlerts,
        ];
    }

    private function view(string $name, array $data = [])
    {
        return view('hmis::vendor.lab.' . $name, array_merge($this->chrome(), $data));
    }

    // ── TAB 1: Worklist ────────────────────────────────────────────────────
    public function home()
    {
        $this->boot();
        if (auth('vendor')->check()) {
            return redirect()->route('vendor.lab.worklist');
        }
        $map = [
            'lab_worklist.view' => 'vendor.lab.worklist',
            'lab_result.view'   => 'vendor.lab.result-entry',
            'lab_report.view'   => 'vendor.lab.reports',
            'lab_critical.view' => 'vendor.lab.critical',
            'lab_order.view'    => 'vendor.lab.order',
            'lab_reagent.view'  => 'vendor.lab.reagents',
            'lab_history.view'  => 'vendor.lab.history',
            'lab_billing.view'  => 'vendor.lab.billing',
            'lab_catalog.view'  => 'vendor.lab.catalog',
        ];
        foreach ($map as $perm => $route) {
            [$f, $a] = explode('.', $perm);
            if (hasPermission($f, $a)) {
                return redirect()->route($route);
            }
        }
        abort(403);
    }

    public function worklist(Request $request)
    {
        $this->boot();
        $storeId = $this->storeId();

        // The queue must never drop work that is still open — a date window alone hid pending
        // orders once they aged past it. Outstanding orders stay until they are finished;
        // finished ones (verified/sent) linger briefly so the technician still sees them.
        $orders = LabOrder::where('store_id', $storeId)
            ->with(['patient', 'items', 'doctorProfile.employee'])
            ->where(function ($q) {
                $q->whereNotIn('status', ['verified', 'sent'])
                    ->orWhereDate('created_at', '>=', today()->subDays(2));
            })
            ->when($request->department, fn($q) => $q->where('department', $request->department))
            ->latest()->get();

        // Deliveries taken on trust and never confirmed with the lab, keyed by order.
        //
        // Surfaced on the worklist rather than buried in the order because of who reads it: the
        // person about to key results off a delivered report is the last point at which an
        // unverified origin can still be caught, and they are looking at this list, not at an
        // audit screen. Newest per order wins — that is the delivery the report in hand came from.
        \App\Models\HmisHandover::ensureSchema();
        $wlUnconfirmed = \App\Models\HmisHandover::where('store_id', $storeId)
            ->where('subject_type', 'lab_order')
            ->whereIn('subject_id', $orders->pluck('id'))
            ->where('direction', 'in')
            ->where('verify_state', 'provisional')
            ->whereNotNull('happened_at')
            ->orderByDesc('happened_at')
            ->get()
            ->keyBy('subject_id');

        return $this->view('worklist', compact('orders', 'wlUnconfirmed'));
    }

    public function startTest($id)
    {
        $this->boot();
        [$actorId] = $this->actor();
        $order = LabOrder::where('store_id', $this->storeId())->findOrFail($id);
        if ($order->status === 'ordered') {
            $order->update(['status' => 'in_progress', 'collected_at' => now()]);
            $order->items()->update(['status' => 'in_progress']);
        }
        Toastr::success($order->order_no . ' started — ready for result entry.');
        return redirect()->route('vendor.lab.result-entry', ['order' => $order->id]);
    }

    // ── TAB 2: Result Entry ──────────────────────────────────────────────────
    public function resultEntry(Request $request)
    {
        $this->boot();
        $storeId = $this->storeId();

        $pickable = LabOrder::where('store_id', $storeId)
            ->whereIn('status', ['in_progress', 'ordered', 'resulted'])
            ->with('patient')->latest()->get();

        $orderId = $request->get('order');
        $order = null;
        if ($orderId) {
            $order = LabOrder::where('store_id', $storeId)
                ->with(['patient', 'doctorProfile.employee', 'items.results', 'results'])
                ->find($orderId);
        }
        if (!$order && $pickable->count()) {
            $order = LabOrder::where('store_id', $storeId)
                ->with(['patient', 'doctorProfile.employee', 'items.results', 'results'])
                ->find($pickable->first()->id);
        }

        if ($order) {
            $this->materialiseResults($order);
            $order->load(['items.results', 'results']);
        }

        // The unconfirmed delivery this order's paperwork came in on, if there is one.
        //
        // Result entry is the last moment a forged report can still be stopped, because after this
        // its numbers are indistinguishable from any other result in the patient's chart. So the
        // warning belongs on the screen where somebody is about to type them in, not only in an
        // audit trail nobody opens.
        $reUnconfirmed = null;
        if ($order) {
            \App\Models\HmisHandover::ensureSchema();
            $reUnconfirmed = \App\Models\HmisHandover::where('store_id', $storeId)
                ->where('subject_type', 'lab_order')
                ->where('subject_id', $order->id)
                ->where('direction', 'in')
                ->where('verify_state', 'provisional')
                ->whereNotNull('happened_at')
                ->latest('happened_at')
                ->first();
        }

        // Who may be named as having analysed or verified a report. Doctors first: a report is
        // verified by whoever is qualified to, and picking them out of one flat list of every
        // employee is the slow way round.
        $doctorEmpIds = \App\Models\DoctorProfile::where('store_id', $storeId)
            ->pluck('emp_id')->filter()->unique()->all();
        $signers = \App\Models\VendorEmployee::where('store_id', $storeId)
            ->orderBy('f_name')->get(['id', 'f_name', 'l_name'])
            ->map(fn($e) => [
                'name'   => trim(($e->f_name ?? '') . ' ' . ($e->l_name ?? '')),
                'group'  => in_array($e->id, $doctorEmpIds) ? 'Doctors' : 'Other staff',
            ])
            ->filter(fn($r) => $r['name'] !== '')
            ->values();

        $owner = \App\Models\Store::with('vendor')->find($storeId)?->vendor;
        $ownerName = $owner ? trim(($owner->f_name ?? '') . ' ' . ($owner->l_name ?? '')) : '';
        if ($ownerName !== '') {
            $signers->prepend(['name' => $ownerName, 'group' => 'Owner']);
        }

        // Whoever is at the screen — a technician saving results is naming themselves far more
        // often than anyone else, so that is what the box should already say.
        [, , $currentSigner] = $this->actor();

        return $this->view('result_entry', compact(
            'order', 'pickable', 'reUnconfirmed', 'signers', 'currentSigner'
        ));
    }

    // Create empty result rows (one per test parameter) the first time an order is opened for entry.
    /**
     * Both of these moved to LabResults so the importers judge a value exactly as this screen
     * does. Kept as wrappers rather than replaced at every call site: they are used throughout
     * this controller, and the indirection costs nothing.
     */
    private function materialiseResults(LabOrder $order): void
    {
        \App\Services\LabResults::materialise($order);
    }

    public function saveResults(Request $request, $id)
    {
        $this->boot();
        $order = LabOrder::where('store_id', $this->storeId())->with('results', 'items')->findOrFail($id);

        $values = $request->input('result_value', []);
        foreach ($order->results as $res) {
            if (!array_key_exists($res->id, $values)) {
                continue;
            }
            $val = trim((string) $values[$res->id]);
            $res->result_value = $val === '' ? null : $val;
            [$flag, $critical] = $this->evaluate($val, $res);
            $res->result_flag = $val === '' ? null : $flag;
            $res->is_critical = $val === '' ? 0 : $critical;
            if (!$critical) {
                $res->critical_notified_at = $res->critical_notified_at; // keep
            }
            $res->save();
        }

        // Per test, and only where the box was actually shown, so a locked report posting
        // nothing back cannot blank what is already on it.
        foreach ((array) $request->input('interpretation', []) as $itemId => $text) {
            $item = $order->items->firstWhere('id', (int) $itemId);
            if ($item) {
                $item->interpretation = trim((string) $text) ?: null;
                $item->save();
            }
        }

        $order->technician_notes = $request->technician_notes;
        // Only when the field actually came back. Analysed By is a select now, and a select that
        // is disabled -- which is how a locked report renders it -- posts nothing at all; assigning
        // unconditionally would wipe the name off a finalized report on any later save.
        if ($request->has('analysed_by')) {
            $order->analysed_by = $request->analysed_by;
        }

        $hasAny = $order->results()->whereNotNull('result_value')->exists();
        if ($request->boolean('finalize')) {
            [$actorId, $type, $name] = $this->actor();
            $order->status = 'verified';
            $order->verified_by_name = $request->verified_by ?: $name;
            $order->reported_at = now();
            $order->items()->update(['status' => 'completed']);
            Toastr::success('Report ' . $order->order_no . ' finalized.');
        } elseif ($hasAny) {
            $order->status = 'resulted';
            Toastr::success('Results saved.');
        } else {
            Toastr::success('Draft saved.');
        }
        $order->save();

        // Verified is the first moment this report may reach a patient — 'resulted' means the
        // values are typed in but nobody has checked them. Only fires for hospitals that turned
        // the lab report on under Send Notifications, and only once per order.
        if ($request->boolean('finalize')) {
            HmisWhatsAppShare::auto('lab', (int) $order->store_id, (int) $order->id,
                fn() => HmisWhatsAppShare::labReport($order));
        }

        return $request->boolean('finalize')
            ? redirect()->route('vendor.lab.reports')
            : back();
    }

    private function evaluate($value, LabOrderResult $res): array
    {
        return \App\Services\LabResults::evaluate($value, $res);
    }

    // ── TAB 3: Reports ────────────────────────────────────────────────────
    public function reports(Request $request)
    {
        $this->boot();
        $orders = LabOrder::where('store_id', $this->storeId())
            ->whereIn('status', ['verified', 'sent'])
            ->with(['patient', 'results'])
            ->when($request->search, fn($q) => $q->where('order_no', 'like', "%{$request->search}%")
                ->orWhereHas('patient', fn($p) => $p->where('name', 'like', "%{$request->search}%")))
            ->latest()->paginate(25)->withQueryString();

        return $this->view('reports', compact('orders'));
    }

    public function sendReport($id)
    {
        $this->boot();
        $order = LabOrder::where('store_id', $this->storeId())->findOrFail($id);
        $order->update(['status' => 'sent']);
        Toastr::success('Report ' . $order->order_no . ' marked as sent to doctor.');
        return back();
    }

    public function report($id)
    {
        $this->boot();
        $order = LabOrder::where('store_id', $this->storeId())
            ->with(['patient', 'doctorProfile.employee', 'items.results'])
            ->findOrFail($id);
        $store = Helpers::get_store_data();
        // The lab prints under its own address, GSTIN and licences when it has been given them;
        // otherwise the helper hands back the hospital's own details unchanged.
        $letterhead = \App\Models\HospitalDepartmentProfile::letterhead($this->storeId(), 'lab', $store);
        return view('hmis::vendor.lab.report', compact('order', 'store', 'letterhead'));
    }

    // ── TAB 4: Critical Values ────────────────────────────────────────────
    public function critical()
    {
        $this->boot();
        $storeId = $this->storeId();
        $open = LabOrderResult::with('order.patient', 'order.doctorProfile.employee')
            ->where('is_critical', 1)->whereNull('critical_notified_at')
            ->whereHas('order', fn($q) => $q->where('store_id', $storeId))
            ->latest()->get();
        $log = LabOrderResult::with('order.patient')
            ->where('is_critical', 1)->whereNotNull('critical_notified_at')
            ->whereHas('order', fn($q) => $q->where('store_id', $storeId))
            ->latest()->take(20)->get();
        return $this->view('critical', compact('open', 'log'));
    }

    public function notifyCritical(Request $request, $id)
    {
        $this->boot();
        $res = LabOrderResult::whereHas('order', fn($q) => $q->where('store_id', $this->storeId()))->findOrFail($id);
        $res->update([
            'critical_notified_at' => now(),
            'critical_notified_to' => $request->doctor ?: 'Concerned Doctor',
        ]);
        Toastr::success($res->parameter_name . ' — ' . $res->critical_notified_to . ' notified.');
        return back();
    }

    public function notifyAllCritical()
    {
        $this->boot();
        $count = LabOrderResult::where('is_critical', 1)->whereNull('critical_notified_at')
            ->whereHas('order', fn($q) => $q->where('store_id', $this->storeId()))
            ->update(['critical_notified_at' => now(), 'critical_notified_to' => 'All concerned doctors']);
        Toastr::success($count . ' critical value(s) notified to doctors.');
        return back();
    }

    // ── TAB 5: Order New Test ─────────────────────────────────────────────
    public function orderForm()
    {
        $this->boot();
        $storeId = $this->storeId();
        $patients = Patient::where('store_id', $storeId)->orderBy('name')->get(['id', 'name', 'patient_uid', 'gender', 'dob']);
        $doctors = DoctorProfile::where('store_id', $storeId)->with('employee:id,f_name,l_name')->get();
        $tests = LabTest::where('store_id', $storeId)->where('is_active', 1)->orderBy('department')->orderBy('name')->get();

        // Sample types offered on the order form = the standard ones + whatever this store has
        // actually typed against its tests in the Test Catalog (each may be a comma-separated list).
        $sampleTypes = collect(['Venous Blood', 'Capillary Blood', 'Urine', 'Stool', 'Sputum', 'Swab'])
            ->merge(
                LabTest::where('store_id', $storeId)->whereNotNull('sample_type')->pluck('sample_type')
                    ->flatMap(fn ($s) => preg_split('/\s*,\s*/', (string) $s))
            )
            ->map(fn ($s) => trim($s))
            ->filter()
            ->unique(fn ($s) => mb_strtolower($s))
            ->values();

        // Referral labs, out of the same supplier book the clinic invoices them from — the same
        // list OpdLabWork reads. One address book, so the phone number a handover code is sent to
        // is the number that gets corrected when the lab changes it.
        $referralLabs = \App\Models\StoreCustomer::where('store_id', $storeId)
            ->where('user_type', 'vendor')
            ->orderBy('f_name')
            ->get(['id', 'f_name', 'phone', 'address']);

        return $this->view('order', compact('patients', 'doctors', 'tests', 'sampleTypes', 'referralLabs'));
    }

    public function storeOrder(Request $request)
    {
        $this->boot();
        $request->validate([
            'patient_id'    => 'required|exists:patients,id',
            'tests'         => 'required|array|min:1',
            'sample_types'  => 'nullable|array',
            'sample_types.*' => 'nullable|string|max:80',
            'external_lab_id'    => 'nullable|integer',
            'external_lab_name'  => 'nullable|string|max:190',
            'external_lab_phone' => 'nullable|string|max:40',
        ]);
        $storeId = $this->storeId();
        [$actorId, $actorType] = $this->actor();

        $selected = LabTest::where('store_id', $storeId)->whereIn('id', $request->tests)->get();
        if ($selected->isEmpty()) {
            Toastr::error('Select at least one valid test.');
            return back()->withInput();
        }

        $order = LabOrder::create([
            'store_id'          => $storeId,
            'patient_id'        => $request->patient_id,
            'doctor_profile_id' => $request->doctor_profile_id ?: null,
            'source'            => 'walkin',
            'department'        => $request->department ?: 'OPD',
            'priority'          => $request->priority ?: 'routine',
            'status'            => 'ordered',
            'sample_type'       => $this->resolveSampleTypes($request, $selected),
            'clinical_notes'    => $request->clinical_notes,
            'referred_by'       => $request->referred_by,
            'total_amount'      => $selected->sum('price'),
            'created_by'        => $actorId,
            'created_by_type'   => $actorType,
            // A referral lab is only recorded where one was actually named. The flag is derived
            // rather than trusted from the form, so an order cannot be marked outsourced with
            // nowhere to send it — which would put a handover button on a row with no number
            // behind it to verify against.
            'external_lab_id'    => $request->external_lab_id ?: null,
            'external_lab_name'  => $request->external_lab_name ?: null,
            'external_lab_phone' => $request->external_lab_phone ?: null,
            'is_outsourced'      => filled($request->external_lab_name) || filled($request->external_lab_id) ? 1 : 0,
        ]);
        $order->order_no = 'LAB-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
        $order->save();

        foreach ($selected as $t) {
            LabOrderItem::create([
                'lab_order_id' => $order->id,
                'lab_test_id'  => $t->id,
                'test_name'    => $t->name,
                'department'   => $t->department,
                'price'        => $t->price,
                'status'       => 'pending',
                // Copied, not looked up at print time: a report already handed to a patient must
                // keep saying what it said when it was issued, whatever the catalogue says later.
                'interpretation' => $t->interpretation,
            ]);
        }

        Toastr::success('Lab order ' . $order->order_no . ' placed.');
        return redirect()->route('vendor.lab.worklist');
    }

    /**
     * An order may need several samples (e.g. blood + urine). Combine every sample the selected
     * tests require with any extra ones the user ticked, de-duplicated. Each source may itself be
     * a comma-separated list, so they're split before merging. Falls back to the legacy single
     * `sample_type` field for callers that still send it.
     */
    private function resolveSampleTypes(Request $request, $selected): ?string
    {
        $samples = collect($request->input('sample_types', []))
            ->merge($selected->pluck('sample_type'))
            ->merge([$request->input('sample_type')])
            ->flatMap(fn ($s) => preg_split('/\s*,\s*/', (string) $s))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->unique(fn ($s) => mb_strtolower($s))
            ->values();

        return $samples->isEmpty() ? null : mb_substr($samples->implode(', '), 0, 255);
    }

    // ── Create a lab order straight from an OPD/IPD consultation ──────────
    public function orderFromOpd(Request $request)
    {
        $this->boot();
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'tests'      => 'required|array|min:1',
        ]);
        $storeId = $this->storeId();
        [$actorId, $actorType] = $this->actor();

        // OPD checkbox value => [code, name, department, sample type]
        $map = [
            'CBC'          => ['CBC', 'Complete Blood Count (CBC)', 'Haematology', 'Venous Blood'],
            'HbA1c'        => ['HBA1C', 'HbA1c', 'Biochemistry', 'Venous Blood'],
            'Lipid'        => ['LIPID', 'Lipid Profile', 'Biochemistry', 'Venous Blood (Fasting)'],
            'FBS'          => ['FBS', 'Fasting Blood Glucose', 'Biochemistry', 'Venous Blood (Fasting)'],
            'RFT'          => ['RFT', 'Renal Function Test', 'Biochemistry', 'Venous Blood'],
            'LFT'          => ['LFT', 'Liver Function Test', 'Biochemistry', 'Venous Blood'],
            'Thyroid'      => ['THYROID', 'Thyroid Profile', 'Endocrinology', 'Venous Blood'],
            'Electrolyte'  => ['ELEC', 'Electrolytes', 'Biochemistry', 'Venous Blood'],
            'ECG'          => ['ECG', 'Electrocardiogram (ECG)', 'Cardiology', 'N/A'],
            'Echo'         => ['ECHO', 'Echocardiography (ECHO)', 'Cardiology', 'N/A'],
            'Urine'        => ['URINE', 'Urine Routine', 'Microbiology', 'Urine'],
            'Microalbumin' => ['MICROALB', 'Urine Microalbumin', 'Biochemistry', 'Urine'],
        ];

        $testIds = [];
        foreach ($request->tests as $code) {
            $m = $map[$code] ?? [strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $code), 0, 12)), $code, null, null];
            $test = LabTest::where('store_id', $storeId)
                ->where(fn($q) => $q->where('code', $m[0])->orWhere('name', $m[1]))
                ->first();
            if (!$test) {
                $test = LabTest::create([
                    'store_id' => $storeId, 'name' => $m[1], 'code' => $m[0],
                    'department' => $m[2], 'sample_type' => $m[3], 'price' => 0, 'is_active' => 1,
                ]);
                LabTestParameter::create(['lab_test_id' => $test->id, 'name' => $m[1], 'sort_order' => 0]);
            }
            $testIds[] = $test->id;
        }

        $selected = LabTest::whereIn('id', array_unique($testIds))->get();

        $order = LabOrder::create([
            'store_id'          => $storeId,
            'patient_id'        => $request->patient_id,
            'doctor_profile_id' => $request->doctor_profile_id ?: null,
            'opd_id'            => $request->opd_id ?: null,
            'source'            => $request->source ?: 'opd',
            'department'        => $request->department ?: 'OPD',
            'priority'          => 'routine',
            'status'            => 'ordered',
            'clinical_notes'    => $request->clinical_notes,
            'total_amount'      => $selected->sum('price'),
            'created_by'        => $actorId,
            'created_by_type'   => $actorType,
        ]);
        $order->order_no = 'LAB-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
        $order->save();

        foreach ($selected as $t) {
            LabOrderItem::create([
                'lab_order_id' => $order->id, 'lab_test_id' => $t->id, 'test_name' => $t->name,
                'department' => $t->department, 'price' => $t->price, 'status' => 'pending',
                'interpretation' => $t->interpretation,
            ]);
        }

        return response()->json([
            'success'  => true,
            'order_no' => $order->order_no,
            'count'    => $selected->count(),
            'redirect' => route('vendor.lab.worklist'),
        ]);
    }

    // ── TAB 6: Reagents ───────────────────────────────────────────────────
    public function reagents()
    {
        $this->boot();
        $reagents = LabReagent::where('store_id', $this->storeId())->orderBy('name')->get();
        return $this->view('reagents', compact('reagents'));
    }

    public function saveReagent(Request $request)
    {
        $this->boot();
        $request->validate(['name' => 'required|string|max:160']);
        LabReagent::create([
            'store_id' => $this->storeId(),
            'name' => $request->name, 'machine' => $request->machine, 'for_test' => $request->for_test,
            'expiry_date' => $request->expiry_date ?: null, 'stock' => $request->stock ?? 0,
            'min_level' => $request->min_level ?? 0, 'unit_label' => $request->unit_label ?: 'tests',
        ]);
        Toastr::success('Reagent added.');
        return back();
    }

    public function updateReagent(Request $request, $id)
    {
        $this->boot();
        $r = LabReagent::where('store_id', $this->storeId())->findOrFail($id);
        $r->update($request->only(['name', 'machine', 'for_test', 'expiry_date', 'stock', 'min_level', 'unit_label']));
        Toastr::success('Reagent updated.');
        return back();
    }

    public function deleteReagent($id)
    {
        $this->boot();
        LabReagent::where('store_id', $this->storeId())->findOrFail($id)->delete();
        Toastr::success('Reagent removed.');
        return back();
    }

    // ── TAB 7: Test History ───────────────────────────────────────────────
    public function history(Request $request)
    {
        $this->boot();
        $orders = LabOrder::where('store_id', $this->storeId())
            ->with(['patient', 'results'])
            ->when($request->date, fn($q) => $q->whereDate('created_at', $request->date))
            ->when($request->search, fn($q) => $q->where('order_no', 'like', "%{$request->search}%")
                ->orWhereHas('patient', fn($p) => $p->where('name', 'like', "%{$request->search}%")))
            ->latest()->paginate(30)->withQueryString();
        return $this->view('history', compact('orders'));
    }

    // ── TAB 8: Billing ────────────────────────────────────────────────────
    public function billing(Request $request)
    {
        $this->boot();
        $storeId = $this->storeId();

        $order = null;
        if ($request->order) {
            $order = LabOrder::where('store_id', $storeId)->with(['patient', 'items', 'invoice'])->find($request->order);
        }
        $billable = LabOrder::where('store_id', $storeId)->whereDoesntHave('invoice')
            ->with('patient')->latest()->take(40)->get();

        $today = today();
        $revenue = [
            'billed'    => LabInvoice::where('store_id', $storeId)->whereDate('created_at', $today)->sum('subtotal'),
            'insured'   => LabInvoice::where('store_id', $storeId)->whereDate('created_at', $today)->sum('insurance_covered'),
            'cash'      => LabInvoice::where('store_id', $storeId)->whereDate('created_at', $today)->sum('payable'),
            'count'     => LabInvoice::where('store_id', $storeId)->whereDate('created_at', $today)->count(),
        ];
        $recent = LabInvoice::where('store_id', $storeId)->with('patient', 'order')->latest()->take(8)->get();

        return $this->view('billing', compact('order', 'billable', 'revenue', 'recent'));
    }

    public function generateInvoice(Request $request, $id)
    {
        $this->boot();
        $storeId = $this->storeId();
        $order = LabOrder::where('store_id', $storeId)->with('items')->findOrFail($id);

        $request->validate([
            'transaction_id' => 'required_if:payment_mode,Online,Card,UPI|nullable|string|max:100',
        ]);

        $subtotal = $order->items->sum('price');
        $insurance = (float) ($request->insurance_covered ?: 0);
        $discount = (float) ($request->discount ?: 0);
        $payable = max(0, $subtotal - $insurance - $discount);
        $taxType = 'non-gst';
        $mode = strtolower($request->payment_mode ?: 'cash');
        $isOnline = in_array($mode, ['online', 'card', 'upi']);

        DB::beginTransaction();
        try {
            // Use the store's running invoice number (same sequence as hospital bills).
            $invoiceId = Helpers::generateInvoiceId('H', true, null, $taxType);

            $manual = ManualInvoice::create([
                'invoice_id'     => $invoiceId,
                'invoice_serial' => (int) substr($invoiceId, strrpos($invoiceId, '_') + 1),
                'financial_year' => _currentFinancialYear(),
                'bill_to'        => $order->patient_id,
                'bill_to_type'   => 'patient',
                'user_type'      => 'hospital_patient',
                'vendor_id'      => $storeId,
                'total_amount'   => $payable,
                'payment_status' => 'Paid',
                'payment_method' => $request->payment_mode ?: 'Cash',
                'payment_date'   => now()->toDateString(),
                'invoice_date'   => now()->toDateString(),
                'tax_type'       => $taxType,
                'cash_amount'    => $isOnline ? 0 : $payable,
                'online_amount'  => $isOnline ? $payable : 0,
                'reference_number' => $isOnline && $request->transaction_id ? ['transaction_id' => $request->transaction_id] : [],
                'meta'           => array_filter([
                    'source'         => 'lab',
                    'transaction_id' => $isOnline ? $request->transaction_id : null,
                ]),
            ]);

            foreach ($order->items as $it) {
                InvoiceItem::create([
                    'rand_invoice_id'   => $invoiceId,
                    'manual_invoice_id' => $manual->id,
                    'name'              => $it->test_name,
                    'qty'               => 1,
                    'price'             => $it->price,
                    'tax'               => 0,
                    'gst_status'        => 'excluding',
                ]);
            }

            // Lab-side billing summary (insurance/discount split) linked to the running invoice.
            LabInvoice::updateOrCreate(
                ['lab_order_id' => $order->id],
                [
                    'store_id' => $storeId, 'patient_id' => $order->patient_id, 'invoice_no' => $invoiceId,
                    'subtotal' => $subtotal, 'insurance_provider' => $request->insurance_provider,
                    'insurance_covered' => $insurance, 'discount' => $discount, 'payable' => $payable,
                    'payment_mode' => $request->payment_mode ?: 'cash', 'status' => 'finalized',
                ]
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Toastr::error('Could not create invoice: ' . $e->getMessage());
            return back();
        }

        try {
            $data = _createBillPdf($manual, 'vendor');
            $manual->update(['pdf' => $data['pdf']]);
            Toastr::success('Lab invoice ' . $invoiceId . ' finalized.');
        } catch (\Throwable $e) {
            // PDF is non-fatal — the invoice is already saved, so the staff member still gets
            // taken to it; the viewer says the PDF is missing rather than silently showing nothing.
            report($e);
            Toastr::warning('Lab invoice ' . $invoiceId . ' was saved, but its PDF could not be generated: ' . $e->getMessage());
        }

        return redirect()->route('vendor.lab.invoices.view', $manual->id);
    }

    // The finalized bill itself. Scoped to the lab's own billing permission so a technician who
    // can raise an invoice can also open it, without needing the store-wide billing feature.
    public function invoice($id)
    {
        $this->boot();
        $storeId = $this->storeId();
        $invoice = is_numeric($id)
            ? ManualInvoice::where('vendor_id', $storeId)->find($id)
            : ManualInvoice::where('vendor_id', $storeId)->where('invoice_id', $id)->latest('id')->first();

        if (!$invoice) {
            abort(404, 'Invoice not found.');
        }

        return view('vendor-views.billing.view_invoice', compact('invoice'));
    }

    // ── Test Catalog (management) ─────────────────────────────────────────
    public function catalog(Request $request)
    {
        $this->boot();
        $tests = LabTest::where('store_id', $this->storeId())->withCount('parameters')
            ->when($request->search, fn($q) => $q->where(fn($w) => $w
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%")))
            ->orderBy('department')->orderBy('name')->get();
        return $this->view('catalog', compact('tests'));
    }

    public function testForm($id = null)
    {
        $this->boot();
        $test = $id ? LabTest::where('store_id', $this->storeId())->with('parameters')->findOrFail($id) : null;
        return $this->view('catalog_form', compact('test'));
    }

    public function saveTest(Request $request, $id = null)
    {
        $this->boot();
        // The catalog list edits the money and labelling fields inline and posts without any
        // parameter rows; only the full form sends them. So parameters are validated when they
        // are present and left untouched when they are not — a quick price edit must not wipe
        // the reference ranges.
        $request->validate([
            'name'  => 'required|string|max:200',
            'price' => 'required|numeric|min:0',
            'parameters'        => 'nullable|array',
            'parameters.*.name' => 'nullable|string',
        ]);
        $storeId = $this->storeId();

        $test = $id ? LabTest::where('store_id', $storeId)->findOrFail($id) : new LabTest(['store_id' => $storeId]);
        $test->fill([
            'store_id'    => $storeId,
            'name'        => $request->name,
            'code'        => $request->code,
            'department'  => $request->department,
            'sample_type' => $request->sample_type,
            'price'       => $request->price,
            'tat_text'    => $request->tat_text,
            'interpretation' => $request->interpretation,
            'is_active'   => $request->has('is_active') ? 1 : 0,
        ])->save();

        if (!$request->has('parameters')) {
            // A test with nothing to measure cannot be resulted, so a brand new one gets a single
            // parameter named after itself; ranges are filled in later from the full form.
            if (!$id) {
                LabTestParameter::create(['lab_test_id' => $test->id, 'name' => $test->name, 'sort_order' => 0]);
            }

            Toastr::success('Test ' . ($id ? 'updated' : 'added') . '.');
            return redirect()->route('vendor.lab.catalog');
        }

        $test->parameters()->delete();
        foreach (array_values($request->parameters) as $i => $p) {
            if (empty($p['name'])) {
                continue;
            }
            LabTestParameter::create([
                'lab_test_id'    => $test->id,
                'name'           => $p['name'],
                'unit'           => $p['unit'] ?? null,
                'normal_low'     => ($p['normal_low'] ?? '') === '' ? null : $p['normal_low'],
                'normal_high'    => ($p['normal_high'] ?? '') === '' ? null : $p['normal_high'],
                'ref_range_text' => $p['ref_range_text'] ?? null,
                'critical_low'   => ($p['critical_low'] ?? '') === '' ? null : $p['critical_low'],
                'critical_high'  => ($p['critical_high'] ?? '') === '' ? null : $p['critical_high'],
                'sort_order'     => $i,
            ]);
        }

        Toastr::success('Test ' . ($id ? 'updated' : 'added') . '.');
        return redirect()->route('vendor.lab.catalog');
    }

    public function deleteTest($id)
    {
        $this->boot();
        $test = LabTest::where('store_id', $this->storeId())->findOrFail($id);
        $test->parameters()->delete();
        $test->delete();
        Toastr::success('Test removed.');
        return back();
    }

    /* ── Catalog import / export ──────────────────────────────────────────
       A lab arrives with its catalog already written down somewhere — two hundred tests, their
       parameters, units and reference ranges — and typing that in twice is the reason a lab module
       goes unused. Export and import share one column layout on purpose: what comes out can be
       edited and put straight back in.                                                          */

    /** The catalog as it stands, in the shape the importer reads back. */
    public function catalogExport()
    {
        $this->boot();

        $tests = LabTest::where('store_id', $this->storeId())
            ->with('parameters')
            ->orderBy('department')->orderBy('name')
            ->get();

        $rows = [];
        foreach ($tests as $test) {
            $head = [
                $test->name, $test->code, $test->department, $test->sample_type,
                $test->price, $test->tat_text, $test->is_active ? 'Yes' : 'No',
            ];

            // A test with no parameters is still a row — it is a priced line on a bill even when
            // it measures nothing, and leaving it out would make the export an incomplete catalog.
            if ($test->parameters->isEmpty()) {
                $rows[] = array_merge($head, ['', '', '', '', '', '', '']);
                continue;
            }

            foreach ($test->parameters as $p) {
                $rows[] = array_merge($head, [
                    $p->name, $p->unit, $p->normal_low, $p->normal_high,
                    $p->ref_range_text, $p->critical_low, $p->critical_high,
                ]);
            }
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport($rows, self::CATALOG_COLUMNS),
            'lab_test_catalog_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /** The same columns with two worked examples in them, for a lab starting from nothing. */
    public function catalogTemplate()
    {
        $this->boot();

        $sample = [
            ['Complete Blood Count', 'CBC', 'Haematology', 'EDTA Blood', 250, 'Same day', 'Yes',
                'Haemoglobin', 'g/dL', 13, 17, '13 – 17 g/dL', 7, 20],
            ['Complete Blood Count', 'CBC', 'Haematology', 'EDTA Blood', 250, 'Same day', 'Yes',
                'Total WBC', 'cells/µL', 4000, 11000, '4,000 – 11,000', 2000, 30000],
            ['Fasting Glucose', 'GLUF', 'Biochemistry', 'Fluoride Plasma', 120, '2 hours', 'Yes',
                'Glucose (Fasting)', 'mg/dL', 70, 100, '70 – 100 mg/dL', 50, 400],
        ];

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport($sample, self::CATALOG_COLUMNS),
            'lab_test_catalog_template.xlsx'
        );
    }

    public function catalogImport(Request $request)
    {
        $this->boot();

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ], [
            'file.mimes' => 'Upload the catalog as .xlsx, .xls or .csv.',
        ]);

        $import = new \App\Imports\LabTestImport($this->storeId());

        try {
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Lab catalog import failed: ' . $e->getMessage());
            Toastr::error('That file could not be read. Start from the sample template and keep the column order.');
            return back();
        }

        if (!$import->created && !$import->updated) {
            Toastr::warning('Nothing was imported — no test rows were found in that file.');
            return back();
        }

        Toastr::success(
            $import->created . ' test(s) added, ' . $import->updated . ' updated, '
            . $import->parameters . ' parameter(s) written.'
            . ($import->skipped ? ' ' . count($import->skipped) . ' row(s) skipped.' : '')
        );

        return back();
    }

    /** The test register — what was ordered, for whom, and where it got to. */
    public function historyExport(Request $request)
    {
        $this->boot();

        $orders = LabOrder::where('store_id', $this->storeId())
            ->with(['patient', 'items', 'doctorProfile.employee'])
            ->when($request->date, fn($q) => $q->whereDate('created_at', $request->date))
            ->when($request->search, fn($q) => $q->where('order_no', 'like', "%{$request->search}%")
                ->orWhereHas('patient', fn($p) => $p->where('name', 'like', "%{$request->search}%")))
            ->latest()
            ->get();

        $rows = $orders->map(fn($o) => [
            $o->order_no,
            $o->created_at?->format('d M Y h:i A'),
            $o->patient?->name,
            $o->patient?->patient_uid,
            $o->department,
            $o->items->pluck('test_name')->implode(', '),
            $o->doctorProfile
                ? 'Dr. ' . trim(($o->doctorProfile->employee->f_name ?? '') . ' ' . ($o->doctorProfile->employee->l_name ?? ''))
                : $o->referred_by,
            ucfirst((string) $o->priority),
            ucfirst(str_replace('_', ' ', (string) $o->status)),
            $o->is_outsourced ? ($o->external_lab_name ?: 'Outsourced') : 'In-house',
            $o->collected_at?->format('d M Y h:i A'),
            $o->reported_at?->format('d M Y h:i A'),
            $o->total_amount,
        ])->toArray();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport($rows, [
                'Order No', 'Ordered On', 'Patient', 'UID', 'Department', 'Test(s)', 'Ordered By',
                'Priority', 'Status', 'Where', 'Sample Collected', 'Reported', 'Amount',
            ]),
            'lab_test_history_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * One column layout for the catalog, used by the export, the template and the importer's
     * documentation alike — three places that must never disagree about what column 9 is.
     */
    const CATALOG_COLUMNS = [
        'Test Name', 'Code', 'Department', 'Sample Type', 'Price', 'TAT', 'Active',
        'Parameter', 'Unit', 'Normal Low', 'Normal High', 'Reference Range', 'Critical Low', 'Critical High',
    ];

    const ORDER_COLUMNS  = ['Patient UID', 'Test Code(s)', 'Department', 'Priority', 'Referred By', 'Clinical Notes'];
    const RESULT_COLUMNS = ['Sample ID', 'Parameter', 'Value'];

    /* ── Orders and results, in and out ───────────────────────────────────
       A lab's day arrives and leaves as files: a camp list of two hundred people to be ordered in
       one go, and an analyser's values to be read back against the samples it ran. Both are shaped
       so what comes out can be edited and put back in.                                          */

    /** Today's worklist, or whatever the filters are showing, as a spreadsheet. */
    public function worklistExport(Request $request)
    {
        $this->boot();

        $orders = LabOrder::where('store_id', $this->storeId())
            ->with(['patient', 'items', 'doctorProfile.employee'])
            ->when($request->department, fn($q) => $q->where('department', $request->department))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority))
            ->when($request->date, fn($q) => $q->whereDate('created_at', $request->date))
            ->latest()
            ->get();

        $rows = $orders->map(fn($o) => [
            $o->order_no,
            $o->created_at?->format('d M Y h:i A'),
            $o->patient?->name,
            $o->patient?->patient_uid,
            $o->department,
            $o->items->pluck('test_name')->implode(', '),
            $o->doctorProfile
                ? 'Dr. ' . trim(($o->doctorProfile->employee->f_name ?? '') . ' ' . ($o->doctorProfile->employee->l_name ?? ''))
                : $o->referred_by,
            ucfirst((string) $o->priority),
            ucfirst(str_replace('_', ' ', (string) $o->status)),
            $o->sample_type,
            $o->is_outsourced ? ($o->external_lab_name ?: 'Outsourced') : 'In-house',
            $o->total_amount,
        ])->toArray();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport($rows, [
                'Sample ID', 'Ordered On', 'Patient', 'UID', 'Department', 'Test(s)', 'Ordered By',
                'Priority', 'Status', 'Sample Type', 'Where', 'Amount',
            ]),
            'lab_worklist_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Every parameter waiting on a value, as the sheet an analyser's output is pasted into.
     *
     * Exported with the values already in it, so the same file is both "what still needs doing"
     * and a correction sheet for what has been entered.
     */
    public function resultsExport(Request $request)
    {
        $this->boot();

        $orders = LabOrder::where('store_id', $this->storeId())
            ->whereIn('status', $request->status ? [$request->status] : ['ordered', 'in_progress', 'resulted'])
            ->when($request->date, fn($q) => $q->whereDate('created_at', $request->date))
            ->with(['patient', 'items'])
            ->latest()
            ->get();

        $rows = [];
        foreach ($orders as $order) {
            \App\Services\LabResults::materialise($order);

            foreach (LabOrderResult::where('lab_order_id', $order->id)->orderBy('sort_order')->get() as $res) {
                $rows[] = [
                    $order->order_no,
                    $res->parameter_name,
                    $res->result_value,
                    $res->unit,
                    $res->ref_range_text ?: trim((string) $res->normal_low . ' – ' . (string) $res->normal_high, ' –'),
                    $res->result_flag,
                    $order->patient?->name,
                ];
            }
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport($rows, [
                'Sample ID', 'Parameter', 'Value', 'Unit', 'Reference Range', 'Flag', 'Patient',
            ]),
            'lab_results_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function ordersTemplate()
    {
        $this->boot();

        $test = LabTest::where('store_id', $this->storeId())->orderBy('name')->first();
        $uid  = Patient::where('store_id', $this->storeId())->value('patient_uid');

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport([
                [$uid ?: 'P-00001', $test->code ?: $test->name ?? 'CBC', 'OPD', 'routine', 'Dr. Ramani', 'Fever, 3 days'],
                [$uid ?: 'P-00002', ($test->code ?: 'CBC') . ', GLUF', 'OPD', 'urgent', 'Camp', ''],
            ], self::ORDER_COLUMNS),
            'lab_orders_template.xlsx'
        );
    }

    public function ordersImport(Request $request)
    {
        $this->boot();

        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120']);

        [$actorId, $actorType] = $this->actor();
        $import = new \App\Imports\LabOrderImport($this->storeId(), $actorId, $actorType);

        try {
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Lab order import failed: ' . $e->getMessage());
            Toastr::error('That file could not be read. Start from the sample template and keep the column order.');
            return back();
        }

        if (!$import->created) {
            Toastr::error('No orders were raised. ' . ($import->skipped[0] ?? 'No usable rows were found.'));
            return back()->with('lab_import_skipped', $import->skipped);
        }

        Toastr::success($import->created . ' order(s) raised covering ' . $import->tests . ' test(s).'
            . ($import->skipped ? ' ' . count($import->skipped) . ' row(s) need attention.' : ''));

        return back()->with('lab_import_skipped', $import->skipped);
    }

    public function resultsTemplate()
    {
        $this->boot();

        // Built from a real pending sample where there is one, so the template is a file the lab
        // could actually fill in and import rather than an illustration.
        $order = LabOrder::where('store_id', $this->storeId())
            ->whereIn('status', ['ordered', 'in_progress'])
            ->with('items')
            ->latest()
            ->first();

        $rows = [];
        if ($order) {
            \App\Services\LabResults::materialise($order);
            foreach (LabOrderResult::where('lab_order_id', $order->id)->orderBy('sort_order')->get() as $res) {
                $rows[] = [$order->order_no, $res->parameter_name, ''];
            }
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport($rows ?: [['LAB-0001', 'Haemoglobin', '13.4']], self::RESULT_COLUMNS),
            'lab_results_template.xlsx'
        );
    }

    public function resultsImport(Request $request)
    {
        $this->boot();

        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120']);

        $import = new \App\Imports\LabResultImport($this->storeId());

        try {
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Lab result import failed: ' . $e->getMessage());
            Toastr::error('That file could not be read. Start from the sample template and keep the column order.');
            return back();
        }

        if (!$import->updated) {
            Toastr::error('No results were written. ' . ($import->skipped[0] ?? 'No usable rows were found.'));
            return back()->with('lab_import_skipped', $import->skipped);
        }

        $message = $import->updated . ' result(s) written across ' . count($import->touched) . ' sample(s).'
            . ($import->skipped ? ' ' . count($import->skipped) . ' row(s) need attention.' : '');

        // Criticals are the one outcome that must not sit inside a success message: they need a
        // doctor told, and the Critical Values tab is where that happens.
        if ($import->critical) {
            Toastr::warning($message . ' ' . $import->critical . ' value(s) are CRITICAL — check the Critical Values tab.');
        } else {
            Toastr::success($message . ' Nothing is verified — check each report before sending it.');
        }

        return back()->with('lab_import_skipped', $import->skipped);
    }
}
