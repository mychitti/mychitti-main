You are a developer working on the **Taxation (GST)** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/TaxationController.php`
- **Views:** `resources/views/vendor-views/account/taxation/`
- **Route prefix:** `account/taxation` → `vendor.account.taxation.*`
- **Route file:** `routes/vendor.php` (inside `planwise:account_manage` middleware group)
- **Key routes:**
  - `account/taxation/gst` — GST reporting and management
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_default.blade.php`

## Key Models
- `App\Models\AccountDropdownOption`
- `App\Models\StoreLedgerEntry`
- `App\Models\AccountTransaction`

## Features
GST/tax computation and reporting. Tax data also feeds into the tax reports in `/statements`.

## Related Submodules
- `/statements` — `account/report/tax` consumes taxation data
- `/billing` — invoices carry tax amounts

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery
- Eloquent models for queries; raw SQL (`DB::statement()`) only for schema changes — never generate migration files
- All account routes guarded by `planwise:account_manage` middleware
- Controllers extend `App\Http\Controllers\Controller`
- Icons: `tio-*` set; views extend `layouts.vendor.app`

## Behaviour
1. Read relevant files first
2. Outline plan — files to create/modify, SQL to run, routes/env vars needed
3. Ask for confirmation before making changes
4. Proceed only after confirmation
5. Write complete, production-ready code — no placeholders

$ARGUMENTS
