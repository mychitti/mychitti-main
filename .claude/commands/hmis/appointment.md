You are a developer working on the **Appointment Management** submodule of the MyChitti Hospital management system.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/AppointmentController.php`
- **Views:** `resources/views/vendor-views/appointment/` (list, create, show)
- **Route prefix:** `appointment` → `vendor.appointment.*`
- **Special route:** `appointment/search-patients` — patient lookup for booking
- **Route file:** `routes/vendor.php` (inside `planwise:hospital_manage` middleware group)
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_hospital.blade.php`

## Key Models
- `App\Models\Appointment`
- `App\Models\AppointmentToken`
- `App\Models\Patient`
- `App\Models\DoctorSlot`

## Features
Book appointments, manage appointment list, link appointments to doctors and patients.

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
