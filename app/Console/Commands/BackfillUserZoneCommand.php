<?php

namespace App\Console\Commands;

use App\CentralLogics\Helpers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill users.zone_id for accounts that never got one.
 *
 * The column was historically only written when a customer placed an order, so the vast
 * majority of rows are NULL (or 0, from an API path that wrote the cast of a missing header).
 * Anything filtering customers by zone therefore sees almost nobody.
 *
 * Sources are tried most-reliable first and the first hit wins. Users with no location
 * signal anywhere are left NULL on purpose — a guessed zone would quietly drop someone into
 * a vendor's marketing audience, which is worse than a known unknown.
 */
class BackfillUserZoneCommand extends Command
{
    protected $signature = 'users:backfill-zone
                            {--dry-run : Report what would change without writing}
                            {--chunk=500 : Users to process per batch}';

    protected $description = 'Populate users.zone_id from addresses, service requests, orders and vendor client books';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $pending = DB::table('users')
            ->where(fn($q) => $q->whereNull('zone_id')->orWhere('zone_id', 0))
            ->pluck('id');

        if ($pending->isEmpty()) {
            $this->info('Every user already has a zone.');
            return self::SUCCESS;
        }

        $this->info($pending->count() . ' user(s) without a zone.' . ($dry ? ' (dry run)' : ''));

        $validZones = DB::table('zones')->pluck('id')->flip();
        $resolved = [];

        foreach ($this->sources() as $label => $resolver) {
            $remaining = $pending->reject(fn($id) => isset($resolved[$id]))->values();
            if ($remaining->isEmpty()) {
                break;
            }

            $found = 0;
            foreach ($remaining->chunk((int) $this->option('chunk')) as $chunk) {
                foreach ($resolver($chunk->all()) as $userId => $zoneId) {
                    $zoneId = (int) $zoneId;
                    if ($zoneId > 0 && isset($validZones[$zoneId]) && !isset($resolved[$userId])) {
                        $resolved[$userId] = $zoneId;
                        $found++;
                    }
                }
            }
            $this->line(sprintf('  %-28s %d', $label, $found));
        }

        if (empty($resolved)) {
            $this->warn('No zones could be resolved.');
            return self::SUCCESS;
        }

        if ($dry) {
            $this->info(count($resolved) . ' user(s) would be updated; ' . ($pending->count() - count($resolved)) . ' have no location signal.');
            return self::SUCCESS;
        }

        // Grouped by zone so the whole backfill is a handful of statements, not thousands.
        $byZone = [];
        foreach ($resolved as $userId => $zoneId) {
            $byZone[$zoneId][] = $userId;
        }

        $updated = 0;
        foreach ($byZone as $zoneId => $userIds) {
            foreach (array_chunk($userIds, 1000) as $slice) {
                $updated += DB::table('users')->whereIn('id', $slice)->update(['zone_id' => $zoneId]);
            }
        }

        $this->info("Updated {$updated} user(s). " . ($pending->count() - count($resolved)) . ' left without a zone (no location signal).');
        return self::SUCCESS;
    }

    /**
     * @return array<string, callable(array<int>): array<int, int>> user_id => zone_id
     */
    private function sources(): array
    {
        return [
            'customer_addresses.zone_id' => fn(array $ids) => DB::table('customer_addresses')
                ->whereIn('user_id', $ids)
                ->whereNotNull('zone_id')->where('zone_id', '!=', 0)
                ->orderByDesc('id')
                ->pluck('zone_id', 'user_id')->all(),

            'orders.zone_id' => fn(array $ids) => DB::table('orders')
                ->whereIn('user_id', $ids)
                ->whereNotNull('zone_id')->where('zone_id', '!=', 0)
                ->orderByDesc('id')
                ->pluck('zone_id', 'user_id')->all(),

            'service_requests.zone_id' => function (array $ids) {
                // A JSON array of every zone the request was broadcast to ("[95,70,21]").
                // Zones nest, so the list runs country → state → city; resolve_primary_zone()
                // picks the smallest polygon rather than the first entry.
                $out = [];
                DB::table('service_requests')
                    ->whereIn('user_id', $ids)
                    ->whereNotNull('zone_id')
                    ->orderByDesc('id')
                    ->select('user_id', 'zone_id')
                    ->get()
                    ->each(function ($row) use (&$out) {
                        if (isset($out[$row->user_id])) {
                            return;
                        }
                        $zone = Helpers::resolve_primary_zone($row->zone_id);
                        if ($zone) {
                            $out[$row->user_id] = $zone;
                        }
                    });
                return $out;
            },

            'vendor client book (phone)' => function (array $ids) {
                // Last resort: a vendor has this phone in their client book, so place the
                // customer in that store's zone.
                $phones = DB::table('users')->whereIn('id', $ids)
                    ->whereNotNull('phone')->where('phone', '!=', '')
                    ->pluck('phone', 'id');

                $bySuffix = [];
                foreach ($phones as $userId => $phone) {
                    $suffix = substr(preg_replace('/[^0-9]/', '', (string) $phone) ?? '', -10);
                    if (strlen($suffix) === 10) {
                        $bySuffix[$suffix][] = $userId;
                    }
                }
                if (empty($bySuffix)) {
                    return [];
                }

                $expr = "RIGHT(REPLACE(REPLACE(REPLACE(sc.phone, ' ', ''), '-', ''), '+', ''), 10)";
                $rows = DB::table('store_customers as sc')
                    ->join('stores as s', 's.id', '=', 'sc.store_id')
                    ->whereNotNull('s.zone_id')->where('s.zone_id', '!=', 0)
                    ->whereIn(DB::raw($expr), array_keys($bySuffix))
                    ->select(DB::raw("$expr as suffix"), 's.zone_id')
                    ->get();

                $out = [];
                foreach ($rows as $row) {
                    foreach ($bySuffix[$row->suffix] ?? [] as $userId) {
                        $out[$userId] ??= $row->zone_id;
                    }
                }
                return $out;
            },
        ];
    }
}
