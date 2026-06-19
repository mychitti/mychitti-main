# Account & Finance Module

## Controllers
- `app/Http/Controllers/Vendor/AccountController.php` — ledger, transactions, chart of accounts
- `app/Http/Controllers/Vendor/AccountSettingController.php` — account settings, chart of accounts setup
- `app/Http/Controllers/Vendor/SalaryController.php` — salary processing, payroll
- `app/Http/Controllers/Vendor/AssetController.php` — asset management
- `app/Http/Controllers/Vendor/BankingController.php` — bank accounts, reconciliation
- `app/Http/Controllers/Vendor/TaxController.php` — taxation, GST

## Routes
- File: `routes/vendor.php` (inside `planwise:account_manage` middleware group)
- Prefix: `account` → `vendor.account.*`

## Key Routes
- `GET  account/setting/common-settings` — global account settings
- `GET  account/setting/chart-of-account` — chart of accounts (`permission:assets_chart_of_accounts`)
- `POST account/setting/chart-of-account/store` — add account type
- `account/salary/*` — salary routes (`permission:salary_manage`)
- `account/banking/*` — banking routes
- `account/tax/*` — taxation routes
- `account/assets/*` — asset management

## Key Models
- `App\Models\Account`
- `App\Models\AccountDetail`
- `App\Models\AccountOption`
- `App\Models\LedgerAccountType`
- `App\Models\StoreAccount`
- `App\Models\Salary`
- `App\Models\Asset`

## Permissions
- `settings_common`, `assets_chart_of_accounts`, `salary_manage`, `banking_manage`
- Middleware: `planwise:account_manage`

## Views
- `resources/views/vendor-views/account/` — ledger, dashboard, chart of accounts, settings
- `resources/views/vendor-views/salary/` — payroll, salary slips
- `resources/views/vendor-views/assets/` — asset register

## Key Notes
- Account module is completely inside `planwise:account_manage` (premium)
- Billing/invoicing is SEPARATE from account module — billing routes have no planwise guard
- Chart of accounts is the foundation — other submodules depend on it
- Salary processing creates journal entries automatically
