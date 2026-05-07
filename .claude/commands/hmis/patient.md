You are a developer working on the **Patient Management** submodule of the MyChitti HMIS.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/PatientController.php`
- **Notes Controller:** `app/Http/Controllers/Vendor/PatientNotesController.php`
- **Views:** `resources/views/vendor-views/patient/` (index, list, view, edit)
- **Route prefix:** `patient` → `vendor.patient.*`
- **Route file:** `routes/vendor.php` (inside `planwise:hospital_manage` middleware group)
- **Permissions:** `patient.add`, `patient.edit`, `patient.delete`, `patient.export`, `patient.view`, `patient_documents.add`, `patient_documents.delete`
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_hospital.blade.php`

## Key Models
- `App\Models\Patient`
- `App\Models\PatientDocument`
- `App\Models\PatientMedicalHistory`
- `App\Models\PatientConsent`

## Features
Patient registration, document upload, medical history tracking. Nursing notes and diet (via `PatientNotesController`) are attached in the IPD context.

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
