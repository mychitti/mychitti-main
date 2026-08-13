<?php

namespace App\Traits;

use App\CentralLogics\Helpers;
use App\Models\UserNotificationPreference;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Who a store may message on WhatsApp, and which of its approved templates can be sent.
 *
 * Extracted from the vendor WhatsApp controller so the one-off bulk composer and the drip
 * campaign builder pick their audience by exactly the same rules. Opt-outs, the nearby-offers
 * preference, the 30-day frequency cap and the last-10-digit dedupe are the reason this lives in
 * one place: a second copy of these queries would drift, and the copy that drifts is the one that
 * messages someone who asked to be left alone.
 */
trait WhatsAppAudience
{
    /**
     * The store's own client book — people it already has a relationship with.
     *
     * nearby_offers deliberately does not apply here: that preference is about businesses the
     * customer has NOT dealt with, and this is the one store they have. Only an explicit STOP
     * removes someone.
     */
    protected function clientQuery(int $storeId)
    {
        return $this->excludeOptedOut(
            DB::table('store_customers')
                ->where('store_id', $storeId)
                ->whereNotNull('phone')
                ->where('phone', '!=', ''),
            $storeId
        );
    }

    /**
     * Everyone else reachable in this store's city, as one list: MyChitti account holders plus
     * the client books of the other stores in the city (and the orphan rows whose store_id was
     * never saved, which used to be reachable by nobody).
     *
     * Presented to the vendor as a single "MyChitti users" count. Numbers are never shown for
     * this audience and results come back masked — the vendor addresses it by size, not by
     * picking people out of it.
     *
     * Deduped on the last 10 digits of the phone across BOTH sources, so an account holder who
     * also sits in another store's book counts once and is messaged once. The store's own
     * customers are subtracted so this and clientQuery() never overlap.
     */
    protected function outreachQuery(int $storeId)
    {
        $ownPhones = $this->ownClientPhones($storeId);

        $users = $this->platformUserQuery($storeId)
            ->select(
                DB::raw("TRIM(CONCAT(COALESCE(`users`.`f_name`, ''), ' ', COALESCE(`users`.`l_name`, ''))) as name"),
                'users.phone as phone'
            );

        $clients = $this->otherStoreClientQuery($storeId)
            ->select('f_name as name', 'phone as phone');

        if (!empty($ownPhones)) {
            $users->whereNotIn(DB::raw($this->phone10Sql('users.phone')), $ownPhones);
            $clients->whereNotIn(DB::raw($this->phone10Sql('phone')), $ownPhones);
        }

        $phone10 = $this->phone10Sql('t.phone');

        $query = DB::query()
            ->fromSub($users->unionAll($clients), 't')
            ->selectRaw("MIN(t.name) as name, MIN(t.phone) as phone")
            ->groupByRaw($phone10);

        // Same 30-day cap the shared pool has always had. It is what stops every vendor in a
        // city blasting the same few thousand people until they all opt out.
        $capped = $this->nearbyCappedPhones();
        if (!empty($capped)) {
            $query->whereNotIn(DB::raw($phone10), $capped);
        }

        // Anyone this store itself reached recently. The cap above is about every vendor
        // together; this is one vendor walking forward through the pool rather than restarting
        // at the same people. Without it, "reach 500 users" meant the same 500 lowest-numbered
        // phones on every send — the rest of the audience was unreachable until the front of the
        // list burnt through the shared cap.
        //
        // A correlated subquery, not a list pulled into PHP: the pool runs to tens of thousands
        // and this query is also what the displayed audience count is built from.
        if (Schema::hasTable('whatsapp_messages')) {
            $reached = $this->collatedPhone($this->phone10Sql('m.recipient'));
            $candidate = $this->collatedPhone($phone10);

            $query->whereNotExists(function ($q) use ($storeId, $reached, $candidate) {
                $q->select(DB::raw(1))->from('whatsapp_messages as m')
                    ->where('m.store_id', $storeId)
                    ->where('m.direction', 'out')
                    ->whereIn('m.context', WhatsAppService::PLATFORM_AUDIENCE_CONTEXTS)
                    ->where('m.sent_at', '>=', now()->subDays(WhatsAppService::PLATFORM_ROTATION_DAYS))
                    ->whereRaw("{$reached} = {$candidate}");
            });
        }

        return $query;
    }

