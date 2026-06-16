# School Management Module — Implementation Plan

> A self-contained vertical module (`School`) for MyChitti, built exactly like the existing
> **HMIS** and **POS** modules. An institution (school/college/coaching) subscribes to it via a
> plan and gets a full Student Information System (SIS) on top of the shared platform services
> (HR, Accounts, Billing, Inventory, Library, Notifications).

---

## 1. Naming & Conventions

| Thing | Value |
|---|---|
| Module folder | `app/Modules/School/` |
| Service provider | `App\Modules\School\SchoolServiceProvider` (registered in `config/app.php`) |
| Plan / gate key (planwise) | `school_manage` |
| Sidebar menu slug | `school` |
| Views namespace | `school::` |
| Business-module config key | `school` (in `config/business_modules.php`) |
| Permission style | `permission:{key},{action}` e.g. `students,add` · `fees,collect` · `exams,enter_marks` |

---

## 2. How it plugs into the platform (mirrors HMIS/POS)

1. **Folder structure**
   ```
   app/Modules/School/
   ├── SchoolServiceProvider.php        # loadViewsFrom(__DIR__.'/views', 'school')
   ├── Controllers/
   │   ├── Admin/                       # plan tiers, activity logs
   │   └── Vendor/                      # all school submodule controllers
   ├── routes/
   │   ├── admin.php                    # admin: plan + logs
   │   ├── vendor.php                   # requires the submodule route files (planwise:school_manage)
   │   └── vendor/                      # one file per submodule (students.php, fees.php, ...)
   └── views/
       ├── admin/
       ├── vendor/                      # school::vendor.* (submodule screens, dashboards)
       └── vendor-views/                # overrides of shared screens (dashboard, staff_dashboard)
   ```

2. **Service provider** — minimal, just `loadViewsFrom(__DIR__.'/views', 'school')`. Register in
   `config/app.php` providers array alongside `HMISServiceProvider`.

3. **Controller override map** — add a `'school'` block to `config/business_modules.php` mapping each
   base `App\Http\Controllers\Vendor\*` controller to `App\Modules\School\Controllers\Vendor\*`, plus
   `'views' => app_path('Modules/School/views')`. (So shared base routes resolve to school versions
   when the module is active — same as HMIS.)

4. **Route loading** — in `routes/vendor.php` and `routes/admin.php`, `require` the module's route
   files (guarded by file_exists), exactly like the HMIS block. Module `routes/vendor.php` wraps the
   submodule files in `Route::group(['middleware' => ['planwise:school_manage']], ...)`.

5. **Plan gating** — `planwise:school_manage` (handled by `App\Http\Middleware\PermissionCheck`).
   Add `'school_manage' => ['school']` to `VendorSubscription::MODULE_MENU_MAP` so purchasing the plan
   reveals the sidebar menu.

6. **Sidebar** — a `school` menu partial with submenu links, shown when the store has the
   `school` menu visibility / `school_manage` permission.

7. **Admin side** — `Admin/` controllers for **plan tiers** (e.g. student-count tiers, like HMIS bed
   tiers) and **activity logs** (reuse the activity-log pattern).

8. **Dashboards** — school `dashboard.blade.php` (admin/owner) and `staff_dashboard.blade.php`
   (teacher) under `views/vendor-views/`, overriding the shared dashboards for school context.

---

## 3. Reuse (do NOT rebuild) — wire into school context

| Platform feature | Used for |
|---|---|
| **HR** (Staff, Attendance, Leave, Shift, Salary/Payroll) | Teachers & staff management, staff attendance, payroll |
| **Accounts / Finance** (ledger, daybook, statements) | Fee collections post here (double-entry, like OP-consultation→ledger) |
| **Billing / Invoicing** (`ManualInvoice`) | Fee receipts / PDF |
| **Inventory & Assets** | Uniforms, books, lab/sports equipment |
| **Library** (`LibraryController`) | Book catalog, issue/return, fines |
| **Notifications / Smart Calendar** | Parent/staff alerts, school events |
| **Leads / Service requests** | Admission enquiries pipeline |
| **Documents / Gatepass** | Student/staff documents |

---

