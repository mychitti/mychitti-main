<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * How an OPD visit is being paid for — cash, an insurer, a government scheme, a corporate tie-up.
 *
 * Recorded on the visit because it changes what the desk does next: an Arogyasri patient needs a
 * scheme number before the consultation, an insurance patient needs a pre-authorisation, and a
 * cash patient needs neither. Kept apart from visit_type, which is about the *clinical* nature of
 * the visit (new, follow-up, emergency) and answers a different question.
 *
 * Follows OpdClinicalTerm's shape rather than seeding rows per store: DEFAULTS is the platform
 * list and is read, never copied. A row here is only ever the difference between that list and
 * what this hospital wants — a type it added (hidden = 0), or a default it does not offer
 * (hidden = 1). That way a hospital that never opens the settings screen still gets a working
 * dropdown, and the platform list can be extended later without touching anyone's data.
 */
class OpdOpType extends Model
{
    protected $fillable = ['store_id', 'name', 'hidden'];

    protected $casts = ['hidden' => 'boolean'];

    /**
     * The list every hospital starts with. Read, never written to a store's rows. Deliberately
     * just the one: a hospital builds its own list from the register form or Hospital Settings,
     * and a long platform list would only be something most of them have to switch off.
     */
    const DEFAULTS = [
        'ESI',
    ];

    public static function ensureSchema(): void
    {
        if (!Schema::hasTable('opd_op_types')) {
            DB::statement("CREATE TABLE `opd_op_types` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `hidden` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `oot_store_name` (`store_id`, `name`),
                KEY `oot_store_hidden` (`store_id`, `hidden`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * What the OP Type dropdown offers this hospital: the platform defaults it has not hidden,
     * followed by the ones it added. Matched case-insensitively so "Insurance" added by hand does
     * not sit beside the default spelling of itself.
     */
    public static function listFor(int $storeId): array
    {
        static::ensureSchema();

        $rows = static::forStore($storeId)->get(['name', 'hidden']);

        $hidden = $rows->where('hidden', true)
            ->pluck('name')
            ->mapWithKeys(fn($name) => [mb_strtolower(trim($name)) => true])
            ->all();

        $names = [];
        foreach (self::DEFAULTS as $name) {
            $key = mb_strtolower(trim($name));
            if (!isset($hidden[$key])) {
                $names[$key] = $name;
            }
        }

        foreach ($rows->where('hidden', false)->pluck('name') as $name) {
            $key = mb_strtolower(trim($name));
            if (!isset($hidden[$key])) {
                $names[$key] = $name;
            }
        }

        return array_values($names);
    }

    /** The store's own additions, for the settings screen. */
    public static function ownNames(int $storeId): array
    {
        static::ensureSchema();

        return static::forStore($storeId)->where('hidden', false)->orderBy('name')->pluck('name')->all();
    }

    /** Defaults this store has switched off, for the settings screen. */
    public static function hiddenNames(int $storeId): array
    {
        static::ensureSchema();

        return static::forStore($storeId)->where('hidden', true)->pluck('name')->all();
    }

    /** Add a type to this store's list. No-op when it is already offered. */
    public static function add(int $storeId, string $name): void
    {
        static::ensureSchema();

        $name = trim($name);
        if ($name === '') {
            return;
        }

        // Adding back something that was hidden is a restore, not a duplicate row.
        $existing = static::forStore($storeId)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            $existing->update(['hidden' => false]);
            return;
        }

        static::create(['store_id' => $storeId, 'name' => mb_substr($name, 0, 100), 'hidden' => false]);
    }

    /**
     * Stop offering a type. A platform default gets a hidden row; a type this store added is
     * simply removed, because there is no platform entry left behind to suppress.
     */
    public static function hide(int $storeId, string $name): void
    {
        static::ensureSchema();

        $name  = trim($name);
        $isOwn = static::forStore($storeId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->where('hidden', false)
            ->exists();

        $isDefault = collect(self::DEFAULTS)
            ->contains(fn($d) => mb_strtolower($d) === mb_strtolower($name));

        if ($isOwn && !$isDefault) {
            static::forStore($storeId)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->delete();
            return;
        }

        static::updateOrCreate(
            ['store_id' => $storeId, 'name' => mb_substr($name, 0, 100)],
            ['hidden' => true]
        );
    }

    /** Put a hidden default back on the list. */
    public static function restore(int $storeId, string $name): void
    {
        static::ensureSchema();

        static::forStore($storeId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->where('hidden', true)
            ->delete();
    }
}