    /**
     * How many distinct people outreachQuery() would reach.
     *
     * Cached, because this is a headline figure on pages the vendor opens and re-opens, and the
     * query behind it is the most expensive one either the bulk composer or the campaign builder
     * runs: a union of the platform user table and every other store's book in the city, grouped
     * on a computed phone suffix that no index covers. There is no reason to pay for it again on
     * every page view.
     *
     * Display only. Nothing is ever sent to a cached figure — bulkSend() and the campaign runner
     * re-run outreachQuery() live, so a stale count can at worst offer a number that comes back
     * a little smaller when the batch is actually built.
     */
    protected function outreachCount(int $storeId): int
    {
        // 10 minutes: long enough that reopening the page is free, short enough that the figure
        // still tracks the pool as other vendors send into it.
        return (int) Cache::remember(
            $this->outreachCountKey($storeId),
            600,
            fn() => DB::query()->fromSub($this->outreachQuery($storeId), 'c')->count()
        );
    }

    /** Drop the cached figure after a send — the rotation window has just moved. */
    protected function forgetOutreachCount(int $storeId): void
    {
        Cache::forget($this->outreachCountKey($storeId));
    }

    protected function outreachCountKey(int $storeId): string
    {
        return 'wa_outreach_count_' . $storeId;
    }

    /** Client books of the OTHER stores in this city, plus rows with no store_id at all. */
    protected function otherStoreClientQuery(int $storeId)
    {
        $otherStoreIds = array_values(array_diff($this->storeIdsInCity($storeId), [$storeId]));

        // Resolved to a list of store ids rather than joined to `stores`: that table has its
        // own `phone` column, which would make the opt-out filters below ambiguous.
        $query = DB::table('store_customers')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where(function ($w) use ($otherStoreIds) {
                $w->whereNull('store_id')->orWhere('store_id', 0);
                if (!empty($otherStoreIds)) {
                    $w->orWhereIn('store_id', $otherStoreIds);
                }
            });

        // Phone-matched rather than joined: store_customers has no user_id, and the only thing
        // tying a vendor's client row to a MyChitti account is the number.
        $blocked = $this->offersOptedOutPhones();
        if (!empty($blocked)) {
            $query->whereNotIn(DB::raw($this->phone10Sql('phone')), $blocked);
        }

        return $this->excludeOptedOut($query, $storeId);
    }

    /** Last-10-digit forms of the numbers already in this store's own book. */
    protected function ownClientPhones(int $storeId): array
    {
        $phones = DB::table('store_customers')
            ->where('store_id', $storeId)
            ->whereNotNull('phone')
            ->pluck('phone');

        return array_values(array_unique(array_filter($phones
            ->map(fn($p) => substr(preg_replace('/[^0-9]/', '', (string) $p) ?? '', -10))
            ->all())));
    }

    /**
     * Stores counting as "this store's city" — those sharing its zone or sitting in a zone
     * nested inside it, plus stores that never set a zone at all.
     */
    protected function storeIdsInCity(int $storeId): array
    {
        $zoneIds = Helpers::zone_with_descendants(
            DB::table('stores')->where('id', $storeId)->value('zone_id')
        );

        return DB::table('stores')
            ->where(fn($q) => $this->inZoneOrUnknown($q, 'zone_id', $zoneIds))
            ->pluck('id')
            ->all();
    }

    /** Stored numbers vary between "+91 98…", "098…" and "98…" — compare the last 10 digits. */
    protected function phone10Sql(string $column): string
    {
        $quoted = implode('.', array_map(fn($p) => "`$p`", explode('.', $column)));
        return "RIGHT(REPLACE(REPLACE(REPLACE($quoted, ' ', ''), '-', ''), '+', ''), 10)";
    }

    /**
     * Pin a string expression to one collation so it can be compared with another column.
     *
     * Comparing a phone column against a bound list needs none of this — the literal takes
     * whatever collation the column has. Comparing two *columns* does: whatsapp_messages.recipient
     * came out utf8mb4_general_ci while users.phone is utf8mb4_unicode_ci, and MySQL refuses the
     * '=' outright ("Illegal mix of collations"). Which table holds which collation varies per
     * install, so both sides are converted rather than trusting either one.
     */
    protected function collatedPhone(string $expression): string
    {
        return 'CONVERT(' . $expression . ' USING utf8mb4) COLLATE utf8mb4_unicode_ci';
    }

