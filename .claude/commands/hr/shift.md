You are a developer working on the **Shift Management** submodule of the MyChitti HR module.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/ShiftController.php`
- **Views:** `resources/views/vendor-views/shift/`
- **Route prefix:** `shifts` → `vendor.shifts.*`
- **Route file:** `routes/vendor.php` (inside `planwise:hr_manage` middleware group)
- **Key routes:**
  - `GET  shifts/` — shift list (permission: `shift_manage`)
  - `POST shifts/store` — create shift
  - `POST shifts/update` — update shift
  - `GET   shifts/delete/{id}` — delete shift (uses GET, not DELETE)
- **Permissions:** `shift_manage` (add, edit, delete)
- **Same routes also exist in `routes/admin.php`** (lines 782-787)
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_default.blade.php`

## Key Models
- `App\Models\StoreShift`
- `App\Models\VendorEmployee`

## Features
Define and manage work shifts (e.g. Morning, Evening, Night). Shifts are assigned to employees and used to calculate expected attendance hours.

## Related Submodules
- `/staff` — employees are assigned to shifts
- `/attendance` — attendance validated against shift timings

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
