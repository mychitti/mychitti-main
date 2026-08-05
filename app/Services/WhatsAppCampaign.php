<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Drip campaigns: one audience, a series of approved templates, and a reply that decides who
 * stays in the series.
 *
 * The shape the vendor asked for: step 1 goes to everyone with "Interested" / "Not interested"
 * quick-reply buttons. Anyone who taps "Not interested" is dropped for good; everyone else —
 * the interested AND the silent — receives step 2 after its delay, and so on down the series.
 *
 * Every send is one row in wa_campaign_sends, so per-step delivery and per-step replies are both
 * countable after the fact. wa_campaign_recipients carries the live verdict per person, which is
 * what the next step filters on.
 */
class WhatsAppCampaign
{
    /** Recipients messaged per campaign per runner pass — keeps one campaign from starving the rest. */
    const RUN_BATCH = 100;

    /** Who a step goes out to, decided by how the recipient answered the previous ones. */
    const TARGETS = [
        'interested_no_reply' => 'Interested + no reply (skip “not interested”)',
        'interested'          => 'Only those who replied Interested',
        // Those people are dropped from the series when they answer, so a step aimed at them
        // reaches past that — see eligibleQuery(). Use it sparingly: they said no once.
        'not_interested'      => 'Only those who replied Not interested',
        'no_reply'            => 'Only those who never replied',
        'engaged'             => 'Anyone who replied at all',
        'all'                 => 'Everyone still in the series',
    ];

    const STATUS_DRAFT     = 'draft';
    const STATUS_RUNNING   = 'running';
    const STATUS_PAUSED    = 'paused';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /** Button/text answers that keep someone in the series, and the ones that drop them. */
    const DEFAULT_POSITIVE = ['interested', 'yes', 'tell me more', 'more info', 'send details', 'i am interested'];
    const DEFAULT_NEGATIVE = ['not interested', 'no', 'no thanks', 'not now', 'stop', 'unsubscribe', 'remove me'];

    /** Label under which a classification's tokens are metered. */
    const AI_USAGE_CONTEXT = 'campaign reply';

