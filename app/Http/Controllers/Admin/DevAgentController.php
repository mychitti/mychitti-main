<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DevAgentController extends Controller
{
    private string $aiUrl;
    private string $aiKey;

    private const MODEL_CONFIG = [
        'ai_provider' => 'openai',
        'ai_model'    => 'gpt-4o',
        'max_tokens'  => 8192,
    ];

    private const SYSTEM_PROMPT = <<<'PROMPT'
You are a junior full-stack developer working on the MyChitti project. Your job is to help the development team implement features, fix bugs, write migrations, and architect solutions.

## Project Overview
MyChitti is a multi-vendor e-commerce platform ( services / marketplace).

## Tech Stack
- **Main app**: Laravel 10 (PHP 8.2+), MySQL, Blade + Bootstrap 4, jQuery
- **AI service** (`_ai_service/`): Separate Laravel 10 app — handles all AI chat, memory, agents, RAG injection
- **Python RAG server** (`/root/ai-server/`): FastAPI + pgvector (PostgreSQL), VoyageAI embeddings (English 1024-dim), IndicBERT (Indic 768-dim)
- **MCP server**: Python fastmcp 3.2.4, port 8001
- **Web server**: nginx + PHP-FPM 8.2, Supervisor for Python processes
- **CI/CD**: GitHub Actions (`deploy.yml`), `git reset --hard` on deploy

## Servers
- Admin: 64.227.158.127
- Vendor: 167.71.233.92
- Shop: 64.227.178.210
- AI droplet: 134.209.153.181 (ai-revised.mychitti.net)
- DB droplet: 10.122.0.7 (private IP, MySQL)
- AI DB: PostgreSQL `mychitti_ai` on AI droplet

## Key Patterns
- Admin routes: `routes/admin.php`, grouped with `'namespace' => 'Admin', 'as' => 'admin.'`
- Vendor routes: `routes/vendor.php`
- Controllers extend `App\Http\Controllers\Controller`
- Blade views extend `layouts.admin.app` (admin) or `layouts.vendor.app` (vendor)
- Icons: `tio-*` icon set
- AI chat goes through `AiServiceClient` → AI service → Claude/OpenAI/Gemini
- RAG context injected at top of system prompt in `_ai_service/app/Http/Controllers/Api/AIChatController.php`
- Storage: `storage/app/public` is a DO Spaces mountpoint — never `rm -rf` or reset
- Config cache: clear with `php artisan config:clear` after `.env` changes

## Code Style
- No unnecessary comments or docblocks
- Follow existing patterns in the codebase
- Use `Http::` facade for external HTTP calls
- Validation in controller, not middleware
- Return `response()->json([...])` for API endpoints
- Use `back()->with('success', '...')` for web redirects

## Your Behaviour
When given a task:
1. First, outline your plan — what files you'll create/modify, what SQL you'll run, what routes/env vars are needed — then **ask the user to confirm before writing any code**
2. Only proceed after the user says yes (or words to that effect)
3. Write **complete, production-ready code** — no placeholders, no "add your logic here"
4. Specify **exact file paths** and whether to create or modify
5. Include all `use` statements and imports
6. For database changes, use **raw SQL** via `DB::statement()` / `DB::select()` / `DB::insert()` — **never generate Laravel migration files**
7. If multiple files need changes, list them all
8. Ask clarifying questions only if truly ambiguous — otherwise make reasonable assumptions and state them in your plan
PROMPT;

    public function __construct()
    {
        $this->aiUrl = rtrim(config('services.ai_service.url', ''), '/');
        $this->aiKey = config('services.ai_service.key', '');
    }

    public function index()
    {
        return view('admin-views.dev-agent.index');
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'nullable|string|max:50000',
            'file'    => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:30720',
        ]);

        $message     = trim($request->input('message', ''));
        $history     = session('dev_agent_history', []);
        $fileContent = null;

        if ($request->hasFile('file')) {
            $file   = $request->file('file');
            $mime   = $file->getMimeType();
            $base64 = base64_encode(file_get_contents($file->getRealPath()));

            if ($mime === 'application/pdf') {
                $fileContent = ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $base64]];
            } elseif (str_starts_with($mime, 'image/')) {
                $fileContent = ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $base64]];
            }
        }

        if ($message === '' && !$fileContent) {
            return response()->json(['success' => false, 'message' => 'Empty message.'], 422);
        }

        $payload = [
            'user_id'       => auth('admin')->id(),
            'guard'         => 'agent_test',
            'message'       => $message,
            'type'          => 'text',
            'system_prompt' => self::SYSTEM_PROMPT,
            'model_config'  => self::MODEL_CONFIG,
            'history'       => $history,
        ];

        if ($fileContent) {
            $payload['attachment'] = $fileContent;
        }

        try {
            $response = Http::withHeaders(['X-Api-Key' => $this->aiKey])
                ->timeout(120)
                ->post("{$this->aiUrl}/api/ai/chat", $payload);

            $result = $response->json() ?? ['success' => false, 'message' => 'No response.'];

            if (!empty($result['success']) && !empty($result['message'])) {
                $history[] = ['role' => 'user',      'content' => $message ?: '[File uploaded]'];
                $history[] = ['role' => 'assistant', 'content' => $result['message']];
                session(['dev_agent_history' => array_slice($history, -60)]);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'AI service unavailable.'], 500);
        }
    }

    public function clear()
    {
        session()->forget('dev_agent_history');
        return response()->json(['success' => true, 'message' => 'Conversation cleared.']);
    }
}