    /**
     * Last-10-digit forms of numbers whose MyChitti account has nearby offers turned off.
     * Only accounts with a saved preference row count — never having set one is not a refusal.
     */
    protected function offersOptedOutPhones(): array
    {
        try {
            UserNotificationPreference::ensureTable();
            $phones = DB::table('users')
                ->join('user_notification_prefs as p', 'p.user_id', '=', 'users.id')
                ->where('p.nearby_offers', 0)
                ->whereNotNull('users.phone')
                ->pluck('users.phone');
        } catch (\Throwable $e) {
            Log::warning('offers opt-out lookup failed: ' . $e->getMessage());
            return [];
        }

        return array_values(array_unique(array_filter($phones
            ->map(fn($p) => substr(preg_replace('/[^0-9]/', '', (string) $p) ?? '', -10))
            ->all())));
    }

    /**
     * Last-10-digit forms of numbers that already hit the pool's 30-day cap.
     *
     * The query itself lives on WhatsAppService because a drip campaign has to re-apply the cap
     * between its own steps — building the audience once is not enough when the series spans days.
     */
    protected function nearbyCappedPhones(): array
    {
        return WhatsAppService::nearbyCappedPhones();
    }

    /**
     * Drop anyone who has opted out of marketing from this store (or platform-wide).
     * Matched on the last 10 digits because stored numbers vary between "+91 98…",
     * "098…" and "98…" — the opt-out table holds the normalized form.
     */
    protected function excludeOptedOut($query, int $storeId, string $phoneColumn = 'phone')
    {
        $suffixes = array_values(array_unique(array_filter(array_map(
            fn($p) => substr(preg_replace('/[^0-9]/', '', (string) $p) ?? '', -10),
            WhatsAppService::optedOutPhones($storeId)
        ))));

        if (empty($suffixes)) {
            return $query;
        }

        return $query->whereNotIn(DB::raw($this->phone10Sql($phoneColumn)), $suffixes);
    }

    /**
     * MyChitti users in this store's OWN zone who are reachable on WhatsApp.
     *
     * Matches users.zone_id, which is populated at registration, on address add/update and on
     * service-request creation, and was backfilled for existing accounts.
     *
     * Matched against the store's zone AND every zone inside it, because zones nest: a store
     * registered in "India" or "Andhra Pradesh" must reach the Tirupati customers within it,
     * not just the few tagged with that exact broad zone.
     *
     * Users whose zone was never resolved are included too — see inZoneOrUnknown().
     *
     * Gated on nearby_offers: a saved preference of 0 removes them, no preference row at all
     * does not. Feeds outreachQuery() rather than being an audience on its own.
     */
    protected function platformUserQuery(int $storeId)
    {
        $zoneId = DB::table('stores')->where('id', $storeId)->value('zone_id');
        $zoneIds = Helpers::zone_with_descendants($zoneId);

        $query = DB::table('users')
            ->where(fn($q) => $this->inZoneOrUnknown($q, 'users.zone_id', $zoneIds))
            ->whereNotNull('users.phone')
            ->where('users.phone', '!=', '')
            ->select('users.*');

        // leftJoin, not a phone match: these rows have a real user_id to key on. Nearby offers
        // are on by default, so most accounts have no preference row and an inner join would
        // cut the audience to the handful who once saved their settings.
        try {
            UserNotificationPreference::ensureTable();
            $query->leftJoin('user_notification_prefs as p', 'p.user_id', '=', 'users.id')
                ->where(fn($q) => $q->whereNull('p.nearby_offers')->orWhere('p.nearby_offers', 1))
                // Staying in the pool never overrides turning WhatsApp off entirely.
                ->where(fn($q) => $q->whereNull('p.whatsapp')->orWhere('p.whatsapp', 1));
        } catch (\Throwable $e) {
            Log::warning('offers preference unavailable: ' . $e->getMessage());
        }

        return $this->excludeOptedOut($query, $storeId, 'users.phone');
    }

    /**
     * "Same city, or no city on record."
     *
     * users.zone_id is only written when the app actually learns where someone is — signup
     * with location, an address, a service request, an order. Plenty of accounts never hit
     * any of those and sit NULL (or 0, from an API path that cast a missing header), and
     * excluding them made most of the customer base unreachable. They are treated as
     * potentially local rather than definitely not.
     *
     * With no zone list at all — a store that never set its own zone — only the unknowns
     * qualify, since there is no city to match against.
     */
    protected function inZoneOrUnknown($query, string $column, array $zoneIds)
    {
        $query->whereNull($column)->orWhere($column, 0);

        if (!empty($zoneIds)) {
            $query->orWhereIn($column, $zoneIds);
        }

        return $query;
    }

