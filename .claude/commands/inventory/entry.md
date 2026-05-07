You are a developer working on the **Inventory Item Entry (Stock Entries)** submodule of the MyChitti platform.

## Submodule Scope
- **Vendor Controller:** `app/Http/Controllers/Vendor/InventoryController.php`
- **Admin Controller:** `app/Http/Controllers/Admin/InventoryController.php`
- **Views:** `resources/views/vendor-views/inventory/item_entry.blade.php`
- **Admin Views:** `resources/views/admin-views/inventory/item_entry.blade.php`
- **Route prefix:** `inventory/entry` → `vendor.inventory.entry.*`
- **Route file:** `routes/vendor.php` (inside `planwise:inventory_manage`) — mirrored in `routes/admin.php`
- **Key routes:**
  - `POST inventory/save-entry` → save stock entry (permission: `inventory_item_entry,add`)
  - `POST inventory/save-entry-pdf` → save entry and generate PDF
  - `GET  inventory/entries` → list all entries
  - `GET  inventory/entry/export` → export entries (permission: `inventory_item_entry,export`)
  - `GET  inventory/entry/export-selected` → export selected
  - `POST inventory/entry/bulk-delete` → bulk delete (permission: `inventory_item_entry,delete`)
  - `POST inventory/entry/import` → import entries (permission: `inventory_item_entry,import`)
- **Permissions:** `inventory_item_entry` (add, export, delete, import)
- **Middleware:** `planwise:inventory_manage`

## Key Models
- `App\Models\ItemEntry`
- `App\Models\Item`
- `App\Models\InventoryItem`

## Features
Stock entry records track when inventory is added (stock-in). Each entry logs item, quantity, batch, expiry, purchase price. Supports import/export and bulk operations.

## Related Submodules
- `/items` — entries add stock to items
- `/stock` — stock in/out view aggregates entries
- `/purchase` — purchase orders can generate entries on goods receipt

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery
- Eloquent models for queries; raw SQL (`DB::statement()`) only for schema changes — never generate migration files
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
