You are a developer working on the **POS Reports & Calendar** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/SalespointController.php`
- **Admin Controller:** `app/Http/Controllers/Admin/SalespointController.php`
- **Views:** `resources/views/vendor-views/salespoint/` (report.blade.php, calendar.blade.php, dashboard.blade.php)
- **Admin Views:** `resources/views/admin-views/salespoint/` (calendar-js.blade.php)
- **Form Modals:** `resources/views/vendor-views/form_modals/pos_calendar.blade.php`, `pos_item_modal.blade.php`
- **Route file:** `routes/vendor.php` (lines 793-797, 825-827) and `routes/admin.php` (lines 710-714)
- **Key routes:**
  - `GET  pos/dashboard` → `SalespointController@dashboard` (permission: `pos,dashboard`)
  - `GET  pos/report/{action?}` → `SalespointController@report` (permission: `pos,report`)
  - `GET  pos/calendar` → `SalespointController@calendar`
  - `GET  pos/calendar-export` → export calendar data
  - `POST pos/get-branch-item-data` → fetch branch inventory for reports (AJAX)
  - Admin: `GET admin/pos/report/{action?}` — admin POS report view
  - Admin: `GET admin/pos/calendar-export`
- **Middleware:** `module:pos`
- **Permissions:** `pos,dashboard`, `pos,report`

## Key Models
- `App\Models\PosToken`
- `App\Models\PosTokenItem`
- `App\Models\Branch`

## Features
POS dashboard with summary stats (sales, tokens, revenue). Detailed reports with date filtering and export. Calendar view of POS activity. All read-heavy — aggregates over PosToken and PosTokenItem.

## Related Submodules
- `/tokens` — all report data comes from token records
- `/branches` — reports are filterable by branch

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery
- Eloquent models for queries; raw SQL (`DB::statement()`) only for schema changes — never generate migration files
- Guarded by `module:pos` middleware
- Controllers extend `App\Http\Controllers\Controller`
- Icons: `tio-*` set; views extend `layouts.vendor.app` (vendor) / `layouts.admin.app` (admin)

## Behaviour
1. Read relevant files first
2. Outline plan — files to create/modify, SQL to run, routes/env vars needed
3. Ask for confirmation before making changes
4. Proceed only after confirmation
5. Write complete, production-ready code — no placeholders

$ARGUMENTS
