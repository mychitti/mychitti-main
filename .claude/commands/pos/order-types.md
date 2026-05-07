You are a developer working on the **POS Order Types** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/OrderTypeController.php`
- **Route prefix:** `pos/order-type` → `vendor.pos.order-type.*`
- **Route file:** `routes/vendor.php` (lines 838-842, inside `module:pos`)
- **Key routes:**
  - `POST pos/order-type/store` — create order type (permission: `pos_order_type,add`)
  - `GET  pos/order-type/delete/{id}` — delete order type (permission: `pos_order_type,delete`)
  - `POST pos/order-type/update` — update order type (permission: `pos_order_type,edit`)
- **Middleware:** `module:pos`

## Key Models
- `App\Models\OrderType`

## Features
Order types define how an order is fulfilled — e.g. Dine-In, Takeaway, Delivery, Drive-Through. Each order type can have different behaviour (e.g. dine-in triggers table selection). Configured per store.

## Related Submodules
- `/pos` — order type selected at checkout
- `/dinein` — dine-in order type triggers table flow

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery
- Eloquent models for queries; raw SQL (`DB::statement()`) only for schema changes — never generate migration files
- Guarded by `module:pos` middleware
- Controllers extend `App\Http\Controllers\Controller`
- Icons: `tio-*` set; views extend `layouts.vendor.app`

## Behaviour
1. Read relevant files first
2. Outline plan — files to create/modify, SQL to run, routes/env vars needed
3. Ask for confirmation before making changes
4. Proceed only after confirmation
5. Write complete, production-ready code — no placeholders

$ARGUMENTS
