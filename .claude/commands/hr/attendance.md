You are a developer working on the **Attendance Management** submodule of the MyChitti HR module.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/AttendanceController.php`
- **Self-service Controller:** `app/Http/Controllers/Vendor/VendorEmployeeController.php` (employee's own attendance view)
- **Export:** `app/Http/Controllers/Vendor/AttendanceExport.php`
- **Views:** `resources/views/vendor-views/attendance/`
- **Route prefix:** `attendance` → `vendor.attendance.*`
- **Route file:** `routes/vendor.php` (inside `planwise:hr_manage` middleware group)
- **Routes inside `planwise:hr_manage`:**
  - `GET  attendance/list` → `AttendanceController@index` (permission: `attendance_manage,list`)
  - `GET  attendance/manage/{id}` → `AttendanceController@manage`
  - `POST attendance/save` → `AttendanceController@save`
  - `GET  attendance/report` → `AttendanceController@report` (permission: `attendance_report`)
  - `GET  attendance/export` → `AttendanceExport`
- **Employee self-service (outside `planwise:hr_manage`):**
  - `GET /attendance` → `VendorEmployeeController@attendance` — employee views own attendance
- **Permissions:** `attendance_manage` (list, edit, view), `attendance_report` (export)
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_default.blade.php`

## Key Models
- `App\Models\Attendance`
- `App\Models\VendorEmployee`
- `App\Models\EmployeeTimeCard`

## Features
Daily attendance tracking per employee, attendance editing/correction, attendance reports and export.

## Clock-in / Clock-out
Clock routes are **outside** `planwise:hr_manage` and handled by a separate controller:
- Vendor panel: `GET /clock-in`, `GET /clock-out` → `VendorEmployeeController` (routes/vendor.php line ~42-43)
- Admin panel: `GET employee/clock-in`, `GET employee/clock-out` → `Admin/Employee/EmployeeController` (routes/admin.php)

## Related Submodules
- `/staff` — employees being tracked
- `/employee` — clock-in/clock-out actions
- `/shift` — shifts define expected working hours

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery
- Eloquent models for queries; raw SQL (`DB::statement()`) only for schema changes — never generate migration files
- All HR routes guarded by `planwise:hr_manage` middleware
- Controllers extend `App\Http\Controllers\Controller`
- Icons: `tio-*` set; views extend `layouts.vendor.app`

## Behaviour
1. Read relevant files first
2. Outline plan — files to create/modify, SQL to run, routes/env vars needed
3. Ask for confirmation before making changes
4. Proceed only after confirmation
5. Write complete, production-ready code — no placeholders

$ARGUMENTS
