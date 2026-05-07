You are a developer working on the **Statements & Reports** submodule of the MyChitti platform.

## Submodule Scope
- **Statement Controller:** `app/Http/Controllers/Vendor/AccountStatementController.php`
- **Report Controller:** `app/Http/Controllers/Vendor/AccountReportController.php`
- **Views:** `resources/views/vendor-views/account/statement/`, `resources/views/vendor-views/account/report/`
- **Route prefix:** `account/statement`, `account/report` → `vendor.account.*`
- **Route file:** `routes/vendor.php` (inside `planwise:account_manage` middleware group)
- **Key routes:**
  - `GET account/statement/trial-balance` — trial balance (permission: `statements_trial_balance`)
  - `GET account/statement/balance-sheet` — balance sheet (permission: `statements_balance_sheet`) — may be a placeholder; verify route exists before building on it
  - `GET account/report/tax` — tax report (permission: `reports_tax_report`)
  - `GET account/report/audit-logs` — audit log (permission: `reports_audit_logs`)
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_default.blade.php`

## Key Models
- `App\Models\StoreLedgerEntry`
- `App\Models\AccountTransaction`
- `App\Models\StoreBankTransaction`

## Features
Trial balance, balance sheet generation. Tax reports (GST/income tax summaries). Audit logs for accounting actions. All read-heavy — primarily aggregate queries over ledger entries and transactions.

## Related Submodules
- `/books` — ledger entries feed into statements
- `/taxation` — tax data feeds into tax reports

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery
- Eloquent models for queries; raw SQL (`DB::statement()`) only for schema changes — never generate migration files
- All account routes guarded by `planwise:account_manage` middleware
- Controllers extend `App\Http\Controllers\Controller`
- Icons: `tio-*` set; views extend `layouts.vendor.app`

## Behaviour
1. Read relevant files first
2. Outline plan — files to create/modify, SQL to run, routes/env vars needed
3. Ask for confirmation before making changes
4. Proceed only after confirmation
5. Write complete, production-ready code — no placeholders

$ARGUMENTS
