You are a developer working on the **POS Tokens (Transactions)** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/SalespointController.php`
- **Views:** `resources/views/vendor-views/salespoint/` (token_list.blade.php, index.blade.php)
- **Route prefix:** `pos` → `vendor.pos.*`
- **Route file:** `routes/vendor.php` (lines 799-812, inside `module:pos`)
- **Key routes:**
  - `GET  pos/token/{id?}` — view/generate token (permission: `pos_token,generate`)
  - `GET  pos/token-list` — list all tokens (permission: `pos_token,list`)
  - `GET  pos/token-export` — export tokens (permission: `pos_token,export`)
  - `GET  pos/convert-to-bill/{id}` — convert token to invoice (permission: `pos_token,convert to invoice`)
  - `GET  pos/token-delete/{id}` — delete token (permission: `pos_token,delete`)
  - `GET  pos/token-cancel/{id}` — cancel token (permission: `pos_token,cancel`)
  - `POST pos/token-generate` — generate new token
  - `GET  pos/mark-paid/{id}` — mark token as paid (permission: `pos_token,mark_paid`)
  - `POST pos/payment-method` — set payment method (permission: `pos_token,edit`)
- **Middleware:** `module:pos`

## Key Models
- `App\Models\PosToken`
- `App\Models\PosTokenItem`
- `App\Models\ManualInvoice` (token converts to invoice via `convert-to-bill`)
- `App\Models\Branch`

## Features
A POS Token represents a transaction/order. Tokens are generated at checkout, can be marked as paid, converted to formal invoices, cancelled, or deleted. Token list is the transaction history view.

## Related Submodules
- `/pos` — orders placed in the POS interface become tokens
- `/pos-reports` — token data feeds into reports
- `/billing` (account module) — converted invoices land here

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
