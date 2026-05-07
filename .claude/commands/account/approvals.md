You are a developer working on the **Approvals & Request Forms** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/AccountRequestFormController.php`
- **Views:** `resources/views/vendor-views/account/request-form/`
- **Route prefix:** `account/request-form`, `account/approvals` → `vendor.account.*`
- **Route file:** `routes/vendor.php` (inside `planwise:account_manage` middleware group)
- **Key routes:**
  - `account/approvals` — approval workflow list
  - `account/request-form/journal-entry` — submit journal entry for approval (permission: `apporval_form_journal_entry`)
  - `account/request-form/master-ledger` — submit master ledger entry for approval (permission: `apporval_form_master_ledger`)
  - `account/request-form/incoming-requests` — review & approve/reject incoming requests (permission: `apporval_form_incoming_requests`)
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_default.blade.php`

## Key Models
- `App\Models\StoreLedgerEntry`
- `App\Models\AccountTransaction`
- `App\Models\Account`

## Features
Multi-step approval workflow for journal entries and master ledger changes. Staff submit requests; approvers review incoming requests and approve/reject. Designed for businesses requiring maker-checker controls on accounting entries.

## Related Submodules
- `/books` — approved entries are posted to master ledger / journal

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
