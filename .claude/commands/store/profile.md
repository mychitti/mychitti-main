You are a developer working on the **Store Profile & Business Settings** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/VendorController.php` — store profile
- **Settings Controller:** `app/Http/Controllers/Vendor/BusinessSettingsController.php` — business settings
- **Views:** `resources/views/vendor-views/vendor/`, `resources/views/vendor-views/business-settings/`
- **Route prefix:** `business-settings` → `vendor.business-settings.*`
- **Route file:** `routes/vendor.php`
- **Key routes:**
  - `GET  business-settings` — business settings (name, address, phone, logo, etc.)
  - `POST business-settings/update` — update business settings
  - `GET  settings` — general settings
  - `GET  shop-settings` — shop/store appearance settings
  - `GET  shop` — preview business webpage

## Key Models
- `App\Models\Store` → table `stores`
  - Columns: `id`, `vendor_id`, `name`, `phone`, `email`, `address`, `logo`, `cover_photo`, `description`, `gst`, `minimum_order`, `delivery_time`, `free_delivery_over`, `is_approved`, `active`, `module_id`, `zone_id`
- `App\Models\StoreConfig` → table `store_configs` (dynamic — `store_id` based)
  - Key fields: `invoice_template`, `pos_*`, various toggle settings

## Key Notes
- `stores.vendor_id` = vendor user ID
- `StoreConfig` uses dynamic table — always query by `store_id`
- `invoice_template` in `StoreConfig` controls billing PDF template (`service_n_manual` or `service_n_manual_new`)
- Store is approved/activated by admin — `is_approved = 1`, `active = 1`
- Always use `Helpers::get_store_id()` in vendor controllers to get current store ID

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
