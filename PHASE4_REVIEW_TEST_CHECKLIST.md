# Phase 4 — Review & Test Checklist

**Scope:** Vendor AI Tools & Recommendations (master doc §4). Covers 4a Recommendation Engine + the 4 contextual Sam tools (the pivot away from standalone "paste-text" pages) + the call-lead tracking fix + menu cleanup.

Tick each box. Anything that fails → note it under "Issues" at the bottom.

---

## 0. Pre-requisites (do these first)

- [ ] `OPENAI_API_KEY` is set in the **staging** `.env` (all AI tools need it)
- [ ] `OPENAI_API_KEY` is set in the **production** `.env`
- [ ] Ran on the target server: `php artisan route:clear && php artisan view:clear && php artisan config:clear`
- [ ] `lead_signals` table exists (Phase 3 install) — needed by Lead Inbox + call tracking
- [ ] Logged in as a **vendor** with a store that has at least: a few reviews, a few items/services, and one lead signal
- [ ] Hard-refresh each page before testing (Ctrl+F5) so new JS/blade isn't cached

> Where AI features fail with "AI is not configured. Please contact support." → the API key isn't set on that environment.

---

## 1. Menu cleanup (removed standalone tools)

- [ ] Vendor sidebar **My Business** submenu no longer shows **AI Assistant**, **Marketing AI**, **Business Insights**
- [ ] No broken links / console errors from their removal
- [ ] (Optional) The old URLs `/ai-tools`, `/ai-tools/marketing`, `/insights` still resolve if opened directly (parked, not deleted) — expected, harmless

---

## 2. 4a — Recommendation Engine

### Home feed (consumer)
- [ ] Home page shows **"Recommended for You"** and **"🔥 Trending This Week"** rows (when the zone has data)
- [ ] Both rows **auto-scroll** horizontally and **pause on hover/touch**
- [ ] **No scrollbar** visible under the rows
- [ ] Cards show the correct store name, rating, city, badges
- [ ] Results are **scoped to the user's city/zone** (e.g. Tirupati user sees Tirupati stores, not Mumbai)
- [ ] With OS "reduce motion" on → rows are static but still swipeable (no auto-scroll)

### API
- [ ] `POST /api/v1/recommendations` returns **HTTP 200** on staging
- [ ] `POST /api/v1/recommendations` returns **HTTP 200** on production
- [ ] Response has feed keys: `nearby, trending, recommended, top_rated, special_offers`
- [ ] Feeds are empty for a bogus zone, populated for a real zone (sanity check)

---

## 3. Review Auto-Reply (contextual Sam "reply")

**Where:** vendor panel → **My Business → Reviews** → expand a review → **Reply**

- [ ] A **"✨ Draft with AI"** button appears next to Submit
- [ ] Clicking it fills the reply box with a drafted reply (button shows "Drafting…" then resets)
- [ ] The draft **matches the review** — thanks a 4–5★ review; apologises + offers to make it right for a ≤3★ review
- [ ] Draft does **NOT** invent refunds/compensation or sound defensive
- [ ] Vendor can edit the text, then **Submit** saves it (existing reply flow unchanged)
- [ ] **It does NOT auto-submit** — only fills the box
- [ ] Works across module store types (test on at least 2 of: HMIS/Laundry/POS/PosRetail/default)
- [ ] Security: a vendor cannot draft a reply for a review that isn't their store's (scoped by `store_id`)

---

## 4. Missed-Lead Follow-up (contextual Sam "WhatsApp reply")

**Where:** vendor panel → **My Business → Lead Inbox**

- [ ] Each lead **with a phone** shows a **"✨ Draft"** button (guest/no-phone rows show "—", no button)
- [ ] Clicking **Draft** opens a composer with an AI-written WhatsApp follow-up
- [ ] The message **matches what the customer did** (called / messaged / directions / website)
- [ ] **Send on WhatsApp** opens WhatsApp to that number **with the text pre-filled**
- [ ] Editing the text updates what gets sent (link stays in sync)
- [ ] **Copy** button works
- [ ] Message does not invent prices/offers

---

## 5. AI Draft Items — Quotation (contextual Sam "quotation")

**Where:** vendor panel → **Billing → Create Invoice**

