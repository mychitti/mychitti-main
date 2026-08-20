<?php

namespace App\Traits;

use App\CentralLogics\Helpers;
use App\Models\UserNotificationPreference;
use App\Services\WhatsAppBulkRun;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Who the PLATFORM number may message, and which of its approved templates carry what.
 *
 * Lifted out of Admin\WhatsAppBulkController when bulk sending moved onto the queue: the composer
 * screen and SendPlatformBulkWhatsAppJob both have to build the same audience, and the one that
 * drifts is the one that messages somebody who asked to be left alone. WhatsAppAudience is the
 * vendor equivalent — a store's own book and the shared nearby pool. This is MyChitti's own.
 *
 * Requires WhatsAppAudience alongside it, for phone10Sql() and collatedPhone().
 */
trait PlatformWhatsAppAudience
{
    /** Most numbers the "already messaged recently" exclusion will hold in memory at once. */
    protected function recentExclusionCap(): int
    {
        return 100000;
    }

    /**
     * The people a platform run is meant to reach.
     *
     * 'all' takes the next unclaimed people in the filtered audience rather than an offset page:
     * an offset restarts at zero every time, which is how a broken run re-messages whoever it
     * already reached. Exclusion by claim is what makes each read return the *next* people.
     */
    protected function platformRecipients(string $audience, string $mode, array $filters, array $ids, array $numbers, int $limit, string $runId)
    {
        if ($audience === 'manual') {
            return collect($numbers)
                ->map(fn($n) => (object) ['id' => null, 'name' => '', 'phone' => trim((string) $n)])
                ->filter(fn($r) => strlen(preg_replace('/[^0-9]/', '', $r->phone) ?? '') >= 10)
                ->values();
        }

        $query = $this->audienceQuery($audience, $filters);

        if ($mode === 'selected') {
            $column = $audience === 'vendors' ? 'stores.id' : 'users.id';

            return $query->whereIn($column, $ids)->get();
        }

        $phone10 = $this->phone10Sql($audience === 'vendors' ? 'stores.phone' : 'users.phone');
        // Column against column, so both sides are pinned to one collation — the claim table and
        // the contact tables can carry different ones. See WhatsAppAudience::collatedPhone().
        $claimed = $this->collatedPhone('b.`phone10`');
        $candidate = $this->collatedPhone($phone10);

        return $query
            ->whereNotExists(function ($q) use ($runId, $claimed, $candidate) {
                $q->select(DB::raw(1))->from('wa_bulk_sends as b')
                    ->where('b.run_id', $runId)
                    ->whereRaw("{$claimed} = {$candidate}");
            })
            ->orderByRaw($phone10)
            ->limit($limit)
            ->get();
    }

    /** Either audience as {id, name, phone}, already stripped of everyone who opted out. */
    protected function audienceQuery(string $audience, array $filters = [])
    {
        $zoneId = $filters['zone_id'] ?? null;
        $search = $filters['search'] ?? null;

        $query = $audience === 'vendors'
            ? $this->vendorQuery($zoneId, $search, $filters['status'] ?? 'active', $filters['category_id'] ?? null)
            : $this->customerQuery($zoneId, $search);

        $skipDays = (int) ($filters['skip_days'] ?? 0);
        if ($skipDays > 0) {
            $recent = $this->recentlyMessagedSuffixes($skipDays);
            if (!empty($recent)) {
                $column = $audience === 'vendors' ? 'stores.phone' : 'users.phone';
                $query->whereNotIn(DB::raw($this->phone10Sql($column)), $recent);
            }
        }

        return $query;
    }

    /**
     * Numbers a platform bulk send already reached inside the window.
     *
     * A bound list rather than a correlated NOT EXISTS: both sides of a column-to-column phone
     * comparison have to be wrapped in CONVERT/COLLATE, which makes every index useless and turns
     * an audience count into a per-candidate subquery. phone10 is stored already normalized, so a
     * list compares straight against the same suffix expression the opt-out filter uses.
     *
     * Cached for a minute because a long run asks per pass, and re-reading a month of sends over
     * and over is most of the work the run would do. Staleness inside that minute cannot cause a
     * double message: the per-run claim, not this, is the hard guarantee.
     *
     * Capped — this screen exists for sends big enough that a month of them should not be pulled
     * into memory whole. Past the cap the list is partial and someone may be offered again; the
     * claim still stops them being messaged twice inside one run.
     */
    protected function recentlyMessagedSuffixes(int $days): array
    {
        try {
            return Cache::remember('wa_admin_bulk_recent_' . $days, 60, function () use ($days) {
                return DB::table('wa_bulk_sends')
                    ->where('store_id', WhatsAppBulkRun::PLATFORM_SCOPE)
                    ->where('status', 'sent')
                    ->where('sent_at', '>=', now()->subDays($days))
                    ->distinct()
                    ->limit($this->recentExclusionCap())
                    ->pluck('phone10')
                    ->all();
            });
        } catch (\Throwable $e) {
            Log::warning('recent platform send lookup failed: ' . $e->getMessage());
            return [];
        }
    }

