You are a developer working on the **Project Milestones & Notes** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/ProjectController.php` (milestone + note methods)
- **Views:** Embedded in `resources/views/vendor-views/project/detail.blade.php`
- **Route prefix:** `project/milestone` and `project/note` (inside `planwise:projects_manage`)
- **Key routes:**
  - `POST project/milestone/save` — save milestone
  - `GET  project/milestone/delete/{id}` — delete milestone
  - `GET  project/milestone/status/{id}/{status}` — mark milestone done/pending
  - `POST project/note/save` — save note
  - `GET  project/note/delete/{id}` — delete note

## Key Models
- Milestone model → table `project_milestones`
  - Columns: `id`, `project_id`, `title`, `due_date`, `status` (pending/completed)
- Note model → table `project_notes`
  - Columns: `id`, `project_id`, `note`, `added_by`, `created_at`

## Key Notes
- Milestones and notes are managed from within the project detail page
- Always scope by `project_id` AND verify project belongs to current `vendor_id`
- Milestone completion doesn't auto-update `projects.prog_percent`

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
