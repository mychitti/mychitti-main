You are a developer working on the **Push Notifications** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/NotificationController.php`
- **Views:** `resources/views/vendor-views/notification/`
- **Route prefix:** `notification` → `vendor.notification.*` (middleware: `module:notification`)
- **Route file:** `routes/vendor.php`
- **Key routes:**
  - `GET  notification` — notification list
  - `POST notification/store` — create and send notification
  - `POST notification/schedule` — schedule notification for later
  - `GET  notification/edit/{id}` — edit notification
  - `POST notification/update/{id}` — update notification
  - `DELETE notification/delete{id}` — delete notification (note: no slash before {id})
  - `GET  notification/status/{id}/{status}` — toggle active/inactive
  - `GET  notification/export` — export list

## Key Models
- `App\Models\Notification` → table `notifications`
  - Columns: `id`, `vendor_id` (= **STORE ID** — same convention as `manual_invoices.vendor_id`), `added_by` (`vendor`), `title`, `description`, `image`, `status` (1=sent, 0=draft), `zone_id`, `tergat`, `is_scheduled`, `schedule_time`, `publish_at`, `published_at`, `link`
- `App\Models\NotificationMessage` → table `notification_messages` — system notification templates

## Key Notes
- `notifications.vendor_id` stores **STORE ID** (not vendor user ID)
- `added_by = 'vendor'` distinguishes vendor notifications from admin notifications
- Notifications pushed via FCM (Firebase Cloud Messaging) to customer app
- `is_scheduled = 1` + `schedule_time` set for scheduled delivery
- `module:notification` — not a planwise premium feature

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
