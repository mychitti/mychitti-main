<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeService
{
    private string $apiKey;
    private string $model;
    private string $apiUrl    = 'https://api.anthropic.com/v1/messages';
    private string $openaiUrl = 'https://api.openai.com/v1/chat/completions';

    /** Collects tool calls made during the current request for debug output */
    private array $debugLog = [];

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key');
        $this->model  = config('services.anthropic.model', 'claude-sonnet-4-6');
    }

    /**
     * Send messages and get a response.
     * Routes to the correct provider based on model_config['ai_provider'].
     *
     * @param  array       $messages     [['role'=>'user','content'=>...], ...]
     * @param  string      $system       System prompt
     * @param  int         $maxTokens
     * @param  array|null  $modelConfig  { ai_provider, ai_model, max_tokens, temperature, top_p, api_key_override }
     * @param  int|null    $userId       Owner user id — passed to tools so they can fetch user data
     * @param  string|null $guard        Auth guard (user, admin, vendor) — controls which tools are available
     */
    public function getDebugLog(): array
    {
        return $this->debugLog;
    }

    public function chat(array $messages, string $system = '', int $maxTokens = 4096, ?array $modelConfig = null, ?int $userId = null, ?string $guard = null, ?int $agentId = null): string
    {
        $this->debugLog = []; // reset per request

        $provider = is_array($modelConfig) ? ($modelConfig['ai_provider'] ?? 'anthropic') : 'anthropic';

        return match (strtolower($provider)) {
            'openai'    => $this->chatOpenAI($messages, $system, $maxTokens, $modelConfig),
            'gemini'    => $this->chatGemini($messages, $system, $maxTokens, $modelConfig),
            default     => $this->chatClaude($messages, $system, $maxTokens, $modelConfig, $userId, $guard, $agentId),
        };
    }

    // ── Anthropic / Claude ────────────────────────────────────────────────

    private function chatClaude(array $messages, string $system, int $maxTokens, ?array $cfg, ?int $userId = null, ?string $guard = null, ?int $agentId = null): string
    {
        $cfg       = $cfg ?? [];
        $model     = $cfg['ai_model']   ?? $this->model;
        $maxTokens = (int) ($cfg['max_tokens'] ?? $maxTokens);
        $apiKey    = !empty($cfg['api_key_override']) ? $cfg['api_key_override'] : $this->apiKey;

        // Inform Claude about authentication context
        if ($userId && $guard === 'user') {
            $authNote = "SYSTEM NOTE: This user is already authenticated (ID: {$userId}). Do not ask them to log in. You have access to the `get_service_bookings` tool — use it when the user asks about their bookings or appointments. IMPORTANT: When a service enquiry is submitted via `create_service_booking`, always refer to it as a 'service enquiry' — NEVER as a 'confirmed booking'. The enquiry is sent to nearby vendors who will contact the user; it is not an instant confirmation.";
            $system   = trim($system);
            $system   = $system ? $system . "\n\n" . $authNote : $authNote;
        } elseif ($guard === 'guest') {
            $authNote = "SYSTEM NOTE: This is a website visitor who is not logged in. You can help them with general questions and information. If they want to book a service or view their bookings, kindly let them know they need to log in or create an account first.";
            $system   = trim($system);
            $system   = $system ? $system . "\n\n" . $authNote : $authNote;
        }

        $payload = [
            'model'      => $model,
            'max_tokens' => $maxTokens,
            'messages'   => $messages,
            'tools'      => $this->getTools($guard, $agentId),
        ];

        if (!empty($system)) {
            $payload['system'] = $system;
        }
        if (isset($cfg['temperature'])) {
            $payload['temperature'] = (float) $cfg['temperature'];
        }
        if (isset($cfg['top_p']) && !isset($cfg['temperature'])) {
            $payload['top_p'] = (float) $cfg['top_p'];
        }

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'anthropic-beta'    => 'pdfs-2024-09-25',
            'content-type'      => 'application/json',
        ])->timeout(120)->post($this->apiUrl, $payload);

        if ($response->failed()) {
            Log::error('Claude API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Claude API error1: ' . $response->body());
        }

        $data = $response->json();

        if (isset($data['stop_reason']) && $data['stop_reason'] === 'tool_use') {
            return $this->handleClaudeToolCall($data, $messages, $system, $maxTokens, $apiKey, $userId, $guard, $agentId);
        }

        return data_get($data, 'content.0.text', '');
    }

    // ── OpenAI ────────────────────────────────────────────────────────────

    private function chatOpenAI(array $messages, string $system, int $maxTokens, ?array $cfg): string
    {
        $model     = $cfg['ai_model']   ?? 'gpt-4o';
        $maxTokens = (int) ($cfg['max_tokens'] ?? $maxTokens);
        $apiKey    = !empty($cfg['api_key_override']) ? $cfg['api_key_override'] : config('services.openai.key');

        // Prepend system message in OpenAI format
        $oaiMessages = [];
        if (!empty($system)) {
            $oaiMessages[] = ['role' => 'system', 'content' => $system];
        }
        foreach ($messages as $m) {
            $oaiMessages[] = [
                'role'    => $m['role'],
                'content' => is_array($m['content'])
                    ? collect($m['content'])->where('type', 'text')->pluck('text')->implode(' ')
                    : $m['content'],
            ];
        }

        $payload = [
            'model'      => $model,
            'max_tokens' => $maxTokens,
            'messages'   => $oaiMessages,
        ];
        // OpenAI does not allow temperature and top_p together — use temperature if available, else top_p
        $temperature = $cfg['temperature'] ?? null;
        $topP        = $cfg['top_p']        ?? null;
        if ($temperature !== null) {
            $payload['temperature'] = (float) $temperature;
        } elseif ($topP !== null) {
            $payload['top_p'] = (float) $topP;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(120)->post($this->openaiUrl, $payload);

        if ($response->failed()) {
            Log::error('OpenAI API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('OpenAI API error: ' . $response->body());
        }

        return data_get($response->json(), 'choices.0.message.content', '');
    }

    // ── Google Gemini (via OpenAI-compatible endpoint) ────────────────────

    private function chatGemini(array $messages, string $system, int $maxTokens, ?array $cfg): string
    {
        $model     = $cfg['ai_model']   ?? 'gemini-1.5-pro';
        $maxTokens = (int) ($cfg['max_tokens'] ?? $maxTokens);
        $apiKey    = !empty($cfg['api_key_override']) ? $cfg['api_key_override'] : config('services.gemini.key');

        $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        // Build Gemini contents format
        $contents = [];
        if (!empty($system)) {
            $contents[] = ['role' => 'user',  'parts' => [['text' => $system]]];
            $contents[] = ['role' => 'model', 'parts' => [['text' => 'Understood.']]];
        }
        foreach ($messages as $m) {
            $text = is_array($m['content'])
                ? collect($m['content'])->where('type', 'text')->pluck('text')->implode(' ')
                : $m['content'];
            $contents[] = [
                'role'  => $m['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $text]],
            ];
        }

        $payload = [
            'contents'          => $contents,
            'generationConfig'  => ['maxOutputTokens' => $maxTokens],
        ];
        if (isset($cfg['temperature'])) {
            $payload['generationConfig']['temperature'] = (float) $cfg['temperature'];
        }
        if (isset($cfg['top_p'])) {
            $payload['generationConfig']['topP'] = (float) $cfg['top_p'];
        }

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(120)
            ->post($geminiUrl, $payload);

        if ($response->failed()) {
            Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Gemini API error: ' . $response->body());
        }

        return data_get($response->json(), 'candidates.0.content.parts.0.text', '');
    }

    // ── Claude tool-call follow-up ────────────────────────────────────────

    private function handleClaudeToolCall(array $data, array $messages, string $system, int $maxTokens, string $apiKey, ?int $userId = null, ?string $guard = null, ?int $agentId = null): string
    {
        $toolResults = []; 

        foreach ($data['content'] as $block) {
            if ($block['type'] !== 'tool_use') continue;

            $result   = $this->executeTool($block['name'], $block['input'], $userId, $agentId);
            $isError  = false;

            if (is_string($result) && str_starts_with($result, 'PERMISSION_DENIED:')) {
                $denial  = trim(substr($result, strlen('PERMISSION_DENIED:')));
                $result  = $denial !== '' ? $denial : "You don't have permission to use this tool.";
                $isError = true;
            }

            $toolResults[] = [
                'type'        => 'tool_result',
                'tool_use_id' => $block['id'],
                'content'     => $result,
                'is_error'    => $isError,
            ];
        }

        // Re-cast tool_use input fields: PHP decodes empty JSON objects {} as []
        // but the Anthropic API requires input to be an object {}, not an array [].
        $assistantContent = array_map(function ($block) {
            if ($block['type'] === 'tool_use' && is_array($block['input'])) {
                $block['input'] = (object) $block['input'];
            }
            return $block;
        }, $data['content']);

        $messages[] = ['role' => 'assistant', 'content' => $assistantContent];
        $messages[] = ['role' => 'user',      'content' => $toolResults];

        $followPayload = [
            'model'      => $this->model,
            'max_tokens' => $maxTokens,
            'messages'   => $messages,
            'tools'      => $this->getTools($guard, $agentId),
        ];
        if (!empty($system)) {
            $followPayload['system'] = $system;
        }

        $followResponse = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'anthropic-beta'    => 'pdfs-2024-09-25',
            'content-type'      => 'application/json',
        ])->timeout(120)->post($this->apiUrl, $followPayload);

        if ($followResponse->failed()) {
            Log::error('Claude tool follow-up error', ['status' => $followResponse->status(), 'body' => $followResponse->body()]);
            throw new \Exception('Claude API error2: ' . $followResponse->body());
        }

        $followUp = $followResponse->json();

        return collect($followUp['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');
    }

    // ── Tools (Claude format) ─────────────────────────────────────────────

    private function executeTool(string $toolName, array $input, ?int $userId = null, ?int $agentId = null): string
    {
        // ── Agent context: check for configured api_tool endpoint first ──
        if ($agentId) {
            $apiTool = DB::selectOne(
                'SELECT * FROM agent_api_tools WHERE agent_id = ? AND api_name = ? AND status = 1 LIMIT 1',
                [$agentId, $toolName]
            );

            if ($apiTool) {
                $result = $this->callAgentApiTool($apiTool, $input, $userId);
                $this->debugLog[] = ['tool' => $toolName, 'input' => $input, 'result' => $result, 'via' => 'agent_api_tool'];
                return $result;
            }

            // No agent api_tool match — fall through to hardcoded tools below
        }

        // ── Hardcoded built-in tools ──
        $result = match ($toolName) {
            'store_query'             => $this->storeQuery($input),
            'get_service_bookings'    => $this->fetchServiceBookings($userId),
            'list_services'           => $this->fetchServices(),
            'get_user_addresses'      => $this->fetchUserAddresses($userId),
            'create_service_booking'  => $this->submitServiceBooking($userId, $input),
            default                   => 'Tool not found.',
        };

        $this->debugLog[] = ['tool' => $toolName, 'input' => $input, 'result' => $result];

        return $result;
    }

    /**
     * Call a dynamically-configured agent API tool endpoint.
     * Endpoint may contain {userId} / {user_id} which are replaced at runtime.
     */
    private function callAgentApiTool(object $apiTool, array $input, ?int $userId): string
    {
        $mainUrl = rtrim(config('services.main_app.url', ''), '/');
        $key     = config('services.main_app.key', '');

        $endpoint = str_replace(['{userId}', '{user_id}'], (string) ($userId ?? 0), $apiTool->endpoint);
        $fullUrl = str_starts_with($endpoint, 'http') ? $endpoint : $mainUrl . $endpoint;

        try {
            $method = strtolower($apiTool->method ?? 'post');
            $response = Http::withHeaders([
                'X-Api-Key'    => $key,
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(15);

            if ($method === 'get') {
                $response = $response->get($fullUrl, $input);
            } else {
                $response = $response->$method($fullUrl, $input);
            }

            if ($response->failed()) {
                $body   = $response->json();
                $detail = $body['debug_error'] ?? $body['message'] ?? $response->body();
                Log::error('Agent API tool call failed', ['tool' => $apiTool->api_name, 'status' => $response->status(), 'body' => $response->body()]);
                return 'Tool call failed: HTTP ' . $response->status() . ': ' . $detail;
            }

            $data = $response->json();
            // Return message if present, otherwise compact JSON
            return $data['message'] ?? json_encode($data);
        } catch (\Exception $e) {
            Log::error('Agent API tool exception', ['tool' => $apiTool->api_name, 'error' => $e->getMessage()]);
            return 'Tool call failed: ' . $e->getMessage();
        }
    }

    private function storeQuery(array $input): string
    {
        try {
            \App\Models\Contact::create([
                'subject' => $input['subject'],
                'message' => $input['message'],
                'name'    => $input['name'],
                'email'   => $input['email'],
                'seen'    => '0',
            ]);
            return 'Query stored successfully. A support agent will follow up soon.';
        } catch (\Exception $e) {
            return 'Failed to store query: ' . $e->getMessage();
        }
    }

    private function getTools(?string $guard = null, ?int $agentId = null): array
    {
        // ── Agent-configured function schemas take full control ────────────
        if ($agentId) {
            $functions = DB::select(
                'SELECT * FROM agent_functions WHERE agent_id = ? ORDER BY sort_order ASC',
                [$agentId]
            );

            if (!empty($functions)) {
                return array_map(fn($f) => [
                    'name'         => $f->function_name,
                    'description'  => $f->description,
                    'input_schema' => $f->json_schema
                        ? (is_string($f->json_schema) ? json_decode($f->json_schema, true) : (array) $f->json_schema)
                        : ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
                ], $functions);
            }

            // ── No function schemas — build hardcoded tools, then add any
            //    extra agent api_tools that aren't already hardcoded ──────
        }

        // ── Hardcoded tools (always the source of truth for schemas) ──
        $tools = [
            [
                'name'         => 'store_query',
                'description'  => 'Store a customer query or complaint in the database for follow-up by the support team.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'subject' => ['type' => 'string', 'description' => 'Short subject/title of the query'],
                        'message' => ['type' => 'string', 'description' => 'Full details of the customer query'],
                        'name'    => ['type' => 'string', 'description' => 'Full Name'],
                        'email'   => ['type' => 'string', 'description' => 'Email'],
                    ],
                    'required' => ['subject', 'message', 'name', 'email'],
                ],
            ],
        ];

        // User-guard tools: only for authenticated users (not guests)
        if ($guard === 'user' || $guard === null) {
            $tools[] = [
                'name'         => 'get_service_bookings',
                'description'  => 'Fetch the current user\'s service booking history and real-time status. Call this when the user asks about their service bookings, repairs, or appointments.',
                'input_schema' => ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
            ];
            $tools[] = [
                'name'         => 'list_services',
                'description'  => 'Get all available services the user can book. Call this when the user wants to see what services are offered or is deciding what to book.',
                'input_schema' => ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
            ];
            $tools[] = [
                'name'         => 'get_user_addresses',
                'description'  => 'Get the saved addresses for the current user. Call this when you need to pick an address for a new service booking.',
                'input_schema' => ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
            ];
            $tools[] = [
                'name'        => 'create_service_booking', 
                'description' => 'Submit a service enquiry for the user. This sends an enquiry to nearby vendors — it is NOT an instant confirmed booking. Only call this after confirming the service with the user. address_id is optional — if not provided the system uses the user\'s most recently saved address automatically.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'item_id'      => ['type' => 'integer', 'description' => 'The ID of the service to book (from list_services)'],
                        'address_id'   => ['type' => 'integer', 'description' => 'The ID of the user\'s address (from get_user_addresses). Omit to use the most recently saved address.'],
                        'requirements' => ['type' => 'string',  'description' => 'Optional notes or special requirements for the service'],
                    ],
                    'required' => ['item_id'],
                ],
            ];
        }

        // ── If agent has api_tools not covered by hardcoded definitions, add them ──
        if ($agentId) {
            $apiTools = DB::select(
                'SELECT * FROM agent_api_tools WHERE agent_id = ? AND status = 1 ORDER BY sort_order ASC',
                [$agentId]
            );

            $hardcodedNames = array_column($tools, 'name');
            foreach ($apiTools as $t) {
                if (!in_array($t->api_name, $hardcodedNames, true)) {
                    $tools[] = [
                        'name'         => $t->api_name,
                        'description'  => $t->description ?: ucfirst(str_replace('_', ' ', $t->api_name)),
                        'input_schema' => ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
                    ];
                }
            }
        }

        return $tools;
    }

    private function fetchServices(): string
    {
        $data     = $this->callMainApp('services');
        if (isset($data['error'])) return 'Could not fetch services: ' . $data['error'];
        $services = $data['services'] ?? [];
        if (empty($services)) return 'No services are currently available.';

        $lines = array_map(fn($s) => "- [ID:{$s['id']}] {$s['name']}", $services);
        return "Available Services (" . count($services) . "):\n" . implode("\n", $lines);
    }

    private function fetchUserAddresses(?int $userId): string
    {
        if (!$userId) return 'User context not available.';
        $data      = $this->callMainApp("user/{$userId}/addresses");
        if (isset($data['error'])) return 'Could not fetch addresses: ' . $data['error'];
        $addresses = $data['addresses'] ?? [];
        if (empty($addresses)) return 'No saved addresses found. Ask the user to add an address in the app first.';

        $lines = array_map(fn($a) => "- [ID:{$a['id']}] {$a['label']}: {$a['address']}", $addresses);
        return "Saved Addresses (" . count($addresses) . "):\n" . implode("\n", $lines);
    }

    private function submitServiceBooking(?int $userId, array $input): string
    {
        if (!$userId) return 'User context not available.';

        $data = $this->postMainApp("user/{$userId}/service-booking", [
            'item_id'      => $input['item_id'],
            'address_id'   => $input['address_id'] ?? null,
            'requirements' => $input['requirements'] ?? null,
        ]);

        if (isset($data['error'])) return 'Booking failed: ' . $data['error'];
        if (!($data['success'] ?? false)) return 'Booking failed: ' . ($data['message'] ?? 'Unknown error.');

        return "Service enquiry sent successfully! Enquiry ID: #{$data['booking_id']}. Vendors in your area have been notified and will contact you shortly.";
    }

    // ── Main app API caller ───────────────────────────────────────────────

    private function postMainApp(string $endpoint, array $body = []): array
    {
        $url = rtrim(config('services.main_app.url', ''), '/');
        $key = config('services.main_app.key', '');

        if (empty($url)) {
            Log::warning('MAIN_APP_URL not configured — cannot call internal API');
            return ['error' => 'Main app URL not configured.'];
        }

        try {
            $response = Http::withHeaders([
                'X-Api-Key'    => $key,
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(15)->post("{$url}/api/v1/ai-internal/{$endpoint}", $body);

            if ($response->failed()) {
                $body = $response->json();
                $detail = $body['debug_error'] ?? $body['message'] ?? $response->body();
                Log::error('Main app internal API POST error', [
                    'endpoint' => $endpoint,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);
                return ['error' => 'HTTP ' . $response->status() . ': ' . $detail];
            }

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('Main app internal API POST exception', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    private function callMainApp(string $endpoint, array $query = []): array
    {
        $url = rtrim(config('services.main_app.url', ''), '/');
        $key = config('services.main_app.key', '');

        if (empty($url)) {
            Log::warning('MAIN_APP_URL not configured — cannot call internal API');
            return ['error' => 'Main app URL not configured.'];
        }

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $key,
                'Accept'    => 'application/json',
            ])->timeout(15)->get("{$url}/api/v1/ai-internal/{$endpoint}", $query);

            if ($response->failed()) {
                Log::error('Main app internal API error', [
                    'endpoint' => $endpoint,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);
                return ['error' => 'API call failed: HTTP ' . $response->status()];
            }

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('Main app internal API exception', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    private function fetchServiceBookings(?int $userId): string
    {
        if (!$userId) {
            return 'User context not available.';
        }

        $data = $this->callMainApp("user/{$userId}/service-bookings");

        if (isset($data['error'])) {
            return 'Could not fetch service bookings: ' . $data['error'];
        }

        $bookings = $data['bookings'] ?? [];

        if (empty($bookings)) {
            return 'No service bookings found for this user.';
        }

        $lines = array_map(function ($b) {
            $line = "- [#{$b['id']}] {$b['service_name']} | Status: {$b['status']} | Date: {$b['date']}";
            if (!empty($b['city']))         $line .= " | City: {$b['city']}";
            if (!empty($b['requirements'])) $line .= " | Notes: {$b['requirements']}";

            $store = $b['store'] ?? null;
            $staff = $b['staff'] ?? null;

            if (!empty($store)) {
                $line .= " | Store: {$store}";
            } elseif ($b['assigned']) {
                $line .= " | Vendor: Assigned";
            }

            if (!empty($staff)) {
                $line .= " | Staff: {$staff}";
            }

            return $line;
        }, $bookings);

        return "Service Booking History (" . count($bookings) . " records):\n" . implode("\n", $lines);
    }


    // ── Summarization & Fact Extraction (use platform default) ───────────

    public function summarize(array $messages): string
    {
        $transcript = collect($messages)
            ->map(fn($m) => strtoupper($m['role']) . ': ' . (is_array($m['content']) ? json_encode($m['content']) : $m['content']))
            ->implode("\n\n");

        return $this->chat(
            [['role' => 'user', 'content' => "Please summarize the following conversation concisely, preserving all important context:\n\n{$transcript}"]],
            'You are a conversation summarizer. Be concise but complete.',
            1024
        );
    }

    public function extractFacts(array $messages): array
    {
        $transcript = collect($messages)
            ->map(fn($m) => strtoupper($m['role']) . ': ' . (is_array($m['content']) ? json_encode($m['content']) : $m['content']))
            ->implode("\n\n");

        $prompt = <<<EOT
From this conversation, extract key facts about the USER only (not the assistant).
Focus on: name, job, skills, preferences, location, goals, language, and any personal details they shared.
Return ONLY a valid JSON array like:
[{"key": "name", "value": "John"}, {"key": "job_title", "value": "PHP Developer"}]
If nothing useful, return an empty array [].

CONVERSATION:
{$transcript}
EOT;

        $raw   = $this->chat([['role' => 'user', 'content' => $prompt]], 'You are a fact extractor. Return only valid JSON.', 512);
        $raw   = preg_replace('/```json|```/', '', $raw);
        $facts = json_decode(trim($raw), true);

        return is_array($facts) ? $facts : [];
    }
}
