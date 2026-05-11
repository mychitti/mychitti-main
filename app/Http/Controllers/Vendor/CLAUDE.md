# Vendor Controllers

All vendor controllers live here. Route file: `routes/vendor.php`. Views: `resources/views/vendor-views/`.

## Module → Controller Mapping
| Module | Controller(s) |
|--------|--------------|
| Billing / Invoicing | `BillingController.php`, `ServiceController.php` |
| Billing Settings | `SettingsController.php` (invoice_settings, save_invoice_template, update_serial_number) |
| Staff / HR | `EmployeeController.php`, `StaffController.php`, `VendorEmployeeController.php`, `BasicStaffController.php`, `HRController.php` |
| Attendance | `AttendanceController.php` |
| Leave | `LeaveController.php` |
| Salary | `SalaryController.php` |
| Shift | `ShiftController.php` |
| Inventory | `InventoryController.php` |
| Account / Finance | `AccountController.php`, `AccountSettingController.php`, `BankingController.php`, `TaxController.php` |
| Assets | `AssetController.php` |
| CRM / Leads | `LeadController.php` |
| Project / Task | `ProjectController.php`, `TaskController.php` |
| Calendar | `SmartCalendarController.php` |
| AI Chat | `AIChatController.php` — proxies to `AiServiceClient` |
| Business Settings | `BusinessSettingController.php` |
| Store / Profile | `VendorController.php` |

## Route Middleware Groups
- `planwise:hr_manage` — HR premium features (staff CRUD, attendance, leave, shifts)
- `planwise:inventory_manage` — Inventory features
- `planwise:account_manage` — Account / finance features
- No planwise guard — Billing routes (`routes/vendor.php` lines 137–177), self-service HR (clock-in/out, salary-history)

## Common Patterns
```php
$storeId = Helpers::get_store_id();   // current vendor's store ID
$vendorId = auth('vendor')->id();      // current vendor user ID
Toastr::success('Done'); return back();
Toastr::error('Failed'); return back();
```

## Key Helpers
- `Helpers::get_store_id()` — returns store ID for the logged-in vendor
- `Helpers::generateInvoiceId($storeId, $fy)` — generates `{PREFIX}_{SERIAL}` format
- `_createBillPdf($invoiceModel, 'vendor')` — generates PDF, returns `['pdf' => 'filename.pdf']`
- `hasPermission($module, $action)` — check fine-grained permission
- `hasAnyModulePermission(['module1', 'module2'])` — check if any of these modules permitted
