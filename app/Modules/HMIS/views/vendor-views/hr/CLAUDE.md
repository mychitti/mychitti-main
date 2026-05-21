# HR Module

## Controllers
- `app/Http/Controllers/Vendor/HRController.php` — HR dashboard
- `app/Http/Controllers/Vendor/EmployeeController.php` — staff CRUD (add/list/edit/delete)
- `app/Http/Controllers/Vendor/VendorEmployeeController.php` — clock-in/out, salary history, advance payment
- `app/Http/Controllers/Vendor/StaffController.php` — save-info, settings, status, team CRUD
- `app/Http/Controllers/Vendor/BasicStaffController.php` — free tier (max 10 staff)
- `app/Http/Controllers/Vendor/AttendanceController.php` — attendance records
- `app/Http/Controllers/Vendor/LeaveController.php` — leave requests and approvals
- `app/Http/Controllers/Vendor/SalaryController.php` — salary management (inside `planwise:account_manage`)
- `app/Http/Controllers/Vendor/ShiftController.php` — shift management

## Routes
- File: `routes/vendor.php`
- Premium routes inside `planwise:hr_manage` middleware group (lines 713–732 approx)
- Self-service routes (clock-in/out, salary-history, advance-payment, resign) are OUTSIDE `planwise:hr_manage`
- Permissions: `staff_manage`, `staff_department`, `staff_team`, `staff_role`, `hr_manage`

## Key Routes
- `GET  staff/add-new` → add new staff form
- `GET  staff/list` → staff list
- `GET  staff/edit/{id}` → edit staff
- `DELETE staff/delete/{id}` → delete staff
- `POST staff/save-info` → save employee data
- `GET  staff/status/{id}/{status}` → toggle status
- `staff/team/*` → team CRUD
- `GET  clock-in` / `GET clock-out` → self-service (outside planwise guard)
- `GET  salary-history` → employee's own salary history
- `GET  hr/dashboard` → HR dashboard (`permission:hr_manage,dashboard`)
- `leave/*` → leave requests, approvals
- `attendance/*` → attendance records, reports
- `shift/*` → shift definitions and assignments

## Key Models
- `App\Models\VendorEmployee` — primary model (free + premium tier)
- `App\Models\EmployeeRole`
- `App\Models\EmployeeTimeCard`
- `App\Models\StoreEmployeeComment`
- `App\Models\Salary`
- `App\Models\AdvanceRequest`
- `App\Models\LeaveRequest`
- `App\Models\Attendance`
- `App\Models\Shift`

## Views
- `resources/views/vendor-views/hr/` — HR dashboard, attendance, leave
- `resources/views/vendor-views/employee/` — employee profile, ID card, timecards
- `resources/views/vendor-views/staff/` — staff list, add, edit
- `resources/views/vendor-views/salary/` — salary records
- `resources/views/vendor-views/leave/` — leave management
- `resources/views/vendor-views/attendance/` — attendance records
- `resources/views/vendor-views/shift/` — shifts

## Key Notes
- `VendorEmployee.store_id` links to `stores` table
- Staff department routes use `permission:staff_department` but are outside `planwise:hr_manage`
- Custom role routes use `permission:staff_role` — also outside planwise guard
- Basic staff (free tier) max 10 employees, handled by `BasicStaffController`
