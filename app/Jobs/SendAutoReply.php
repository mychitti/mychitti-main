<?php

namespace App\Jobs;

use App\CentralLogics\Helpers;
use App\Models\StoreKnowledgeDoc;
use App\Services\NotificationPrefs;
use App\Services\WhatsAppAgent;
use App\Services\WhatsAppBilling;
use App\Services\WhatsAppInternalAgent;
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
 * AI auto-reply to an inbound WhatsApp message, answered ONLY from saved Auto-Reply
 * Knowledge documents and sent from the relevant number.
 *
 * Two modes, chosen by $storeId:
 *   - VENDOR   (storeId = the store id): the vendor's own connected number, the store's
 *              knowledge, replies to that store's customers.
 *   - PLATFORM (storeId = null): MyChitti's own WABA, the platform knowledge base
 *              (store_id 0), replies to vendors and customers who message MyChitti.
 *
 * Dispatched by the webhook per inbound text message. No active knowledge = no auto-reply.
 */
class SendAutoReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // No auto-retry: a duplicate auto-reply reads as spam.
    public int $tries = 1;
    public int $timeout = 150;

    /**
     * How many auto-replies one customer gets from one store per window. Past this the bot
     * stops answering and the conversation is handed to the vendor (see handle()) — a question
     * that has taken this many turns is not one the knowledge base is going to close. Doubles
     * as the loop guard when the other side is also a bot.
     */
    const MAX_PER_WINDOW = 10;
    const WINDOW_HOURS   = 6;

    /** How much of the thread the model sees for context. */
    const HISTORY_MESSAGES = 12;

    /** Marker the model appends when the knowledge does not cover the question. */
    const ESCALATE_MARKER = '[[NEEDS_VENDOR]]';

    /** Knowledge / prefs sentinel for the platform (store_id 0). */
    const PLATFORM_SCOPE = 0;

    /**
     * $numberId is the store's own number the message arrived on. Replying from the default
     * instead would move the conversation to a different number mid-thread, which reads as a
     * stranger answering. Null for the platform number and for stores on a single number.
     */
    public function __construct(
        public ?int $storeId,
        public string $from,
        public string $body,
        public ?int $numberId = null
    ) {
    }

    protected function isPlatform(): bool
    {
        return $this->storeId === null;
    }

    /** store_id used for knowledge, prefs and RAG (0 for platform). */
    protected function scopeId(): int
    {
        return $this->storeId ?? self::PLATFORM_SCOPE;
    }

    /** Apply the whatsapp_messages store scope: a real store id, or NULL for platform. */
    protected function applyMsgScope($query)
    {
        return $this->isPlatform()
            ? $query->whereNull('store_id')
            : $query->where('store_id', $this->storeId);
    }

    public function handle(): void
    {
        try {
            // Feature gate — platform uses a business-setting toggle; vendors use their own.
            if ($this->isPlatform()) {
                if (!static::platformAutoReplyEnabled()) {
                    return;
                }
            } elseif (!NotificationPrefs::enabled($this->storeId, 'whatsapp_send', 'auto_reply')) {
                return;
            }

            $wa = WhatsAppService::make($this->storeId, null, $this->numberId);
            if ($this->isPlatform()) {
                if (!$wa->isConfigured()) {
                    return; // platform WABA not set up
                }
            } elseif ($wa->source() !== 'vendor') {
                return; // vendor hasn't connected their own number
            }

            $key = substr(preg_replace('/[^0-9]/', '', $this->from) ?? '', -10);
            if (strlen($key) < 10) {
                return;
            }

            // The business messaging its own number: the owner or a staff member, recognised by
            // the phone the store has on record. They get their own prompt further down, and the
            // three customer guards below do not apply to them — see each one.
            $who = $this->isPlatform() ? null : WhatsAppInternalAgent::identify($this->storeId, $key);

            // No knowledge = auto-reply not set up. Stay silent rather than improvising answers
            // about a business we know nothing about. The internal assistant answers from the
            // business's own records instead, so it needs no knowledge documents.
            $docs = StoreKnowledgeDoc::activeForStore($this->scopeId());
            if ($docs->isEmpty() && !$who) {
                return;
            }

            $sentRecently = $this->applyMsgScope(
                DB::table('whatsapp_messages')
                    ->where('context', 'auto reply')
                    ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(recipient, ' ', ''), '-', ''), '+', ''), 10) = ?", [$key])
                    ->where('sent_at', '>=', now()->subHours(self::WINDOW_HOURS))
            )->count();
            // Cap reached — hand the conversation to a human instead of going silent. escalate()
            // is deduped for 30 minutes per customer, so a burst of messages raises one alert.
            // Not applied to the business's own people: the cap exists to stop a bot arguing with
            // a customer, and escalating here would alert the vendor to their own messages.
            if (!$who && $sentRecently >= self::MAX_PER_WINDOW) {
                $this->escalate($key, 'Auto-reply limit reached (' . self::MAX_PER_WINDOW . ' replies in ' . self::WINDOW_HOURS . 'h)');
                return;
            }

            // Basic buys messaging, not a chatbot. Hand the conversation straight to the vendor
            // rather than answering on a plan that does not include answering.
            if (!$this->isPlatform() && !WhatsAppBilling::botIncluded($this->storeId)) {
                $this->escalate($key, 'Your plan has no chatbot — move to AI Agent Starter or Pro in WhatsApp → Plan & Billing to reply automatically');
                return;
            }

            // Both bots spend the one plan allowance.
            $pool = $this->isPlatform() ? null : WhatsAppAgent::pool($this->storeId);

            // Out of tokens = no auto-reply; the conversation goes to the vendor rather than
            // going silent. Input and output are separate buckets that never lend to each other,
            // so either one running dry stops the bot — say which, or the vendor tops up the
            // wrong one. Only metered once the store is on the paid WhatsApp platform.
            if (!$this->isPlatform() && WhatsAppBilling::aiMeteringApplies($this->storeId)) {
                $empty = WhatsAppBilling::exhaustedDirection($this->storeId, $pool);
                if ($empty) {
                    $label = $empty === WhatsAppBilling::DIR_OUT ? 'output' : 'input';
                    $this->escalate($key, 'AI ' . $label . ' tokens exhausted — top up ' . $label
                        . ' tokens in WhatsApp → Plan & Billing to resume automatic replies');
                    return;
                }
            }

            $storeName = $this->isPlatform()
                ? 'MyChitti'
                : (DB::table('stores')->where('id', $this->storeId)->value('name') ?: 'our store');

            $reply = $this->generateReply($storeName, $docs, $key, $who);

            // AI unavailable — never leave the sender on silence: send a holding reply
            // and alert the team to take over.
            if ($reply === '') {
                $wa->sendText(
                    $this->from,
                    'Thanks for your message! Our team will get back to you shortly.',
                    false,
                    'auto reply'
                );
                $this->escalate($key, 'Auto-reply could not respond');
                return;
            }

            // Appointment action marker (book / reschedule). Only honoured when the vendor has
            // left booking on — the prompt withholds the markers in that case anyway, so this is
            // the belt to that braces.
            // Never for the business's own people: an owner asking about tomorrow's diary is not
            // a patient asking for a slot, and booking one against their own phone would put a
            // spurious appointment in the calendar they were asking about.
            $action = (!$this->isPlatform() && !$who && WhatsAppAgent::canBook($this->storeId))
                ? \App\Services\WhatsAppAppointmentBot::tryHandle($reply, $this->scopeId(), $key, $this->from)
                : null;
            if ($action !== null) {
                if ($action['message'] !== '') {
                    $wa->sendText($this->from, $action['message'], false, 'auto reply');
                }
                if (!empty($action['escalate'])) {
                    $this->escalate($key, $action['reason'] ?: 'Appointment request needs attention');
                }
                return;
            }

            // Platform only: a message showing interest in MyChitti auto-creates a Sales Query
            // (Sales & Marketing CRM). Strips its marker from the customer-facing reply.
            if ($this->isPlatform()) {
                $cleaned = \App\Modules\SalesCRM\Services\WhatsAppSalesLead::extractAndCreate($reply, $key, $this->from, $this->body);
                if ($cleaned !== null) {
                    $reply = $cleaned;
                }
            }

            // The model flags questions it couldn't answer from the knowledge with a marker —
            // strip it from the customer-facing text and alert the team.
            $needsHuman = str_contains($reply, self::ESCALATE_MARKER);
            if ($needsHuman) {
                $reply = trim(str_replace(self::ESCALATE_MARKER, '', $reply));
            }

            if ($reply !== '') {
                $wa->sendText($this->from, $reply, false, 'auto reply');
            }

            // Not for internal senders: the alert goes to the vendor, and telling them a customer
            // needs help because they themselves asked something is noise. Their prompt is never
            // shown the marker anyway — this is the belt to that braces.
            if ($needsHuman && !$who) {
                $this->escalate($key, 'Auto-reply could not answer from the knowledge base');
            }
        } catch (\Throwable $e) {
            Log::warning('WA auto-reply skipped (scope ' . $this->scopeId() . '): ' . $e->getMessage());
        }
    }

    /** Platform auto-reply on/off — admin toggle in whatsapp_config (default on). */
    public static function platformAutoReplyEnabled(): bool
    {
        $cfg = Helpers::get_business_settings('whatsapp_config');
        return !is_array($cfg) || !array_key_exists('auto_reply', $cfg) || !empty($cfg['auto_reply']);
    }

    /**
     * Notify a human that a message is waiting: the vendor (vendor mode) or the admin
     * (platform mode). Throttled to one per contact per 30 minutes.
     */
    protected function escalate(string $key, string $reason): void
    {
        if (!$this->isPlatform() && !NotificationPrefs::enabled($this->storeId, 'push_receive', 'chat_escalation')) {
            return;
        }
        if (!\Illuminate\Support\Facades\Cache::add("wa_escalate:{$this->scopeId()}:{$key}", 1, 1800)) {
            return;
        }

        $phone = '+' . ltrim($this->from, '+');
        $lines = [];

        if ($this->isPlatform()) {
            // Identify the sender from platform data — a customer (users) or a vendor (stores).
            $user   = DB::table('users')
                ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [$key])
                ->first(['f_name', 'l_name', 'email']);
            $store  = DB::table('stores')
                ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [$key])
                ->first(['name']);
            if ($store) {
                $lines[] = 'Vendor store: ' . $store->name . ' (' . $phone . ')';
            } elseif ($user) {
                $lines[] = 'Customer: ' . trim(($user->f_name ?? '') . ' ' . ($user->l_name ?? '')) . ' (' . $phone . ')';
                if ($user->email) {
                    $lines[] = 'Email: ' . $user->email;
                }
            } else {
                $lines[] = 'From: ' . $phone . ' (unknown number)';
            }
            $lines[] = 'Asked: "' . mb_substr($this->body, 0, 200) . '"';
            $lines[] = $reason . ' — open WhatsApp Chats to reply.';

            _inAppNotification(
                'WhatsApp: message needs a reply',
                implode("\n", $lines),
                null,
                null,
                route('admin.business-settings.third-party.whatsapp-inbox'),
                'admin'
            );
            return;
        }

        // Vendor mode — full customer detail from the store's own book.
        $customer = DB::table('store_customers')
            ->where('store_id', $this->storeId)
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [$key])
            ->first(['f_name', 'phone', 'email', 'address', 'user_type']);

        if ($customer) {
            $lines[] = 'Customer: ' . ($customer->f_name ?: 'Unknown') . ' (' . $phone . ')'
                . ($customer->user_type && $customer->user_type !== 'customer' ? ' — ' . ucfirst($customer->user_type) : '');
            if ($customer->email) {
                $lines[] = 'Email: ' . $customer->email;
            }
            if ($customer->address) {
                $lines[] = 'Address: ' . mb_substr($customer->address, 0, 80);
            }
        } else {
            $lines[] = 'Customer: ' . $phone . ' (not in your customer list)';
        }
        $lines[] = 'Asked: "' . mb_substr($this->body, 0, 200) . '"';
        $lines[] = $reason . ' — open WhatsApp Chats to reply.';

        _inAppNotification(
            'WhatsApp: customer needs your reply',
            implode("\n", $lines),
            null,
            $this->storeId,
            route('vendor.whatsapp.inbox'),
            'vendor'
        );
    }

    protected function generateReply(string $storeName, $docs, string $key, ?array $who = null): string
    {
        // RAG first: semantic search over the indexed knowledge returns only the chunks
        // relevant to THIS question. Falls back to all active docs when RAG is unreachable.
        $knowledge = '';
        try {
            // Short timeout: a hung RAG server must degrade to the full-docs fallback fast,
            // not stall every reply (seen live: uvicorn hang cost 15s per message).
            $resp = Http::timeout(5)->post(rtrim((string) config('services.ai_server.url'), '/') . '/rag/search', [
                'query'    => $this->body,
                'category' => StoreKnowledgeDoc::ragCategory($this->scopeId()),
                'top_k'    => 4,
            ]);
            if ($resp->successful()) {
                $chunks = (array) data_get($resp->json(), 'results', []);
                $knowledge = collect($chunks)
                    ->map(fn($c) => '### ' . data_get($c, 'title') . "\n" . data_get($c, 'content'))
                    ->implode("\n\n");
            }
        } catch (\Throwable $e) {
            Log::warning('WA auto-reply RAG search failed, using full docs: ' . $e->getMessage());
        }

        if (trim($knowledge) === '') {
            $knowledge = $docs->map(function ($d) {
                return '### ' . StoreKnowledgeDoc::typeLabel($d->doc_type) . " — {$d->title}\n{$d->content}";
            })->implode("\n\n");
        }

        // The business's own owner or staff writing in. Everything below this point is built for
        // a stranger — "answer only from the knowledge", "never share information about other
        // vendors", this customer's records — which is exactly backwards for the people who own
        // the data. They get their own prompt instead of an exception carved into this one.
        if ($who) {
            return $this->generateInternalReply($storeName, $knowledge, $key, $who);
        }

        $intro = $this->isPlatform()
            ? "You are the official WhatsApp assistant for MyChitti, a multi-vendor business platform in India. "
                . "You are talking to a vendor or a customer who messaged MyChitti.\n\n"
            : "You are the WhatsApp assistant replying on behalf of \"{$storeName}\". "
                . "A customer has messaged the business and you answer for it.\n\n";

        $greetingName = $this->isPlatform() ? 'MyChitti' : $storeName;

        $system = $intro
            . "KNOWLEDGE (your ONLY source of truth):\n\n{$knowledge}\n\n"
            . "RULES:\n"
            . "- Answer ONLY from the knowledge above. Never invent prices, timings, availability or policies.\n"
            . "- If the answer is not in the knowledge, say you will pass the question to the team and they will reply shortly. Do not guess. "
            . "Then append the exact marker " . self::ESCALATE_MARKER . " at the very end of your reply (the sender never sees it; it alerts the team).\n"
            . "- If the message is just a greeting (hi, hello, hii, hey) or has no clear question, greet them warmly by name — "
            . "e.g. \"Hi! Welcome to {$greetingName}. How can I help you today?\" — and do NOT append the marker.\n"
            . "- Keep replies short and WhatsApp-friendly: 1–4 sentences, plain text. No markdown, no headings, no asterisks.\n"
            . "- ALWAYS reply in English unless the sender clearly wrote a full sentence in another language — then match that language. "
            . "Short words like \"hi\", \"hii\", \"ok\", \"k\" are English; never assume any other language from them.\n"
            . "- Be warm and professional. Do not say you are an AI unless asked directly.\n"
            . "- Never share information about other customers or vendors.";

        // AI Agent stores get lead & appointment tooling plus this customer's own records for
        // whatever the vendor allowed. The knowledge bot gets none of it and simply defers.
        $agentSection = $this->isPlatform() ? '' : WhatsAppAgent::promptSection($this->storeId, $key, $this->from);
        if ($agentSection !== '') {
            $system .= $agentSection;
        } else {
            $system .= "\n- For booking, order or status requests: share what the knowledge says and tell them the team will confirm shortly.";
        }

        // Platform assistant can capture sales leads into the Sales & Marketing CRM.
        if ($this->isPlatform()) {
            $system .= \App\Modules\SalesCRM\Services\WhatsAppSalesLead::promptSection();
        }

        return $this->callAi($system, $key);
    }

    /**
     * The reply to the business's own owner or staff.
     *
     * Built from the same pieces as a customer reply — one AI call, the same thread history, the
     * same metering to the vendor's wallet — but with the internal context and rules in place of
     * the customer ones. The knowledge base is still offered when there is one: an owner asking
     * "what are our timings" should get the answer the customers get.
     */
    protected function generateInternalReply(string $storeName, string $knowledge, string $key, array $who): string
    {
        $system = "You are the WhatsApp assistant for \"{$storeName}\". You are talking to someone who "
            . "works at this business, not to a customer.\n";

        if (trim($knowledge) !== '') {
            $system .= "\nWHAT CUSTOMERS ARE TOLD (the business's own knowledge base):\n\n{$knowledge}\n";
        }

        $system .= WhatsAppInternalAgent::promptSection($this->storeId, $who);

        return $this->callAi($system, $key);
    }

    /**
     * One AI call: thread history, the configured model, and the token metering that bills the
     * store's own wallet. Shared so the internal assistant can never be billed differently, or
     * quietly not billed at all.
     */
    protected function callAi(string $system, string $key): string
    {
        // Thread history, oldest first. The webhook stored the current inbound before
        // dispatching this job, so drop the trailing user turn — it goes as `message`.
        $history = $this->applyMsgScope(
            DB::table('whatsapp_messages')
                ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(recipient, ' ', ''), '-', ''), '+', ''), 10) = ?", [$key])
                ->where('type', '!=', 'template')
                ->whereNotNull('body')
        )
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
                'user_id'       => 900000000 + $this->scopeId(), // stable, collision-free memory id
                'guard'         => 'agent_test',
                'message'       => $this->body,
                'type'          => 'text',
                'system_prompt' => $system,
                'model_config'  => $modelConfig,
                'history'       => $history,
            ]);

        if (!$resp->successful() || empty($resp->json('success'))) {
            Log::warning('WA auto-reply AI call failed', ['scope' => $this->scopeId(), 'status' => $resp->status(), 'body' => $resp->json()]);
            return '';
        }

        $reply = trim(mb_substr((string) $resp->json('message'), 0, 1500));

        // Meter what this reply cost the vendor. Everything a vendor's own conversation consumes
        // is the vendor's to pay for, whatever added it — so the provider's own numbers are used
        // when the AI service reports them. They cover what this side never sees: the second RAG
        // block the service prepends, the memory context it builds, and every tool round-trip.
        //
        // The local estimate is the fallback for an AI service that predates `usage`, and it
        // undercounts: it can only weigh the prompt this job composed, and chars/4 is generous to
        // non-Latin scripts. Better a low figure than none — but the provider's count is the bill.
        if (!$this->isPlatform() && WhatsAppBilling::aiMeteringApplies($this->storeId)) {
            $usedIn  = (int) $resp->json('usage.input', 0);
            $usedOut = (int) $resp->json('usage.output', 0);

            if ($usedIn <= 0 && $usedOut <= 0) {
                $historyText = collect($history)->pluck('content')->implode(' ');
                $usedIn  = WhatsAppBilling::estimateTokens($system, $historyText, $this->body);
                $usedOut = WhatsAppBilling::estimateTokens($reply);
            }

            WhatsAppBilling::recordTokenUsage(
                $this->storeId,
                $usedIn,
                $usedOut,
                'auto reply',
                WhatsAppAgent::pool($this->storeId)
            );
        }

        return $reply;
    }
}
