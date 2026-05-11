You are a developer working on the **Coupons** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/CouponController.php`
- **Views:** `resources/views/vendor-views/coupon/`
- **Route prefix:** `coupon` → `vendor.coupon.*` (middleware: `module:coupon`)
- **Route file:** `routes/vendor.php`
- **Key routes:**
  - `GET  coupon` — coupon list
  - `GET  coupon/add-new` — add coupon form
  - `POST coupon/save` — save coupon
  - `GET  coupon/edit/{id}` — edit coupon
  - `GET  coupon/status/{id}/{status}` — toggle active/inactive
  - `GET  coupon/delete/{id}` — delete coupon

## Key Models
- `App\Models\Coupon` → table `coupons`
  - Columns: `id`, `store_id` (nullable), `code`, `title`, `discount`, `discount_type` (`percentage`/`flat`), `coupon_type` (`store_wise`/`first_order`/`customer_wise`), `min_purchase`, `max_discount`, `limit`, `start_date`, `expire_date`, `status`, `created_by` (`vendor`/`admin`), `total_uses`, `module_id`
- `App\Models\CouponCondition` — optional extra conditions

## Key Notes
- `store_id` is **nullable** — admin coupons have `store_id = null`, vendor coupons have `store_id = storeId`
- Always filter `WHERE store_id = storeId` for vendor scope
- `coupon_type` values: `store_wise` (any order at this store), `first_order`, `customer_wise`
- `total_uses` auto-increments on each redemption — do not set manually
- Active coupon = `status = 1` AND `expire_date >= today`
- `module:coupon` — not planwise premium

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
