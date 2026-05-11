# Billing Module

## Controllers
- `app/Http/Controllers/Vendor/BillingController.php` — create/edit/view invoices, purchase bills, payment
- `app/Http/Controllers/Vendor/ServiceController.php` — manual bill, invoice list, mark paid, reminder
- `app/Http/Controllers/Vendor/SettingsController.php` — billing settings (serial numbers, invoice template)

## Routes
- File: `routes/vendor.php` lines 137–177
- Prefix: `billing` → route name prefix `invoice.*`  (e.g. `vendor.invoice.list`)
- Outside `planwise:account_manage` group — no planwise guard on billing routes

## Key Routes
- `billing/list` — invoice list (`permission:billing,list`)
- `billing/create-invoice` — create invoice (`permission:billing,add_advanced`)
- `billing/veiw-invoice/{id}` — view invoice (`permission:billing,view`) ← typo intentional, do NOT fix
- `billing/edit/{id}` — edit invoice
- `billing/manual-bill` — manual bill (`permission:billing,add_basic`)
- `billing/mark-paid` — mark paid
- `billing/pay-bill/{id}` — payment processing
- `billing/settings` — billing settings (`vendor.invoice.settings`)
- `billing/save-invoice-template` — POST, save template choice (`vendor.invoice.save-invoice-template`)
- `billing/update-serial` — POST, update serial numbers

## Key Models
- `App\Models\ManualInvoice` — `vendor_id` = STORE ID (not vendor user ID)
- `App\Models\InvoiceItem`
- `App\Models\InvoiceConfiguration`
- `App\Models\OrderInvoice`
- `App\Models\ServiceInvoice`
- `App\Models\StoreConfig` — `invoice_template` field controls which PDF template is used

## Invoice PDF Generation
- Helper: `_createBillPdf($invoiceModel, 'vendor')` in `app/helpers.php`
- Reads `StoreConfig->invoice_template` for vendor invoices (`service_n_manual` or `service_n_manual_new`)
- Requires `bill_to`, `bill_to_type`, `tax_type` set on the invoice model before calling
- Invoice ID format: `{STORE_PREFIX}_{SERIAL}` e.g. `KHB_3` (not slash-separated)
- `store_customers` table used for `bill_to` linkage

## Views
- `resources/views/vendor-views/billing/` — invoice list, create, edit, view, purchase bills
- `resources/views/invoice_template/` — PDF templates: `service_n_manual.blade.php` (classic), `service_n_manual_new.blade.php` (modern)
- `resources/views/vendor-views/settings/invoice_settings.blade.php` — billing settings page
