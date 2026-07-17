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

## 2026-07-15 — Phase 4 code now lives on the `phase4` branch (RESOLVED)

The Phase 4 AI work **was** parked in a git stash (`On main: phase4: reviews, lead signals, billing,
about_us WIP`) with **no branch** — so it vanished from the working tree whenever another branch was
checked out. It has now been restored:

```
main → branched to `phase4` → stash popped → committed 880ec5c5 (32 files)
```
**All Phase 4 work is committed on the `phase4` branch.** Not yet merged to `main`, not yet pushed.

### ⚠️ Parked: hmis WIP
While restoring, uncommitted `hmis` work was parked in a stash:
```
stash@{0}: On hmis: hmis WIP: pharmacy walkin (parked by Claude 2026-07-15)
```
That's `app/Modules/HMIS/views/vendor/pharmacy/walkin.blade.php`. To get it back:
```bash
git checkout hmis
git stash pop "stash@{0}"     # verify it's still index 0 first: git stash list
```

> Lesson: the working tree only reflects the current branch. If a feature "disappears",
> run `git stash list` and `git branch` before assuming it was lost.

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
