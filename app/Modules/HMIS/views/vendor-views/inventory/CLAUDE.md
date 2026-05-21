# Inventory Module

## Controllers
- `app/Http/Controllers/Vendor/InventoryController.php` — items CRUD, barcode, variants, export
- `app/Http/Controllers/Admin/InventoryController.php` — admin mirror of same routes

## Routes
- File: `routes/vendor.php` (lines 229–314, inside `planwise:inventory_manage`)
- Prefix: `inventory/item` → `vendor.inventory.item.*`
- Admin mirror in `routes/admin.php` lines 487–571

## Key Routes
- `GET  inventory/item` — item list (index)
- `GET  inventory/item/scan-barcode` — barcode scanner
- `POST inventory/item/fetch-by-sku` — fetch item by SKU (AJAX)
- `GET  inventory/item/detail/{id}` — item detail (`permission:inventory_item,view`)
- `GET  inventory/item/delete/{id}` — delete (`permission:inventory_item,delete`)
- `POST inventory/item/bulk-delete` — bulk delete
- `GET  inventory/item/export` — export all (`permission:inventory_item,export`)
- `GET  inventory/item/print/{item_id}/{type}` — print barcode/label
- `POST inventory/item/variant-combination` — generate variant combinations
- `POST inventory/item/update-variant-combination` — update variant
- `POST inventory/item-images-store` — store item images
- `inventory/entry/*` — stock entry (purchase/inward)
- `inventory/stock/*` — stock adjustments, reports
- `inventory/warehouse/*` — warehouse management
- `inventory/gatepass/*` — gate pass (inward/outward)

## Key Models
- `App\Models\InventoryItem`
- `App\Models\InventoryVariant`
- `App\Models\InventoryEntry`
- `App\Models\InventoryStock`
- `App\Models\Warehouse`
- `App\Models\Category` (for item categorisation)

## Permissions
- `inventory_item`: add, edit, delete, export, view, show_on_website
- Middleware: `planwise:inventory_manage`

## Views
- `resources/views/vendor-views/inventory/` — item list, edit, detail, scanner, PDF label templates
- `resources/views/admin-views/inventory/` — admin mirror
- PDF templates in `resources/views/vendor-views/inventory/pdf_templates/`: labels, label_barcode, label_description, label_full, single_label

## Key Notes
- SKU must be unique per store
- Variant combinations generated from attribute sets
- Barcode scanner uses browser camera API
- Low stock threshold configurable per item