    /** Vendors, by the phone on their store record. */
    protected function vendorQuery(?int $zoneId = null, ?string $search = null, string $status = 'active', ?int $categoryId = null)
    {
        $query = DB::table('stores')
            ->whereNotNull('stores.phone')
            ->where('stores.phone', '!=', '')
            ->select('stores.id as id', 'stores.name as name', 'stores.phone as phone');

        // The trade the vendor signed up under. category_1 is the one that is actually filled —
        // 4,933 of 4,940 stores carry it, against 91 for category_2 and 3 for subcategories — so
        // it is what "the vendor's category" means in practice. Stored as a varchar holding a
        // category id, which MySQL compares to an integer happily enough.
        if ($categoryId) {
            $query->where('stores.category_1', $categoryId);
        }

        // Deactivated stores are still real businesses an admin may need to reach (an onboarding
        // nudge, a billing notice), so 'all' is offered — it is simply not the default.
        if ($status !== 'all') {
            $query->where('stores.status', 1);
        }

        if ($zoneId) {
            $query->whereIn('stores.zone_id', Helpers::zone_with_descendants($zoneId));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('stores.name', 'like', "%{$search}%")->orWhere('stores.phone', 'like', "%{$search}%");
            });
        }

        return $this->excludeOptOuts($query, 'stores.phone');
    }

    /**
     * MyChitti account holders.
     *
     * The WhatsApp channel preference is honoured here as well as through the opt-out list:
     * turning the dashboard toggle off writes a platform-wide opt-out row today, but accounts that
     * set it before that behaviour existed only have the preference to speak for them.
     */
    protected function customerQuery(?int $zoneId = null, ?string $search = null)
    {
        $query = DB::table('users')
            ->whereNotNull('users.phone')
            ->where('users.phone', '!=', '')
            ->select(
                'users.id as id',
                DB::raw("TRIM(CONCAT(COALESCE(`users`.`f_name`, ''), ' ', COALESCE(`users`.`l_name`, ''))) as name"),
                'users.phone as phone'
            );

        // leftJoin, not an inner one: WhatsApp is on by default and most accounts have no
        // preference row at all, which an inner join would read as a refusal.
        try {
            UserNotificationPreference::ensureTable();
            $query->leftJoin('user_notification_prefs as p', 'p.user_id', '=', 'users.id')
                ->where(fn($q) => $q->whereNull('p.whatsapp')->orWhere('p.whatsapp', 1));
        } catch (\Throwable $e) {
            Log::warning('WhatsApp preference lookup unavailable: ' . $e->getMessage());
        }

        if ($zoneId) {
            $query->whereIn('users.zone_id', Helpers::zone_with_descendants($zoneId));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.f_name', 'like', "%{$search}%")
                    ->orWhere('users.l_name', 'like', "%{$search}%")
                    ->orWhere('users.phone', 'like', "%{$search}%");
            });
        }

        return $this->excludeOptOuts($query, 'users.phone');
    }

    /** Drop everyone who has replied STOP or switched WhatsApp off platform-wide. */
    protected function excludeOptOuts($query, string $phoneColumn)
    {
        $suffixes = $this->optOutSuffixes();

        return empty($suffixes)
            ? $query
            : $query->whereNotIn(DB::raw($this->phone10Sql($phoneColumn)), $suffixes);
    }

    /** Last-10-digit forms of every platform-wide opt-out. */
    protected function optOutSuffixes(): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn($p) => substr(preg_replace('/[^0-9]/', '', (string) $p) ?? '', -10),
            WhatsAppService::optedOutPhones(null)
        ))));
    }

    /**
     * One template as Meta holds it on the PLATFORM's account.
     *
     * WhatsAppService::templateHeaderFormat() and templateBodyText() answer the same questions,
     * but both return null unless the credentials resolve to a vendor's own WABA — they were
     * written for the vendor composer. This is the platform's equivalent, cached for the same
     * reason: a run works through the audience in passes, and without it a 1,000-person send would
     * ask Graph for one unchanging template list dozens of times over.
     */
    protected function platformTemplate(string $name, ?string $lang = null): ?array
    {
        try {
            $key = 'wa_admin_tpl_' . md5(strtolower($name . '|' . $lang));

            return Cache::remember($key, 600, function () use ($name, $lang) {
                $wa = WhatsAppService::make();
                if (!$wa->hasWaba()) {
                    return null;
                }

                $res = $wa->listTemplates();
                if (!$res['success']) {
                    return null;
                }

                foreach ($res['data'] as $tpl) {
                    if (strtolower((string) data_get($tpl, 'name')) !== strtolower($name)) {
                        continue;
                    }
                    if ($lang && strtolower((string) data_get($tpl, 'language')) !== strtolower($lang)) {
                        continue;
                    }
                    return (array) $tpl;
                }

                return null;
            });
        } catch (\Throwable $e) {
            // Unknown, never "no header" — the caller must not read a Graph outage as permission
            // to send a media template without its file.
            return null;
        }
    }

    /** TEXT / IMAGE / VIDEO / DOCUMENT, or null when the template has no header. */
    protected function headerFormatOf(?array $tpl): ?string
    {
        foreach ((array) data_get($tpl, 'components', []) as $c) {
            if (strtoupper((string) data_get($c, 'type')) === 'HEADER') {
                return strtoupper((string) data_get($c, 'format', 'TEXT')) ?: 'TEXT';
            }
        }

        return null;
    }

    /** The approved body text, still carrying its {{variables}}. */
    protected function bodyTextOf(?array $tpl): ?string
    {
        foreach ((array) data_get($tpl, 'components', []) as $c) {
            if (strtoupper((string) data_get($c, 'type')) === 'BODY') {
                return (string) data_get($c, 'text', '');
            }
        }

        return null;
    }
}
