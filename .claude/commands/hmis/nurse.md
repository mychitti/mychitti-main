You are a developer working on the **Nurse (Staff) Management** submodule of the MyChitti HMIS.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/NurseController.php`
- **Views:** `resources/views/vendor-views/nurse/` (list, add, edit, view)
- **Route prefix:** `nurse` → `vendor.nurse.*`
- **Route file:** `routes/vendor.php` (inside `planwise:hospital_manage` middleware group)
- **Permissions:** `staff_nurse.list`, `staff_nurse.add`, `staff_nurse.export`
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_hospital.blade.php`

## Key Models
- `App\Models\NurseProfile`

## Features
Nurse registration and profile management. Nurses are linked to IPD admissions via nursing notes (recorded through PatientNotesController).

## Related Submodules
- IPD: `vendor.ipd.*` — nursing notes recorded against admissions

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
