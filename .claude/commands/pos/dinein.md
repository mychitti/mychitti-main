You are a developer working on the **POS Dine-In & Restaurant Tables** submodule of the MyChitti platform.

## Submodule Scope
- **Tables Controller:** `app/Http/Controllers/Vendor/RestaurantTableController.php`
- **Dine-In Controller:** `app/Http/Controllers/Vendor/SalespointController.php`
- **Views:** `resources/views/vendor-views/salespoint/tables/` (index, create, edit)
- **Route prefix:** `pos` → `vendor.pos.*`
- **Route file:** `routes/vendor.php` (lines 785-790 for tables, 814-816 for dine-in, inside `module:pos`)
- **Table routes (resource):**
  - `GET    pos/restaurant-tables` — list tables (permission: `restaurant_tables,list`)
  - `GET    pos/restaurant-tables/create` — create form (permission: `restaurant_tables,create`)
  - `POST   pos/restaurant-tables` — store table
  - `GET    pos/restaurant-tables/{id}/edit` — edit form (permission: `restaurant_tables,edit`)
  - `PUT    pos/restaurant-tables/{id}` — update table
  - `DELETE pos/restaurant-tables/{id}` — delete table (permission: `restaurant_tables,delete`)
- **Dine-in routes:**
  - `POST pos/dine-in/open-table` → `SalespointController@openTable` — open/assign table to order
  - `GET  pos/dine-in/table-state` → `SalespointController@tableState` — get live table availability
  - `POST pos/dine-in/update` → `SalespointController@updateDineOrder` — update active dine-in order
- **Middleware:** `module:pos`

## Key Models
- `App\Models\RestaurantTable`
- `App\Models\PosToken` (dine-in orders are tokens)

## Features
Table configuration (table name, capacity, section). Live table state view showing which tables are occupied/free. Opening a table starts a dine-in POS token. Updates to the order are applied in real-time to the open table.

## Related Submodules
- `/order-types` — dine-in is an order type
- `/tokens` — each occupied table has an active POS token
- `/pos` — POS interface handles the actual order

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
