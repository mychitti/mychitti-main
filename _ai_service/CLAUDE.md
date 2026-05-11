# AI Service Context

Separate Laravel 10 app inside `_ai_service/` — handles all AI chat, memory, RAG, and agents.
The main app communicates with it via `app/Services/AiServiceClient.php` over HTTP.

## Key Files
- **Entry point**: `_ai_service/routes/api.php`
- **Chat controller**: `_ai_service/app/Http/Controllers/Api/AIChatController.php`
  - Accepts: `user_id`, `guard`, `message`, `attachment` (array), `page_screenshot` (array), `page_structure` (string), `agent_id`, `system_prompt`, `model_config`
  - Builds user message content: page structure text → screenshot image → file attachment → user text
  - Injects RAG context at top of system prompt
- **ClaudeService**: `_ai_service/app/Services/ClaudeService.php`
  - `chat()` routes to `chatClaude()`, `chatOpenAI()`, or `chatGemini()` based on `model_config.ai_provider`
  - `chatOpenAI()` converts Anthropic-format image blocks → OpenAI `image_url` blocks
  - Tool calls handled in `handleClaudeToolCall()` / `handleOpenAIToolCalls()`
- **MemoryService**: `_ai_service/app/Services/MemoryService.php` — per-user chat history, summarisation
- **RagService**: `_ai_service/app/Services/RagService.php` — vector search against Python RAG server
- **VendorAgentController**: `_ai_service/app/Http/Controllers/Api/VendorAgentController.php`
  - Handles `vendor_api_call` tool — 27 modules: staff, inventory, invoice, leads, crm, attendance, leave, salary, project, task, calendar, job_card, store, account, banking, assets, shifts, quotation, clients, orders, service, pos, documents, notification, items, campaign, coupon

## Main App Side
- **AiServiceClient**: `app/Services/AiServiceClient.php`
  - `chat()` builds system prompt (vendor profile + nav context + capabilities prompt + RAG)
  - Passes `attachment`, `page_screenshot`, `page_structure`, `page_structure_type` in payload
  - `vendorCapabilitiesPrompt()` — full tool documentation for Sam
  - `resolveVendorNavigation()` — maps URL paths to breadcrumb strings
- **Vendor AI Chat Controller**: `app/Http/Controllers/Vendor/AIChatController.php`
  - Accepts `page_screenshot` (base64 string), `page_screenshot_type`, `page_structure` from the floating chat widget
  - Converts screenshot to Anthropic image content block before passing to AiServiceClient

## Page Context Flow
Browser (html2canvas + DOM extractor) → `page_structure` (text) + `page_screenshot` (base64 PNG)
→ `AIChatController` → `AiServiceClient` → AI service → user message content blocks
→ GPT-4o / Claude sees both DOM structure and visual screenshot

## RAG Server
- URL: `https://ai-revised.mychitti.net/rag`
- Endpoints: `POST /ingest`, `PUT /documents/{id}`, `GET /documents`, `DELETE /documents/{id}`
- Admin UI: `app/Http/Controllers/Admin/RagDocumentController.php`

## Deployment
- AI service runs on AI droplet (134.209.153.181)
- Deploy: SSH to droplet, `cd /var/www/html/ai-revised && git pull`
- NOT included in GitHub Actions workflow — must deploy manually
