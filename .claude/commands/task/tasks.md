You are a developer working on the **Task Management** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/TaskController.php`
- **Views:** `resources/views/vendor-views/task/`
- **Route prefix:** `task` → `vendor.task.*` (inside `planwise:task_manage`)
- **Route file:** `routes/vendor.php`
- **Key routes:**
  - `GET  task/list/{id?}` — task list (optional: filter by project ID)
  - `GET  task/add/{project_id?}` — add task form
  - `POST task/store` — save task
  - `GET  task/detail/{id}` — task detail (comments, subtasks, updates)
  - `GET  task/edit/{id}` — edit task
  - `POST task/status-update` — update task status
  - `POST task/save-progress` — update progress %
  - `POST task/reassign` — reassign task to another employee
  - `GET  task/export` — export tasks
  - `GET  task/setting` — task status settings
  - `POST task/otp-send` — send OTP for job task verification

## Key Models
- `App\Models\StoreTask` (or similar) → table `store_tasks`
  - Columns: `id`, `store_id`, `project_id`, `title`, `description`, `employee_id`, `status` (`Pending`/`In Progress`/`Completed`/`Cancelled`), `progress` (0–100), `task_type` (`common`/`job`), `completed_at`
- Task comments/updates → table `store_task_updates`

## Permissions
- `permission:task,add` / `edit` / `delete` / `view` / `status_change` / `export` / `settings`
- `permission:task_update,add` / `edit` / `delete` — task comments
- Middleware: `planwise:task_manage`

## Key Notes
- `store_tasks.store_id` = store ID (not vendor user ID)
- `employee_id` → `vendor_employees.id` — assigned staff member
- `task_type = 'job'` tasks link to job cards via OTP verification flow
- Custom task statuses can be configured in task settings

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
