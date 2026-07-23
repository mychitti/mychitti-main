<?php

namespace App\Jobs;

use App\Models\StoreKnowledgeDoc;
use App\Services\NotificationPrefs;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI auto-reply to an inbound customer WhatsApp message, answered ONLY from the store's
 * saved Auto-Reply Knowledge documents and sent from the vendor's own connected number.
 *
 * Dispatched by the webhook per inbound text message. A store with no active knowledge
 * documents never auto-replies — adding knowledge is how a vendor turns the feature on
 * (plus the toggle on the Send Notifications page). Replies always land inside the 24h
 * customer-service window because the customer just messaged.
 */
class SendAutoReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // No auto-retry: a duplicate auto-reply reads as spam.
    public int $tries = 1;
    public int $timeout = 150;

    /** Loop guard: if the other side is also a bot, stop after this many replies per window. */
    const MAX_PER_WINDOW = 10;
    const WINDOW_HOURS   = 6;

    /** How much of the thread the model sees for context. */
    const HISTORY_MESSAGES = 12;

    public function __construct(
        public int $storeId,
        public string $from,
        public string $body
    ) {
    }

    public function handle(): void
    {
        try {
            if (!NotificationPrefs::enabled($this->storeId, 'whatsapp_send', 'auto_reply')) {
                return;
            }

            $wa = WhatsAppService::make($this->storeId);
            if ($wa->source() !== 'vendor') {
                return;
            }

            // No knowledge = the vendor hasn't set auto-reply up. Stay silent rather than
            // improvising answers about a business we know nothing about.
            $docs = StoreKnowledgeDoc::activeForStore($this->storeId);
            if ($docs->isEmpty()) {
                return;
            }

            $key = substr(preg_replace('/[^0-9]/', '', $this->from) ?? '', -10);
            if (strlen($key) < 10) {
                return;
            }

            $sentRecently = DB::table('whatsapp_messages')
                ->where('store_id', $this->storeId)
                ->where('context', 'auto reply')
                ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(recipient, ' ', ''), '-', ''), '+', ''), 10) = ?", [$key])
                ->where('sent_at', '>=', now()->subHours(self::WINDOW_HOURS))
                ->count();
            if ($sentRecently >= self::MAX_PER_WINDOW) {
                return;
            }

            $storeName = DB::table('stores')->where('id', $this->storeId)->value('name') ?: 'our store';
            $reply = $this->generateReply($storeName, $docs, $key);
            if ($reply === '') {
                return;
            }

            $wa->sendText($this->from, $reply, false, 'auto reply');
        } catch (\Throwable $e) {
            Log::warning('WA auto-reply skipped (store ' . $this->storeId . '): ' . $e->getMessage());
        }
    }

    protected function generateReply(string $storeName, $docs, string $key): string
    {
        $knowledge = $docs->map(function ($d) {
            return '### ' . StoreKnowledgeDoc::typeLabel($d->doc_type) . " — {$d->title}\n{$d->content}";
        })->implode("\n\n");

        $system = "You are the WhatsApp assistant replying on behalf of \"{$storeName}\". "
            . "A customer has messaged the business and you answer for it.\n\n"
            . "BUSINESS KNOWLEDGE (your ONLY source of truth):\n\n{$knowledge}\n\n"
            . "RULES:\n"
            . "- Answer ONLY from the business knowledge above. Never invent prices, timings, availability or policies.\n"
            . "- If the answer is not in the knowledge, say you will pass the question to the team and they will reply shortly. Do not guess.\n"
            . "- Keep replies short and WhatsApp-friendly: 1–4 sentences, plain text. No markdown, no headings, no asterisks.\n"
            . "- Reply in the same language the customer wrote in.\n"
            . "- Be warm and professional. Do not say you are an AI unless the customer asks directly.\n"
            . "- For booking, appointment or order requests: share what the knowledge says and tell them the team will confirm shortly.\n"
            . "- Never share information about other customers.";

        // Thread history, oldest first. The webhook stored the current inbound before
        // dispatching this job, so drop the trailing user turn — it goes as `message`.
        $history = DB::table('whatsapp_messages')
            ->where('store_id', $this->storeId)
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(recipient, ' ', ''), '-', ''), '+', ''), 10) = ?", [$key])
            ->where('type', '!=', 'template')
            ->whereNotNull('body')
            ->orderByDesc('sent_at')
            ->limit(self::HISTORY_MESSAGES)
            ->get(['direction', 'body'])
            ->reverse()
            ->values()
            ->map(fn($m) => [
                'role'    => $m->direction === 'in' ? 'user' : 'assistant',
                'content' => (string) $m->body,
            ])
            ->all();
        if ($history && end($history)['role'] === 'user') {
            array_pop($history);
        }

        // Model/provider follow whatever the admin configured for the active user agent,
        // so auto-reply never depends on a hardcoded key.
        $resolved = DB::table('system_prompts')
            ->where('user_type', 'user')->where('status', 'active')
            ->orderByDesc('updated_at')
            ->first(['ai_provider', 'ai_model', 'max_tokens', 'api_key_override']);
        $modelConfig = [
            'ai_provider'      => $resolved->ai_provider ?? 'anthropic',
            'ai_model'         => $resolved->ai_model ?? null,
            'max_tokens'       => 1024,
            'api_key_override' => $resolved->api_key_override ?? null,
        ];

        $resp = Http::withHeaders(['X-Api-Key' => config('services.ai_service.key', '')])
            ->timeout(100)
            ->post(rtrim(config('services.ai_service.url', ''), '/') . '/api/ai/chat', [
                'user_id'       => $this->storeId,
                'guard'         => 'agent_test',
                'message'       => $this->body,
                'type'          => 'text',
                'system_prompt' => $system,
                'model_config'  => $modelConfig,
                'history'       => $history,
            ]);

        if (!$resp->successful() || empty($resp->json('success'))) {
            Log::warning('WA auto-reply AI call failed', ['store' => $this->storeId, 'status' => $resp->status(), 'body' => $resp->json()]);
            return '';
        }

        return trim(mb_substr((string) $resp->json('message'), 0, 1500));
    }
}
