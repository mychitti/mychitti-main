# MyChitti — Project Knowledge

## Project Overview
MyChitti is a multi-vendor e-commerce platform (services / marketplace).

## Tech Stack
- **Main app**: Laravel 10 (PHP 8.2+), MySQL, Blade + Bootstrap 4, jQuery
- **AI service** (sibling repo `ai-agent` — NOT part of this repo; deployed separately to the AI droplet): Separate Laravel 10 app — handles all AI chat, memory, agents, RAG injection
- **Python RAG server** (`/root/ai-server/`): FastAPI + pgvector (PostgreSQL), VoyageAI embeddings
- **Web server**: nginx + PHP-FPM 8.2, Supervisor for Python processes
- **CI/CD**: GitHub Actions (`deploy.yml`), deploys to admin / vendor / shop servers on push to main

## Servers
- Admin: 64.227.158.127 — `/var/www/html/admin`
- Vendor: 167.71.233.92 — `/var/www/html/vendor`
- Shop: 64.227.178.210 — `/var/www/html/mychitti`
- AI droplet: 134.209.153.181 (`ai-revised.mychitti.net`) — AI service + Python RAG
- DB droplet: 10.122.0.7 (private IP, MySQL)
- Staging: 134.209.159.233 — `/var/www/html/staging`

## Key Patterns
- Admin routes: `routes/admin.php`, grouped with `'namespace' => 'Admin', 'as' => 'admin.'`
- Vendor routes: `routes/vendor.php`
- Controllers extend `App\Http\Controllers\Controller`
- Blade views extend `layouts.admin.app` (admin) or `layouts.vendor.app` (vendor) or `front-views\layout` (front)
- Icons: `tio-*` icon set in admin and vendor panel
- AI chat goes through `AiServiceClient` → AI service → Claude/OpenAI/Gemini
- RAG context injected at top of system prompt in the ai-agent repo's `app/Http/Controllers/Api/AIChatController.php`
- Storage: `storage/app/public` is a DO Spaces mountpoint — never `rm -rf` or reset

## Database Rules
- Use Eloquent models for queries
- Raw SQL (`DB::statement()`) only for schema changes (CREATE/ALTER TABLE)
- Never generate Laravel migration files

## Code Style
- No unnecessary comments or docblocks
- Follow existing patterns in the codebase
- Use `Http::` facade for external HTTP calls
- Validation in controller, not middleware
- Return `response()->json([...])` for API endpoints
- Use `back()->with('success', '...')` for web redirects
- Use `Toastr::success()` / `Toastr::error()` for flash messages in vendor/admin

## Key Gotchas
- `storage/app/public` is a DO Spaces mountpoint — never overwrite or reset
- `manual_invoices.vendor_id` stores STORE ID (not vendor user ID)
- Invoice ID format: `{STORE_PREFIX}_{SERIAL}` e.g. `KHB_3` — not `/` separated
- `_createBillPdf()` requires `bill_to`, `bill_to_type`, `tax_type` set on the invoice model
- Route `billing/veiw-invoice` has a typo — intentional, do NOT fix (would break existing links)
- `planwise:*` middleware guards premium module access
- `hasPermission()` / `hasAnyModulePermission()` used for fine-grained UI permission checks

## Behaviour
1. Read relevant files first to understand current state
2. Make changes directly — no need to outline plan or ask for confirmation unless the change is destructive or cross-cutting
3. Write complete, production-ready code — no placeholders
4. Include all `use` statements and imports
5. After writing, summarise what changed and any follow-up steps (env vars, server commands, SQL)
