You are a developer working on the **CRM & Clients** submodule of the MyChitti CRM module.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/ClientController.php` (or handled via BillingController for store_customers)
- **Views:** `resources/views/vendor-views/client/` or `crm/`
- **Route prefix:** `client` → `vendor.customer.*` (middleware: `module:client_manage`)
- **Route file:** `routes/vendor.php`
- **Key routes:**
  - `GET  client` — client list
  - `GET  client/add` — add client form
  - `POST client/save` — save client
  - `GET  client/edit/{id}` — edit client
  - `GET  client/delete/{id}` — delete client
  - `GET  client/view/{id}` — client profile (invoices, quotations, leads history)
  - CRM own leads: `lead/` prefix (see `crm/leads.md`)

## Key Models
- `App\Models\StoreCustomer` → table `store_customers`
  - Columns: `id`, `store_id`, `user_type` (`customer`/`supplier`/`both`), `f_name`, `l_name`, `phone`, `email`, `gst`, `id_number`, `address`, `pin_code`, `ledger_account_id`
- `App\Models\Lead` → table `leads` (vendor's own CRM leads)
  - Columns: `id`, `vendor_id`, `client_name`, `client_email`, `client_mobile`, `service`, `requirements`, `status` (`New`/`Contacted`/`Qualified`/`Won`/`Lost`), `follow_up_date`, `channel`, `remarks`

## Key Notes
- `store_customers` is shared across billing (`bill_to`), quotations (`client_name` FK), and CRM
- Always filter by `store_id` for vendor scope
- `user_type` distinguishes customers from suppliers — use `'customer'` for client lists
- `module:client_manage` middleware (not planwise — depends on module activation)
- CRM leads (`leads` table) link to `vendor_id` = vendor user ID (not store ID)

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
