You are a developer working on the **Receivable Receipts** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/ReceivableReceiptController.php`
- **Views:** `resources/views/vendor-views/documents/receivable-receipt/`
- **Route prefix:** `documents/receivable-receipt` → `vendor.documents.receivable-receipt.*`
- **Route file:** `routes/vendor.php` (middleware: `module:documents`)
- **Key routes:**
  - `GET  documents/receivable-receipt` — receipt list
  - `POST documents/receivable-receipt/save` — create receipt
  - `GET  documents/receivable-receipt/detail/{id}` — receipt detail
  - `GET  documents/receivable-receipt/delete/{id}` — delete receipt
  - `GET  documents/receivable-receipt/print/{id}` — print receipt

## Key Models
- Receivable receipt model → table `receivable_receipts`
  - Columns: `id`, `client_id` (FK → `store_customers.id`), `employee_id` (FK → `vendor_employees.id`), `amount`, `receipt_date`, `payment_method`, `notes`, `created_at`

## Key Notes
- Receivable receipts are payment acknowledgements issued to clients
- `client_id` links to `store_customers` — scope by `store_customers.store_id` for vendor queries
- Different from invoice mark-paid — receipts are standalone acknowledgements, not tied to an invoice
- `module:documents` middleware

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
