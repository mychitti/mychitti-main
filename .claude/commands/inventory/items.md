You are a developer working on the **Inventory Items** submodule of the MyChitti platform.

## Submodule Scope
- **Vendor Controller:** `app/Http/Controllers/Vendor/InventoryController.php`
- **Admin Controller:** `app/Http/Controllers/Admin/InventoryController.php`
- **Views:** `resources/views/vendor-views/inventory/` (item_edit, item_detail, item_images, scanner)
- **Admin Views:** `resources/views/admin-views/inventory/`
- **PDF Templates:** `resources/views/vendor-views/inventory/pdf_templates/` (labels, label_barcode, label_description, label_full, single_label)
- **Route prefix:** `inventory/item` → `vendor.inventory.item.*`
- **Route file:** `routes/vendor.php` (lines 229-314, inside `planwise:inventory_manage`) — same routes mirrored in `routes/admin.php` (lines 487-571)
- **Key routes:**
  - `GET  inventory/item/scan-barcode` → barcode scanner view
  - `POST inventory/item/fetch-by-sku` → fetch item by SKU (AJAX)
  - `POST inventory/item/update-variant-combination` → update variant (permission: `inventory_item,edit`)
  - `GET  inventory/item/delete/{id}` → delete item (permission: `inventory_item,delete`)
  - `POST inventory/item/bulk-delete` → bulk delete
  - `GET  inventory/item/export` → export all items (permission: `inventory_item,export`)
  - `GET  inventory/item/export-selected` → export selected
  - `GET  inventory/item/detail/{id}` → item detail (permission: `inventory_item,view`)
  - `POST inventory/item/variant-combination` → generate variant combinations
  - `GET  inventory/item/print/{item_id}/{type}` → print barcode/label
  - `POST inventory/item-images-store` → store item images
- **Permissions:** `inventory_item` (add, edit, delete, export, view, show_on_website)
- **Middleware:** `planwise:inventory_manage`

## Key Models
- `App\Models\Item`
- `App\Models\InventoryItem`
- `App\Models\ItemVariationDetail`
- `App\Models\InvItemVariationDetail`
- `App\Models\BranchInventoryItem`
- `App\Models\Unit`

## Features
Full product/item catalog management with variant support (size, colour, etc.), barcode scanning and label printing, bulk operations, image management, website visibility toggle.

## Related Submodules
- `/entry` — stock entries add quantity to items
- `/pos-items` (POS module) — items assigned to POS branches
- `/purchase` — purchase orders receive items into stock

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery
- Eloquent models for queries; raw SQL (`DB::statement()`) only for schema changes — never generate migration files
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
