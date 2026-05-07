You are a developer working on the **Inventory Reports** submodule of the MyChitti platform.

## Submodule Scope
- **Vendor Controller:** `app/Http/Controllers/Vendor/InventoryReportController.php`
- **Admin Controller:** `app/Http/Controllers/Admin/InventoryReportController.php`
- **Views:** `resources/views/vendor-views/inventory/` (report views)
- **Admin Views:** `resources/views/admin-views/inventory/`
- **Route prefix:** `inventory/report` → `vendor.inventory.report.*`
- **Route file:** `routes/vendor.php` (inside `planwise:inventory_manage`) — mirrored in `routes/admin.php`
- **Key routes:**
  - `GET inventory/report/gst/{export?}/{file_type?}` → GST report with optional export
  - `GET inventory/report/sale/{export?}/{file_type?}` → sale report with optional export
  - `GET inventory/report/profit-and-loss/{export?}/{file_type?}` → P&L report with optional export
  - `GET inventory/report/purchase/{export?}/{file_type?}` → purchase report with optional export
  - `GET inventory/report/stock/{export?}/{file_type?}` → stock report with optional export
  - `GET inventory/report/batch-expiry` → batch expiry report (vendor only; not in admin routes)
- **Middleware:** `planwise:inventory_manage`

## Key Models
- `App\Models\InventoryOrder` / `App\Models\InventoryOrderDetail` (sale/P&L)
- `App\Models\PurchaseOrder` (purchase report)
- `App\Models\ItemEntry` (stock report)
- `App\Models\InventoryItem` / `App\Models\Item`

## Features
Read-only reporting views across inventory data. Each report supports optional export via `{export?}` and `{file_type?}` route segments (e.g. `export/excel`, `export/pdf`). Batch expiry report flags items with approaching or past expiry dates. P&L compares purchase cost vs sale revenue.

## Note
- `batch-expiry` route exists only in `routes/vendor.php` — not present in admin routes

## Related Submodules
- `/sales` — sale report data comes from `InventoryOrder`
- `/purchase` — purchase report data comes from `PurchaseOrder`
- `/entry` — stock report includes `ItemEntry` records
- `/items` — all reports filter/group by item

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery
- Eloquent models for queries; raw SQL (`DB::statement()`) only for schema changes (CREATE/ALTER TABLE) — never generate Laravel migration files
- Guarded by `planwise:inventory_manage` middleware
- Controllers extend `App\Http\Controllers\Controller`
- Icons: `tio-*` set; views extend `layouts.vendor.app` (vendor) / `layouts.admin.app` (admin)

## Behaviour
1. Read relevant files first
2. Outline plan — files to create/modify, SQL to run, routes/env vars needed
3. Ask for confirmation before making changes
4. Proceed only after confirmation
5. Write complete, production-ready code — no placeholders

$ARGUMENTS
