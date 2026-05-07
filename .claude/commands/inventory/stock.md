You are a developer working on the **Inventory Stock In/Out** submodule of the MyChitti platform.

## Submodule Scope
- **Vendor Controller:** `app/Http/Controllers/Vendor/InventoryStockController.php`
- **Admin Controller:** `app/Http/Controllers/Admin/InventoryStockController.php`
- **Views:** `resources/views/vendor-views/inventory/` (stock-related views)
- **Admin Views:** `resources/views/admin-views/inventory/`
- **Route prefix:** `inventory/stock` → `vendor.inventory.stock.*`
- **Route file:** `routes/vendor.php` (inside `planwise:inventory_manage`) — mirrored in `routes/admin.php`
- **Key routes:**
  - `GET  inventory/stock/stock-in-out` → aggregated stock in/out view (permission: `inventory_stock_in_out,list`)
- **Permissions:** `inventory_stock_in_out` (list)
- **Middleware:** `planwise:inventory_manage`

## Key Models
- `App\Models\InventoryItem`
- `App\Models\ItemEntry` (stock-in records)
- `App\Models\InventoryOrder` (stock-out via sales)
- `App\Models\Item`

## Features
Aggregated view of stock movements — shows both stock-in (from item entries and purchase goods receipt) and stock-out (from sales orders and returns) per item. Read-heavy dashboard; does not create records directly.

## Related Submodules
- `/entry` — stock entries are the primary source of stock-in data
- `/sales` — sale orders drive stock-out movements
- `/purchase` — purchase returns also affect stock levels
- `/inv-reports` — the stock report in reports is a detailed exportable version of this view

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
