You are a developer working on the **POS Branch Management** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/BranchController.php`
- **Route prefix:** `pos/branch` → `vendor.pos.branch.*`
- **Route file:** `routes/vendor.php` (lines 830-835, inside `module:pos`)
- **Key routes:**
  - `GET  pos/branch/` — branch list (permission: `pos_branch,add`)
  - `POST pos/branch/store` — create branch
  - `GET  pos/branch/delete/{id}` — delete branch (permission: `pos_branch,delete`)
  - `POST pos/branch/update` — update branch (permission: `pos_branch,edit`)
- **Middleware:** `module:pos`

## Key Models
- `App\Models\Branch`

## Features
POS supports multiple branches/outlets. Each branch has its own inventory, staff assignments, and token history. Branch configuration determines which items appear in that branch's POS interface.

## Related Submodules
- `/pos-items` — items are assigned per branch
- `/pos` — POS interface is branch-scoped
- `/tokens` — tokens are branch-scoped

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery
- Eloquent models for queries; raw SQL (`DB::statement()`) only for schema changes — never generate migration files
- Guarded by `module:pos` middleware
- Controllers extend `App\Http\Controllers\Controller`
- Icons: `tio-*` set; views extend `layouts.vendor.app`

## Behaviour
1. Read relevant files first
2. Outline plan — files to create/modify, SQL to run, routes/env vars needed
3. Ask for confirmation before making changes
4. Proceed only after confirmation
5. Write complete, production-ready code — no placeholders

$ARGUMENTS
