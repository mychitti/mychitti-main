<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The header row behind one bulk WhatsApp send, and the bookkeeping a send needs once it stops
 * living in the browser.
 *
 * Bulk used to be driven by the composer: the page posted a batch of 25, waited, posted the next.
 * Closing the tab — or a laptop sleeping on a 17,000-person run — stopped the send halfway with no
 * way to pick it up. The batches now run on the queue instead, which means something has to hold
 * what the browser used to hold: what was asked for, how far it has got, and whether a worker is
 * on it right now. That is this table.
 *
 * Recipients themselves stay in wa_bulk_sends, one claimed row each, exactly as before — progress
 * is counted off those rows rather than tallied here, so a count can never drift from what it
 * describes. store_id follows the same convention too: the store's own id for a vendor run, 0 for
 * a platform run (Admin\WhatsAppBulkController::PLATFORM_SCOPE), so the two histories can never
 * read each other.
 */
class WhatsAppBulkRun
{
    /** Created and dispatched; recipients not claimed yet. */
    const STATUS_QUEUED = 'queued';
    /** Recipients claimed; a worker is walking them. */
    const STATUS_RUNNING = 'running';
    /** Asked to stop — the pass that owns the run ends after its current chunk. */
    const STATUS_CANCELLING = 'cancelling';
    /** Nobody left to message. */
    const STATUS_DONE = 'done';
    /** Ended early: cancelled, or the wallet ran dry. The reason is in `message`. */
    const STATUS_STOPPED = 'stopped';
    /** Gave up after MAX_ATTEMPTS resumes. */
    const STATUS_FAILED = 'failed';

    /** A run in one of these is over; nothing will pick it up again. */
    const CLOSED = [self::STATUS_DONE, self::STATUS_STOPPED, self::STATUS_FAILED];

    /** Platform (MyChitti-number) runs, which belong to no store. */
    const PLATFORM_SCOPE = 0;

    /**
     * Delivery-log context for platform sends. Deliberately not 'nearby': that context is what
     * WhatsAppService::nearbyCappedPhones() counts to hold every vendor in a city to four
     * messages per person per month, and platform announcements must not eat a vendor's quota.
     */
    const PLATFORM_CONTEXT = 'admin bulk';

    /**
     * How long a pass owns a run before the sweeper may hand it to a fresh worker. Renewed on
     * every chunk, so this is the gap that has to pass with NO progress at all — a killed worker,
     * not a slow one.
     */
    const LOCK_MINUTES = 10;

    /**
     * How many times a STALLED run is resumed before it is called failed.
     *
     * Not a count of passes: a long run is dozens of them, and every one that finishes a chunk
     * resets this to zero. It only climbs when a run has to be rescued without having moved.
     */
    const MAX_ATTEMPTS = 5;

