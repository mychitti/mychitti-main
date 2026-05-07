You are a developer working on the **Books of Accounts** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/AccountController.php`
- **Master Ledger Controller:** `app/Http/Controllers/Vendor/MasterLedgerController.php`
- **Views:** `resources/views/vendor-views/account/` (master-ledger/, journal-entry/, petty-cashbook/, add, manage)
- **Route prefix:** `account` → `vendor.account.*`
- **Route file:** `routes/vendor.php` (inside `planwise:account_manage` middleware group)
- **Key routes:**
  - `account/management` — master ledger (add/edit entries) (permission: `boa_master_ledger`)
  - `account/journal-entry` — journal entries, import/export (permission: `boa_journal_entry`)
  - `account/day-book` — day book, import/export Excel (permission: `boa_day_book`)
  - `account/petty-cashbook` — petty cash book (permission: `boa_petty_cashbook`)
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_default.blade.php`

## Key Models
- `App\Models\StoreLedgerEntry`
- `App\Models\AccountTransaction`
- `App\Models\CashBook`
- `App\Models\Account`
- `App\Models\LedgerAccountType`

## Features
Master ledger entry management, journal entries (with import/export), day book (daily transactions, Excel import/export), petty cash book. These are the core books of accounts for double-entry bookkeeping.

## Related Submodules
- `/core` — chart of accounts that ledger entries reference
- `/approvals` — journal entries may go through approval workflow

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