## 4. New school-specific submodules (to build)

Each = controller(s) + route file + views + permission key + sidebar entry + model(s).

### 4.1 Academic Setup  (`academic`)
- Academic sessions/years, classes/grades, sections, subjects.
- Class-teacher & subject-teacher assignment, grading scheme.
- Admission-number format (prefix/padding/serial — like the patient-UID setting in StoreConfig).
- **Models:** `AcademicSession`, `SchoolClass`, `ClassSection`, `Subject`, `ClassSubjectTeacher`.

### 4.2 Students  (`students`)
- Profile: admission no, name, DOB, gender, photo, category, blood group, guardians, contacts, address, docs.
- Enrollment to class/section + roll number; ID cards.
- Promotion / transfer / alumni; bulk import.
- **Models:** `Student`, `StudentGuardian`, `StudentEnrollment`, `StudentDocument`.

### 4.3 Admissions  (`admissions`)
- Enquiry → application → admission (online form on front/app, reusing service-request flow).
- Seat allotment + initial fee on admission.
- **Models:** `AdmissionEnquiry`, `AdmissionApplication` (or reuse `ServiceRequest`).

### 4.4 Student Attendance  (`student_attendance`)
- Daily or period-wise, marked by class teacher; reports; absence notification to parents.
- **Models:** `StudentAttendance`.

### 4.5 Timetable  (`timetable`)
- Class & teacher timetables, period allocation, substitutions.
- **Models:** `TimetablePeriod`, `TimetableEntry`, `Substitution`.

### 4.6 Examinations & Report Cards  (`exams`)
- Exam terms, schedule, marks entry, grade/GPA calculation.
- Printable marksheets / report cards (PDF), result publishing.
- **Models:** `ExamTerm`, `Exam`, `ExamSchedule`, `MarkEntry`, `GradeScheme`.

### 4.7 Fees  (`fees`)
- Fee structure per class/category; fee heads (tuition, transport, lab…).
- Invoice + receipt (PDF) → **posts to accounts ledger/daybook** (reuse `_masterLedgerEntry` /
  `_saveDayBookEntry`, like the OP-consultation receipt).
- Concessions/scholarships, due/defaulter tracking, reminders, online payment.
- **Models:** `FeeHead`, `FeeStructure`, `FeeStructureItem`, `FeeInvoice`, `FeePayment`, `FeeConcession`.

### 4.8 Homework / Assignments  (`homework`)
- Assign per class/subject, student submission, grading.
- **Models:** `Homework`, `HomeworkSubmission`.

### 4.9 Transport  (`transport`)
- Routes, vehicles, drivers, stops; student assignment; transport fees.
- **Models:** `TransportRoute`, `TransportVehicle`, `TransportStop`, `StudentTransport`.

### 4.10 Hostel  (`hostel`)
- Blocks/rooms, allocation, hostel fees (mirrors HMIS ward/bed allocation).
- **Models:** `HostelBlock`, `HostelRoom`, `HostelAllocation`.

### 4.11 Certificates  (`certificates`)
- Transfer (TC), Bonafide, Character certificates (PDF, configurable templates).
- **Models:** `IssuedCertificate` (+ template settings).

### 4.12 Parent / Student Portal  (`portal`)
- View attendance, marks, fees + pay, homework, timetable, notices (front-site / app side).

### 4.13 Reports & Dashboards  (`school_reports`)
- Attendance %, fee collection vs dues, exam performance, enrollment trends.
- Analytics page styled like the existing income/account dashboards.

---

## 5. Data & schema notes
- One row-per-store scoping via `store_id` everywhere (the school = a vendor store), like HMIS.
- Per-store settings stored in **`StoreConfig`** columns (admission-no format, grading scheme,
  current session) — same approach as `patient_uid_*`.
- Schema created via raw `DB::statement` `CREATE TABLE IF NOT EXISTS` / guarded `ALTER` (project
  rule: **no migration files**).
- Use Eloquent models for all queries.

---

## 6. Permissions (sample keys)
`students` · `admissions` · `student_attendance` · `timetable` · `exams` · `fees` ·
`homework` · `transport` · `hostel` · `certificates` · `academic_setup` · `school_reports`
— each with `list/add/edit/delete/view` (+ specials like `fees,collect`, `exams,enter_marks`,
`exams,publish`, `students,promote`).