    /**
     * Reduce Meta's template payload to what the bulk composer needs.
     * Normally only APPROVED templates are listed (see BULK_SHOW_UNAPPROVED), and only BODY
     * variables are supported — a template with a variable header or a dynamic URL button needs
     * parameters this UI doesn't collect, so it is listed as unsupported instead of failing at
     * send time.
     */
    protected function bulkTemplateOptions(array $data): array
    {
        $out = [];
        foreach ($data as $tpl) {
            $status = strtoupper((string) data_get($tpl, 'status')) ?: 'UNKNOWN';

            if (!WhatsAppService::BULK_SHOW_UNAPPROVED && $status !== 'APPROVED') {
                continue;
            }

            $body = '';
            $unsupported = null;
            $quickReplies = [];
            $headerFormat = null;
            foreach ((array) data_get($tpl, 'components', []) as $c) {
                $type = strtoupper((string) data_get($c, 'type'));
                if ($type === 'BODY') {
                    $body = (string) data_get($c, 'text', '');
                } elseif ($type === 'HEADER') {
                    $headerFormat = strtoupper((string) data_get($c, 'format', 'TEXT')) ?: 'TEXT';
                    // A media header is supported — the composer asks for the file. Only a TEXT
                    // header with a variable in it needs parameters this UI cannot collect.
                    if ($headerFormat === 'TEXT' && str_contains((string) data_get($c, 'text', ''), '{{')) {
                        $unsupported = 'has a variable in its header';
                    }
                } elseif ($type === 'BUTTONS') {
                    foreach ((array) data_get($c, 'buttons', []) as $b) {
                        if (str_contains((string) data_get($b, 'url', ''), '{{')) {
                            $unsupported = 'has a dynamic button URL';
                        }
                        // Quick-reply labels are what a campaign series branches on — the tapped
                        // label is the whole answer, so the builder has to be able to show them.
                        if (strtoupper((string) data_get($b, 'type')) === 'QUICK_REPLY') {
                            $quickReplies[] = (string) data_get($b, 'text');
                        }
                    }
                }
            }

            // Every slot the body needs, in send order. Named slots the platform knows how to
            // fill are marked auto — the composer shows them as filled-per-recipient instead of
            // asking the vendor for a value.
            $vars = [];
            foreach (WhatsAppService::namedVariables($body) as $named) {
                $known = WhatsAppService::TEMPLATE_VARIABLES[$named] ?? null;
                $vars[] = [
                    'key'   => $named,
                    'label' => $known['label'] ?? ucfirst(str_replace('_', ' ', $named)),
                    'auto'  => $known !== null,
                ];
            }
            if (empty($vars)) {
                $count = WhatsAppService::positionalCount($body);
                for ($n = 1; $n <= $count; $n++) {
                    $vars[] = ['key' => (string) $n, 'label' => 'Variable ' . $n, 'auto' => false];
                }
            } elseif (WhatsAppService::positionalCount($body) > 0) {
                // Meta will not have approved this, but a hand-edited body could still reach us.
                $unsupported = 'mixes named and numbered variables';
            }

            $out[] = [
                'name'        => data_get($tpl, 'name'),
                'language'    => data_get($tpl, 'language', 'en_US'),
                'category'    => data_get($tpl, 'category'),
                'body'        => $body,
                'vars'        => $vars,
                'var_count'   => count($vars),
                'buttons'     => $quickReplies,
                'unsupported' => $unsupported,
                'status'      => $status,
                // TEXT / IMAGE / VIDEO / DOCUMENT / null. The composer asks for a file when this
                // is a media format — without one the send fails with Meta error 132012.
                'header'      => $headerFormat,
                'needs_media' => in_array($headerFormat, WhatsAppService::MEDIA_HEADERS, true),
            ];
        }

        usort($out, fn($a, $b) => strcmp((string) $a['name'], (string) $b['name']));
        return $out;
    }

    protected function maskPhone(?string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone) ?? '';
        return strlen($digits) < 4 ? '••••' : '••••••' . substr($digits, -4);
    }
}
