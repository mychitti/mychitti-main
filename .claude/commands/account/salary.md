You are a developer working on the **Salary & Payroll** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/SalaryController.php`
- **Category Controller:** `app/Http/Controllers/Vendor/TaskSalaryCategoryController.php`
- **Views:** `resources/views/vendor-views/salary/`
- **Route prefix:** `salary` → `vendor.salary.*`
- **Route file:** `routes/vendor.php` (inside `planwise:hr_manage` middleware group — different from `account_manage`)
- **Key routes:**
  - `salary/list` — salary list (permission: `salary_manage`)
  - `salary/add` — add salary entry
  - `salary/generate-monthly/{month}` — generate monthly salaries
  - `salary/mark-paid/{month}` — mark month as paid
  - `salary/report` — salary reports
  - `salary/all-advance-requests` — advance payment requests (permission: `advance_requests`)
  - `salary/approve-advance/{id}` — approve advance
  - `salary/reject-advance/{id}` — reject advance
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_default.blade.php`

## Key Models
- `App\Models\Salary`
- `App\Models\TaskSalaryCategory`

## Important
This module uses `planwise:hr_manage` middleware — **not** `account_manage`. It belongs to the HR module but overlaps with accounting for payroll entries.

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
