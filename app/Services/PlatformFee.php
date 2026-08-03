<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Waivers for the monthly platform fee that `platform-fee:deduct` takes from vendor wallets on
 * the 1st of each month.
 *
 * Two kinds, both granted by an admin and both free:
 *   TRIAL    — waived until a date the admin picks, then the fee resumes on its own.
 *   LIFETIME — waived forever, until an admin revokes it.
 *
 * The waiver is recorded against the STORE, because that is what the admin panel works with,
 * but the fee is deducted per vendor wallet — so isWaived() is asked by vendor id and resolves
 * through stores.vendor_id. A vendor with more than one store is waived if any of them carries
 * a live waiver: the fee is charged once per wallet, so it can only be waived once per wallet.
 *
 * The cron must never crash because an admin has not opened the page yet, so every read is
 * guarded on the columns existing and answers "not waived" when they do not.
 */
class PlatformFee
{
    const WAIVER_TRIAL    = 'trial';
    const WAIVER_LIFETIME = 'lifetime';

    /** Idempotent schema bootstrap — same approach as the rest of the platform. */
    public static function ensureColumns(): void
    {
        if (!Schema::hasTable('stores')) {
            return;
        }

        foreach ([
            'platform_fee_waiver'       => "VARCHAR(20) NULL",
            // NULL on a lifetime waiver — there is no date it stops.
            'platform_fee_waiver_until' => "DATE NULL",
            'platform_fee_waiver_note'  => "VARCHAR(190) NULL",
        ] as $column => $definition) {
            if (!Schema::hasColumn('stores', $column)) {
                DB::statement("ALTER TABLE `stores` ADD COLUMN `{$column}` {$definition}");
            }
        }
    }

    /** Are the waiver columns present? Cached per request so the cron does not re-ask per vendor. */
    protected static ?bool $ready = null;

    protected static function ready(): bool
    {
        if (static::$ready === null) {
            static::$ready = Schema::hasTable('stores') && Schema::hasColumn('stores', 'platform_fee_waiver');
        }
        return static::$ready;
    }

    /** The waiver row for a store, or null when it has none. */
    public static function forStore(int $storeId)
    {
        if (!static::ready()) {
            return null;
        }

        $row = DB::table('stores')->where('id', $storeId)
            ->select('platform_fee_waiver', 'platform_fee_waiver_until', 'platform_fee_waiver_note')
            ->first();

        return ($row && $row->platform_fee_waiver) ? $row : null;
    }

    /** Is a waiver still running? A lifetime one always is; a trial one until its date passes. */
    public static function isLive($row): bool
    {
        if (!$row || !($row->platform_fee_waiver ?? null)) {
            return false;
        }
        if ($row->platform_fee_waiver === self::WAIVER_LIFETIME) {
            return true;
        }

        return $row->platform_fee_waiver_until
            && Carbon::parse($row->platform_fee_waiver_until)->endOfDay()->isFuture();
    }

    /**
     * Should this vendor's wallet be skipped this month? Asked by `platform-fee:deduct`, which
     * iterates wallets and only knows the vendor id.
     */
    public static function isWaived(?int $vendorId): bool
    {
        if (!$vendorId || !static::ready()) {
            return false;
        }

        $rows = DB::table('stores')->where('vendor_id', $vendorId)
            ->whereNotNull('platform_fee_waiver')
            ->select('platform_fee_waiver', 'platform_fee_waiver_until')
            ->get();

        foreach ($rows as $row) {
            if (static::isLive($row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Waive the fee for a store. `$until` is required for a trial and ignored for lifetime.
     */
    public static function grant(int $storeId, string $type, ?string $until = null, ?string $note = null): array
    {
        static::ensureColumns();
        static::$ready = true;

        $lifetime = $type === self::WAIVER_LIFETIME;
        if (!$lifetime && $type !== self::WAIVER_TRIAL) {
            return ['success' => false, 'message' => 'Unknown waiver type.'];
        }

        if (!$lifetime) {
            if (!$until) {
                return ['success' => false, 'message' => 'Pick the date the free trial should end.'];
            }
            if (Carbon::parse($until)->endOfDay()->isPast()) {
                return ['success' => false, 'message' => 'That trial end date has already passed.'];
            }
        }

        DB::table('stores')->where('id', $storeId)->update([
            'platform_fee_waiver'       => $type,
            'platform_fee_waiver_until' => $lifetime ? null : Carbon::parse($until)->toDateString(),
            'platform_fee_waiver_note'  => $note ? mb_substr($note, 0, 190) : null,
        ]);

        return [
            'success' => true,
            'message' => $lifetime
                ? 'Platform fee waived for life. This store is never charged the monthly fee again.'
                : 'Platform fee waived until ' . Carbon::parse($until)->format('d M Y')
                    . '. It resumes by itself the following month.',
        ];
    }

    /** Put the store back on the normal monthly fee. */
    public static function revoke(int $storeId): array
    {
        static::ensureColumns();
        static::$ready = true;

        DB::table('stores')->where('id', $storeId)->update([
            'platform_fee_waiver'       => null,
            'platform_fee_waiver_until' => null,
            'platform_fee_waiver_note'  => null,
        ]);

        return ['success' => true, 'message' => 'Waiver removed — the monthly platform fee applies again.'];
    }
}
