# Project Notes — MyChitti

Running notes file. **Add new entries at the top**, newest first, with a date. Use this for decisions,
toggles, gotchas and "why is it like this" context that isn't obvious from the code or git history.

---

## 2026-07-15 — Vendor-panel "Generate with AI" buttons are HIDDEN

**Decision:** All vendor-panel AI generate buttons are hidden behind one feature flag. To be enabled later.

**How to enable (one switch, all 4 tools):**
```env
# .env
VENDOR_AI_TOOLS_ENABLED=true
```
then on the server:
```bash
php artisan config:clear && php artisan view:clear
```
Flag lives in `config/services.php` → `services.vendor_ai_tools.enabled` (default **false**).
Blade guard used on each button: `@if (config('services.vendor_ai_tools.enabled'))`.

**What the flag hides (4 buttons):**
| Tool | Screen | Button |
|---|---|---|
| Review Auto-Reply | My Business → Reviews → Reply | "✨ Draft with AI" |
| Missed-Lead Follow-up | My Business → Lead Inbox | "✨ Draft" |
| AI Draft Items (quotation) | Billing → Create Invoice | "✨ AI Draft Items" |
| Write with AI (description) | Settings → Webpage → About Us | "✨ Write with AI" |

**Not hidden by this flag** (intentionally — consumer-side, not vendor panel):
- Home feed "Recommended for You" / "Trending This Week" (Recommendation Engine, 4a)
- AI Search
- Store page "Call Now" lead tracking

**Backend note:** only the UI is hidden. The endpoints (`vendor.reviews.ai-draft-reply`,
`vendor.lead-signals.ai-follow-up`, `vendor.invoice.ai-quotation-items`,
`vendor.business-settings.about-us.ai`) still exist and are vendor-auth + per-store scoped.
If they must be fully off, also guard the routes — not done yet.

---

## 2026-07-15 — Where the Phase 4 code lives (IMPORTANT)

**There is no `phase4` branch.** The Phase 4 AI work is in a **git stash**:

```
stash@{0}: On main: phase4: reviews, lead signals, billing, about_us WIP
```
29 files, ~1280 insertions. Stashed from **`main`**. Branches that exist: `main`, `hmis` (current),
`backup-main`, `inventory-missing-filter-modules`, `seo-enhancements`.

Inspect without applying:
```bash
git stash show --stat "stash@{0}"     # file list
git stash show -p "stash@{0}"         # full diff
```
Suggested restore (keeps `main` clean, doesn't disturb other branches):
```bash
git checkout main
git checkout -b phase4
git stash pop "stash@{0}"
```
> Working tree only reflects the current branch — if the AI buttons "disappear", check the stash first.

---

## Standing notes

### Vendor views are duplicated per module
Vendor Blade views are copied per module: base `resources/views/vendor-views/<path>` **plus**
`app/Modules/{HMIS,Laundry,POS,PosRetail}/views/vendor-views/<path>`. Editing only the base misses
most vendors. Always run first:
```bash
find . -path "*/vendor-views/<relative/path>" -not -path "*/node_modules/*"
```
Screens that needed all 5 copies: `service/reviews.blade.php`, `billing/create_invoice.blade.php`,
`settings/webpage/about_us.blade.php`, `ck_editor_form.blade.php`.
Single-copy (base only, falls back for all modules): `lead-signals/index.blade.php`.
Controllers and routes are **shared** — only views fork.

### Dead page
`resources/views/vendor-views/business-settings/about-us.blade.php` is an **empty stub** (no form).
The real About Us editor is `resources/views/vendor-views/settings/webpage/about_us.blade.php`
(CKEditor, `#editor`, saved to `StoreConfig.about_us`).

### AI plumbing
All vendor AI tools call **OpenAI gpt-4o-mini** directly via `Http::withToken(config('services.openai.key'))`.
Needs `OPENAI_API_KEY` in `.env` per environment, else they return "AI is not configured".
(The AI droplet's Anthropic key is unfunded — that's why OpenAI is forced.)

### Deploys
File edits auto-upload to **staging** only (SFTP hook). **Production requires git push / CI**
(`.github/workflows/deploy.yml`). Staging ≠ production — verify both.

### Parked / unlinked code
`Vendor/AiToolController` + `Vendor/InsightsController` and their views/routes still exist but are
**unlinked** from the sidebar (the old standalone "paste text → output" pages, judged too basic).
Kept for reuse; delete if they're never revived.
