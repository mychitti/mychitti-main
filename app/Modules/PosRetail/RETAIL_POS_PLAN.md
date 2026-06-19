# Retail POS Billing — Build Plan

Source spec: `MC_Retail_POS_Billing_Module_SRS_v1.0` (June 2026).

## Architecture decision
A **full self-contained module** at `app/Modules/PosRetail` (`business_type = 'pos_retail'`), built
like HMIS/POS: it owns its own controllers + views for every vendor area (billing, inventory,
accounts, HR, etc.). Wiring:
- `config/business_modules.php` → `pos_retail.controllers` maps each base `App\Http\Controllers\Vendor\*`
  to `App\Modules\PosRetail\Controllers\Vendor\*`; `pos_retail.views` → `app_path('Modules/PosRetail/views')`.
- `ResolveModuleControllers` / `ResolveModuleViews` swap controllers + prepend views for `pos_retail`.
- `PosRetailServiceProvider` registers the `posretail::` view namespace.
- Base routes stay; the controller swap routes them to PosRetail per request. Net-new screens live in
  `routes/vendor/retail-pos.php` (the `RetailPosController` billing screen).

The retail billing screen finalizes a sale into a GST `ManualInvoice` (never an `Order`); products come
from `InventoryItem` (`sku_id`, `hsn`, `gst_rate`, `gst_status`, `stock`, `barcode`).

**Finalize a sale into a GST `ManualInvoice`, never an `Order`.** Products come from `InventoryItem`
(`sku_id`, `hsn`, `gst_rate`, `gst_status`, `stock`, `barcode`).

## Reuse map (existing assets)
| SRS | Reuse |
|---|---|
| Finalize → GST invoice | `SalespointController@convert_to_bill` pattern |
| GST calc (CGST/SGST/IGST) | `calculateTax()`, `determineBillGstType()` — `app/helpers.php` |
| A4 invoice PDF | `_createBillPdf()` + `invoice_template/service_n_manual.blade.php` |
| Barcode scan → product | `inventory/scanner.blade.php`, `InventoryController@fetch-by-sku` |
| Stock reduction | `_updateInventoryStock()` (decrements), `Helpers::_placeInventoryOrder()` (Sale Order) |
| Customer / walk-in | `POSController@get_customers/customer_store`, `StoreCustomer` |
| Roles/permissions | `features`/`feature_permissions`/`role_feature_permissions` grid + `permission:` middleware |
| Amount in words | `_convertNumberToWords()` |

## New tables / columns (guarded CREATE/ALTER — no migration files)
- `pos_held_bills` (store_id, terminal_id, hold_code, cashier_id, customer_id, cart_json, status, created_at) — §4.4
- `pos_payment_legs` (manual_invoice_id, mode, sub_type, amount, reference, approval_code) — §4.2/4.5
- `pos_terminals` (store_id, name, code) — §3.2
- `pos_hardware_config` (store_id, terminal_id, device_type, settings_json) — §3.2
- `store_customers` += `loyalty_points`, `wallet_balance`, `credit_limit`, `credit_balance` — §4.2/4.5
- `pos_loyalty_ledger` (customer_id, invoice_id, type, points) — §4.3
- `manual_invoices` += `pos_status` (final/void), `void_reason`, `voided_by`, `terminal_id` — §6 audit

## Permission sub-features (master_module `pos_retail`) — §8
Grantable feature rows:
- `pos_billing` (**New Sale**): `create` (opens the sale screen + finalizes), `price_override`, `hold`, `resume`
- `pos_item_discount` (**Item Discount**): `apply`
- `pos_bill_discount` (**Bill Discount**): `apply`
- `pos_bills` (**Bills**): `view`, `void`, `print` (dashboard also gated on `pos_bills,view`)
- `pos_gst_report` (**GST Report**): `view`
- `pos_branch` (**Branches**): `view`, `create`, `delete`
- `pos_counter` (**Counters**): `create`, `delete` (page view via `pos_branch,view`)
- `pos_branch_stock` (**Branch Stock**): `view`, `edit`

Split/partial payment needs no permission. Cashier item-discount cap (≤5% default) —
above the cap needs `pos_item_discount,apply` or `pos_billing,price_override`. Seeded
self-healing (Radiology/Pharmacy pattern); legacy actions and dropped feature rows are auto-pruned.

## Out of Laravel scope (separate tracks)
- **Hardware desktop agent** (Electron/localhost bridge): cash drawer (RJ11), weighing scale (RS-232),
  thermal printer (ESC/POS). Barcode/QR already work via browser HID.
- **Offline mode** (PWA + SQLite cache + sync, 24h) — largest net-new effort.
- **WhatsApp Business API** invoice + SMS fallback.

## Phasing
- **Phase 1 (done):** billing screen, barcode scan, GST `ManualInvoice` finalize,
  A4 + thermal print, Cash/UPI/Card, walk-in + customer, stock reduction,
  Retail POS roles (per-capability sub-features), today's bills, void.
- **Phase 2a (done):** Hold & Resume (`pos_held_bills`), multi-leg split + partial credit
  (customer credit limit), loyalty points + wallet redemption (`pos_loyalty_ledger`;
  `store_customers` += loyalty_points/wallet_balance/credit_limit/credit_balance), customer search/link.
- **Phase 2b (done):** email invoice, WhatsApp invoice + SMS-fallback (config-gated), GST report
  (GSTR-1/3B summary by slab + CSV export), multi-terminal + hardware config (`pos_terminals`).
- **Billing polish (done):** invoice numbers use the platform's `Helpers::generateInvoiceId()` (same
  `{PREFIX}_{infix}_{FY}_{serial}` series as all manual bills); void shown with a `V`-prefix; cashier
  discount cap (5%, manager override); price override (Owner/Manager + audit `pos_audit_log`);
  out-of-stock warn + manager override; batch/expiry (≤30d) warning; Quick Access = top-sold.
- **Phase 3 (infra):**
  - **Hardware agent** — JS `POSAgent` shim wired (drawer kick + ESC/POS print + scale read), contract
    in `HARDWARE_AGENT.md`. The agent itself is a **separate Electron app** (not this repo).
  - **Offline mode (planned, not built):** core billing offline needs a client cache the current
    server-rendered Blade screen doesn't have. Approach: make New Sale a PWA — cache the product
    catalog in IndexedDB, queue finalized sales locally, and replay them to `finalize` on reconnect
    (idempotency via a client-generated sale UUID). 24h tolerance per SRS §6. This is a dedicated
    front-end track and is intentionally deferred.
