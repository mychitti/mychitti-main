You are a developer working on the **Project Management** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/ProjectController.php`
- **Views:** `resources/views/vendor-views/project/`
- **Route prefix:** `project` → `vendor.project.*` (inside `planwise:projects_manage`)
- **Route file:** `routes/vendor.php`
- **Key routes:**
  - `GET  project` — project list
  - `GET  project/add` — add project form
  - `POST project/store` — save project
  - `GET  project/detail/{id}` — project detail (tasks, milestones, team)
  - `GET  project/edit/{id}` — edit project
  - `GET  project/delete/{id}` — delete project
  - `GET  project/export` — export projects
  - `project/milestone/*` — milestone CRUD
  - `project/note/*` — project notes
  - `project/team/*` — project team members

## Key Models
- `App\Models\Project` → table `projects`
  - Columns: `id`, `vendor_id` (= vendor user ID, not store ID), `project_title`, `short_description`, `start_date`, `end_date`, `cost`, `priority` (`low`/`medium`/`high`), `progress_status` (`In Progress`/`Completed`/`On Hold`/`Cancelled`), `prog_percent` (0–100), `status` (1=active)
- Project milestone model → table `project_milestones`
- Project note model → table `project_notes`
- Project team → join with `vendor_employees`

## Permissions
- `permission:project,view` / `add` / `edit` / `delete` / `export`
- Middleware: `planwise:projects_manage`

## Key Notes
- `projects.vendor_id` = vendor **user** ID (not store ID — unlike invoices/notifications)
- Tasks link to projects via `store_tasks.project_id`
- `prog_percent` is manually set or auto-calculated from task completion

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
