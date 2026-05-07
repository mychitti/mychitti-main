You are a developer working on the **Inventory Settings, Dashboard & Categories** submodule of the MyChitti platform.

## Submodule Scope
- **Vendor Controller:** `app/Http/Controllers/Vendor/InventoryController.php`
- **Admin Controller:** `app/Http/Controllers/Admin/InventoryController.php`
- **Views:** `resources/views/vendor-views/inventory/` (settings, dashboard, category views)
- **Admin Views:** `resources/views/admin-views/inventory/`
- **Route file:** `routes/vendor.php` (inside `planwise:inventory_manage`) — mirrored in `routes/admin.php`
- **Key routes:**
  - `GET  inventory/dashboard` → inventory dashboard (`InventoryController@dashboard`, permission: `inventory,dashboard`)
  - `GET  inventory/` → inventory management index (`InventoryController@inventory_management`)
  - `GET  inventory/settings` → settings page (permission: `inventory,settings`)
  - `POST inventory/settings-save` → save settings (permission: `inventory,settings_save`)
  - `GET  inventory/category/` → list categories (`InventoryController@category`)
  - `POST inventory/category/store` → create category
  - `POST inventory/category/update` → update category
  - `POST inventory/category/delete/{id}` → delete category
- **Permissions:** `inventory` (dashboard, settings, settings_save)
- **Middleware:** `planwise:inventory_manage`

## Key Models
- `App\Models\InventoryItem`
- `App\Models\Item`
- `App\Models\ItemEntry`
- `App\Models\InventoryOrder`

## Features
Inventory dashboard with summary KPIs (total items, low stock, stock value, recent entries). Settings page for configuring inventory behaviour (e.g. low-stock threshold, currency, GST). Category management for organising items — note: `category/store`, `update`, `delete` routes share handler methods with storage units (`storage_spaces_store`, `storage_spaces_update`, `storage_spaces_delete`).

## Note
- Category CRUD routes call `InventoryController@storage_spaces_store/update/delete` — same methods as storage units. Check the controller before adding category-specific logic.

## Related Submodules
- `/items` — items belong to categories
- `/warehouse` — storage unit routes share the same controller methods
- `/inv-reports` — dashboard KPIs are a summary of report data

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
