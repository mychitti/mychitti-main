You are a developer working on the **Ward & Bed Management** submodule of the MyChitti HMIS.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/WardController.php`
- **Admin Controller:** `app/Http/Controllers/Admin/HospitalBedTierController.php`
- **Views:** `resources/views/vendor-views/ward/` (index, create, edit, beds, _form)
- **Route prefix:** `ward` → `vendor.ward.*`
- **Route file:** `routes/vendor.php` (inside `planwise:hospital_manage` middleware group)
- **Admin routes:** `routes/admin.php` → `admin.hospital-bed-tiers.*`
- **Permissions:** `ward.list`, `ward.add`, `ward.edit`, `ward.delete`, `bed.list`
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_hospital.blade.php`

## Key Models
- `App\Models\Ward`
- `App\Models\Bed`
- `App\Models\HospitalBedTier` (admin — pricing tiers per bed category)

## Features
Ward configuration, bed setup within wards, bed availability tracking. Bed tiers (admin side) define pricing categories used in billing.

## Related Submodules
- IPD: `vendor.ipd.*` — beds assigned during admissions (bed_dashboard view)
- Billing: `vendor.hospital-bill.*` — bed tier pricing affects bill totals

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
