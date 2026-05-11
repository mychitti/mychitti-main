You are a developer working on the **Service Reports** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/ServiceReportController.php`
- **Views:** `resources/views/vendor-views/documents/service-report/`
- **Route prefix:** `documents/service-report` → `vendor.documents.service-report.*`
- **Route file:** `routes/vendor.php` (middleware: `module:documents`)
- **Key routes:**
  - `GET  documents/service-report` — service report list
  - `POST documents/service-report/save` — create report
  - `GET  documents/service-report/detail/{id}` — report detail
  - `GET  documents/service-report/delete/{id}` — delete report
  - `GET  documents/service-report/print/{id}` — print report

## Key Models
- Service report model → table `service_reports`
  - Columns: `id`, `service_request_id`, `store_id`, `description`, `findings`, `actions_taken`, `created_by`, `created_at`

## Key Notes
- Service reports document the work done for a service request
- Links to `service_requests` via `service_request_id`
- `module:documents` middleware — not planwise

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
