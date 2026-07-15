# Phase 4 — Vendor AI Tools & Recommendations — Build Plan 

Source: "AI SEO, GEO & Growth Master Document" v1.0, **Phase 4** (Month 9–12 business scale).
Goal: **accelerate revenue** with premium vendor AI features + personalized consumer discovery.
Follows Phase 3 (AI Search & Intelligence — complete). Same rules: no migration files (guarded
CREATE/ALTER via `DB::statement`), Eloquent for queries, `Http::` for service calls.

## Where things run (repos)
- **mychitti-main** (Laravel) — consumer app + main DB. Home of the **Recommendation Engine** and consumer feeds.
- **ai-agent** (Laravel `_ai_service`) — LLM gateway. `AIChatController` + `ClaudeService` (Claude/OpenAI/Gemini) + `AiServiceClient` (in mychitti-main). **Sam** = the vendor chat agent driven by `AiServiceClient::vendorCapabilitiesPrompt()`. Phase 4 generators call through here (force OpenAI, like the SEO pipeline, since the server's Anthropic is unfunded).
- **mcvendorhub** (Laravel, `vendor.mcvendorhub.com`) — the vendor SaaS dashboard. **No AI yet** — this is where the vendor-facing AI Tools UI (Sam/Zayan panels) is built.

## What Phase 3 already gives us (reuse map)
| Phase 4 need | Reuse from Phase 3 / existing |
|---|---|
| Semantic understanding, multilingual | `ai-server` `/business/*` (Voyage + IndicBERT) — **multilingual AI search already done** |
| Distance / proximity | haversine in `AiSearchController` (`_calcDeliveryCharge` formula) |
| Trust signals | `stores.vendor_trust_score` + `store_trust_badges()` |
| Offers | `store_offers` + `StoreOffer::active()` + `/api/v1/offers/*` |
| Lead / interest signals | `lead_signals` (call/whatsapp/directions/website) |
| Review sentiment | `store_reviews.sentiment` + ai-server `/analyze/sentiment` |
| LLM plumbing (Claude/OpenAI) | ai-agent `ClaudeService`; `AiServiceClient::chat()`; ai-server `/business/ask` pattern |
| Vendor agent shell (Sam) | ai-agent chat + `vendorCapabilitiesPrompt()` tool docs |

## Phasing

### Phase 4a — Personalized Recommendation Engine (mychitti-main) — START HERE
Highest ROI, self-contained, reuses everything above. Powers consumer home feeds.
- **`POST /api/v1/recommendations`** → returns feed sections:
  - **Nearby** — active stores in the user's zone, ranked by distance + rating + trust.
  - **Trending this week** — stores with the most `lead_signals` / orders in the last 7 days (in-zone).
  - **Recommended for you** — from the user's search history + booking history + `lead_signals` → embed the intent, semantic match via ai-server `/business/search`, filter to zone.
  - **Special offers** — `StoreOffer::active()` for the user's zone (verified vendors first).
- Inputs (per master doc §4.2): GPS/zone, search history, booking history, popular-in-area, profile prefs.
- New: `user_search_history` (already partially exists via `save_search`?) — reuse if present, else a light `user_activity` signal table.
- Tasks: `RecommendationController`, feed builders (reuse ranking helpers), route + rate-limit, home-screen wiring (app + web).

### Phase 4b — Sam P0 generators (ai-agent + mcvendorhub)
Structured "generate" tools on top of the existing Sam agent. Each = a prompt template + OpenAI call (via `AiServiceClient`/ai-agent), returned to the vendor to edit before use.
- **AI Quotation Generator** — item/service + client context → draft quotation lines.
- **AI WhatsApp Reply Drafts** — incoming message + store profile → 2–3 reply options.
- **AI Business Description / Profile Builder** — store data → polished description (also feeds SEO landing + embeddings).
- Tasks: generator endpoints in ai-agent (or `/api/v1/vendor/ai-content` in mychitti-main proxying ai-agent), prompt templates, **MC Vendor Hub UI** (buttons in Billing / Client Management / Account) — first AI surface in `mcvendorhub`.
- Gate behind the **Gold/Platinum** plan (revenue upsell).

### Phase 4c — Zayan agent + AI Marketing / AI SEO modules (mcvendorhub + ai-agent)
Net-new agent + two vendor modules (P1).
- **Zayan** — marketing/SEO/outreach persona (new prompt + tool set in ai-agent).
- **AI Marketing module** — generate social-media posts / captions on demand.
- **AI SEO module** — generate SEO content for the vendor's page (ties into Phase 2 programmatic SEO).
- **CRM AI Outreach** — sales follow-up drafts for CRM leads.

### Phase 4d — Advanced vendor analytics (mcvendorhub)
- KPI dashboard: leads (from Phase 3 `lead_signals` + enquiries), conversion, offer performance, review sentiment trend, AI-search appearances.
- Reuses Phase 3 data; mostly a reporting/visualization layer.

## New tables / columns (guarded — install via a `phase4:install` command)
- `user_activity` (user_id, type[search|view|booking], ref, zone_id, created_at) — recommendation signals, if not already captured.
- `vendor_ai_generations` (store_id, tool[quotation|reply|description|social|seo], input_json, output_text, model, created_at) — audit + reuse of generated content.
- `store_config` += `about_us`/description fields already exist; reuse.

## API surface (new)
| Endpoint | Auth | Purpose |
|---|---|---|
| `POST /api/v1/recommendations` | user (or public+zone) | personalized home feeds (4a) |
| `POST /api/v1/vendor/ai-content` | vendor JWT | Sam generators (4b) — quotation/reply/description |
| (mcvendorhub internal) | vendor session | Zayan marketing/SEO tools (4c) |

## LLM strategy (per master doc, tiered)
- Premium (Sam/Zayan chat, quality copy): Claude Sonnet — **but** force **OpenAI gpt-4o/4o-mini** for now (server Anthropic unfunded, same as Phase 3 `/business/ask` + sentiment). Revisit when Anthropic is funded.
- Bulk (captions, templated SEO): gpt-4o-mini.

## Open decisions before building
1. **Recommendation auth** — public (zone-scoped, guest) vs user-JWT (uses history). Recommend: works for both; richer when logged in.
2. **Plan gating** — which generators are Gold vs Platinum (revenue model §Revenue).
3. **Where Sam generators live** — new endpoints in ai-agent, or mychitti-main `/vendor/ai-content` proxying ai-agent. Recommend the proxy (keeps vendor auth + plan checks in the main app).
4. **`user_activity` capture** — confirm whether `save_search`/booking history already give enough signal before adding a new table.
5. **mcvendorhub is a fresh AI surface** — needs its own AI service client + auth wiring to ai-agent.

## Suggested order
**4a (recommendation engine) → 4b (Sam P0 generators) → 4c (Zayan/modules) → 4d (analytics).**
4a ships consumer value immediately with zero new infra; 4b/4c drive the premium-plan revenue the phase targets.
