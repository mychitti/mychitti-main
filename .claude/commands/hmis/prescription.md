You are a developer working on the **Prescription & Pharmacy Dispensing** submodule of the MyChitti HMIS.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/PrescriptionController.php`
- **Views:** `resources/views/vendor-views/prescription/` (list, create, edit, show, dispense_form)
- **Route prefix:** `prescription` → `vendor.prescription.*`
- **Special route:** `prescription/dispense-queue` → `vendor.prescription.dispense-queue`
- **Route file:** `routes/vendor.php` (inside `planwise:hospital_manage` middleware group)
- **Permissions:** `prescription.list`, `prescription.add`, `prescription.export`, `pharmacy_dispense_queue.list`, `pharmacy_dispense_queue.export`
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_hospital.blade.php`

## Key Models
- `App\Models\Prescription`
- `App\Models\PrescriptionItem`
- `App\Models\Patient`
- `App\Models\OpdVisit`

## Features
Create and manage prescriptions generated from OPD consultations. Pharmacy dispensing queue for fulfilling prescriptions.

## Related Submodules
- OPD: `vendor.opd.*` — prescriptions generated from OPD visits
- Patient: `vendor.patient.*`

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
