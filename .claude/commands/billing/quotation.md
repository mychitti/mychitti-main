You are a developer working on the **Quotation** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/QuoteController.php`
- **Views:** `resources/views/vendor-views/quotation/`
- **Route prefix:** `quotation` → `vendor.quotation.*`
- **Route file:** `routes/vendor.php` (inside `planwise:quotaiton_manage` — note: intentional typo in middleware name, do NOT fix)
- **Key routes:**
  - `GET  quotation` — quotation list
  - `GET  quotation/new` — new quotations (status: New)
  - `GET  quotation/accepted` — accepted quotations
  - `GET  quotation/declined` — declined quotations
  - `GET  quotation/create` — create form
  - `POST quotation/save` — save quotation
  - `GET  quotation/view/{id}` — view quotation
  - `GET  quotation/pdf/{id}` — download PDF
  - `GET  quotation/accept/{id}` / `quotation/decline/{id}` — update status
  - `GET  quotation/convert-invoice/{id}` — convert quotation to manual invoice

## Key Models
- `App\Models\Quotation` → table `quotations`
  - `vendor_id` stores **STORE ID** (same convention as `manual_invoices.vendor_id`)
  - `client_name` is a FK to `store_customers.id` — despite the column name it is an integer ID
  - `status`: `New`, `Accepted`, `Declined`
  - `total`, `pdf`
- `App\Models\QuotationDetail` → table `quotation_details`
- `App\Models\QuotationDetailItem` → line items

## Key Notes
- `planwise:quotaiton_manage` middleware — intentional typo, keep as-is
- Converting a quotation to invoice uses `BillingController` and sets `vendor_id = storeId`
- `client_name` column stores integer FK to `store_customers.id` — use join to get name

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
