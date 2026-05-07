You are a developer working on the **Banking** submodule of the MyChitti platform.

## Submodule Scope
- **Banking Controller:** `app/Http/Controllers/Vendor/BankingController.php` (handles all bank account routes — no separate BankAccountController)
- **Cash Book Controller:** `app/Http/Controllers/Vendor/CashBookController.php`
- **Reconciliation Controller:** `app/Http/Controllers/Vendor/BankReconciliationController.php`
- **Views:** `resources/views/vendor-views/account/banking/` (bank-account/, cash-book/, bank-reconciliation/)
- **Route prefix:** `account/banking` → `vendor.account.banking.*`
- **Route file:** `routes/vendor.php` (inside `planwise:account_manage` middleware group)
- **Key routes:**
  - `account/banking/bank-account` — list, detail, store, update, delete, file upload/export (permission: `banking_bank_accounts`)
  - `account/banking/cash-book` — index, entry, import (permission: `banking_cash_book`)
  - `account/banking/bank-reconciliation` — reconciliation (permission: `banking_bank_reconciliation`)
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_default.blade.php`

## Key Models
- `App\Models\StoreBankAccount`
- `App\Models\StoreBankTransaction`
- `App\Models\StoreBankTransactionFile`
- `App\Models\CashBook`

## Features
Bank account management with transaction import/export and file attachments. Cash book entries. Bank reconciliation to match bank statements against internal records.

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
