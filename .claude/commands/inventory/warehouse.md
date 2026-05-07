You are a developer working on the **Inventory Warehouse / Storage Units** submodule of the MyChitti platform.

## Submodule Scope
- **Vendor Controller:** `app/Http/Controllers/Vendor/InventoryController.php`
- **Admin Controller:** `app/Http/Controllers/Admin/InventoryController.php`
- **Views:** `resources/views/vendor-views/inventory/` (storage/warehouse views)
- **Admin Views:** `resources/views/admin-views/inventory/`
- **Route prefix:** `inventory/storage-unit` → `vendor.inventory.storage-unit.*`
- **Route file:** `routes/vendor.php` (inside `planwise:inventory_manage`) — mirrored in `routes/admin.php`
- **Key routes:**
  - `GET  inventory/storage-spaces` → list storage spaces/units (`InventoryController@storage_spaces`)
  - `GET  inventory/storage-unit/get-stands` → get stands for a unit (AJAX)
  - `POST inventory/storage-unit/store` → create storage unit (permission: `inventory_storage_units,add`)
  - `POST inventory/storage-unit/update` → update storage unit (permission: `inventory_storage_units,edit`)
  - `POST inventory/storage-unit/delete/{id}` → delete storage unit (permission: `inventory_storage_units,delete`)
- **Permissions:** `inventory_storage_units` (add, edit, delete)
- **Middleware:** `planwise:inventory_manage`

## Key Models
- `App\Models\StorageUnit`
- `App\Models\InventoryItem`

## Features
Define physical storage locations (warehouses, shelves, stands) for organising inventory. Items can be assigned to specific storage units. Supports hierarchical structure (unit → stands/locations).

## Related Submodules
- `/items` — items can be assigned to storage units
- `/entry` — stock entries may reference a storage unit

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
