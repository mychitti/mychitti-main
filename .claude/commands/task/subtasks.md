You are a developer working on the **Subtasks & Task Comments** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/TaskController.php` (subtask + comment methods)
- **Views:** Embedded in `resources/views/vendor-views/task/detail.blade.php`
- **Route prefix:** `task/subtask` and `task/comment` and `task/subtask-update`
- **Key routes:**
  - `POST task/subtask/add` — add subtask
  - `GET  task/subtask/delete/{id}` — delete subtask
  - `GET  task/subtask/edit/{id}` — edit subtask
  - `POST task/subtask/update` — update subtask
  - `GET  task/subtask/detail/{id}` — subtask detail
  - `POST task/subtask/status-update` — update subtask status
  - `POST task/comment/add` — add task comment
  - `GET  task/comment/delete/{id}` — delete comment
  - `POST task/subtask-update/add` — add subtask update/comment

## Key Models
- Subtasks are rows in `store_tasks` with a `parent_id` pointing to the parent task
- Task comments → table `store_task_updates` (or similar)
  - Columns: `id`, `task_id`, `comment`, `added_by`, `attachments`, `created_at`

## Permissions
- `permission:subtask,add` / `edit` / `delete` / `view` / `status_change`
- `permission:subtask_update,add` / `edit` / `delete`
- `permission:task_update,add` / `edit` / `delete`

## Key Notes
- Subtasks inherit `store_id` from parent task
- Permission checks on subtasks are separate from parent task permissions

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
