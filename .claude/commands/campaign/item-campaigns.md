You are a developer working on the **Item Campaigns** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/CampaignController.php`
- **Views:** `resources/views/vendor-views/campaign/`
- **Route prefix:** `campaign` → `vendor.campaign.*` (middleware: `module:campaign`)
- **Route file:** `routes/vendor.php`
- **Key routes:**
  - `GET  campaign` — campaign list
  - `GET  campaign/add` — add campaign form
  - `POST campaign/store` — save campaign
  - `GET  campaign/edit/{id}` — edit campaign
  - `GET  campaign/status/{id}/{status}` — toggle active/inactive
  - `GET  campaign/delete/{id}` — delete campaign

## Key Models
- `App\Models\ItemCampaign` → table `item_campaigns`
  - Columns: `id`, `store_id`, `module_id`, `title`, `slug`, `description`, `price`, `discount`, `discount_type`, `status` (1=active), `stock`, `category_id`, `start_date`, `end_date`, `start_time`, `end_time`, `veg`
- **Important:** `App\Models\Campaign` → table `campaigns` — these are **admin-level** campaigns (different model, different table). Always use `ItemCampaign` for vendor scope.

## Key Notes
- `item_campaigns` are vendor-created promotional items with custom pricing and time windows
- Running = `status = 1` AND `end_date >= today`
- Filter by `store_id` for vendor scope
- `module:campaign` middleware — not planwise premium
- `ItemCampaign` uses a `ZoneScope` global scope — may need to disable if querying across zones

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