- [ ] An **"✨ AI Draft Items"** button appears next to "+ Add Item"
- [ ] Clicking it opens a modal to describe the work
- [ ] Describing a job (e.g. *"AC service 1200, gas refill 1800 x2"*) → **Generate items** adds rows to the invoice
- [ ] Each row has the right **name / price / qty**
- [ ] Invoice **totals recalculate** automatically after items are added
- [ ] Items with **no stated price come in as 0** (AI does not invent prices)
- [ ] Rows are **editable** and the invoice **saves normally** (existing save flow unchanged)
- [ ] Works on at least 2 module store types (all 5 `create_invoice` copies patched)
- [ ] Guarded by permission `billing,add_advanced` (a vendor without it can't call it)

---

## 6. Write with AI — Business Description (contextual Sam "description")

**Where:** vendor panel → **Settings → Webpage → About Us tab**

- [ ] A **"✨ Write with AI"** button appears next to **Update**
- [ ] Clicking it opens the "Write About Us with AI" modal
- [ ] **Generate** writes a 2-paragraph description **into the rich (CKEditor) editor**
- [ ] The description **reflects the store's real services** (pulled from the `items` table), not generic filler
- [ ] It does **not invent services** the store doesn't offer
- [ ] Optional details box (e.g. "10 yrs experience") is included when filled
- [ ] If the store already has an About Us, it **improves/rewrites** rather than blanking it
- [ ] Vendor can edit, then **Update** saves it
- [ ] A store with **no items** still gets a description from name/area + typed notes (graceful fallback)

---

## 7. Call-lead tracking fix

**Where:** public store page using **template-2** (has "Enquire Now" + "Call Now")

- [ ] Clicking **"Call Now"** fires a `POST /track-store-contact` request (visible in DevTools → Network)
- [ ] A matching `call` lead appears in that store's **Lead Inbox** afterwards
- [ ] Logged-in customer's call shows their **name + phone**; a guest call shows "Guest" with no phone
- [ ] The existing phone-number link (`.store-phone-call`) still tracks too (no double-count issues)

> Note: "Call Now" exists only in **template-2**. Stores on other templates need the same `data-lead-action="call"` + `data-lead-store` tags if they have a call CTA.

---

## 8. Code review points (spot-check)

- [ ] **Per-store scoping** on every AI endpoint (`Helpers::get_store_id()` / `where('store_id', …)`)
- [ ] **Module-view parity** — the 5-copy screens (Reviews, Create Invoice, About Us, ck_editor_form) all got the same change
- [ ] **Error handling** — each AI button shows a friendly message on failure and never leaves a spinner stuck
- [ ] **No secrets in the client** — the OpenAI key is only used server-side
- [ ] **CSRF** present on all POSTs (X-CSRF-TOKEN header or `_token`)
- [ ] `php -l` clean on: ReviewController, LeadSignalController, BillingController, BusinessSettingsController, routes/vendor.php

---

## 9. Deploy to production

- [ ] All Phase 4 changes committed and pushed (staging auto-upload does NOT cover prod)
- [ ] CI / `deploy.yml` ran green
- [ ] Post-deploy: `php artisan route:clear && view:clear && config:clear` on prod
- [ ] Re-run §2 (API 200) + one AI tool on production
- [ ] Production `laravel.log` shows no new `production.ERROR` from these endpoints

---

## Files changed in Phase 4 (reference)

**Controllers:** `Api/V1/RecommendationController`, `Vendor/ReviewController`, `Vendor/LeadSignalController`, `Vendor/BillingController`, `Vendor/BusinessSettingsController`, `Front/FrontController`
**Routes:** `routes/vendor.php`, `routes/api/v1/api.php`, `routes/web.php`
**Views (5 copies each where noted):** `front-views/home.blade.php`, `front-views/store_webpage/template-2.blade.php`, `service/reviews.blade.php` ×5, `lead-signals/index.blade.php`, `billing/create_invoice.blade.php` ×5, `settings/webpage/about_us.blade.php` ×5, `ck_editor_form.blade.php` ×5, sidebar menu
**Parked (unlinked):** `Vendor/AiToolController`, `Vendor/InsightsController` + their views/routes

---

## Issues found

| # | Feature | What happened | Severity |
|---|---------|---------------|----------|
|   |         |               |          |
