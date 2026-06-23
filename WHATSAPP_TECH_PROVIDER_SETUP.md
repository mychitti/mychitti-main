# WhatsApp Multi-Vendor (Tech Provider) Setup — MyChitti

Goal: each vendor sends WhatsApp from **their own number**, with the vendor **never seeing
Meta/Facebook**. That requires MyChitti to become an approved **Meta Tech Provider** and use
the **WhatsApp Business Management API** to register vendor numbers on their behalf.

This file is the **Meta-side checklist** (the long pole — days to weeks of approvals). The app
code for Phase 1 (global number, vendor-branded content) and the per-vendor plumbing is already
in place; Phase 2 (in-app number + OTP onboarding) is built *after* the steps below are done.

---

## Phase 0 — Become a Meta Tech Provider (BLOCKER, do this first)

### 1. Meta Business Account
- Create / use a **Meta Business** at https://business.facebook.com.
- Business Settings → **Business Info** → complete legal name, address, website.

### 2. Business Verification
- Business Settings → **Security Center** → **Start Verification**.
- Submit business documents (incorporation certificate, GST, utility bill, etc.).
- ⏳ Approval: typically a few days; can bounce back for document mismatches. **Start now.**

### 3. App + WhatsApp product
- https://developers.facebook.com → **Create App** → type **Business**.
- Add the **WhatsApp** product to the app.
- App → **App Settings → Basic** → add Privacy Policy URL, App icon, Category, Business use.

### 4. Advanced Access to the WhatsApp permissions
- App → **App Review → Permissions and Features**.
- Request **Advanced Access** for:
  - `whatsapp_business_management`
  - `whatsapp_business_messaging`
- This needs App Review (screencast + use-case description). Describe: *"SaaS platform that
  manages WhatsApp messaging on behalf of our business customers (their own numbers)."*

### 5. Tech Provider / Solution Partner
- To register **client** WhatsApp numbers under your portfolio (so vendors don't use their own
  Facebook), you act as a **Tech Provider**. Confirm eligibility in WhatsApp Manager.
- For higher volume / official BSP features, apply to the **WhatsApp Business Solution Provider**
  program (optional initially).

### 6. System User token (permanent)
- Business Settings → **Users → System Users** → create one (Admin).
- **Generate token** → expiry **Never** → scopes `whatsapp_business_management`,
  `whatsapp_business_messaging`.
- This platform token is what MyChitti uses to call the API for every vendor.

### 7. Payment method
- WhatsApp Manager → add a **payment method** (conversations are billed).

---

## What the vendor experience will be (Phase 2, after Phase 0)

No Meta screens. Inside MyChitti:
1. Vendor enters their business phone number.
2. MyChitti calls the API to **register** that number (under MyChitti's WABA portfolio).
3. Meta sends an **OTP** (SMS/voice) to the vendor's phone.
4. Vendor types the OTP into **MyChitti's** screen.
5. MyChitti stores the resulting `phone_number_id` on `stores.wa_phone_number_id` and sets
   `wa_enabled = 1`. From then on, that vendor's messages send from their own number.

### Hard constraints (cannot be engineered around)
- The number must be **real and controlled by the vendor**, and **not already active on the
  WhatsApp consumer app** (must be deleted/migrated first).
- The **OTP step is mandatory** — someone must read the code on that phone.
- All vendor WABAs sit under **your** portfolio → you own quality rating, limits, and policy
  compliance for them.

### Relevant Management API calls (Phase 2 implementation reference)
- Create WABA (or reuse a shared one): `POST /{business_id}/owned_whatsapp_business_accounts`
- Register a phone number / request code: `POST /{phone_number_id}/request_code`
  (or the newer `POST /{waba_id}/phone_numbers` to add a number)
- Verify the code: `POST /{phone_number_id}/verify_code` with `{ code }`
- Register for Cloud API: `POST /{phone_number_id}/register` with a 6-digit PIN
- Send messages: `POST /{phone_number_id}/messages` (already implemented in `WhatsAppService`)

---

## What's already built in MyChitti (no Meta dependency)

- **Global send pipeline** — `App\Services\WhatsAppService` (text/document/template), credential
  resolution **per-vendor → global → env**.
- **Admin config** — Settings → 3rd Party → **WhatsApp API** (global creds, test sender,
  template sender).
- **Webhook** — `/whatsapp/webhook` (verify + status/inbound) → **Delivery Report** page.
- **Per-vendor columns** — `stores.wa_enabled / wa_phone_number_id / wa_token /
  wa_business_account_id / wa_api_version` (auto-created; resolution reads them when
  `wa_enabled = 1`). These are what Phase 2 onboarding fills in.
- **Vendor branding** — outbound invoice messages prepend the store name, so even on the shared
  global number the customer sees which business it's from.

## Phase 1 vs Phase 2

| | Phase 1 (live now) | Phase 2 (after Phase 0 approvals) |
|---|---|---|
| Sender number | MyChitti global number | Each vendor's own number |
| Vendor identity | Store name in message content | Vendor's verified WhatsApp name |
| Vendor onboarding | none | In-app number + OTP (no Meta screens) |
| Meta prerequisites | global creds only | Tech Provider + Advanced Access |
