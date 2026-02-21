<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private string $model; 

    public function __construct(private \OpenAI\Client $client) 
    {
        $this->model = config('services.openai.model', 'gpt-4o');
    }

    /**
     * Proxy to the underlying OpenAI client's audio resource (e.g. Whisper transcription).
     */
    public function audio(): \OpenAI\Resources\Audio
    {
        return $this->client->audio();
    }

    /**
     * Send messages to OpenAI and get a response.
     * Accepts Claude-style content blocks and converts them automatically.
     */
    public function chat(array $messages, string $system = '', int $maxTokens = 4096): string
    {
        $openaiMessages = [];
 
        if (!empty($system)) {
            $openaiMessages[] = ['role' => 'system', 'content' => $system];
        }

        foreach ($messages as $message) {
            $openaiMessages[] = $this->convertMessage($message);
        } 

        try {
            $response = $this->client->chat()->create([
                'model'      => $this->model,
                'messages'   => $openaiMessages,
                'max_tokens' => $maxTokens,
                'tools'      => $this->getTools(),
            ]);
        } catch (\Exception $e) {
            Log::error('OpenAI API error', ['error' => $e->getMessage()]);
            throw new \Exception('OpenAI API error: ' . $e->getMessage());
        }

        $choice = $response->choices[0];

        if ($choice->finishReason === 'tool_calls') {
            return $this->handleToolCall($choice->message->toolCalls, $openaiMessages, $maxTokens);
        }

        return $choice->message->content ?? '';
    }

    /**
     * Convert a single message from Claude format to OpenAI format.
     */
    private function convertMessage(array $message): array
    {
        if (is_string($message['content'])) {
            return $message;
        }

        $content = array_map([$this, 'convertContentBlock'], $message['content']);

        return ['role' => $message['role'], 'content' => $content];
    }

    /**
     * Convert a Claude content block to OpenAI content block.
     */
    private function convertContentBlock(array $block): array
    {
        // Text block – same in both APIs
        if ($block['type'] === 'text') {
            return $block;
        }

        // Claude image → OpenAI image_url
        if ($block['type'] === 'image' && isset($block['source']['type']) && $block['source']['type'] === 'base64') {
            $src = $block['source'];
            return [
                'type'      => 'image_url',
                'image_url' => ['url' => "data:{$src['media_type']};base64,{$src['data']}"],
            ];
        }

        // Claude PDF/document – OpenAI chat doesn't support PDFs natively
        if ($block['type'] === 'document') {
            return [
                'type' => 'text',
                'text' => '[A PDF was uploaded. GPT does not support PDF parsing directly — please paste the text content if you need it analyzed.]',
            ];
        }

        return $block;
    }

    /**
     * Handle a tool_calls finish_reason from OpenAI.
     */
    private function handleToolCall(array $toolCalls, array $messages, int $maxTokens): string
    {
        $messages[] = [
            'role'       => 'assistant',
            'tool_calls' => array_map(fn($tc) => [
                'id'       => $tc->id,
                'type'     => 'function',
                'function' => ['name' => $tc->function->name, 'arguments' => $tc->function->arguments],
            ], $toolCalls),
        ];

        foreach ($toolCalls as $tc) {
            $args = json_decode($tc->function->arguments, true) ?? [];
            $messages[] = [
                'role'         => 'tool',
                'tool_call_id' => $tc->id,
                'content'      => $this->executeTool($tc->function->name, $args),
            ];
        }

        $followUp = $this->client->chat()->create([
            'model'      => $this->model,
            'messages'   => $messages,
            'max_tokens' => $maxTokens,
        ]);

        return $followUp->choices[0]->message->content ?? '';
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

    private function getTools(): array
    {
        return [
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'store_query',
                    'description' => 'Store a customer query or complaint in the database for follow-up by the support team.',
                    'parameters'  => [
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
            ],
        ];
    }

    /**
     * Summarize a block of conversation history.
     */
    public function summarize(array $messages): string
    {
        $transcript = collect($messages)
            ->map(fn($m) => strtoupper($m['role']) . ': ' . (is_array($m['content']) ? json_encode($m['content']) : $m['content']))
            ->implode("\n\n");

        return $this->chat(
            [['role' => 'user', 'content' => "Summarize this conversation concisely, keeping all important context:\n\n{$transcript}"]],
            'You are a conversation summarizer. Be concise but complete.',
            1024
        );
    }

    /**
     * Extract key facts about the user from a conversation.
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
  {"key": "job_title", "value": "PHP Developer"}
]
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
