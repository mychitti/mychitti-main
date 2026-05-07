You are a developer working on the **Inventory Sales Orders** submodule of the MyChitti platform.

## Submodule Scope
- **Vendor Controller:** `app/Http/Controllers/Vendor/InventoryOrderController.php`
- **Admin Controller:** `app/Http/Controllers/Admin/InventoryOrderController.php`
- **Views:** `resources/views/vendor-views/inventory/` (sale-order-related views)
- **Admin Views:** `resources/views/admin-views/inventory/`
- **Route prefix:** `inventory/sale` → `vendor.inventory.sale.*`
- **Route file:** `routes/vendor.php` (inside `planwise:inventory_manage`) — mirrored in `routes/admin.php`
- **Key routes:**
  - `GET  inventory/sale/orders` → list sale orders
  - `GET  inventory/sale/order-status/{id}/{status}` → change order status (permission: `inventory_sale_order,status_change`)
  - `GET  inventory/sale/order-export/{return?}` → export orders (optionally returns)
  - `GET  inventory/sale/orders-return` → list sale return orders (permission: `inventory_sale_return,add`)
  - `POST inventory/sale/order-details-fetch` → fetch order details (AJAX)
- **Permissions:** `inventory_sale_order` (status_change), `inventory_sale_return` (add)
- **Middleware:** `planwise:inventory_manage`

## Key Models
- `App\Models\InventoryOrder`
- `App\Models\InventoryOrderDetail`
- `App\Models\Item`
- `App\Models\InventoryItem`

## Features
View and manage customer sales orders placed through the website or other channels. Update order statuses (pending → confirmed → dispatched → delivered). Handle sale returns. Export order data.

## Related Submodules
- `/items` — sold items come from the item catalog
- `/stock` — fulfilled orders reduce stock quantities
- `/inv-reports` — sale report aggregates `InventoryOrder` data

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
