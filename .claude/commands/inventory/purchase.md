You are a developer working on the **Inventory Purchase Orders** submodule of the MyChitti platform.

## Submodule Scope
- **Vendor Controller:** `app/Http/Controllers/Vendor/InventoryPurchaseController.php`
- **Admin Controller:** `app/Http/Controllers/Admin/InventoryPurchaseController.php`
- **Views:** `resources/views/vendor-views/inventory/` (purchase-related views)
- **Admin Views:** `resources/views/admin-views/inventory/`
- **Route prefix:** `inventory/purchase` → `vendor.inventory.purchase.*`
- **Route file:** `routes/vendor.php` (inside `planwise:inventory_manage`) — mirrored in `routes/admin.php`
- **Key routes:**
  - `GET  inventory/purchase/orders` → list purchase orders (permission: `inventory_purchase_order,list`)
  - `POST inventory/purchase/order-place` → create purchase order (permission: `inventory_purchase_order,add`)
  - `GET  inventory/purchase/export_orders` → export purchase orders (permission: `inventory_purchase_order,export`)
  - `GET  inventory/purchase/return` → purchase return form (permission: `inventory_purchase_return,add`)
  - `POST inventory/purchase/return-store` → save purchase return (permission: `inventory_purchase_return,add`)
  - `POST inventory/purchase/items-in-invoice` → fetch items in invoice (permission: `inventory_purchase_return,add`)
- **Permissions:** `inventory_purchase_order` (list, add, export), `inventory_purchase_return` (add)
- **Middleware:** `planwise:inventory_manage`

## Key Models
- `App\Models\PurchaseOrder`
- `App\Models\ReturnPurchaseSlip`
- `App\Models\Item`
- `App\Models\InventoryItem`

## Features
Create and manage purchase orders for restocking inventory items. Record goods received against orders to update stock levels. Handle purchase returns (return slips) when items need to be sent back to supplier.

## Related Submodules
- `/entry` — stock entries may be generated on goods receipt
- `/items` — items are selected when creating purchase orders
- `/gatepass` — gatepass routes also dispatch through `InventoryPurchaseController@store`

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
