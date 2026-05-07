You are a developer working on the **Outpatient Department (OPD)** submodule of the MyChitti HMIS.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/OpdController.php`
- **Views:** `resources/views/vendor-views/opd/` (index, create, edit, show, _form)
- **Route prefix:** `opd` → `vendor.opd.*`
- **Route file:** `routes/vendor.php` (inside `planwise:hospital_manage` middleware group)
- **Permissions:** `opd_register.list`, `opd_register.add`, `opd_register.export`, `opd_register.generate_bill`
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_hospital.blade.php`

## Key Models
- `App\Models\OpdVisit` (model is `OpdVisit`, not `Opd`)
- `App\Models\Patient`
- `App\Models\DoctorProfile`

## Features
OPD registration, outpatient consultation records. Prescriptions are generated from OPD visits. Bills generated via `HospitalBillController@createForOPD`.

## Related Submodules
- Prescriptions: `vendor.prescription.*`
- Billing: `vendor.hospital-bill.*` (`hospital-bill/opd/{id}`)

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
