You are a developer working on the **Smart Calendar & Appointments** submodule of the MyChitti platform.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/SmartCalendarController.php`
- **Views:** `resources/views/vendor-views/smart-calendar/`
- **Route prefix:** `smart-calendar` → `vendor.smart-calendar.*`
- **Route file:** `routes/vendor.php`
- **Key routes:**
  - `GET  smart-calendar` — calendar view
  - `GET  smart-calendar/list` — appointment list
  - `POST smart-calendar/save` — create appointment
  - `GET  smart-calendar/detail/{id}` — appointment detail
  - `POST smart-calendar/status/{id}` — update appointment status
  - `GET  smart-calendar/delete/{id}` — delete appointment

## Key Models
- `App\Models\Appointment` (or similar) → table `appointments`
  - Columns: `id`, `store_id`, `appointment_date`, `appointment_time`, `status` (`scheduled`/`checked_in`/`completed`/`cancelled`), `reason`, `booking_type`, `user_id`, `notes`, `created_at`

## Appointment Status Flow
`scheduled` → `checked_in` → `completed`
Also: `cancelled`, `no_show`

## Key Notes
- `appointments.store_id` = store ID for vendor scope
- Booking types vary by module (service booking, clinic, salon, etc.)
- Calendar view uses FullCalendar JS library
- Upcoming appointments: `appointment_date >= today` AND `status IN (scheduled, checked_in)`

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery + FullCalendar
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
