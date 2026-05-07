You are a developer working on the **Staff Management** submodule of the MyChitti HR module.

## Submodule Scope
- **Staff Controller:** `app/Http/Controllers/Vendor/StaffController.php`
  - Handles: `staff/save-info`, `staff/add`, `staff/settings`, `staff/save-settings`, `staff/status/{id}/{status}`, `staff/team/*`
- **Employee Controller:** `app/Http/Controllers/Vendor/EmployeeController.php`
  - Handles: `staff/add-new`, `staff/list`, `staff/edit/{id}`, `staff/delete/{id}`
- **Basic Staff Controller:** `app/Http/Controllers/Vendor/BasicStaffController.php` (free tier, max 10 staff)
- **Views:** `resources/views/vendor-views/employee/`, `resources/views/vendor-views/department/`
- **Route file:** `routes/vendor.php` (inside `planwise:hr_manage` middleware group, lines 713-732)
- **Key routes:**
  - `GET  staff/add-new` → `EmployeeController` — add new staff form
  - `GET  staff/list` → `EmployeeController` — staff list
  - `GET  staff/edit/{id}` → `EmployeeController` — edit form
  - `DELETE staff/delete/{id}` → `EmployeeController` — delete
  - `POST staff/save-info` → `StaffController` — save employee data
  - `GET  staff/settings` → `StaffController` — settings
  - `GET  staff/status/{id}/{status}` → `StaffController` — change status
  - `staff/team/*` → `StaffController` — team CRUD (index, save, delete, edit, update, view, member.delete)
  - `staff-department/` — department list/save/delete/status-change — **outside** `planwise:hr_manage`, per-route `permission:staff_department` only
  - `custom-role` — staff roles — **outside** `planwise:hr_manage`, per-route `permission:staff_role` only
  - `basic-staff/*` — free-tier staff CRUD — **outside** `planwise:hr_manage` guard
- **Permissions:** `staff_manage` (add, edit, delete, view, export, settings, comment, status_change, terminate, resignation), `staff_department`, `staff_team`, `staff_role`
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_default.blade.php`

## Key Models
- `App\Models\VendorEmployee` (primary model — used by both free and premium tier)
- `App\Models\EmployeeRole`
- `App\Models\EmployeeTimeCard`
- `App\Models\StoreEmployeeComment`
- `App\Models\Department`

## Two-Tier System
- **Free tier:** `basic-staff/*` routes via `BasicStaffController` — no planwise guard, max 10 members
- **Premium tier:** `staff/*` routes via `StaffController` + `EmployeeController` — requires `planwise:hr_manage`

## Related Submodules
- `/attendance` — attendance is tracked per employee
- `/leave` — leave requests tied to employees
- `/employee` — admin-side actions: timecard, resign, terminate

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery
- Eloquent models for queries; raw SQL (`DB::statement()`) only for schema changes — never generate migration files
- Controllers extend `App\Http\Controllers\Controller`
- Icons: `tio-*` set; views extend `layouts.vendor.app`

## Behaviour
1. Read relevant files first
2. Outline plan — files to create/modify, SQL to run, routes/env vars needed
3. Ask for confirmation before making changes
4. Proceed only after confirmation
5. Write complete, production-ready code — no placeholders

$ARGUMENTS
