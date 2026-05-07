You are a developer working on the **Leave Management** submodule of the MyChitti HR module.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/LeaveController.php`
- **Views:** `resources/views/vendor-views/leave/`
- **Route prefix:** `leave` → `vendor.leave.*`
- **Route file:** `routes/vendor.php` and `routes/admin.php`
- **Routes inside `planwise:hr_manage` (vendor.php lines 195-202):**
  - `GET  leave/list` → `LeaveController@index` — leave list (permission: `leave_manage`)
  - `GET  leave/add` → `LeaveController@add` — add leave form
  - `POST leave/save` → `LeaveController@save_leave` — save leave
  - `POST leave/save-info` → `LeaveController@save_info`
  - `GET  leave/manage/{id}` → `LeaveController@manage`
  - `GET  leave/status/{id}/{status}` → `LeaveController@status` — change status
- **Employee self-service routes (outside `planwise:hr_manage`, via `VendorEmployeeController`):**
  - `GET  /leaves` → view own leave requests
  - `POST /leave-save` → submit leave request
  - `GET  /approve-leave/{id}` → approve leave
  - `GET  /reject-leave/{id}` → reject leave
- **Admin routes (`routes/admin.php`, outside planwise):**
  - `GET  leave/my-requests` → `LeaveController@myLeaves`
  - `POST leave/request` → `LeaveController@requestLeave`
- **Permissions:** `leave_manage` (add, status_change)
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_default.blade.php`

## Key Models
- `App\Models\Leave`
- `App\Models\VendorEmployee`
- `App\Models\Holiday`

## Features
Leave request submission by employees, approval/rejection workflow by managers, leave balance tracking. Holiday calendar affects leave calculations.

## Related Submodules
- `/staff` — employees requesting leave
- `/holiday` — public holidays excluded from leave count

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery
- Eloquent models for queries; raw SQL (`DB::statement()`) only for schema changes — never generate migration files
- Management routes inside `planwise:hr_manage`; employee self-service routes outside (accessible without HR subscription)
- Controllers extend `App\Http\Controllers\Controller`
- Icons: `tio-*` set; views extend `layouts.vendor.app`

## Behaviour
1. Read relevant files first
2. Outline plan — files to create/modify, SQL to run, routes/env vars needed
3. Ask for confirmation before making changes
4. Proceed only after confirmation
5. Write complete, production-ready code — no placeholders

$ARGUMENTS
