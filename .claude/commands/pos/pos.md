You are a developer working on the **POS Interface (Cart & Checkout)** submodule of the MyChitti platform.

## Submodule Scope
- **Vendor Controller:** `app/Http/Controllers/Vendor/POSController.php`
- **Admin Controller:** `app/Http/Controllers/Admin/POSController.php`
- **Vendor Views:** `resources/views/vendor-views/pos/` (index, invoice, _cart, _address, _quick-view-data, _quick-view-cart-item, _single_product)
- **Admin Views:** `resources/views/admin-views/pos/`
- **Route prefix:** `pos` → `vendor.pos.*` / `admin.pos.*`
- **Route file:** `routes/vendor.php` (lines 852-876) and `routes/admin.php` (lines 1656-1679)
- **Middleware:** `module:pos`
- **Key routes (vendor & admin mirror each other):**
  - `GET  pos/` — POS product listing / main interface
  - `POST pos/variant_price` — calculate variant pricing
  - `GET  pos/quick-view` — quick product view
  - `GET  pos/quick-view-cart-item` — quick cart item view
  - `POST pos/add-to-cart` — add item to cart
  - `POST pos/remove-from-cart` — remove from cart
  - `POST pos/cart-items` — get current cart
  - `POST pos/update-quantity` — update item quantity
  - `POST pos/empty-cart` — clear cart
  - `POST pos/tax` — update tax
  - `POST pos/discount` — apply discount
  - `POST pos/paid` — update paid amount
  - `GET  pos/customers` — customer lookup
  - `POST pos/order` — place order / checkout
  - `POST pos/customer-store` — create new customer on-the-fly
  - `POST pos/add-delivery-info` — add delivery address
  - `GET  pos/data` — fetch extra charges
  - `GET  pos/invoice/{id}` — generate invoice (admin only)

## Key Models
- `App\Models\Cart`
- `App\Models\PosToken`
- `App\Models\PosTokenItem`
- `App\Models\Branch`
- `App\Models\OrderType`
- `App\Models\StoreCustomer` (customer lookup/creation)

## Features
The main POS screen: browse products, manage cart, apply discounts/tax, select customer, choose order type (dine-in/takeaway/delivery), place order, generate invoice.

## Related Submodules
- `/tokens` — placed orders become POS tokens
- `/branches` — items are branch-specific
- `/dinein` — dine-in order flow uses table selection
- `/order-types` — order type config

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery
- Eloquent models for queries; raw SQL (`DB::statement()`) only for schema changes — never generate migration files
- Guarded by `module:pos` middleware
- Controllers extend `App\Http\Controllers\Controller`
- Icons: `tio-*` set; vendor views extend `layouts.vendor.app`, admin views extend `layouts.admin.app`

## Behaviour
1. Read relevant files first
2. Outline plan — files to create/modify, SQL to run, routes/env vars needed
3. Ask for confirmation before making changes
4. Proceed only after confirmation
5. Write complete, production-ready code — no placeholders

$ARGUMENTS
