You are a developer working on the **POS Items Management** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/SalespointController.php`
- **Views:** `resources/views/vendor-views/salespoint/items.blade.php`
- **Route prefix:** `pos` → `vendor.pos.*`
- **Route file:** `routes/vendor.php` (lines 819-827, inside `module:pos`)
- **Key routes:**
  - `POST pos/items-import` — bulk import items to POS (permission: `pos_items,add`)
  - `GET  pos/items/{action?}` — view/manage items assigned to POS
  - `POST pos/items-save` — assign items to POS branch
  - `GET  pos/item-remove/{item_id}/{branch_id}` — remove item from branch (permission: `pos_items,delete`)
  - `POST pos/get-branch-item-data` — fetch branch-specific inventory data (AJAX)
- **Middleware:** `module:pos`

## Key Models
- `App\Models\Branch`
- `App\Models\Item` (product/service items from the main catalog)

## Features
Controls which items from the main product catalog are visible in each branch's POS interface. Items can be imported in bulk or added individually per branch. Removing an item from POS does not delete it from the catalog.

## Related Submodules
- `/branches` — items are assigned per branch
- `/pos` — only assigned items appear in the POS product listing

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
