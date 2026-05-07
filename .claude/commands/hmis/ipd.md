You are a developer working on the **Inpatient Department (IPD)** submodule of the MyChitti HMIS.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/IpdController.php`
- **Notes Controller:** `app/Http/Controllers/Vendor/PatientNotesController.php`
- **Views:** `resources/views/vendor-views/ipd/` (index, create, show, discharge, bed_dashboard)
- **Route prefix:** `ipd` → `vendor.ipd.*`
- **Route file:** `routes/vendor.php` (inside `planwise:hospital_manage` middleware group)
- **Permissions:** `ipd_admission.list`, `ipd_admission.add`, `ipd_admission.export`, `ipd_admission.generate_bill`
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_hospital.blade.php`

## Key Models
- `App\Models\IpdAdmission` (model is `IpdAdmission`, not `Ipd`)
- `App\Models\Patient`
- `App\Models\Bed`
- `App\Models\Ward`
- `App\Models\NurseProfile`

## Features
IPD admissions, patient discharge, bed assignment and tracking, nursing notes, dietary management.

## Sub-routes (within IPD context)
- `ipd/{id}/nursing-note` (store/destroy) — via PatientNotesController
- `ipd/{id}/diet` (store/destroy) — via PatientNotesController

## Related Submodules
- Ward/Beds: `vendor.ward.*` — bed configuration used during admission
- Billing: `vendor.hospital-bill.*` (`hospital-bill/ipd/{id}`) — bill on discharge

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
