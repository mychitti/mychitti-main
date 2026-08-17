<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * One row per attempt to message a customer, whatever the outcome.
 *
 * The dedupe tables (wa_hmis_auto_sends, wa_invoice_sends) record what was claimed, not what
 * happened, and the commonest outcome by far — a skip, because a toggle is off or Meta has not
 * approved the template — never reached a table at all. It went to the PHP log, where no vendor
 * will ever read it, which is why "it just didn't send" was unanswerable without SSH.
 *
 * Deliberately not the source of truth for anything: nothing reads this back to decide whether to
 * send. It is a record for the vendor, so every write is best-effort and a logging failure must
 * never turn into a failed send.
 */
class MessageLog
{
    const TABLE = 'wa_message_log';

    const SENT    = 'sent';
    const SKIPPED = 'skipped';
    const FAILED  = 'failed';
    const QUEUED  = 'queued';

    /** Checked once per request rather than on every write. */
    protected static bool $ready = false;

    public static function ensureTable(): void
    {
        if (static::$ready) {
            return;
        }

        if (Schema::hasTable(self::TABLE)) {
            static::$ready = true;
            return;
        }

        DB::statement("CREATE TABLE `" . self::TABLE . "` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `store_id` BIGINT NOT NULL,
            `message_key` VARCHAR(60) NULL,
            `label` VARCHAR(120) NULL,
            `status` VARCHAR(10) NOT NULL,
            `reason` VARCHAR(255) NULL,
            `template` VARCHAR(120) NULL,
            `recipient` VARCHAR(120) NULL,
            `sent_to` VARCHAR(32) NULL,
            `record_type` VARCHAR(30) NULL,
            `record_id` BIGINT NULL,
            `automatic` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`),
            KEY `wml_store_time` (`store_id`, `created_at`),
            KEY `wml_store_status` (`store_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        static::$ready = true;
    }

    /**
     * Record an attempt. $reason is what the vendor needs to act on — "the invoice_ready template
     * is not approved", not "skipped" — so it is written for a skip and a failure alike.
     */
    public static function record(int $storeId, string $status, array $data = []): void
    {
        try {
            if (!$storeId) {
                return;
            }

            static::ensureTable();

            DB::table(self::TABLE)->insert([
                'store_id'    => $storeId,
                'message_key' => static::clip($data['key'] ?? null, 60),
                'label'       => static::clip($data['label'] ?? null, 120),
                'status'      => $status,
                'reason'      => static::clip($data['reason'] ?? null, 255),
                'template'    => static::clip($data['template'] ?? null, 120),
                'recipient'   => static::clip($data['recipient'] ?? null, 120),
                'sent_to'     => static::clip(static::maskPhone($data['to'] ?? null), 32),
                'record_type' => static::clip($data['record_type'] ?? null, 30),
                'record_id'   => isset($data['record_id']) ? (int) $data['record_id'] : null,
                'automatic'   => array_key_exists('automatic', $data) ? (int) (bool) $data['automatic'] : 1,
                'created_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            // A log that breaks a send is worse than no log.
            Log::warning('Message log write skipped: ' . $e->getMessage());
        }
    }

    public static function sent(int $storeId, array $data = []): void
    {
        static::record($storeId, self::SENT, $data);
    }

    public static function skipped(int $storeId, string $reason, array $data = []): void
    {
        static::record($storeId, self::SKIPPED, $data + ['reason' => $reason]);
    }

    public static function failed(int $storeId, string $reason, array $data = []): void
    {
        static::record($storeId, self::FAILED, $data + ['reason' => $reason]);
    }

    public static function queued(int $storeId, string $reason, array $data = []): void
    {
        static::record($storeId, self::QUEUED, $data + ['reason' => $reason]);
    }

    /** Recent attempts for the log screen, newest first. */
    public static function recent(int $storeId, array $filters = [], int $perPage = 30)
    {
        static::ensureTable();

        $query = DB::table(self::TABLE)->where('store_id', $storeId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['key'])) {
            $query->where('message_key', $filters['key']);
        }
        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('recipient', 'like', $term)
                    ->orWhere('sent_to', 'like', $term)
                    ->orWhere('label', 'like', $term)
                    ->orWhere('reason', 'like', $term);
            });
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    /** Counts per status over the last $days, for the summary strip. */
    public static function summary(int $storeId, int $days = 7): array
    {
        static::ensureTable();

        $rows = DB::table(self::TABLE)
            ->where('store_id', $storeId)
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('status')
            ->pluck(DB::raw('count(*)'), 'status')
            ->all();

        return [
            self::SENT    => (int) ($rows[self::SENT] ?? 0),
            self::SKIPPED => (int) ($rows[self::SKIPPED] ?? 0),
            self::FAILED  => (int) ($rows[self::FAILED] ?? 0),
            self::QUEUED  => (int) ($rows[self::QUEUED] ?? 0),
        ];
    }

    /** Distinct message types this store has actually logged, for the filter dropdown. */
    public static function keysUsed(int $storeId): array
    {
        static::ensureTable();

        return DB::table(self::TABLE)
            ->where('store_id', $storeId)
            ->whereNotNull('message_key')
            ->select('message_key', DB::raw('MAX(label) as label'))
            ->groupBy('message_key')
            ->orderBy('label')
            ->pluck('label', 'message_key')
            ->all();
    }

    protected static function clip($value, int $length): ?string
    {
        $value = is_string($value) ? trim($value) : $value;
        if ($value === null || $value === '') {
            return null;
        }
        return mb_substr((string) $value, 0, $length);
    }

    /** The log is read by staff, so the number is masked the same way the toasts mask it. */
    protected static function maskPhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }
        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';
        return strlen($digits) < 4 ? '••••' : '••••••' . substr($digits, -4);
    }
}