---

## 7. Phased roadmap

**Phase 1 — Core SIS (MVP):**
Module scaffolding & wiring → **Academic Setup → Students → Student Attendance → Fees
(structure + collection → ledger) → school dashboard.** Reused modules (HR, Library, Inventory,
Accounts, Notifications) enabled from day one. *(This alone is a sellable product.)*

**Phase 2:** Examinations & Report Cards → Timetable → Admissions → Certificates.

**Phase 3:** Transport → Hostel → Homework → Parent/Student portal → advanced reports.

---

## 8. Build checklist for scaffolding (Phase 1, step 0)
- [x] `app/Modules/School/SchoolServiceProvider.php` + register in `config/app.php`
- [x] `app/Modules/School/routes/vendor.php` wrapped in `planwise:school_manage` (+ `school` prefix, dashboard route)
- [x] `app/Modules/School/routes/admin.php` (placeholder for plan tiers + logs)
- [x] `require` module routes from `routes/vendor.php` & `routes/admin.php`
- [x] `VendorSubscription::MODULE_MENU_MAP` → `'school_manage' => ['school']`
- [x] `school` sidebar partial (`_sidebar_menu_school`) + sidebar `business_type === 'school'` branch
- [x] School `DashboardController` + `school::vendor.dashboard` view
- [ ] `config/business_modules.php` → add `'school'` controller-override + views block (add when shared-controller overrides are needed)
- [ ] Admin plan tier + activity-log controllers (Phase 1 admin)
- [ ] Seed permission keys for school submodules (done per-submodule as built)

### Progress
- [x] **Slice 0 — Scaffolding** (module, provider, routes, planwise gate, sidebar, dashboard)
- [x] **Slice 1 — Academic Setup** (sessions, classes, sections, subjects, subject↔teacher mapping;
  tables auto-created; `store_id`+`branch_id` columns; permission key `academic_setup`; tabbed UI at
  `/school/academic`)
- [x] **Slice 2 — Students** (profile, photo, enrollment to class/section + roll no, admission-no
  format in StoreConfig, ID card, settings; list with class/section/search filters; `/school/students`)
- [x] **Slice 3 — Student Attendance** (daily section-wise marking with All-Present/Absent, 6 statuses,
  one-row-per-day upsert, monthly report with %/below-75% flag; `/school/student-attendance`)
- [x] **Slice 4 — Fees** (fee heads + GST%, per-class/session fee structure, fee collection with
  concession + partial pay → posts to accounts ledger/daybook + printable/PDF GST receipt, dues/
  defaulter landing, collection report; `/school/fees`)

**✅ Phase-1 Core SIS shippable:** scaffolding + Academic Setup + Students + Attendance + Fees.

- [x] **Slice 5 — Exams & Report Cards** (exams per class, subjects + max/pass marks, subject-wise
  mark entry with absent flag, auto grade (A1–E), section results with rank, printable report card;
  `/school/exams`)
- [x] **Slice 6 — Certificates** (TC / Bonafide / Character; per-store editable templates with
  placeholder tokens stored in StoreConfig, auto serial per type, printable + A4 PDF; `/school/certificates`)
- [x] **Slice 7 — Timetable** (period/time-slot setup with breaks; per class-section weekly grid editor
  with subject+teacher per day×period and auto-teacher-fill from subject mapping; class timetable view +
  A4-landscape PDF; teacher weekly view; date-based period substitutions; `/school/timetable`)
- [x] **Slice 8 — Admissions** (enquiry intake with source/status pipeline New→Contacted→Visited→
  Admitted/Rejected, pipeline tiles + filters, quick status update, follow-up dates; one-click
  **Convert to Student** → creates the student record and opens the admission form; `/school/admissions`)
- [x] **Slice 9 — Branch switcher** (reuses the existing `branches` table; owner manages campuses &
  switches the active branch from the sidebar, staff pinned to their `branch_id`; `BranchScoped` trait =
  global scope + auto-set `branch_id` on create, applied to students/enrollments/attendance/fees/
  admissions/certificates; academic catalogue stays store-wide; serial generators bypass the scope so
  admission/invoice/receipt/enquiry numbers stay unique store-wide; `/school/branches`)
