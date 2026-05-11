You are a developer working on the **Document Gatepass** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/GatepassController.php` (documents gatepass — different from inventory gatepass)
- **Views:** `resources/views/vendor-views/documents/gatepass/`
- **Route prefix:** `documents/gatepass` → `vendor.documents.gatepass.*` (middleware: `module:documents`)
- **Route file:** `routes/vendor.php`
- **Key routes:**
  - `GET  documents/gatepass` — gatepass list
  - `POST documents/gatepass/save` — create gatepass
  - `GET  documents/gatepass/detail/{id}` — gatepass detail
  - `GET  documents/gatepass/delete/{id}` — delete gatepass
  - `GET  documents/gatepass/print/{id}` — print gatepass

## Key Models
- `App\Models\InventoryGatepass` → table `inventory_gatepasses`
  - Columns: `id`, `store_id`, `type` (`inward`/`outward`), `status`, `created_at`
- `App\Models\InventoryGatepassItem` → table `inventory_gatepass_items`
  - Columns: `id`, `gatepass_id`, `item_id`, `quantity`
- `App\Models\GatePassItem` → table `gate_pass_items` (may be used for job-card gatepasses)

## Key Notes
- Documents gatepass (`documents/gatepass`) tracks goods movement for service/field jobs
- Inventory gatepass (`inventory/gatepass`) tracks warehouse stock movement — different module
- `module:documents` middleware — not planwise
- `type` values: `inward` (items received), `outward` (items dispatched)

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
