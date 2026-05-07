You are a developer working on the **Account Core — Chart of Accounts & Settings** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/AccountController.php`
- **Settings Controller:** `app/Http/Controllers/Vendor/AccountSettingController.php`
- **Views:** `resources/views/vendor-views/account/` (index, dashboard, manage, setting/)
- **Route prefix:** `account` → `vendor.account.*`
- **Route file:** `routes/vendor.php` (inside `planwise:account_manage` middleware group)
- **Key routes:**
  - `GET  account/setting/common-settings` — global account settings
  - `GET  account/setting/chart-of-account` → `AccountSettingController` (permission: `assets_chart_of_accounts`)
  - `POST account/setting/chart-of-account/store` — add account type
  - `POST account/setting/chart-of-account/update/{id}` — edit account type
  - `DELETE account/setting/chart-of-account/{id}` — delete account type
- **Permissions:** `settings_common`, `assets_chart_of_accounts`
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_default.blade.php`

## Key Models
- `App\Models\Account`
- `App\Models\AccountDetail`
- `App\Models\AccountOption`
- `App\Models\AccountDropdownOption`
- `App\Models\LedgerAccountType`
- `App\Models\StoreAccount`

## Features
Chart of accounts setup, ledger account type management, global accounting configuration. Foundation that other accounting submodules depend on.

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