- [x] **Slice 10 — Promotion / Year-end roll-over** (pick a session+class+section → roster with
  per-student checkboxes; promote selected to a target session/class/section with auto roll numbers
  (closes old enrolment, opens new), or graduate the final class to alumni (`status=2`); next-class
  auto-suggested; branch-scoped; `/school/promotion`)
- [x] **Slice 11 — Reports & Analytics** (dashboard: active students / month collection / outstanding
  dues / month attendance %; enrollment-by-class bars, gender split, 6-month fee-collection trend, top
  defaulters; session filter; branch-scoped; links to detailed attendance/fee reports; `/school/reports`)
- [x] **Front Admission Enquiry form** (public, login-free intake on the store webpage:
  `{city}/store/{slug}/admission`; school-only floating "Apply for Admission" CTA injected via the
  front layout for `business_type=school`; submissions land in the vendor Admissions pipeline with
  `source=Website`, store-wide enquiry no; optional campus + seeking-class pickers)
- [x] **Slice 12 — Notice Board** (announcements with audience targeting Everyone/Students/Parents/
  Staff + optional per-class scope, pin-to-top, publish/draft toggle, notice & expiry dates;
  filter + search; `/school/notices`)
- [x] **Slice 13 — Bulk Student Import** (CSV upload, dependency-free native parsing; downloadable
  template; class/section/session matched by name; auto or provided admission no with dup-check;
  optional enrollment; imports into the active branch; per-row skip report; `/school/students/import`)
- [x] **Student Documents** (upload prior-school records — TC, marksheets, birth/caste/ID certs,
  medical — per student on the profile; PDF/image up to 8 MB, typed + titled, view/delete;
  stored under `school/student-docs/`)
- [x] **School public webpage** (dedicated admission-focused store template auto-served when
  `business_type=school`: hero + Apply-for-Admission CTA, stats, classes-offered (from academic setup),
  why-choose-us facilities, latest notices (published store-wide), campus gallery, parent reviews,
  contact/CTA; `front-views/store_webpage/school.blade.php`)
- [x] **2nd school webpage template + picker** (premium editorial design `school-2.blade.php` — Playfair
  serif + gold accents, sticky nav, animated hero; vendor chooses Template 1 (Classic) or 2 (Premium)
  from School Settings → saved to `store_config.school_template_id`, with live preview links;
  `?school_template=N` also previews)
- [x] Homework
- [x] Transport
- [x] **Hostel** (blocks w/ type+warden, rooms w/ floor/capacity/rent, capacity-checked student room
  allocation + monthly fee, occupancy badges, branch-scoped, tabbed UI; `/school/hostel`)
- [x] **Parent/Student portal** (front customer side, `auth('web')` — no new app/guard, per locked
  decision #2: `/my-school`; parent links a child by admission no + DOB → `student_guardian_links`;
  child dashboard shows attendance %, latest results, fee dues, homework, weekly timetable & notices;
  read-only, branch-agnostic; entry in customer dashboard menu)
- [x] **Admin plan tiers** (platform-admin CRUD for school subscription tiers by student count +
  branch cap + monthly/yearly price; mirrors HMIS bed-tiers; `SchoolStudentTier` + `forStudentCount()`;
  seeded Starter/Growth/Professional/Enterprise; `student_tier_id` on VendorSubscription; soft
  enforcement blocks new admissions past the cap; admin: Services Billing → School Plan Tiers)
- [x] **Fee Concession & Scholarships** (managed schemes — percent/fixed with optional cap; assign to
  students per session; auto-applies & pre-fills the Concession field at fee collection with a scheme
  breakdown; records `concession_note` on the invoice + receipt; summary of awarded/beneficiaries;
  reuses `fees` permission; `FeeConcession` + `StudentConcession`; Fees → Scholarships)