    public static function ensureTable(): void
    {
        if (Schema::hasTable('wa_bulk_runs')) {
            return;
        }

        DB::statement("CREATE TABLE `wa_bulk_runs` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `store_id` BIGINT NOT NULL,
            `run_id` VARCHAR(40) NOT NULL,
            `scope` VARCHAR(10) NOT NULL DEFAULT 'vendor',
            `template` VARCHAR(190) NULL,
            `language` VARCHAR(20) NULL,
            `audience` VARCHAR(20) NULL,
            `requested` INT NOT NULL DEFAULT 0,
            `total` INT NOT NULL DEFAULT 0,
            `blocked` INT NOT NULL DEFAULT 0,
            `status` VARCHAR(20) NOT NULL DEFAULT 'queued',
            `message` VARCHAR(255) NULL,
            `payload` LONGTEXT NULL,
            `attempts` INT NOT NULL DEFAULT 0,
            `lock_token` VARCHAR(40) NULL,
            `locked_until` TIMESTAMP NULL,
            `started_at` TIMESTAMP NULL,
            `finished_at` TIMESTAMP NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `wabr_run` (`run_id`),
            KEY `wabr_store` (`store_id`, `id`),
            KEY `wabr_sweep` (`status`, `locked_until`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /** Record a run the moment it is accepted, before the job that will carry it out is queued. */
    public static function open(int $storeId, string $runId, array $spec, int $requested, array $meta = []): void
    {
        static::ensureTable();

        DB::table('wa_bulk_runs')->insert([
            'store_id'   => $storeId,
            'run_id'     => $runId,
            'scope'      => $meta['scope'] ?? 'vendor',
            'template'   => isset($meta['template']) ? mb_substr((string) $meta['template'], 0, 190) : null,
            'language'   => isset($meta['language']) ? mb_substr((string) $meta['language'], 0, 20) : null,
            'audience'   => isset($meta['audience']) ? mb_substr((string) $meta['audience'], 0, 20) : null,
            'requested'  => $requested,
            'total'      => 0,
            'status'     => self::STATUS_QUEUED,
            'payload'    => json_encode($spec),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function find(string $runId, ?int $storeId = null)
    {
        if (!Schema::hasTable('wa_bulk_runs')) {
            return null;
        }

        $query = DB::table('wa_bulk_runs')->where('run_id', $runId);
        if ($storeId !== null) {
            $query->where('store_id', $storeId);
        }

        return $query->first();
    }

    /** The run this store has on the go, if any — what the composer reattaches to on a reload. */
    public static function current(int $storeId)
    {
        if (!Schema::hasTable('wa_bulk_runs')) {
            return null;
        }

        return DB::table('wa_bulk_runs')
            ->where('store_id', $storeId)
            ->whereNotIn('status', self::CLOSED)
            ->orderByDesc('id')
            ->first();
    }

    public static function spec($run): array
    {
        return (array) json_decode((string) ($run->payload ?? ''), true) ?: [];
    }

    /**
     * Take ownership of a run for one pass.
     *
     * An atomic UPDATE rather than a read-then-write: the sweeper and a re-dispatched pass can
     * reach for the same run in the same second, and two workers walking one run would send the
     * same chunk twice. Whoever changes the row owns it; everyone else backs off.
     */
    public static function acquire(string $runId): ?string
    {
        $token = (string) Str::uuid();

        $taken = DB::table('wa_bulk_runs')
            ->where('run_id', $runId)
            ->whereNotIn('status', self::CLOSED)
            ->where(fn($q) => $q->whereNull('locked_until')->orWhere('locked_until', '<', now()))
            ->update([
                'lock_token'   => $token,
                'locked_until' => now()->addMinutes(self::LOCK_MINUTES),
                // Laravel's now(), not MySQL's NOW(): the app runs on Asia/Kolkata and the
                // database on UTC, so the raw form stamped this row 5.5 hours behind every
                // other timestamp on it and made a run look like it started before it existed.
                'started_at'   => DB::raw('COALESCE(`started_at`, ' . DB::getPdo()->quote(now()->toDateTimeString()) . ')'),
                'updated_at'   => now(),
            ]);

        return $taken ? $token : null;
    }

    /**
     * Hold the run for another window and show the sweeper this pass is still moving.
     *
     * Called ahead of each chunk, and reaching another chunk is the only proof a run is healthy —
     * so the rescue counter goes back to zero here. Without that, a long-but-fine run would eventually be
     * called failed for having been rescued once, hours earlier.
     */
    public static function renew(string $runId, string $token): bool
    {
        return (bool) DB::table('wa_bulk_runs')
            ->where('run_id', $runId)
            ->where('lock_token', $token)
            ->update([
                'locked_until' => now()->addMinutes(self::LOCK_MINUTES),
                'attempts'     => 0,
                'updated_at'   => now(),
            ]);
    }

    /**
     * Put a chunk beyond the reach of any other pass before its messages go out.
     *
     * The lock on the run is the real guard, but it is checked between chunks; this closes the
     * seam in the middle of one. A pass whose lock expired while it was working stops at its next
     * renew(), and until then the rows it is holding are not offered to the pass that took over.
     */
    public static function markSending(array $ids): void
    {
        if ($ids) {
            DB::table('wa_bulk_sends')->whereIn('id', $ids)->where('status', 'queued')
                ->update(['status' => 'sending', 'updated_at' => now()]);
        }
    }

    /**
     * Hand back rows a dead pass was holding. Called when a pass takes the run over: whoever held
     * these is by definition no longer entitled to the run, so their unfinished chunk goes back
     * into the queue rather than being lost.
     */
    public static function reclaim(string $runId): void
    {
        DB::table('wa_bulk_sends')->where('run_id', $runId)->where('status', 'sending')
            ->update(['status' => 'queued', 'updated_at' => now()]);
    }

    /** Hand the run back without closing it — the next pass, or the sweeper, carries on. */
    public static function release(string $runId, string $token): void
    {
        DB::table('wa_bulk_runs')
            ->where('run_id', $runId)
            ->where('lock_token', $token)
            ->update(['lock_token' => null, 'locked_until' => null, 'updated_at' => now()]);
    }

    public static function markRunning(string $runId, int $total, int $blocked = 0): void
    {
        DB::table('wa_bulk_runs')->where('run_id', $runId)->update([
            'status'     => self::STATUS_RUNNING,
            'total'      => $total,
            'blocked'    => $blocked,
            'updated_at' => now(),
        ]);
    }

    /**
     * Close the run for good and let go of the lock in the same write.
     *
     * A run that is already closed stays as it was closed: whoever got there first knew why it
     * ended, and a second caller arriving with a vaguer reason must not overwrite that.
     */
    public static function close(string $runId, string $status, ?string $message = null): void
    {
        DB::table('wa_bulk_runs')->where('run_id', $runId)->whereNotIn('status', self::CLOSED)->update([
            'status'       => $status,
            'message'      => $message ? mb_substr($message, 0, 255) : null,
            'lock_token'   => null,
            'locked_until' => null,
            'finished_at'  => now(),
            'updated_at'   => now(),
        ]);
    }

    public static function note(string $runId, string $message): void
    {
        DB::table('wa_bulk_runs')->where('run_id', $runId)
            ->update(['message' => mb_substr($message, 0, 255), 'updated_at' => now()]);
    }

    /**
     * Ask a run to stop.
     *
     * If a pass holds the lock it is told to stop and closes itself after its current chunk —
     * killing it mid-message would leave a claimed row nobody ever resolves. With no live pass
     * there is nobody to tell, so the run is closed here and now.
     */
    public static function requestStop(string $runId, int $storeId, string $reason = 'Stopped.'): bool
    {
        $run = static::find($runId, $storeId);
        if (!$run || in_array($run->status, self::CLOSED, true)) {
            return false;
        }

        $live = $run->locked_until && $run->locked_until > now()->toDateTimeString();

        if ($live) {
            DB::table('wa_bulk_runs')->where('run_id', $runId)->update([
                'status'     => self::STATUS_CANCELLING,
                'message'    => $reason,
                'updated_at' => now(),
            ]);
        } else {
            static::close($runId, self::STATUS_STOPPED, $reason);
        }

        return true;
    }

    /** Whether the pass currently walking this run has been asked to put it down. */
    public static function stopping(string $runId): bool
    {
        $status = (string) DB::table('wa_bulk_runs')->where('run_id', $runId)->value('status');

        return $status === self::STATUS_CANCELLING || in_array($status, self::CLOSED, true);
    }

    /**
     * How the run is going, counted off the recipient rows themselves.
     *
     * One grouped read on (run_id, status) — the composer polls this every few seconds, and a
     * tally kept on the run row would only be a second copy of the same numbers waiting to drift.
     */
    public static function counts(string $runId): array
    {
        $out = ['queued' => 0, 'sending' => 0, 'sent' => 0, 'failed' => 0];

        if (!Schema::hasTable('wa_bulk_sends')) {
            return $out;
        }

        $rows = DB::table('wa_bulk_sends')->where('run_id', $runId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();

        foreach ($rows as $row) {
            $out[$row->status] = (int) $row->total;
        }

        return $out;
    }

    /** What the composer polls for: the run row, live counts, and why anything failed. */
    public static function progress(string $runId, ?int $storeId, bool $mask = false): ?array
    {
        $run = static::find($runId, $storeId);
        if (!$run) {
            return null;
        }

        $counts = static::counts($runId);
        $done = $counts['sent'] + $counts['failed'];
        // 'sending' is a chunk in flight — still waiting as far as anyone reading this is
        // concerned, and it must be counted somewhere or a stopped run looks short of recipients.
        $pending = $counts['queued'] + $counts['sending'];

        // Claimed rows are what actually exists to send; until seeding finishes, the number the
        // composer asked for is the only figure there is.
        $total = max((int) $run->total, $done + $pending) ?: (int) $run->requested;

        $failures = $counts['failed']
            ? DB::table('wa_bulk_sends')->where('run_id', $runId)->where('status', 'failed')
                ->orderByDesc('id')->limit(25)->get(['name', 'phone', 'error'])
                ->map(fn($r) => [
                    'name'  => $r->name ?: '—',
                    'phone' => $mask ? static::mask($r->phone) : $r->phone,
                    'error' => $r->error ?: 'Not delivered.',
                ])->all()
            : [];

        return [
            'run_id'   => $run->run_id,
            'status'   => $run->status,
            'message'  => $run->message,
            'total'    => $total,
            'sent'     => $counts['sent'],
            'failed'   => $counts['failed'],
            'pending'  => $pending,
            // Everyone asked for who never became a row: already messaged in this run, or a
            // number that appeared in both audiences and is only ever messaged once.
            'skipped'  => max(0, (int) $run->requested - (int) $run->total - (int) $run->blocked),
            'blocked'  => (int) $run->blocked,
            'finished' => in_array($run->status, self::CLOSED, true),
            'failures' => $failures,
        ];
    }

    /**
     * Claim a whole audience in one go, at the head of the run.
     *
     * The browser used to claim as it went, batch by batch. A queued run claims everybody up
     * front instead: the rows ARE the work list, so a pass that dies leaves the remainder sitting
     * in the table for the next one to find, and progress becomes a count rather than something
     * only the open tab knew. insertOrIgnore, so the unique key quietly absorbs both a re-seed and
     * a number that sits in two audiences at once.
     *
     * @param  iterable  $people  objects carrying id (nullable), name, phone
     */
    public static function seed(int $storeId, string $runId, iterable $people, string $audience, ?string $template, array $blockedSuffixes = []): array
    {
        $seeded = 0;
        $blocked = 0;
        $rows = [];

        foreach ($people as $person) {
            $phone = trim((string) ($person->phone ?? ''));
            $phone10 = static::phone10($phone);
            if ($phone10 === '') {
                continue;
            }

            // The audience queries already drop these; a pasted list has not been through them,
            // and someone who replied STOP must not be reachable again by typing their number in.
            if ($blockedSuffixes && in_array($phone10, $blockedSuffixes, true)) {
                $blocked++;
                continue;
            }

            $rows[] = static::claimRow(
                $storeId, $runId, $person->id ?? null, $phone,
                trim((string) ($person->name ?? '')) ?: 'Customer', $audience, $template
            );

            if (count($rows) >= 500) {
                $seeded += static::insertClaims($rows);
                $rows = [];
            }
        }

        if ($rows) {
            $seeded += static::insertClaims($rows);
        }

        return ['seeded' => $seeded, 'blocked' => $blocked];
    }

    protected static function insertClaims(array $rows): int
    {
        try {
            return (int) DB::table('wa_bulk_sends')->insertOrIgnore($rows);
        } catch (\Throwable $e) {
            Log::error('WA bulk seed failed: ' . $e->getMessage());
            return 0;
        }
    }

    protected static function claimRow(int $storeId, string $runId, $clientId, string $phone, string $name, string $audience, ?string $template): array
    {
        return [
            'store_id'   => $storeId,
            'run_id'     => $runId,
            'phone10'    => static::phone10($phone),
            'phone'      => $phone,
            'name'       => mb_substr($name, 0, 190),
            'client_id'  => $clientId ?: null,
            'audience'   => $audience,
            'template'   => $template ? mb_substr($template, 0, 190) : null,
            'status'     => 'queued',
            'sent_at'    => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /** The next people waiting to be messaged in this run. */
    public static function pending(string $runId, int $limit)
    {
        return DB::table('wa_bulk_sends')
            ->where('run_id', $runId)
            ->where('status', 'queued')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'client_id', 'phone', 'name', 'audience']);
    }

    /** Meta rejects newlines and runs of spaces inside a parameter, and caps its length. */
    public static function sanitizeParam(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim(mb_substr($value, 0, 900));
    }

    /** Put one recipient's parameter values into the template body, for the record kept of it. */
    public static function fillBody(string $body, array $values): string
    {
        $tokens = [];
        foreach ($values as $key => $value) {
            $tokens['{{' . $key . '}}'] = $value;
            $tokens['{{ ' . $key . ' }}'] = $value;
        }

        return strtr($body, $tokens);
    }

    /**
     * Build the body parameters for one recipient out of the composer's parameter list.
     *
     * {name} / {phone} are substituted inside whatever was typed; the named slots
     * ({{customer_name}}, {{customer_phone}}) are filled outright. Either way the number only ever
     * lands in the message that number receives, so it stays hidden from the sender even when the
     * audience is anonymous.
     *
     * @return array{0: array, 1: array}  [parameters, filled values]
     */
    public static function bodyParameters(array $rawParams, string $name, string $phone): array
    {
        $auto = ['customer_name' => $name, 'customer_phone' => $phone];
        $tokens = [
            '{name}'           => $name,
            '{customer_name}'  => $name,
            '{phone}'          => $phone,
            '{customer_phone}' => $phone,
        ];

        $parameters = [];
        $filled = [];

        foreach ($rawParams as $i => $raw) {
            // A slot is {key, value} for named templates; older callers send bare strings, which
            // are positional in the order they arrive.
            $key   = trim(is_array($raw) ? (string) ($raw['key'] ?? '') : '') ?: (string) ($i + 1);
            $value = is_array($raw) ? (string) ($raw['value'] ?? '') : (string) $raw;

            $value = array_key_exists($key, $auto) ? $auto[$key] : strtr($value, $tokens);

            $clean = static::sanitizeParam($value);
            $filled[$key] = $clean;
            $parameters[] = WhatsAppService::bodyParameter($key, $clean);
        }

        return [$parameters, $filled];
    }

    /** Write back how one recipient's message landed. */
    public static function record(int $sendId, array $res, ?string $templateBody, array $filled, string $language): void
    {
        DB::table('wa_bulk_sends')->where('id', $sendId)->update([
            'wamid'      => $res['id'] ?? null,
            'status'     => !empty($res['success']) ? 'sent' : 'failed',
            'error'      => $res['error'] ?? null,
            // This recipient's own copy — {{customer_name}} carries their name, not the next
            // person's, so the history reads back exactly what each number received.
            'body'       => $templateBody ? mb_substr(static::fillBody($templateBody, $filled), 0, 2000) : null,
            'language'   => mb_substr($language, 0, 20),
            'updated_at' => now(),
        ]);
    }

    public static function phone10(?string $phone): string
    {
        return substr(preg_replace('/[^0-9]/', '', (string) $phone) ?? '', -10);
    }

    protected static function mask(?string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone) ?? '';

        return strlen($digits) < 4 ? '••••' : '••••••' . substr($digits, -4);
    }
}
