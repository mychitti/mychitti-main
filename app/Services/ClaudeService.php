<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeService
{
    private string $apiKey;
    private string $model;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private $client;

    public function __construct() 
    {  
        $this->apiKey = config('services.anthropic.key');
        $this->model  = config('services.anthropic.model', 'claude-sonnet-4-5-20250929');

        // 👇 initialize the client
        $this->client = \Illuminate\Support\Facades\Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->baseUrl('https://api.anthropic.com/v1/');
    }
    /**
     * Send messages to Claude and get a response.
     *
     * @param  array  $messages   Full message array [['role'=>'user','content'=>...], ...]
     * @param  string $system     System prompt
     * @param  int    $maxTokens
     * @return string
     */
    public function chat(array $messages, string $system = '', int $maxTokens = 4096): string
    {
        $payload = [
            'model'      => $this->model,
            'max_tokens' => $maxTokens,
            'messages'   => $messages,
            'tools'      => $this->getTools(),
        ];

        if (!empty($system)) {
            $payload['system'] = $system;
        }

        $response = Http::withHeaders([
            'x-api-key'          => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'anthropic-beta'    => 'pdfs-2024-09-25',
            'content-type'      => 'application/json',
        ])->timeout(120)->post($this->apiUrl, $payload);

        if ($response->failed()) {
            Log::error('Claude API error', [
                'status' => $response->status(),
                'body'   => $response->body()
            ]);

            throw new \Exception('Claude API error: ' . $response->body());
        }

        // ✅ FIX: read response JSON
        $data = $response->json();

        if (
            isset($data['stop_reason']) &&
            $data['stop_reason'] === 'tool_use' &&
            method_exists($this, 'handleToolCall')
        ) {
            return $this->handleToolCall($data, $messages, $system, $maxTokens);
        }

        return data_get($data, 'content.0.text', '');
    }

    private function handleToolCall(array $data, array $messages, string $system, int $maxTokens): string
    {
        $toolResults = [];

        foreach ($data['content'] as $block) {
            if ($block['type'] !== 'tool_use') continue;

            $toolName  = $block['name'];
            $toolInput = $block['input'];
            $toolUseId = $block['id'];

            // Execute the tool
            $result = $this->executeTool($toolName, $toolInput);

            $toolResults[] = [
                'type'        => 'tool_result',
                'tool_use_id' => $toolUseId,
                'content'     => $result,
            ];
        }

        // Send result back to Claude so it can form a natural reply
        $messages[] = ['role' => 'assistant', 'content' => $data['content']];
        $messages[] = ['role' => 'user',      'content' => $toolResults];

        $followUp = $this->client->post('messages', [
            'model'      => 'claude-sonnet-4-5-20250929',
            'max_tokens' => $maxTokens,
            'system'     => $system,
            'messages'   => $messages,
            'tools'      => $this->getTools(),
        ])->json();

        return collect($followUp['content'])
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');
    }
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
                'subject'  => $input['subject'],
                'message'  => $input['message'],
                'name' => $input['name'],
                'email' => $input['email'],
                'seen'   => '0',
            ]);

            return 'Query stored successfully. A support agent will follow up soon.';
        } catch (\Exception $e) {
            return 'Failed to store query: ' . $e->getMessage();
        }
    }
    private function getTools(): array
    {
        return [
            [
                'name' => 'store_query',
                'description' => 'Store a customer query or complaint in the database for follow-up by the support team.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'subject' => [
                            'type' => 'string',
                            'description' => 'Short subject/title of the query'
                        ],
                        'message' => [
                            'type' => 'string',
                            'description' => 'Full details of the customer query'
                        ],
                        'name' => [
                            'type' => 'string',
                            'description' => 'Full Name'
                        ],
                        'email' => [
                            'type' => 'string',
                            'description' => 'Email'
                        ],
                    ],
                    'required' => ['subject', 'message', 'name', 'email']
                ]
            ],

            // You can add more tools here later
            // e.g. check_order_status, cancel_order, get_invoice etc.
        ];
    }

    /**
     * Ask Claude to summarize a block of conversation history.
     */
    public function summarize(array $messages): string
    {
        $transcript = collect($messages)
            ->map(fn($m) => strtoupper($m['role']) . ': ' . (is_array($m['content']) ? json_encode($m['content']) : $m['content']))
            ->implode("\n\n");

        $prompt = "Please summarize the following conversation concisely, preserving all important context, decisions, and topics discussed:\n\n{$transcript}";

        return $this->chat(
            [['role' => 'user', 'content' => $prompt]],
            'You are a conversation summarizer. Be concise but complete.',
            1024
        );
    }

    /**
     * Ask Claude to extract key facts about the user from a conversation.
     * Returns JSON array of {key, value} pairs.
     */
    public function extractFacts(array $messages): array
    {
        $transcript = collect($messages)
            ->map(fn($m) => strtoupper($m['role']) . ': ' . (is_array($m['content']) ? json_encode($m['content']) : $m['content']))
            ->implode("\n\n");

        $prompt = <<<EOT
From this conversation, extract key facts about the USER only (not the assistant).
Focus on: name, job, skills, preferences, location, goals, language, and any personal details they shared.
Return ONLY a valid JSON array like:
[
  {"key": "name", "value": "John"},
  {"key": "job_title", "value": "PHP Developer"},
  {"key": "preferred_language", "value": "PHP"}
]
If nothing useful, return an empty array [].

CONVERSATION:
{$transcript}
EOT;

        $raw = $this->chat(
            [['role' => 'user', 'content' => $prompt]],
            'You are a fact extractor. Return only valid JSON.',
            512
        );

        // Strip markdown code fences if present
        $raw = preg_replace('/```json|```/', '', $raw);

        $facts = json_decode(trim($raw), true);

        return is_array($facts) ? $facts : [];
    }
}