- [x] **Student Leave Management** (file leave by class→student with type/date-range/reason; pending→
  approve/reject pipeline with tiles & filters; **approval auto-marks attendance as `leave`** for the
  span, preserving holidays; branch-scoped; reuses `student_attendance` permission; `StudentLeave`;
  Students → Attendance → Leave Requests. **Parents also submit from the portal** — `/my-school/{id}`
  has a request form + status history; submissions land as `pending` in the school's pipeline with the
  child's branch_id set so branch staff see them; `school.portal.leave`)
- [x] **Question Bank** (reusable questions per class+subject with chapter/type/difficulty/marks; MCQ
  options + answer key; filterable list; **one-click printable Question Paper** generator with random
  pull, marks total, optional answer key; store-wide like academic catalogue; reuses `exams` permission;
  `QuestionBankItem`; Academics → Question Bank)
- [x] **Per-student deep dashboard** (staff analytics page per child: overall attendance % + 6-month
  trend + status breakdown, exam-performance bars with pass/fail + average, fee billed/paid/due +
  invoices, recent leave; reuses `students,view`; `students/{id}/dashboard`, linked from list & profile)
- [x] **Portal extras** (parent-side: student photo on cards & child page, printable **ID Card**
  button → `school.portal.id-card`; portal pages now render inside the customer-dashboard sidebar)
- [x] **Short Leave / Gate Pass** (mid-day early-exit: issue a gate pass by class→student with out-time,
  reason, picked-up-by + relation + contact, returning-today toggle; auto serial `GP-n`; **printable
  gate-pass slip** with signature lines; mark-returned with return-time; optional half-day attendance
  mark when not returning; date-filtered list + today tiles; reuses `student_attendance` permission;
  `StudentShortLeave`; Students → Attendance → Short Leave / Gate Pass)
- [ ] Online fee payment (portal) · Activity logs · Consent/Health/Wellness (later)

---

## 9. Locked decisions (confirmed) + v3.3 FRD alignment
Reconciled with **School Management FRD v3.3** (70+ features, groups A–O, phases 1/2a/2b/3).

1. **Tenancy:** `school_id` in the FRD **= `store_id`** here. **One store = one school; a school has
   many branches** → every school table is scoped by **`store_id` + `branch_id`**. Branch reuses the
   existing Branch system; HQ = the store owner, branch staff scoped by `branch_id`.
2. **No separate parent/student app.** Parents/students use **existing MyChitti channels** (web/app) —
   **no new auth guard or standalone app is built.** Parent/student-facing views are deferred and ride
   on the existing customer/user side; not part of the early slices.
3. **Roles → permission keys + custom roles.** The FRD 12-role matrix (HQ/Branch Admin, Principal,
   Teacher, Accountant, Librarian, Counselor, Nurse, Gate, Canteen, Parent, Student) becomes
   **permission keys** assigned via `CustomRoleController`. `View*` = reason-logged access for
   health/wellness data.
4. **Subscription gating:** FRD `PlanFeatureMiddleware` → our **`planwise:school_manage`** + per-tier
   plan flags (Starter/Growth/Professional/Enterprise). Branch count enforced per tier.
5. **AI (Sam Agent):** routes through existing **`_ai_service/` + `AiServiceClient`** (Phase 2b+).
6. **Fees + GST receipts:** reuse **`ManualInvoice`** (has cgst/sgst/igst) + **post to accounts
   ledger/daybook** (the OP-consultation pattern). Fee collection **always** posts to the ledger.
7. **No migration files** — schema via guarded `CREATE TABLE / ALTER`; per-store settings in
   `StoreConfig`.
8. **Build order (confirmed):** scaffolding → Academic Setup → Students → Student Attendance →
   Fees (structure + collection→ledger + GST receipt) → dashboard; then per FRD phases.

### Deferred / later-phase (from FRD, not in first slices)
PDPB **Consent Management** (gates wellness/health features), **Mental Wellness & Health Records**
(separately-encrypted, reason-logged), **AI talent/career**, **Online Exam + anti-cheat**, **Canteen/
Mess**, **Visitor/Gate Pass**, **Offline/PWA**, **Biometric**, **WhatsApp tiered-markup credits**,
**Alumni**, **Onboarding wizard + data migration**, **audit log/export/backup**.
