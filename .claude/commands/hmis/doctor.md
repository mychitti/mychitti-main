You are a developer working on the **Doctor (Staff) Management** submodule of the MyChitti HMIS.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/DoctorController.php`
- **Doctor Profile Controller:** `app/Http/Controllers/Vendor/MyDoctorProfileController.php`
- **Views:** `resources/views/vendor-views/doctor/` (list, add, edit, slots)
- **Route prefix:** `doctor` → `vendor.doctor.*`
- **Route file:** `routes/vendor.php` (inside `planwise:hospital_manage` middleware group)
- **Doctor profile routes:** `routes/vendor_employee.php` → `vendor.my-doctor-profile.*`
  - Methods: `edit`, `update`, `slotStore`, `slotToggle`, `slotDestroy`, `slotClone`
- **Permissions:** `staff_doctor.list`, `staff_doctor.add`, `staff_doctor.export`
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_hospital.blade.php`

## Key Models
- `App\Models\DoctorProfile`
- `App\Models\DoctorService`
- `App\Models\DoctorSlot`

## Features
Doctor registration, appointment slot configuration (available times), doctor public profile management. Doctors are linked to appointments and OPD visits.

## Related Submodules
- Appointments: `vendor.appointment.*`
- OPD: `vendor.opd.*`

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
