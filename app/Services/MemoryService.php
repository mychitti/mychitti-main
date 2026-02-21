<?php

namespace App\Services;

use App\Models\AIChatMessage;
use App\Models\AdminMemory;
use App\Models\Summary;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\AI\AgentFactory;

class MemoryService
{
    const SUMMARIZE_THRESHOLD = 20;
    const RECENT_MESSAGES_LIMIT = 20;

    public function __construct(private ClaudeService $claude) {}

    /**
     * Derive the column name from guard: 'admin' → 'admin_id', 'vendor' → 'vendor_id'
     */
    private function col(string $guard): string
    {
        return $guard . '_id';
    }
    // private function getAgentPrompt(string $agentType): string
    // {
    //     $prompt = DB::table('system_prompts')
    //         ->where('user_type', $agentType)
    //         ->value('prompt');

    //     return $prompt ?? '';
    // }
    private function resolveAgentType(string $guard): string
    {
        return match ($guard) {
            'vendor' => 'vendor',
            'admin'  => 'admin',
            default  => 'user',
        };
    }

    public function buildContext(int $ownerId, string $guard = 'admin'): array
    {
        $col = $this->col($guard);
        $systemParts = [];

        // 1. Facts/profile
        $facts = AdminMemory::where($col, $ownerId)->get();
        if ($facts->isNotEmpty()) {
            $factLines = $facts->map(fn($f) => "- {$f->key}: {$f->value}")->implode("\n");
            $systemParts[] = "Known facts about this user:\n{$factLines}";
        }

        // 2. Old summary
        $summary = Summary::where($col, $ownerId)->latest()->first();
        if ($summary) {
            $systemParts[] = "Summary of previous conversations:\n{$summary->summary}";
        }

        // 3. Define agent type FIRST
        $agentType = $this->resolveAgentType($guard);

        // 4. Load agent
        $agent = AgentFactory::make($agentType);

        // 5. Get prompt from DB (system_prompts table, user_type = guard)
        // $systemParts[] = $agent->systemPrompt();
        $systemParts[] =
        
        <<<'PROMPT'

========================================You are a helpful, friendly AI assistant embedded in a shopping and services platform.

## What you can do

**Text chat**
Answer questions about products, orders, stores, and services. Be concise and accurate. If you don''t know something, say so honestly.

**Voice messages**
The user''s voice has been transcribed to text automatically. Treat it exactly like a typed message. If the transcription seems unclear, politely ask for clarification.

**Images**
When the user shares an image (product photo, screenshot), describe what you see and answer any related question. For product images, help identify the item or answer detail questions. For screenshots (order confirmation, error screen), help the user understand or resolve the issue shown.

**PDF documents**
When a PDF is uploaded (invoice, receipt, catalogue, menu), read its contents and answer questions about it. Summarise if asked. Extract key details like prices, dates, order numbers, or item lists on request.

## Tone and style
- Warm, clear, and concise.
- Use plain language — avoid jargon.
- If a question is outside your scope, redirect the user to contact support.

## Boundaries
- Do not make up product prices, stock levels, or order statuses.
- Do not collect sensitive information like passwords or payment details.
PROMPT;

        $system = implode("\n\n", $systemParts); 



        // 4. Recent messages
        $recentMessages = AIChatMessage::where($col, $ownerId)
            ->where('summarized', false)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($msg) => [
                'role'    => $msg->role,
                'content' => $msg->content,
            ])
            ->toArray();

        return [$system, $recentMessages];
    }

    public function saveMessage(int $ownerId, string $role, string $content, string $type = 'text', string $guard = 'admin'): AIChatMessage
    {
        return AIChatMessage::create([
            $this->col($guard) => $ownerId,
            'role'             => $role,
            'content'          => $content,
            'type'             => $type,
            'summarized'       => false,
        ]);
    }

    public function maybeSummarize(int $ownerId, string $guard = 'admin'): void
    {
        $col = $this->col($guard);

        $unsummarizedCount = AIChatMessage::where($col, $ownerId)
            ->where('summarized', false)
            ->count();

        if ($unsummarizedCount >= self::SUMMARIZE_THRESHOLD) {
            $this->runSummarization($ownerId, $guard);
        }
    }

    private function runSummarization(int $ownerId, string $guard): void
    {
        $col = $this->col($guard);

        try {
            $allUnsummarized = AIChatMessage::where($col, $ownerId)
                ->where('summarized', false)
                ->orderBy('created_at', 'asc')
                ->get();

            $toSummarize = $allUnsummarized->slice(0, -self::RECENT_MESSAGES_LIMIT);

            if ($toSummarize->isEmpty()) {
                return;
            }

            $messagesArray = $toSummarize->map(fn($m) => [
                'role'    => $m->role,
                'content' => $m->content,
            ])->toArray();

            $summaryText = $this->claude->summarize($messagesArray);

            $existing = Summary::where($col, $ownerId)->latest()->first();
            if ($existing) {
                $mergedPrompt = "Previous summary:\n{$existing->summary}\n\nNew summary to merge:\n{$summaryText}";
                $summaryText = $this->claude->chat(
                    [['role' => 'user', 'content' => "Merge these two summaries into one cohesive summary:\n\n{$mergedPrompt}"]],
                    'You are a summarizer. Be concise.',
                    1024
                );
                $existing->delete();
            }

            Summary::create([
                $col               => $ownerId,
                'summary'          => $summaryText,
                'messages_covered' => $toSummarize->count(),
            ]);

            $facts = $this->claude->extractFacts($messagesArray);
            foreach ($facts as $fact) {
                if (!empty($fact['key']) && !empty($fact['value'])) {
                    AdminMemory::updateOrCreate(
                        [$col => $ownerId, 'key' => $fact['key']],
                        ['value' => $fact['value']]
                    );
                }
            }

            $ids = $toSummarize->pluck('id')->toArray();
            AIChatMessage::whereIn('id', $ids)->update(['summarized' => true]);

            Log::info("Summarized {$toSummarize->count()} messages for {$guard} {$ownerId}");
        } catch (\Exception $e) {
            Log::error('Summarization failed', [$col => $ownerId, 'error' => $e->getMessage()]);
        }
    }

    public function getFacts(int $ownerId, string $guard = 'admin'): array
    {
        return AdminMemory::where($this->col($guard), $ownerId)
            ->pluck('value', 'key')
            ->toArray();
    }

    public function clearAll(int $ownerId, string $guard = 'admin'): void
    {
        $col = $this->col($guard);
        AIChatMessage::where($col, $ownerId)->delete();
        Summary::where($col, $ownerId)->delete();
        AdminMemory::where($col, $ownerId)->delete();
    }
}
