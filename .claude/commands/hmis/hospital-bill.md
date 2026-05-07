You are a developer working on the **Hospital Billing** submodule of the MyChitti HMIS.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/HospitalBillController.php`
- **Receipt Controller:** `app/Http/Controllers/Vendor/Hospital/RecieptController.php`
- **Views:** `resources/views/vendor-views/hospital/` (create_bill and billing-related views)
- **Route prefix:** `hospital-bill` → `vendor.hospital-bill.*`
- **Route file:** `routes/vendor.php` (inside `planwise:hospital_manage` middleware group)
- **Admin (Bed Tiers):** `app/Http/Controllers/Admin/HospitalBedTierController.php` → `admin.hospital-bed-tiers.*`
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_hospital.blade.php`

## Exact Routes
- `GET  hospital-bill/ipd/{id}` → `createForIPD()` (permission: `ipd_admission,generate_bill`)
- `GET  hospital-bill/opd/{id}` → `createForOPD()` (permission: `opd_register,generate_bill`)
- `POST hospital-bill/store` → `store()`
- `GET  hospital-bill/inventory-search` → `searchInventory()` (AJAX)

## Key Models
- `App\Models\IpdAdmission`
- `App\Models\OpdVisit`
- `App\Models\HospitalBedTier`
- `App\Models\Patient`

## Features
Bill generation from IPD discharge or OPD visit. Inventory/item search for adding bill line items. Bed tier pricing from admin settings affects bill calculation.

## Related Submodules
- IPD: `vendor.ipd.*` — bills triggered on discharge
- OPD: `vendor.opd.*` — bills triggered after consultation
- Ward: `vendor.ward.*` — bed tier pricing

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery
- Eloquent models for queries; raw SQL (`DB::statement()`) only for schema changes — never generate migration files
- All hospital routes guarded by `planwise:hospital_manage` middleware
- Controllers extend `App\Http\Controllers\Controller`
- Icons: `tio-*` set; views extend `layouts.vendor.app`

## Behaviour
1. Read relevant files first
2. Outline plan — files to create/modify, SQL to run, routes/env vars needed
3. Ask for confirmation before making changes
4. Proceed only after confirmation
5. Write complete, production-ready code — no placeholders

$ARGUMENTS
