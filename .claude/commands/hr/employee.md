You are a developer working on the **Employee Self-Service & Admin Actions** submodule of the MyChitti HR module.

## Submodule Scope
- **Vendor Controller:** `app/Http/Controllers/Vendor/EmployeeController.php`
- **Admin Controller:** `app/Http/Controllers/Admin/Employee/EmployeeController.php` (clock-in/out in admin panel)
- **Vendor Employee Controller:** `app/Http/Controllers/Vendor/VendorEmployeeController.php` (clock-in/out + salary-history in vendor panel)
- **HR Controller:** `app/Http/Controllers/Vendor/HRController.php` (HR dashboard)
- **Views:** `resources/views/vendor-views/hr/`
- **Route file:** `routes/vendor.php`
- **Key routes — outside `planwise:hr_manage` (self-service, accessible without HR subscription):**
  - `GET  clock-in` → `VendorEmployeeController@clock_in`
  - `GET  clock-out` → `VendorEmployeeController@clock_out`
  - `GET  salary-history` → `VendorEmployeeController@my_salary_history`
  - `GET  advance-payment` — advance payment page
  - `POST employee/resign/{id}` — resignation (outside planwise guard)
  - `POST employee/resignation-action/{id}/{action}` — approve/reject resignation
- **Key routes — inside `planwise:hr_manage`:**
  - `GET  employee/view/{id}` — view employee profile
  - `GET  employee/view-id-card/{id}` — view employee ID card
  - `POST employee/terminate/{id}` — terminate employee
  - `GET  employee/timecards/{id}` — view employee timecards
  - `GET  hr/dashboard` — HR dashboard (permission: `hr_manage,dashboard`)
- **Admin routes (`routes/admin.php`):**
  - `GET employee/clock-in` → `Admin/Employee/EmployeeController@clock_in`
  - `GET employee/clock-out` → `Admin/Employee/EmployeeController@clock_out`
- **Permissions:** `staff_manage` (terminate, resignation)
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_default.blade.php`

## Key Models
- `App\Models\VendorEmployee`
- `App\Models\EmployeeTimeCard`
- `App\Models\Salary`
- `App\Models\AdvanceRequest`

## Features
Admin-side employee lifecycle actions: view profile, ID card, clock-in/out, resignation processing, termination. Employee self-service: salary history, advance payment requests. HR dashboard aggregates stats across all HR submodules.

## Related Submodules
- `/staff` — employee records managed here
- `/attendance` — timecards feed into attendance
- `/salary` (in `account/`) — salary history displayed here

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery
- Eloquent models for queries; raw SQL (`DB::statement()`) only for schema changes — never generate migration files
- Self-service routes (clock-in/out, salary-history, advance-payment, resign) are outside `planwise:hr_manage` — accessible to employees without HR subscription
- CRUD and admin actions are inside `planwise:hr_manage`
- Controllers extend `App\Http\Controllers\Controller`
- Icons: `tio-*` set; views extend `layouts.vendor.app`

## Behaviour
1. Read relevant files first
2. Outline plan — files to create/modify, SQL to run, routes/env vars needed
3. Ask for confirmation before making changes
4. Proceed only after confirmation
5. Write complete, production-ready code — no placeholders

$ARGUMENTS
