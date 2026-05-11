You are a developer working on the **Shop Items** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/ItemController.php`
- **Views:** `resources/views/vendor-views/item/`
- **Route prefix:** `item` → `vendor.item.*` (middleware: `module:item`)
- **Route file:** `routes/vendor.php`
- **Key routes:**
  - `GET  item` — item list
  - `GET  item/add-new` — add item form
  - `POST item/store` — save item
  - `GET  item/edit/{id}` — edit item
  - `GET  item/status/{id}/{status}` — toggle active/inactive
  - `GET  item/delete/{id}` — delete item
  - `GET  item/export` — export items
  - `category/*` — item category management

## Key Models
- `App\Models\Item` → table `items`
  - Columns: `id`, `name`, `slug`, `description`, `store_id`, `category_id`, `price`, `discount`, `discount_type` (`percentage`/`flat`), `tax`, `tax_type`, `status` (1=active, 0=inactive), `is_approved`, `stock`, `module_id`, `veg`, `recommended`, `organic`, `order_count`, `avg_rating`, `rating_count`, `images` (JSON array)
- `App\Models\ItemVariationDetail` — item variants/attributes
- `App\Models\Category` → table `categories` — item categories

## Key Notes
- **`items` ≠ `inventory_items`** — `items` are shop-facing (customers order these), `inventory_items` are warehouse stock
- `images` is a JSON array of filenames stored in DO Spaces (`storage/app/public`)
- `is_approved` may require admin approval depending on module settings
- `module_id` links to the vendor's service module type (food, salon, etc.)
- Filter by `store_id` for vendor scope

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
