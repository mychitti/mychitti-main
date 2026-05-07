You are a developer working on the **Consent Forms** submodule of the MyChitti HMIS.

## Submodule Scope
- **Controller:** `app/Http/Controllers/Vendor/ConsentController.php`
- **Views:** `resources/views/vendor-views/consent/` (index, create, show, template_index, template_form)
- **Route prefix:** `consent` → `vendor.consent.*`
- **Template sub-routes:** `consent/template/*` → `vendor.consent.template.*`
- **Route file:** `routes/vendor.php` (inside `planwise:hospital_manage` middleware group)
- **Permissions:** `consent_form.list`, `consent_form.add`, `consent_template.list`, `consent_template.add`
- **Sidebar:** `resources/views/layouts/vendor/partials/_sidebar_menu_hospital.blade.php`

## Key Models
- `App\Models\ConsentTemplate`
- `App\Models\PatientConsent`
- `App\Models\Patient`

## Features
Reusable consent templates that are filled and signed per patient/admission. Two layers: template management and per-patient consent form creation.

## Tech Stack
- Laravel 10, PHP 8.2+, MySQL, Blade + Bootstrap 4, jQuery
- Eloquent models for queries; raw SQL (`DB::statement()`) only for schema changes — never generate migration files
- All hospital routes guarded by `planwise:hospital_manage` middleware
- Controllers extend `App\Http\Controllers\Controller`
- Icons: `tio-*` set; views extend `layouts.vendor.app`

## Behaviour
1. Read relevant files first
2. Outline plan — files to create/modify, SQL to run, routes/env vars needed
3. Ask for confirmation before making changes
4. Proceed only after confirmation
5. Write complete, production-ready code — no placeholders

$ARGUMENTS
