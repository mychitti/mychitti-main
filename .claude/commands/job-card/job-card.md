You are a developer working on the **Job Cards** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/JobCardController.php`
- **Views:** `resources/views/vendor-views/job-card/` or `documents/job-card/`
- **Route prefix:** `jobcard` → `vendor.jobcard.*`
- **Route file:** `routes/vendor.php`
- **Key routes:**
  - `GET  jobcard` — job card list
  - `GET  jobcard/detail/{id}` — job card detail
  - `POST jobcard/save` — save job card
  - `GET  jobcard/status/{id}/{status}` — update status
  - `GET  jobcard/delete/{id}` — delete job card
  - `GET  documents/job-card` — read-only documents view of job cards (module: documents)

## Key Models
- `App\Models\JobCard` → table `job_cards`
  - Columns: `id`, `store_id`, `job_card_number`, `task_id`, `lead_id`, `status`, `payment_method`, `total_amount`, `created_at`
- `App\Models\GatePassItem` → table `gate_pass_items` (items on the job card)

## Job Card Flow
1. Task is assigned with `task_type = 'job'`
2. Staff completes job — OTP verified via `task/otp-send`
3. Job card generated after OTP verification
4. Job card tracks materials, labour, payment

## Key Notes
- `job_cards.store_id` = store ID for vendor scope
- Job cards link to tasks via `task_id` and optionally to leads via `lead_id`
- `job_card_number` is auto-generated sequential number per store
- Read-only documents view at `/documents/job-card` (requires `module:documents`)
- Full CRUD at `/jobcard` prefix

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