    public static function ensureTables(): void
    {
        if (!Schema::hasTable('wa_campaigns')) {
            DB::statement("CREATE TABLE `wa_campaigns` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `name` VARCHAR(190) NOT NULL,
                `audience` VARCHAR(20) NOT NULL DEFAULT 'clients',
                `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
                `positive_labels` TEXT NULL,
                `negative_labels` TEXT NULL,
                `recipients_count` INT NOT NULL DEFAULT 0,
                `last_error` VARCHAR(255) NULL,
                `started_at` TIMESTAMP NULL,
                `completed_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `wac_store` (`store_id`, `status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!Schema::hasTable('wa_campaign_steps')) {
            DB::statement("CREATE TABLE `wa_campaign_steps` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `campaign_id` BIGINT UNSIGNED NOT NULL,
                `store_id` BIGINT NOT NULL,
                `step_no` INT NOT NULL DEFAULT 1,
                `template_name` VARCHAR(190) NOT NULL,
                `language` VARCHAR(20) NOT NULL DEFAULT 'en_US',
                `params` TEXT NULL,
                `target` VARCHAR(30) NOT NULL DEFAULT 'interested_no_reply',
                `delay_hours` INT NOT NULL DEFAULT 24,
                `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `due_at` TIMESTAMP NULL,
                `started_at` TIMESTAMP NULL,
                `completed_at` TIMESTAMP NULL,
                `sent_count` INT NOT NULL DEFAULT 0,
                `failed_count` INT NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wacs_step` (`campaign_id`, `step_no`),
                KEY `wacs_due` (`status`, `due_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!Schema::hasTable('wa_campaign_recipients')) {
            // phone10 is the last 10 digits — the same dedupe key the outreach and opt-out
            // queries use, so +91/0 prefixes never split one person into two recipients.
            DB::statement("CREATE TABLE `wa_campaign_recipients` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `campaign_id` BIGINT UNSIGNED NOT NULL,
                `store_id` BIGINT NOT NULL,
                `client_id` BIGINT NULL,
                `name` VARCHAR(190) NULL,
                `phone` VARCHAR(32) NOT NULL,
                `phone10` VARCHAR(10) NOT NULL,
                `state` VARCHAR(20) NOT NULL DEFAULT 'active',
                `reply` VARCHAR(20) NULL,
                `reply_label` VARCHAR(190) NULL,
                `reply_at` TIMESTAMP NULL,
                `replies` INT NOT NULL DEFAULT 0,
                `steps_sent` INT NOT NULL DEFAULT 0,
                `last_sent_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wacr_phone` (`campaign_id`, `phone10`),
                KEY `wacr_state` (`campaign_id`, `state`, `reply`),
                KEY `wacr_lookup` (`store_id`, `phone10`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!Schema::hasTable('wa_campaign_sends')) {
            DB::statement("CREATE TABLE `wa_campaign_sends` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `campaign_id` BIGINT UNSIGNED NOT NULL,
                `step_id` BIGINT UNSIGNED NOT NULL,
                `recipient_id` BIGINT UNSIGNED NOT NULL,
                `store_id` BIGINT NOT NULL,
                `wamid` VARCHAR(255) NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'sent',
                `error` TEXT NULL,
                `reply` VARCHAR(20) NULL,
                `reply_label` VARCHAR(190) NULL,
                `reply_at` TIMESTAMP NULL,
                `sent_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wacsd_once` (`step_id`, `recipient_id`),
                KEY `wacsd_wamid` (`wamid`),
                KEY `wacsd_step` (`step_id`, `status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // What read the reply — a button label, the word lists, or AI. Shown in the tracker so a
        // vendor can tell why someone was dropped, and audit the AI's calls specifically.
        foreach (['wa_campaign_sends', 'wa_campaign_recipients'] as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'verdict_by')) {
                DB::statement("ALTER TABLE `{$table}` ADD COLUMN `verdict_by` VARCHAR(10) NULL");
            }
        }
    }

    /** Last 10 digits of a phone number, the module's dedupe key. */
    public static function phone10(?string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone) ?? '';
        return substr($digits, -10);
    }

    public static function positiveLabels($campaign): array
    {
        $set = json_decode((string) ($campaign->positive_labels ?? ''), true);
        return is_array($set) && $set ? $set : self::DEFAULT_POSITIVE;
    }

    public static function negativeLabels($campaign): array
    {
        $set = json_decode((string) ($campaign->negative_labels ?? ''), true);
        return is_array($set) && $set ? $set : self::DEFAULT_NEGATIVE;
    }

    /**
     * Which bucket an answer falls in. A negative match wins over a positive one, because
     * "not interested" contains "interested" — checking the other way round would keep every
     * opt-out in the series.
     */
    public static function classify(?string $label, array $positive, array $negative): string
    {
        $text = trim(mb_strtolower((string) $label));
        if ($text === '') {
            return 'other';
        }

        // Short negative labels have to be the whole answer; short positive ones only have to
        // appear as a word. The two mistakes are not equal — reading "no problem, I want two" as a
        // refusal drops a buyer from the series for good, while reading "yes please" as interest
        // costs nothing, since the undecided receive the follow-ups anyway.
        foreach ($negative as $needle) {
            if (self::labelMatches($text, $needle, true)) {
                return 'not_interested';
            }
        }
        foreach ($positive as $needle) {
            if (self::labelMatches($text, $needle, false)) {
                return 'interested';
            }
        }

        return 'other';
    }

    /**
     * A tapped button arrives as its exact label, but a typed reply is a sentence, so longer
     * labels are matched anywhere in it.
     *
     * Short ones are not: "no" is a legitimate negative label, and matching it as a substring
     * would read "I know", "now" and "no problem, I want two" as refusals. Anything up to three
     * characters therefore has to BE the answer — punctuation aside — not merely appear in it.
     * Longer phrasings of the same refusal ("no thanks", "not now") are their own labels and are
     * still caught inside a sentence.
     */
    /**
     * How a reply gets read, and by what.
     *
     * A tapped button IS the label — the vendor wrote both the button and the lists it is matched
     * against, so there is nothing to interpret and no reason to spend the store's AI tokens on it.
     *
     * Typed replies are where judgement is needed. A message that is nothing but the label ("no",
     * "Interested") is decided by the rules, since AI could only agree. Anything else — "price
     * kitna hai", "nahi chahiye", "stop sending to my old number, use this one" — goes to AI when
     * the store is on an AI Agent plan with tokens left. Everything else, including any AI failure,
     * falls back to word matching, so a campaign never stalls on the AI service being down.
     *
     * Returns ['verdict' => interested|not_interested|other, 'by' => button|rules|ai].
     */
    public static function verdict($campaign, ?string $text, bool $wasButton): array
    {
        $clean = trim((string) $text);
        if ($clean === '') {
            return ['verdict' => 'other', 'by' => 'rules'];
        }

        $positive = self::positiveLabels($campaign);
        $negative = self::negativeLabels($campaign);

        if ($wasButton) {
            return ['verdict' => self::classify($clean, $positive, $negative), 'by' => 'button'];
        }

        $whole = self::wholeAnswerVerdict($clean, $positive, $negative);
        if ($whole) {
            return ['verdict' => $whole, 'by' => 'rules'];
        }

        $ai = self::aiVerdict($campaign, $clean);
        if ($ai) {
            return ['verdict' => $ai, 'by' => 'ai'];
        }

        return ['verdict' => self::classify($clean, $positive, $negative), 'by' => 'rules'];
    }

    /** Is the typed reply nothing but one of the labels? Then the rules already know the answer. */
    protected static function wholeAnswerVerdict(string $text, array $positive, array $negative): ?string
    {
        $bare = trim(mb_strtolower(preg_replace('/\s+/u', ' ', preg_replace('/[^\p{L}\p{N}\s]+/u', '', $text) ?? $text) ?? $text));

        foreach ($negative as $needle) {
            if ($bare === trim(mb_strtolower((string) $needle))) {
                return 'not_interested';
            }
        }
        foreach ($positive as $needle) {
            if ($bare === trim(mb_strtolower((string) $needle))) {
                return 'interested';
            }
        }

        return null;
    }

    /**
     * Ask the AI service what a free-text reply means. Null whenever AI must not or cannot answer,
     * which is the caller's signal to fall back to word matching.
     *
     * Gated exactly like the auto-reply bot: an AI Agent plan, and tokens left in both directions
     * once metering applies. Usage is metered per classification under AI_USAGE_CONTEXT so the
     * vendor can see what campaign reading cost them, separately from chat replies.
     */
    protected static function aiVerdict($campaign, string $text): ?string
    {
        $storeId = (int) $campaign->store_id;

        try {
            if (!WhatsAppAgent::isAgent($storeId)) {
                return null;
            }

            $pool = WhatsAppAgent::pool($storeId);
            if (WhatsAppBilling::aiMeteringApplies($storeId)
                && WhatsAppBilling::exhaustedDirection($storeId, $pool) !== null) {
                return null;
            }

            $url = rtrim((string) config('services.ai_service.url', ''), '/');
            if ($url === '') {
                return null;
            }

            $system = "You read one customer reply to a business's promotional WhatsApp message and "
                . "classify their intent. Answer with EXACTLY ONE WORD and nothing else:\n"
                . "INTERESTED — they want the offer, want details, ask about price/availability, or agree.\n"
                . "NOT_INTERESTED — they decline, say no, ask to be left alone, or are annoyed.\n"
                . "UNCLEAR — anything else: an unrelated question, a complaint about a past order, "
                . "or a message whose intent you cannot tell.\n\n"
                . "The reply may be in English, Hindi, Hinglish or any Indian language — judge the meaning, "
                . "not the words. A question about price or delivery is INTERESTED, not UNCLEAR. "
                . "\"Not now\" and \"maybe later\" are NOT_INTERESTED. Never explain, never add punctuation.";

            // Provider and model follow whatever the admin configured for the active user agent,
            // the same as the auto-reply bot — a classification must never depend on a hardcoded
            // key. max_tokens is tiny because the answer is one word.
            $resolved = DB::table('system_prompts')
                ->where('user_type', 'user')->where('status', 'active')
                ->orderByDesc('updated_at')
                ->first(['ai_provider', 'ai_model', 'api_key_override']);

            // Short timeout: reading a reply must never hold anything open for long. The rules are
            // a perfectly good answer if the AI service is slow.
            $resp = Http::withHeaders(['X-Api-Key' => config('services.ai_service.key', '')])
                ->timeout(12)
                ->post($url . '/api/ai/chat', [
                    'user_id'       => 900000000 + $storeId,
                    'guard'         => 'agent_test',
                    'message'       => mb_substr($text, 0, 600),
                    'type'          => 'text',
                    'system_prompt' => $system,
                    'model_config'  => [
                        'ai_provider'      => $resolved->ai_provider ?? 'anthropic',
                        'ai_model'         => $resolved->ai_model ?? null,
                        'max_tokens'       => 16,
                        'api_key_override' => $resolved->api_key_override ?? null,
                    ],
                ]);

            if (!$resp->successful() || empty($resp->json('success'))) {
                Log::warning('WA campaign AI classify failed', ['store' => $storeId, 'status' => $resp->status()]);
                return null;
            }

            $answer = strtoupper(trim((string) $resp->json('message')));

            // Provider's own numbers when the AI service reports them — they include the RAG and
            // memory context it adds on its side, which this estimate cannot see. See
            // SendAutoReply for the same reckoning.
            if (WhatsAppBilling::aiMeteringApplies($storeId)) {
                $usedIn  = (int) $resp->json('usage.input', 0);
                $usedOut = (int) $resp->json('usage.output', 0);

                if ($usedIn <= 0 && $usedOut <= 0) {
                    $usedIn  = WhatsAppBilling::estimateTokens($system, $text);
                    $usedOut = WhatsAppBilling::estimateTokens($answer);
                }

                WhatsAppBilling::recordTokenUsage(
                    $storeId,
                    $usedIn,
                    $usedOut,
                    self::AI_USAGE_CONTEXT,
                    $pool
                );
            }

            // str_contains, not equality: a model that answers "NOT_INTERESTED." still counted.
            // NOT_INTERESTED is tested first because it contains INTERESTED.
            if (str_contains($answer, 'NOT_INTERESTED')) {
                return 'not_interested';
            }
            if (str_contains($answer, 'INTERESTED')) {
                return 'interested';
            }
            if (str_contains($answer, 'UNCLEAR')) {
                return 'other';
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('WA campaign AI classify exception: ' . $e->getMessage());
            return null;
        }
    }

    protected static function labelMatches(string $text, $needle, bool $wholeAnswerForShort = true): bool
    {
        $needle = trim(mb_strtolower((string) $needle));
        if ($needle === '') {
            return false;
        }
        if ($text === $needle) {
            return true;
        }

        if (mb_strlen($needle) <= 3) {
            if ($wholeAnswerForShort) {
                $bare = trim(preg_replace('/\s+/u', ' ', preg_replace('/[^\p{L}\p{N}\s]+/u', '', $text) ?? $text) ?? $text);
                return $bare === $needle;
            }
            return (bool) preg_match('/(?<![\p{L}\p{N}])' . preg_quote($needle, '/') . '(?![\p{L}\p{N}])/u', $text);
        }

        return str_contains($text, $needle);
    }

    /**
     * Log a customer's answer against the campaign it belongs to.
     *
     * $contextWamid is the id of the message being replied to — WhatsApp sends it with every
     * button tap, so the answer lands on the exact step that asked. Without it (a plain typed
     * reply) the most recent send to that number is used instead.
     *
     * "Not interested" excludes the recipient from every later step. Anything else leaves them
     * in, which is what makes silence and interest behave the same way from step 2 on.
     *
     * $wasButton says whether this came from tapping a quick reply rather than typing — see
     * verdict(), which reads the two differently.
     */
    public static function recordReply(?int $storeId, string $phone, ?string $label, ?string $contextWamid = null, bool $wasButton = false): void
    {
        try {
            if (!Schema::hasTable('wa_campaign_sends')) {
                return;
            }

            $send = null;
            if ($contextWamid) {
                $send = DB::table('wa_campaign_sends')->where('wamid', $contextWamid)->first();
            }

            $phone10 = self::phone10($phone);
            if (strlen($phone10) < 10) {
                return;
            }

            if (!$send && $storeId) {
                // Fall back to the newest send to this number in a live campaign of this store.
                //
                // Only with a known store: a message to MyChitti's own platform number carries no
                // store, and guessing which vendor's campaign a stranger's "no" belongs to could
                // drop them from a series they were never answering.
                $send = DB::table('wa_campaign_sends as s')
                    ->join('wa_campaign_recipients as r', 'r.id', '=', 's.recipient_id')
                    ->join('wa_campaigns as c', 'c.id', '=', 's.campaign_id')
                    ->where('r.phone10', $phone10)
                    ->where('s.store_id', $storeId)
                    ->whereIn('c.status', [self::STATUS_RUNNING, self::STATUS_PAUSED, self::STATUS_COMPLETED])
                    ->orderByDesc('s.id')
                    ->select('s.*')
                    ->first();
            }

            if (!$send) {
                return;
            }

            $campaign = DB::table('wa_campaigns')->where('id', $send->campaign_id)->first();
            if (!$campaign) {
                return;
            }

            $read = self::verdict($campaign, $label, $wasButton);
            $verdict = $read['verdict'];
            $now = now();

            // First answer on this step is the one that counts — a second tap must not overwrite
            // the verdict the next step already filtered on.
            if (!$send->reply) {
                DB::table('wa_campaign_sends')->where('id', $send->id)->update([
                    'reply'       => $verdict,
                    'reply_label' => mb_substr((string) $label, 0, 190),
                    'verdict_by'  => $read['by'],
                    'reply_at'    => $now,
                    'updated_at'  => $now,
                ]);
            }

            $recipient = DB::table('wa_campaign_recipients')->where('id', $send->recipient_id)->first();
            if (!$recipient) {
                return;
            }

            $update = [
                'replies'    => (int) $recipient->replies + 1,
                'updated_at' => $now,
            ];

            // A later "not interested" always overrides an earlier yes; a later yes does not
            // pull someone back in — they asked to be left alone.
            if ($verdict === 'not_interested') {
                $update['reply']       = 'not_interested';
                $update['reply_label'] = mb_substr((string) $label, 0, 190);
                $update['verdict_by']  = $read['by'];
                $update['reply_at']    = $now;
                $update['state']       = 'excluded';
            } elseif ($recipient->reply !== 'not_interested') {
                $update['reply']       = $verdict === 'interested' ? 'interested' : ($recipient->reply ?: 'other');
                $update['reply_label'] = mb_substr((string) $label, 0, 190);
                $update['verdict_by']  = $read['by'];
                $update['reply_at']    = $now;
            }

            DB::table('wa_campaign_recipients')->where('id', $recipient->id)->update($update);
        } catch (\Throwable $e) {
            Log::error('WA campaign reply record failed: ' . $e->getMessage());
        }
    }

    /** Mirror a delivery/read/failed status callback onto the campaign send row. */
    public static function recordStatus(?string $wamid, ?string $status, ?string $error = null): void
    {
        if (!$wamid || !$status) {
            return;
        }
        try {
            if (!Schema::hasTable('wa_campaign_sends')) {
                return;
            }
            // Never walk a send backwards: Meta can deliver 'sent' after 'read' on retries.
            $rank = ['failed' => 5, 'read' => 4, 'delivered' => 3, 'sent' => 2, 'accepted' => 1];
            $send = DB::table('wa_campaign_sends')->where('wamid', $wamid)->first();
            if (!$send) {
                return;
            }
            if (($rank[$status] ?? 0) <= ($rank[$send->status] ?? 0)) {
                return;
            }

            DB::table('wa_campaign_sends')->where('id', $send->id)->update(array_filter([
                'status'     => $status,
                'error'      => $error,
                'updated_at' => now(),
            ], fn($v) => !is_null($v)));
        } catch (\Throwable $e) {
            Log::error('WA campaign status record failed: ' . $e->getMessage());
        }
    }

    /** Start (or restart) a draft: step 1 becomes due immediately. */
    public static function start(int $campaignId): array
    {
        self::ensureTables();
        $campaign = DB::table('wa_campaigns')->where('id', $campaignId)->first();
        if (!$campaign) {
            return ['success' => false, 'message' => 'Campaign not found.'];
        }
        if (!DB::table('wa_campaign_recipients')->where('campaign_id', $campaignId)->exists()) {
            return ['success' => false, 'message' => 'This campaign has no recipients.'];
        }
        $first = DB::table('wa_campaign_steps')->where('campaign_id', $campaignId)
            ->orderBy('step_no')->first();
        if (!$first) {
            return ['success' => false, 'message' => 'Add at least one template to the series.'];
        }

        DB::table('wa_campaigns')->where('id', $campaignId)->update([
            'status'     => self::STATUS_RUNNING,
            'last_error' => null,
            'started_at' => $campaign->started_at ?: now(),
            'updated_at' => now(),
        ]);
        if ($first->status === 'pending' && !$first->due_at) {
            DB::table('wa_campaign_steps')->where('id', $first->id)
                ->update(['due_at' => now(), 'updated_at' => now()]);
        }

        return ['success' => true, 'message' => 'Campaign started. Step 1 goes out within a few minutes.'];
    }

    public static function setStatus(int $campaignId, string $status, ?string $note = null): void
    {
        self::ensureTables();

        // A null note clears the banner: the reason a campaign paused must not survive into the
        // run that fixed it, or a completed series still reads "wallet balance too low".
        $update = [
            'status'     => $status,
            'last_error' => $note,
            'updated_at' => now(),
        ];
        if (in_array($status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true)) {
            $update['completed_at'] = now();
        }

        DB::table('wa_campaigns')->where('id', $campaignId)->update($update);
    }

    /** Every campaign the scheduler should look at right now. */
    public static function dueCampaigns()
    {
        self::ensureTables();
        return DB::table('wa_campaigns')
            ->where('status', self::STATUS_RUNNING)
            ->orderBy('id')
            ->get();
    }

    /**
     * Send the next batch of whatever step is due on this campaign.
     *
     * Returns a small report so both the scheduler and the "Send due step now" button can say
     * what happened. Sending stops early — without failing the campaign — when the wallet can no
     * longer cover a message; the vendor recharges and the next pass picks it up.
     */
    public static function runCampaign($campaign, int $budget = self::RUN_BATCH): array
    {
        self::ensureTables();

        $report = ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'step' => null, 'message' => ''];

        if ($campaign->status !== self::STATUS_RUNNING) {
            $report['message'] = 'Campaign is not running.';
            return $report;
        }

        $storeId = (int) $campaign->store_id;
        $wa = WhatsAppService::make($storeId);

        if ($wa->source() !== 'vendor') {
            self::setStatus($campaign->id, self::STATUS_PAUSED, 'Paused: no connected WhatsApp number.');
            $report['message'] = 'No connected WhatsApp number.';
            return $report;
        }
        if (!WhatsAppBilling::isActive($storeId)) {
            self::setStatus($campaign->id, self::STATUS_PAUSED, 'Paused: WhatsApp subscription is not active.');
            $report['message'] = 'Subscription is not active.';
            return $report;
        }

        $step = DB::table('wa_campaign_steps')
            ->where('campaign_id', $campaign->id)
            ->whereIn('status', ['pending', 'sending'])
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now())
            ->orderBy('step_no')
            ->first();

        if (!$step) {
            // Nothing due. If no step is left waiting at all, the series is over.
            $waiting = DB::table('wa_campaign_steps')->where('campaign_id', $campaign->id)
                ->whereIn('status', ['pending', 'sending'])->count();
            if ($waiting === 0) {
                self::setStatus($campaign->id, self::STATUS_COMPLETED);
                $report['message'] = 'All steps have gone out.';
            } else {
                $report['message'] = 'Next step is not due yet.';
            }
            return $report;
        }

        $report['step'] = $step->step_no;

        if ($step->status === 'pending') {
            DB::table('wa_campaign_steps')->where('id', $step->id)->update([
                'status' => 'sending', 'started_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Anyone who sent STOP to this store since the last step is out of the whole series,
        // whichever screen the opt-out came from.
        self::syncOptOuts($campaign, $storeId);
        self::syncCapped($campaign);

        $recipients = self::eligibleQuery($campaign->id, $step)->limit($budget)->get();

        // Platform-audience sends carry context 'nearby' — that is what the 30-day frequency cap
        // counts, and what prices the message at the platform rate.
        $context = $campaign->audience === 'platform' ? 'nearby' : 'campaign';
        $audience = $campaign->audience === 'platform' ? 'platform' : 'own';
        $params = json_decode((string) $step->params, true) ?: [];

        foreach ($recipients as $recipient) {
            if (!WhatsAppBilling::canAffordMessage($storeId, $audience)) {
                self::setStatus($campaign->id, self::STATUS_PAUSED, 'Paused: wallet balance too low to continue sending.');
                $report['message'] = 'Wallet balance too low — campaign paused.';
                // Whatever did go out before the wallet ran dry still belongs on the step.
                self::tallyStep($step->id, $report);
                return $report;
            }

            // Claim the send before dispatching. The unique key on (step_id, recipient_id) is
            // what stops a second runner pass — or an impatient "send now" click — from
            // messaging the same person twice for one step.
            try {
                $sendId = DB::table('wa_campaign_sends')->insertGetId([
                    'campaign_id'  => $campaign->id,
                    'step_id'      => $step->id,
                    'recipient_id' => $recipient->id,
                    'store_id'     => $storeId,
                    'status'       => 'queued',
                    'sent_at'      => now(),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            } catch (\Throwable $e) {
                $report['skipped']++;
                continue;
            }

            $res = $wa->sendTemplate(
                $recipient->phone,
                $step->template_name,
                $step->language,
                self::components($params, $recipient),
                $context
            );

            DB::table('wa_campaign_sends')->where('id', $sendId)->update([
                'wamid'      => $res['id'] ?? null,
                'status'     => $res['success'] ? 'sent' : 'failed',
                'error'      => $res['error'] ?? null,
                'updated_at' => now(),
            ]);

            if ($res['success']) {
                $report['sent']++;
                DB::table('wa_campaign_recipients')->where('id', $recipient->id)->update([
                    'steps_sent'   => DB::raw('steps_sent + 1'),
                    'last_sent_at' => now(),
                    'updated_at'   => now(),
                ]);
            } else {
                $report['failed']++;
            }
        }

        self::tallyStep($step->id, $report);

        // Step is finished only once nobody eligible is left un-messaged.
        if (!self::eligibleQuery($campaign->id, $step)->exists()) {
            self::closeStep($campaign, $step);
            $report['message'] = 'Step ' . $step->step_no . ' complete.';
        } else {
            $report['message'] = 'Step ' . $step->step_no . ' in progress.';
        }

        return $report;
    }

    /** Roll this pass's outcome onto the step's running totals. */
    protected static function tallyStep($stepId, array $report): void
    {
        if (!$report['sent'] && !$report['failed']) {
            return;
        }
        DB::table('wa_campaign_steps')->where('id', $stepId)->update([
            'sent_count'   => DB::raw('sent_count + ' . (int) $report['sent']),
            'failed_count' => DB::raw('failed_count + ' . (int) $report['failed']),
            'updated_at'   => now(),
        ]);
    }

    /** Mark a step done and put the next one on the clock. */
    protected static function closeStep($campaign, $step): void
    {
        DB::table('wa_campaign_steps')->where('id', $step->id)->update([
            'status' => 'sent', 'completed_at' => now(), 'updated_at' => now(),
        ]);

        $next = DB::table('wa_campaign_steps')
            ->where('campaign_id', $campaign->id)
            ->where('step_no', '>', $step->step_no)
            ->orderBy('step_no')
            ->first();

        if (!$next) {
            self::setStatus($campaign->id, self::STATUS_COMPLETED);
            return;
        }

        // The delay is measured from the moment the previous step finished going out, so a slow
        // batch never squeezes two templates into the same hour.
        DB::table('wa_campaign_steps')->where('id', $next->id)->update([
            'due_at'     => now()->addHours(max(0, (int) $next->delay_hours)),
            'updated_at' => now(),
        ]);
    }

    /**
     * Recipients this step still has to reach: still in the series, matching the step's reply
     * filter, and not already sent this step.
     */
    public static function eligibleQuery(int $campaignId, $step)
    {
        // Saying "not interested" sets state='excluded', so a step aimed at those people has to
        // look past the active filter — otherwise it matches nobody, every time. Every other
        // target stays limited to recipients still in the series.
        $targetsDeclined = ($step->target ?? '') === 'not_interested';

        $query = DB::table('wa_campaign_recipients as r')
            ->where('r.campaign_id', $campaignId)
            ->when(!$targetsDeclined, fn($q) => $q->where('r.state', 'active'))
            ->whereNotExists(function ($q) use ($step) {
                $q->select(DB::raw(1))->from('wa_campaign_sends as s')
                    ->whereColumn('s.recipient_id', 'r.id')
                    ->where('s.step_id', $step->id);
            })
            ->orderBy('r.id')
            ->select('r.id', 'r.name', 'r.phone', 'r.client_id');

        switch ($step->target) {
            case 'interested':
                $query->where('r.reply', 'interested');
                break;
            case 'not_interested':
                $query->where('r.reply', 'not_interested');
                break;
            case 'no_reply':
                $query->whereNull('r.reply');
                break;
            case 'engaged':
                $query->whereNotNull('r.reply');
                break;
            case 'interested_no_reply':
                // The default: keep the silent ones in, drop only the explicit noes. Those are
                // already state='excluded', so this is a guard rather than the whole rule.
                $query->where(function ($q) {
                    $q->whereNull('r.reply')->orWhereIn('r.reply', ['interested', 'other']);
                });
                break;
            case 'all':
            default:
                break;
        }

        return $query;
    }

    /** Drop anyone who has opted out of this store's marketing since the campaign was built. */
    protected static function syncOptOuts($campaign, int $storeId): void
    {
        // optedOutPhones() hands back normalized numbers (91…); recipients are keyed on the
        // last 10 digits, so match on that or nothing lines up.
        $phones = array_values(array_filter(array_map(
            fn($p) => self::phone10($p),
            WhatsAppService::optedOutPhones($storeId)
        ), fn($p) => strlen($p) === 10));

        if (empty($phones)) {
            return;
        }
        DB::table('wa_campaign_recipients')
            ->where('campaign_id', $campaign->id)
            ->where('state', 'active')
            ->whereIn('phone10', $phones)
            ->update(['state' => 'opted_out', 'updated_at' => now()]);
    }

    /**
     * Hold the shared outreach pool's 30-day cap open across the whole series.
     *
     * Only platform-audience campaigns are capped — those are people the vendor has no
     * relationship with, and the limit is per person across every business, not per campaign.
     * Checking it once when the audience was built would let a four-step series push someone well
     * past four offers, which is precisely what the cap exists to stop.
     */
    protected static function syncCapped($campaign): void
    {
        if ($campaign->audience !== 'platform') {
            return;
        }

        $capped = WhatsAppService::nearbyCappedPhones();
        if (empty($capped)) {
            return;
        }

        DB::table('wa_campaign_recipients')
            ->where('campaign_id', $campaign->id)
            ->where('state', 'active')
            ->whereIn('phone10', $capped)
            ->update(['state' => 'capped', 'updated_at' => now()]);
    }

    /**
     * Body parameters for one recipient. {name} / {phone} inside a value the vendor typed are
     * substituted, and the platform's own named slots are filled outright — the same rules the
     * one-off bulk composer uses, so a template behaves identically in both places.
     */
    public static function components(array $params, $recipient): array
    {
        $name  = trim((string) $recipient->name) ?: 'Customer';
        $phone = trim((string) $recipient->phone);

        $auto = ['customer_name' => $name, 'customer_phone' => $phone];
        $tokens = [
            '{name}'           => $name,
            '{customer_name}'  => $name,
            '{phone}'          => $phone,
            '{customer_phone}' => $phone,
        ];

        $parameters = [];
        foreach (array_values($params) as $i => $raw) {
            $key   = trim(is_array($raw) ? (string) ($raw['key'] ?? '') : '') ?: (string) ($i + 1);
            $value = is_array($raw) ? (string) ($raw['value'] ?? '') : (string) $raw;
            $value = array_key_exists($key, $auto) ? $auto[$key] : strtr($value, $tokens);

            $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
            $parameters[] = WhatsAppService::bodyParameter($key, trim(mb_substr($value, 0, 900)));
        }

        return $parameters ? [['type' => 'body', 'parameters' => $parameters]] : [];
    }

    /**
     * Fill a campaign's recipient list.
     *
     * 'clients' takes the store's own customer book (deduped, opt-outs already excluded by the
     * caller's query). 'platform' takes the shared MyChitti audience, whose numbers the vendor
     * never sees — they are stored so the series can follow up, and masked everywhere in the UI.
     */
    public static function addRecipients(int $campaignId, int $storeId, iterable $rows): int
    {
        self::ensureTables();

        $now = now();
        $batch = [];
        $seen = [];
        $added = 0;

        foreach ($rows as $row) {
            $phone = trim((string) ($row->phone ?? ''));
            $phone10 = self::phone10($phone);
            if (strlen($phone10) < 10 || isset($seen[$phone10])) {
                continue;
            }
            $seen[$phone10] = true;

            $batch[] = [
                'campaign_id' => $campaignId,
                'store_id'    => $storeId,
                'client_id'   => $row->id ?? null,
                'name'        => mb_substr(trim((string) ($row->name ?? '')), 0, 190) ?: null,
                'phone'       => $phone,
                'phone10'     => $phone10,
                'state'       => 'active',
                'created_at'  => $now,
                'updated_at'  => $now,
            ];

            if (count($batch) >= 500) {
                $added += self::insertRecipients($batch);
                $batch = [];
            }
        }
        if ($batch) {
            $added += self::insertRecipients($batch);
        }

        DB::table('wa_campaigns')->where('id', $campaignId)->update([
            'recipients_count' => DB::table('wa_campaign_recipients')->where('campaign_id', $campaignId)->count(),
            'updated_at'       => $now,
        ]);

        return $added;
    }

    protected static function insertRecipients(array $batch): int
    {
        try {
            DB::table('wa_campaign_recipients')->insertOrIgnore($batch);
            return count($batch);
        } catch (\Throwable $e) {
            Log::error('WA campaign recipient insert failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * The funnel the vendor reads: per step, how many went out, landed, were read, and what came
     * back. "No reply" is deliberately delivered-minus-replied rather than a stored flag — the
     * silent group is defined by the absence of an answer, and it shrinks on its own if someone
     * answers late.
     */
    public static function stepStats(int $campaignId): array
    {
        self::ensureTables();

        $steps = DB::table('wa_campaign_steps')->where('campaign_id', $campaignId)
            ->orderBy('step_no')->get();

        $rows = DB::table('wa_campaign_sends')
            ->where('campaign_id', $campaignId)
            ->selectRaw("step_id,
                COUNT(*) as total,
                SUM(status = 'failed') as failed,
                SUM(status IN ('sent','delivered','read')) as sent,
                SUM(status IN ('delivered','read')) as delivered,
                SUM(status = 'read') as read_count,
                SUM(reply = 'interested') as interested,
                SUM(reply = 'not_interested') as not_interested,
                SUM(reply = 'other') as other_reply")
            ->groupBy('step_id')
            ->get()
            ->keyBy('step_id');

        $out = [];
        foreach ($steps as $step) {
            $s = $rows[$step->id] ?? null;
            $sent      = (int) ($s->sent ?? 0);
            $delivered = (int) ($s->delivered ?? 0);
            $replied   = (int) ($s->interested ?? 0) + (int) ($s->not_interested ?? 0) + (int) ($s->other_reply ?? 0);

            $out[] = [
                'step'           => $step,
                'pending'        => self::eligibleQuery($campaignId, $step)->count(),
                'total'          => (int) ($s->total ?? 0),
                'sent'           => $sent,
                'failed'         => (int) ($s->failed ?? 0),
                'delivered'      => $delivered,
                'read'           => (int) ($s->read_count ?? 0),
                'interested'     => (int) ($s->interested ?? 0),
                'not_interested' => (int) ($s->not_interested ?? 0),
                'other_reply'    => (int) ($s->other_reply ?? 0),
                'replied'        => $replied,
                'no_reply'       => max(0, $sent - $replied),
            ];
        }

        return $out;
    }

    /** Headline numbers for the campaign list and the top of the detail page. */
    public static function summary(int $campaignId): array
    {
        self::ensureTables();

        $people = DB::table('wa_campaign_recipients')->where('campaign_id', $campaignId)
            ->selectRaw("COUNT(*) as total,
                SUM(state = 'active') as active,
                SUM(state = 'excluded') as excluded,
                SUM(state = 'opted_out') as opted_out,
                SUM(state = 'capped') as capped,
                SUM(reply = 'interested') as interested,
                SUM(reply = 'not_interested') as not_interested,
                SUM(reply = 'other') as other_reply,
                SUM(reply IS NULL) as silent")
            ->first();

        $sends = DB::table('wa_campaign_sends')->where('campaign_id', $campaignId)
            ->selectRaw("COUNT(*) as total,
                SUM(status IN ('sent','delivered','read')) as sent,
                SUM(status = 'failed') as failed,
                SUM(status IN ('delivered','read')) as delivered,
                SUM(status = 'read') as read_count")
            ->first();

        return [
            'recipients'     => (int) ($people->total ?? 0),
            'active'         => (int) ($people->active ?? 0),
            'excluded'       => (int) ($people->excluded ?? 0),
            'opted_out'      => (int) ($people->opted_out ?? 0),
            'capped'         => (int) ($people->capped ?? 0),
            'interested'     => (int) ($people->interested ?? 0),
            'not_interested' => (int) ($people->not_interested ?? 0),
            'other_reply'    => (int) ($people->other_reply ?? 0),
            'silent'         => (int) ($people->silent ?? 0),
            'messages'       => (int) ($sends->total ?? 0),
            'sent'           => (int) ($sends->sent ?? 0),
            'failed'         => (int) ($sends->failed ?? 0),
            'delivered'      => (int) ($sends->delivered ?? 0),
            'read'           => (int) ($sends->read_count ?? 0),
        ];
    }

    /** Estimated wallet cost of the whole series, priced per step against its own audience. */
    public static function estimatedCost($campaign, array $stats): float
    {
        $rate = WhatsAppBilling::messageCost($campaign->audience === 'platform' ? 'platform' : 'own');
        $messages = 0;
        foreach ($stats as $row) {
            $messages += $row['total'] + $row['pending'];
        }
        return round($rate * $messages, 2);
    }
}
