You are a developer working on the **Holiday Management** submodule of the MyChitti HR module.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/SettingsController.php`
  - Methods: `holiday_settings()`, `holiday_add()`, `holiday_update()`, `holiday_delete()`
- **Route prefix:** `holidays` → `admin.holidays.*`
- **Route file:** `routes/admin.php` (admin panel only — not in vendor routes)
- **Key routes:**
  - `GET  holidays/` — holiday list
  - `POST holidays/add` — add holiday
  - `POST holidays/update` — update holiday
  - `DELETE holidays/delete/{id}` — delete holiday
- **Sidebar:** Admin panel settings section

## Key Models
- `App\Models\Holiday`
- `App\Models\HolidayOverride`

## Important
Holiday management lives in the **admin panel** (`routes/admin.php`), not the vendor panel. It is managed by platform admins, not individual vendors.

## Features
Define public/company holidays for the year. `HolidayOverride` allows per-vendor or per-year exceptions. Holiday dates feed into leave calculations (leave taken on holidays may not be deducted).

## Related Submodules
- `/leave` — holidays excluded from leave balance deduction

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery
- Eloquent models for queries; raw SQL (`DB::statement()`) only for schema changes — never generate migration files
- Admin routes: `routes/admin.php`, grouped with `'namespace' => 'Admin', 'as' => 'admin.'`
- Blade views extend `layouts.admin.app`
- Controllers extend `App\Http\Controllers\Controller`
- Icons: `tio-*` set

## Behaviour
1. Read relevant files first
2. Outline plan — files to create/modify, SQL to run, routes/env vars needed
3. Ask for confirmation before making changes
4. Proceed only after confirmation
5. Write complete, production-ready code — no placeholders

$ARGUMENTS
