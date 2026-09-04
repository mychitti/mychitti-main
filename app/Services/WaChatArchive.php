<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Archive of the WhatsApp account paired to the Baileys bridge (wa-bridge/) — every chat it
 * can see: the internal team groups and one-to-one conversations with vendors and customers.
 *
 * This is a different pipe from WhatsAppService: that one is the Meta Cloud API, which can
 * only ever see conversations started on the business number. The bridge logs in as a real
 * WhatsApp account over the Web protocol, so the AI can read what was actually said.
 *
 * Each chat carries its own ai_enabled flag. Archiving is all-or-nothing (the bridge decides
 * what it forwards); paying a model to read a chat is opt-out per conversation.
 */
class WaChatArchive
{
    public const KINDS = [
        'sale', 'lead', 'payment', 'task', 'task_update', 'issue', 'decision', 'followup', 'note',
    ];

    public const TYPES = ['dm', 'group'];

    private static bool $schemaChecked = false;

    public static function ensureTables(): void
    {
        if (self::$schemaChecked) {
            return;
        }

        // The archive started life as groups-only. Carry an existing install forward rather
        // than stranding its rows in tables nothing reads any more.
        if (Schema::hasTable('wa_group_messages') && !Schema::hasTable('wa_chat_messages')) {
            DB::statement("RENAME TABLE `wa_group_messages` TO `wa_chat_messages`");
            DB::statement("ALTER TABLE `wa_chat_messages`
                CHANGE `group_jid` `chat_jid` VARCHAR(128) NOT NULL,
                CHANGE `group_name` `chat_name` VARCHAR(191) NULL,
                ADD COLUMN `chat_type` VARCHAR(10) NOT NULL DEFAULT 'group' AFTER `chat_name`");
        }
        if (Schema::hasTable('wa_group_insights') && !Schema::hasTable('wa_chat_insights')) {
            DB::statement("RENAME TABLE `wa_group_insights` TO `wa_chat_insights`");
            DB::statement("ALTER TABLE `wa_chat_insights` CHANGE `group_jid` `chat_jid` VARCHAR(128) NULL");
        }

        if (!Schema::hasTable('wa_chat_messages')) {
            // chat_jid + wa_message_id is the natural key: WhatsApp redelivers the same
            // message on every reconnect and history sync, and a duplicate row would be
            // re-analysed and double-counted as a second sale.
            DB::statement("CREATE TABLE `wa_chat_messages` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `wa_message_id` VARCHAR(128) NOT NULL,
                `chat_jid` VARCHAR(128) NOT NULL,
                `chat_name` VARCHAR(191) NULL,
                `chat_type` VARCHAR(10) NOT NULL DEFAULT 'dm',
                `sender_jid` VARCHAR(128) NULL,
                `sender_name` VARCHAR(191) NULL,
                `sender_phone` VARCHAR(32) NULL,
                `from_me` TINYINT(1) NOT NULL DEFAULT 0,
                `type` VARCHAR(40) NOT NULL DEFAULT 'text',
                `body` MEDIUMTEXT NULL,
                `quoted_message_id` VARCHAR(128) NULL,
                `quoted_body` TEXT NULL,
                `media_mime` VARCHAR(120) NULL,
                `media_name` VARCHAR(255) NULL,
                `sent_at` TIMESTAMP NULL,
                `analyzed_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wa_chat_msg_uniq` (`chat_jid`, `wa_message_id`),
                KEY `wa_chat_msg_sent` (`chat_jid`, `sent_at`),
                KEY `wa_chat_msg_pending` (`analyzed_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        if (!Schema::hasTable('wa_chats')) {
            // One row per conversation. Exists so an admin can see what is being archived and
            // turn AI analysis off for the chats that are personal or simply not worth paying
            // a model to read. `ai_enabled` rather than `analyze` — ANALYZE is reserved in MySQL.
            DB::statement("CREATE TABLE `wa_chats` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `chat_jid` VARCHAR(128) NOT NULL,
                `chat_type` VARCHAR(10) NOT NULL DEFAULT 'dm',
                `name` VARCHAR(191) NULL,
                `phone` VARCHAR(32) NULL,
                `ai_enabled` TINYINT(1) NOT NULL DEFAULT 1,
                `message_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `last_message_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wa_chats_jid` (`chat_jid`),
                KEY `wa_chats_recent` (`last_message_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        if (!Schema::hasTable('wa_chat_insights')) {
            DB::statement("CREATE TABLE `wa_chat_insights` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `message_id` BIGINT UNSIGNED NULL,
                `chat_jid` VARCHAR(128) NULL,
                `kind` VARCHAR(30) NOT NULL,
                `title` VARCHAR(255) NULL,
                `summary` TEXT NULL,
                `assignee` VARCHAR(120) NULL,
                `reporter` VARCHAR(120) NULL,
                `counterparty` VARCHAR(191) NULL,
                `status` VARCHAR(40) NULL,
                `amount` DECIMAL(14,2) NULL,
                `currency` VARCHAR(8) NULL,
                `due_date` DATE NULL,
                `confidence` DECIMAL(4,3) NULL,
                `payload` TEXT NULL,
                `occurred_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `wa_chat_insight_kind` (`kind`, `occurred_at`),
                KEY `wa_chat_insight_msg` (`message_id`),
                KEY `wa_chat_insight_chat` (`chat_jid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        self::$schemaChecked = true;
    }

    /**
     * Persist a batch from the bridge. Returns counts so the bridge can log what landed.
     */
    public static function store(array $rows): array
    {
        self::ensureTables();

        $stored = 0;
        $duplicate = 0;
        $skipped = 0;
        $touched = [];

        foreach ($rows as $row) {
            $waId = trim((string) ($row['wa_message_id'] ?? ''));
            $jid  = trim((string) ($row['chat_jid'] ?? ''));
            if ($waId === '' || $jid === '') {
                $skipped++;
                continue;
            }

            $type = in_array($row['chat_type'] ?? null, self::TYPES, true)
                ? $row['chat_type']
                : (str_ends_with($jid, '@g.us') ? 'group' : 'dm');

            $now = now();
            $payload = [
                'wa_message_id'     => mb_substr($waId, 0, 128),
                'chat_jid'          => mb_substr($jid, 0, 128),
                'chat_name'         => self::clip($row['chat_name'] ?? null, 191),
                'chat_type'         => $type,
                'sender_jid'        => self::clip($row['sender_jid'] ?? null, 128),
                'sender_name'       => self::clip($row['sender_name'] ?? null, 191),
                'sender_phone'      => self::clip($row['sender_phone'] ?? null, 32),
                'from_me'           => !empty($row['from_me']) ? 1 : 0,
                'type'              => self::clip($row['type'] ?? 'text', 40) ?: 'text',
                'body'              => isset($row['body']) ? (string) $row['body'] : null,
                'quoted_message_id' => self::clip($row['quoted_message_id'] ?? null, 128),
                'quoted_body'       => isset($row['quoted_body']) ? (string) $row['quoted_body'] : null,
                'media_mime'        => self::clip($row['media_mime'] ?? null, 120),
                'media_name'        => self::clip($row['media_name'] ?? null, 255),
                'sent_at'           => self::toTimestamp($row['sent_at'] ?? null),
                'created_at'        => $now,
                'updated_at'        => $now,
            ];

            try {
                DB::table('wa_chat_messages')->insert($payload);
                $stored++;

                $key = $payload['chat_jid'];
                $touched[$key] ??= ['chat_type' => $type, 'name' => null, 'phone' => null, 'inserted' => 0, 'last_at' => null];
                $touched[$key]['inserted']++;
                $touched[$key]['name'] ??= $payload['chat_name'];
                $touched[$key]['phone'] ??= ($type === 'dm' ? self::phoneFromJid($jid) : null);
                if ($touched[$key]['last_at'] === null || $payload['sent_at'] > $touched[$key]['last_at']) {
                    $touched[$key]['last_at'] = $payload['sent_at'];
                }
            } catch (\Illuminate\Database\QueryException $e) {
                // 23000 = the unique key rejected a redelivery. That is the normal path on
                // every reconnect, not an error worth logging.
                if ((string) $e->getCode() === '23000') {
                    $duplicate++;
                    // A history-sync copy can carry the chat name the live event lacked.
                    if (!empty($payload['chat_name'])) {
                        DB::table('wa_chat_messages')
                            ->where('chat_jid', $payload['chat_jid'])
                            ->where('wa_message_id', $payload['wa_message_id'])
                            ->whereNull('chat_name')
                            ->update(['chat_name' => $payload['chat_name'], 'updated_at' => $now]);
                    }
                    continue;
                }
                Log::warning('WA chat archive insert failed: ' . $e->getMessage());
                $skipped++;
            }
        }

        foreach ($touched as $jid => $meta) {
            self::touchChat($jid, $meta);
        }

        return ['stored' => $stored, 'duplicate' => $duplicate, 'skipped' => $skipped];
    }

    /** Keep the chat registry in step with what just landed. */
    private static function touchChat(string $jid, array $meta): void
    {
        $existing = DB::table('wa_chats')->where('chat_jid', $jid)->first();

        if ($existing) {
            // Increment rather than re-count: a history sync walks tens of thousands of
            // messages in batches, and a COUNT(*) per chat per batch is the whole cost of it.
            // Only successful inserts are counted here, so duplicates cannot inflate it.
            $values = [
                'chat_type'     => $meta['chat_type'],
                'message_count' => DB::raw('message_count + ' . (int) $meta['inserted']),
                'updated_at'    => now(),
            ];

            // History sync delivers old messages after new ones. Never move the clock back.
            if ($meta['last_at'] && (!$existing->last_message_at || $meta['last_at'] > $existing->last_message_at)) {
                $values['last_message_at'] = $meta['last_at'];
            }
            // A one-to-one chat is named by whoever is on the other end, and the bridge only
            // learns that name once the contact list syncs. Never overwrite a known name.
            if (!empty($meta['name']) && empty($existing->name)) {
                $values['name'] = $meta['name'];
            }
            if (!empty($meta['phone']) && empty($existing->phone)) {
                $values['phone'] = $meta['phone'];
            }

            DB::table('wa_chats')->where('chat_jid', $jid)->update($values);
            return;
        }

        // First sight of this chat. Count for real once: an archive migrated from the
        // groups-only schema already holds messages this registry has never seen.
        $agg = DB::table('wa_chat_messages')
            ->where('chat_jid', $jid)
            ->selectRaw('COUNT(*) as total, MAX(sent_at) as last_at')
            ->first();

        DB::table('wa_chats')->insert([
            'chat_jid'        => $jid,
            'chat_type'       => $meta['chat_type'],
            'name'            => $meta['name'] ?: null,
            'phone'           => $meta['phone'] ?: null,
            'ai_enabled'      => 1,
            'message_count'   => (int) ($agg->total ?? $meta['inserted']),
            'last_message_at' => $agg->last_at ?? $meta['last_at'],
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    /**
     * Read a window of un-analysed messages, ask the model what happened in it, and write
     * the structured rows. Returns [messages, insights] counts.
     *
     * Messages are analysed in chronological batches rather than one at a time because the
     * meaning usually spans several: "done" only means something next to the task above it.
     */
    public static function analyzePending(int $batch = 60, ?string $chatJid = null): array
    {
        self::ensureTables();

        $messages = DB::table('wa_chat_messages as m')
            ->whereNull('m.analyzed_at')
            ->when($chatJid, fn($q) => $q->where('m.chat_jid', $chatJid))
            // A chat switched off costs nothing and stays out of the pending count. Switch it
            // back on and its backlog becomes analysable again on the next run.
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('wa_chats as c')
                    ->whereColumn('c.chat_jid', 'm.chat_jid')
                    ->where('c.ai_enabled', 1);
            })
            ->orderBy('m.sent_at')
            ->orderBy('m.id')
            ->limit($batch)
            ->get([
                'm.id', 'm.chat_jid', 'm.chat_name', 'm.chat_type', 'm.sender_name',
                'm.sender_phone', 'm.from_me', 'm.type', 'm.body', 'm.quoted_body', 'm.sent_at',
            ]);

        if ($messages->isEmpty()) {
            return ['messages' => 0, 'insights' => 0];
        }

        $insightCount = 0;
        $done = collect();

        // One model call per chat. A single batch can straddle several conversations, and
        // mixing them would hand the model one interleaved transcript plus context from
        // whichever chat happened to sort first.
        foreach ($messages->groupBy('chat_jid') as $slice) {
            // Reactions and stickers carry no analysable content but still need marking,
            // so they ride along here and simply produce nothing.
            $analysable = $slice->filter(
                fn($m) => trim((string) $m->body) !== '' && !in_array($m->type, ['reaction', 'sticker'], true)
            );

            if ($analysable->isEmpty()) {
                $done = $done->merge($slice->pluck('id'));
                continue;
            }

            // Prior context: the model cannot judge "shipped it" without the request above it.
            $context = DB::table('wa_chat_messages')
                ->where('chat_jid', $analysable->first()->chat_jid)
                ->where('id', '<', $analysable->first()->id)
                ->orderByDesc('id')
                ->limit(15)
                ->get(['sender_name', 'sender_phone', 'from_me', 'body', 'sent_at'])
                ->reverse()
                ->values();

            $extracted = self::extract($analysable, $context);

            // null means the call itself failed. Marking the slice analysed here would drop
            // it permanently over a transient OpenAI outage — leave it for the next run.
            if ($extracted === null) {
                continue;
            }

            $insightCount += self::persistInsights($extracted, $analysable);
            $done = $done->merge($slice->pluck('id'));
        }

        if ($done->isNotEmpty()) {
            DB::table('wa_chat_messages')
                ->whereIn('id', $done->all())
                ->update(['analyzed_at' => now(), 'updated_at' => now()]);
        }

        return ['messages' => $done->count(), 'insights' => $insightCount];
    }

    /**
     * Ask the model to turn a slice of one conversation into structured rows.
     * Returns null when the call failed, so the caller can retry the slice later.
     */
    private static function extract($messages, $context): ?array
    {
        $key = config('services.openai.key');
        if (!$key) {
            Log::warning('WA chat analysis skipped: OPENAI_API_KEY is not set.');
            return null;
        }

        $first    = $messages->first();
        $isGroup  = $first->chat_type === 'group';
        $chatName = $first->chat_name ?: ($first->chat_jid ?? 'unknown');

        $header = $isGroup
            ? "This is an internal WhatsApp GROUP named \"{$chatName}\". Several colleagues talk here."
            : "This is a ONE-TO-ONE WhatsApp chat between the MyChitti account holder and \"{$chatName}\". "
                . "Lines marked (me) are the account holder; the rest are the other person.";

        $lines = [];
        foreach ($context as $c) {
            $lines[] = sprintf('(earlier) %s %s: %s', self::stamp($c->sent_at), self::who($c), self::oneLine($c->body));
        }
        foreach ($messages as $m) {
            $quote = $m->quoted_body ? ' [replying to: "' . self::oneLine($m->quoted_body, 120) . '"]' : '';
            $lines[] = sprintf('#%d %s %s:%s %s', $m->id, self::stamp($m->sent_at), self::who($m), $quote, self::oneLine($m->body, 1500));
        }

        $system = <<<PROMPT
You read the WhatsApp conversations of the person who runs MyChitti, an Indian multi-vendor SaaS company, and turn them into structured business records.

{$header}

Lines prefixed "(earlier)" are prior context ONLY - never emit a record for them. Emit records only for lines prefixed with #ID, and set message_ref to that ID.

Extract one record per real event. Valid "kind" values:
- sale         : an order, booking, deal closed, or revenue figure being reported
- lead         : someone enquiring, asking for a price/demo/availability - a potential customer not yet closed
- payment      : money requested, promised, received, refunded or overdue (invoices, dues, advances)
- task         : new work being asked for or committed to
- task_update  : progress, completion, blockage or reassignment of work mentioned before
- issue        : a bug, outage, complaint or failure being reported
- decision     : a choice that has been settled on
- followup     : something the account holder owes a reply or action on, especially with a date
- note         : information worth keeping that is none of the above

Rules:
- Chit-chat, greetings, acknowledgements ("ok", "done ji", "good morning"), stickers, forwards and festival wishes produce NO record. An empty list is a correct answer, and most personal conversation should produce one.
- amount: digits only, no separators. Indian shorthand: "2.5L"/"2.5 lakh" = 250000, "1cr" = 10000000, "50k" = 50000. currency defaults to "INR".
- due_date: resolve relative dates ("tomorrow", "by Friday") against the message timestamp. Format YYYY-MM-DD. Null if none stated.
- counterparty: the customer, vendor or company the record is ABOUT, when one is named. Null otherwise.
- assignee/reporter: the person's name as written in the chat. Null if not clear.
- status for task/task_update/followup: one of open, in_progress, blocked, done.
- confidence: 0.0-1.0, how sure you are this is a real business record and not conversation.
- Messages are Hinglish/English mixed. Read both; write title and summary in English.

Return ONLY JSON: {"records": [{"message_ref": <int>, "kind": "...", "title": "...", "summary": "...", "counterparty": null, "assignee": null, "reporter": null, "status": null, "amount": null, "currency": null, "due_date": null, "confidence": 0.0}]}
PROMPT;

        try {
            $response = Http::withToken($key)
                ->timeout(120)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'           => config('services.openai.model', 'gpt-4o'),
                    'temperature'     => 0,
                    'response_format' => ['type' => 'json_object'],
                    'messages'        => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => implode("\n", $lines)],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('WA chat analysis HTTP ' . $response->status() . ': ' . $response->body());
                return null;
            }

            $content = data_get($response->json(), 'choices.0.message.content');
            $decoded = json_decode((string) $content, true);

            // A well-formed reply with no records is a real answer (pure chit-chat), and the
            // slice is done. Unparseable output is a failure — retry it instead.
            if (!is_array($decoded)) {
                Log::error('WA chat analysis returned unparseable JSON.');
                return null;
            }

            return is_array($decoded['records'] ?? null) ? $decoded['records'] : [];
        } catch (\Throwable $e) {
            Log::error('WA chat analysis failed: ' . $e->getMessage());
            return null;
        }
    }

    private static function persistInsights(array $records, $messages): int
    {
        if (!$records) {
            return 0;
        }

        $byId = $messages->keyBy('id');
        $count = 0;

        foreach ($records as $r) {
            $kind = strtolower(trim((string) ($r['kind'] ?? '')));
            if (!in_array($kind, self::KINDS, true)) {
                continue;
            }

            // A record must point at a message in this batch. A hallucinated ref would
            // otherwise attach an insight to an unrelated conversation.
            $msg = $byId->get((int) ($r['message_ref'] ?? 0));
            if (!$msg) {
                continue;
            }

            $title = self::clip($r['title'] ?? null, 255);
            if (!$title) {
                continue;
            }

            DB::table('wa_chat_insights')->insert([
                'message_id'   => $msg->id,
                'chat_jid'     => $msg->chat_jid,
                'kind'         => $kind,
                'title'        => $title,
                'summary'      => isset($r['summary']) ? (string) $r['summary'] : null,
                'assignee'     => self::clip($r['assignee'] ?? null, 120),
                'reporter'     => self::clip($r['reporter'] ?? null, 120) ?: self::clip($msg->sender_name, 120),
                // In a one-to-one chat the other party is the counterparty unless the model
                // named a third company, so the sales view is never a column of nulls.
                'counterparty' => self::clip($r['counterparty'] ?? null, 191)
                    ?: ($msg->chat_type === 'dm' ? self::clip($msg->chat_name, 191) : null),
                'status'       => self::clip($r['status'] ?? null, 40),
                'amount'       => is_numeric($r['amount'] ?? null) ? round((float) $r['amount'], 2) : null,
                'currency'     => self::clip($r['currency'] ?? null, 8) ?: (is_numeric($r['amount'] ?? null) ? 'INR' : null),
                'due_date'     => self::toDate($r['due_date'] ?? null),
                'confidence'   => is_numeric($r['confidence'] ?? null) ? min(1, max(0, (float) $r['confidence'])) : null,
                'payload'      => json_encode($r, JSON_UNESCAPED_UNICODE),
                'occurred_at'  => $msg->sent_at,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
            $count++;
        }

        return $count;
    }

    /* -------------------------------------------------- helpers */

    public static function phoneFromJid(?string $jid): ?string
    {
        if (!$jid || !str_contains($jid, '@')) {
            return null;
        }
        $local = explode('@', $jid)[0];
        $local = explode(':', $local)[0];

        return ctype_digit($local) ? $local : null;
    }

    private static function clip($value, int $len): ?string
    {
        $value = is_string($value) ? trim($value) : $value;
        if ($value === null || $value === '' || !is_scalar($value)) {
            return null;
        }
        return mb_substr((string) $value, 0, $len);
    }

    private static function toTimestamp($value): string
    {
        try {
            return $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return now()->format('Y-m-d H:i:s');
        }
    }

    private static function toDate($value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function who($m): string
    {
        if (!empty($m->from_me)) {
            return '(me)';
        }
        return $m->sender_name ?: ($m->sender_phone ?: 'unknown');
    }

    private static function stamp($sentAt): string
    {
        try {
            return Carbon::parse($sentAt)->timezone('Asia/Kolkata')->format('Y-m-d H:i');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private static function oneLine(?string $text, int $len = 300): string
    {
        $text = preg_replace('/\s+/u', ' ', (string) $text);
        return mb_substr(trim($text), 0, $len);
    }
}
