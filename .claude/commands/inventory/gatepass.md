You are a developer working on the **Inventory Gatepass** submodule of the MyChitti platform.

## Submodule Scope
- **List/Return Controller:** `app/Http/Controllers/Vendor/InventoryGatepassController.php`
- **Store Controller:** `app/Http/Controllers/Vendor/InventoryPurchaseController.php` (gatepass store dispatches through this)
- **Admin Controllers:** `app/Http/Controllers/Admin/InventoryGatepassController.php`, `app/Http/Controllers/Admin/InventoryPurchaseController.php`
- **Views:** `resources/views/vendor-views/inventory/` (gatepass-related views)
- **Admin Views:** `resources/views/admin-views/inventory/`
- **Route prefix:** `inventory/gatepass` → `vendor.inventory.gatepass.*`
- **Route file:** `routes/vendor.php` (inside `planwise:inventory_manage`) — mirrored in `routes/admin.php`
- **Key routes:**
  - `GET  inventory/gatepass/{tab}` → list gatepasses by tab (`InventoryGatepassController@gatepass_list`)
  - `POST inventory/gatepass/store` → create gatepass (`InventoryPurchaseController@store`)
  - `GET  inventory/gatepass/return/{id}` → gatepass return form (`InventoryGatepassController@return`)
  - `POST inventory/gatepass/return-store` → save gatepass return (`InventoryGatepassController@return_store`)
- **Middleware:** `planwise:inventory_manage`

## Key Models
- `App\Models\InventoryGatepass`
- `App\Models\InventoryGatepassItem`
- `App\Models\Item`

## Features
Gatepass management for tracking items leaving the premises (delivery, loan, inspection). Each gatepass records items dispatched and expected return. Return store records items coming back in.

## Related Submodules
- `/purchase` — `InventoryPurchaseController@store` handles gatepass creation
- `/items` — gatepass items are drawn from the item catalog

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
