<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Multi-provider AI service.
 * Supported providers: anthropic | openai | google | custom
 */
class ClaudeService
{
    private string $claudeApiKey;
    private string $claudeModel;
    private string $claudeApiUrl = 'https://api.anthropic.com/v1/messages';
    private string $openaiApiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->claudeApiKey = config('services.anthropic.key');
        $this->claudeModel  = config('services.anthropic.model', 'claude-sonnet-4-5-20250929');
    }

    /**
     * Send messages and get a reply.
     *
     * @param  array       $messages   [['role'=>'user','content'=>'...'], ...]
     * @param  string      $system     System prompt
     * @param  int         $maxTokens
     * @param  string      $provider   anthropic | openai | google | custom
     * @param  string|null $model      Override default model for the provider
     * @param  string|null $customUrl  Required when provider = 'custom'
     * @param  string|null $customKey  API key for custom provider
     */
    public function chat(
        array $messages,
        string $system = '',
        int $maxTokens = 4096,
        string $provider = 'anthropic',
        ?string $model = null,
        ?string $customUrl = null,
        ?string $customKey = null
    ): string {
        return match (strtolower($provider)) {
            'openai'    => $this->chatOpenAI($messages, $system, $maxTokens, $model),
            'google'    => $this->chatGemini($messages, $system, $maxTokens, $model),
            'custom'    => $this->chatCustom($messages, $system, $maxTokens, $model, $customUrl, $customKey),
            default     => $this->chatClaude($messages, $system, $maxTokens, $model),
        };
    }

    // ── Anthropic / Claude ────────────────────────────────────────────────

    private function chatClaude(array $messages, string $system, int $maxTokens, ?string $model): string
    {
        $model  = $model ?? $this->claudeModel;
        $apiKey = $this->claudeApiKey;

        $payload = [
            'model'      => $model,
            'max_tokens' => $maxTokens,
            'messages'   => $messages,
            'tools'      => $this->getClaudeTools(),
        ];
        if (!empty($system)) {
            $payload['system'] = $system;
        }

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'anthropic-beta'    => 'pdfs-2024-09-25',
            'content-type'      => 'application/json',
        ])->timeout(120)->post($this->claudeApiUrl, $payload);

        if ($response->failed()) {
            Log::error('Claude API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Claude API error: ' . $response->body());
        }

        $data = $response->json();

        if (isset($data['stop_reason']) && $data['stop_reason'] === 'tool_use') {
            return $this->handleClaudeToolCall($data, $messages, $system, $maxTokens, $apiKey, $model);
        }

        return data_get($data, 'content.0.text', '');
    }

    private function handleClaudeToolCall(array $data, array $messages, string $system, int $maxTokens, string $apiKey, string $model): string
    {
        $toolResults = [];

        foreach ($data['content'] as $block) {
            if ($block['type'] !== 'tool_use') continue;
            $result = $this->executeTool($block['name'], $block['input']);
            $toolResults[] = [
                'type'        => 'tool_result',
                'tool_use_id' => $block['id'],
                'content'     => $result,
            ];
        }

        $assistantContent = array_map(function ($block) {
            if ($block['type'] === 'tool_use' && is_array($block['input'])) {
                $block['input'] = (object) $block['input'];
            }
            return $block;
        }, $data['content']);

        $messages[] = ['role' => 'assistant', 'content' => $assistantContent];
        $messages[] = ['role' => 'user',      'content' => $toolResults];

        $followPayload = [
            'model'      => $model,
            'max_tokens' => $maxTokens,
            'messages'   => $messages,
            'tools'      => $this->getClaudeTools(),
        ];
        if (!empty($system)) {
            $followPayload['system'] = $system;
        }

        $followResponse = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'anthropic-beta'    => 'pdfs-2024-09-25',
            'content-type'      => 'application/json',
        ])->timeout(120)->post($this->claudeApiUrl, $followPayload);

        if ($followResponse->failed()) {
            throw new \Exception('Claude tool follow-up error: ' . $followResponse->body());
        }

        return collect($followResponse->json()['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');
    }

    // ── OpenAI ────────────────────────────────────────────────────────────

    private function chatOpenAI(array $messages, string $system, int $maxTokens, ?string $model): string
    {
        $model  = $model ?? 'gpt-4o';
        $apiKey = config('services.openai.key');

        return $this->sendOpenAICompatible($this->openaiApiUrl, $apiKey, $model, $messages, $system, $maxTokens, 'OpenAI');
    }

    // ── Google Gemini ─────────────────────────────────────────────────────

    private function chatGemini(array $messages, string $system, int $maxTokens, ?string $model): string
    {
        $model  = $model ?? 'gemini-1.5-pro';
        $apiKey = config('services.gemini.key');

        $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

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

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(120)
            ->post($geminiUrl, [
                'contents'         => $contents,
                'generationConfig' => ['maxOutputTokens' => $maxTokens],
            ]);

        if ($response->failed()) {
            Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Gemini API error: ' . $response->body());
        }

        return data_get($response->json(), 'candidates.0.content.parts.0.text', '');
    }

    // ── Custom / Self-hosted (OpenAI-compatible) ──────────────────────────

    /**
     * Calls any OpenAI-compatible endpoint (Ollama, LiteLLM, DeepSeek, Groq, etc.)
     * Falls back to config values if customUrl / customKey are not provided.
     */
    private function chatCustom(array $messages, string $system, int $maxTokens, ?string $model, ?string $customUrl, ?string $customKey): string
    {
        $url    = $customUrl ?? config('services.custom_ai.url');
        $apiKey = $customKey ?? config('services.custom_ai.key');
        $model  = $model ?? config('services.custom_ai.model', 'custom-model');

        if (empty($url)) {
            throw new \Exception('Custom AI provider URL is not configured.');
        }

        return $this->sendOpenAICompatible($url, $apiKey, $model, $messages, $system, $maxTokens, 'Custom AI');
    }

    /**
     * Shared OpenAI-compatible request (used by OpenAI + Custom providers).
     */
    private function sendOpenAICompatible(string $url, ?string $apiKey, string $model, array $messages, string $system, int $maxTokens, string $label): string
    {
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

        $headers = ['Content-Type' => 'application/json'];
        if (!empty($apiKey)) {
            $headers['Authorization'] = 'Bearer ' . $apiKey;
        }

        $response = Http::withHeaders($headers)
            ->timeout(120)
            ->post($url, [
                'model'      => $model,
                'max_tokens' => $maxTokens,
                'messages'   => $oaiMessages,
            ]);

        if ($response->failed()) {
            Log::error("{$label} API error", ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception("{$label} API error: " . $response->body());
        }

        return data_get($response->json(), 'choices.0.message.content', '');
    }

    // ── Tools (Claude only) ───────────────────────────────────────────────

    private function executeTool(string $toolName, array $input): string
    {
        return match ($toolName) {
            'store_query' => $this->storeQuery($input),
            default       => 'Tool not found.',
        };
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

    private function getClaudeTools(): array
    {
        return [
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
    }

    // ── Utilities ─────────────────────────────────────────────────────────

    public function summarize(array $messages): string
    {
        $transcript = collect($messages)
            ->map(fn($m) => strtoupper($m['role']) . ': ' . (is_array($m['content']) ? json_encode($m['content']) : $m['content']))
            ->implode("\n\n");

        return $this->chat(
            [['role' => 'user', 'content' => "Please summarize the following conversation concisely:\n\n{$transcript}"]],
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
