You are a developer working on the **Orders** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/OrderController.php`
- **Views:** `resources/views/vendor-views/order/`
- **Route prefix:** `order` → `vendor.order.*` (middleware: `module:order`)
- **Route file:** `routes/vendor.php`
- **Key routes:**
  - `GET  order/list` — all orders
  - `GET  order/detail/{id}` — order detail with items
  - `POST order/status/{id}` — update order status
  - `GET  order/invoice/{id}` — order invoice/receipt
  - `GET  order/export` — export orders

## Key Models
- `App\Models\Order` → table `orders`
  - Columns: `id`, `store_id`, `user_id`, `module_id`, `order_status`, `payment_method`, `payment_status`, `order_amount`, `coupon_discount_amount`, `store_discount_amount`, `delivery_charge`, `order_type`, `delivery_man_id`, `coupon_code`, `created_at`
  - `order_type` values: `delivery`, `take_away`, `parcel`, `pos`, `dine_in`
  - `order_status` flow: `pending` → `confirmed` → `processing` → `handover` → `picked_up` → `delivered`
  - Also: `canceled`, `refunded`, `failed`
- `App\Models\OrderDetail` → table `order_details`
  - Columns: `id`, `order_id`, `item_id`, `quantity`, `price`, `variation`, `add_on_ids`
- `App\Models\OrderDeliveryHistory` — status change history

## Key Notes
- POS orders share this same `orders` table — filter by `order_type = 'pos'` for POS-only queries
- Always filter by `store_id` for vendor scope
- `module:order` middleware — depends on module activation, not planwise
- `order_amount` is the final amount after discounts and delivery charges

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery
- Eloquent models for queries; raw SQL (`DB::statement()`) only for schema changes — never generate migration files
- Controllers extend `App\Http\Controllers\Controller`
- Icons: `tio-*` set; views extend `layouts.vendor.app`

## Behaviour
1. Read relevant files first
2. Outline plan — files to create/modify, SQL to run, routes/env vars needed
3. Ask for confirmation before making changes
4. Proceed only after confirmation
5. Write complete, production-ready code — no placeholders

$ARGUMENTS
