<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A named set of complaints a hospital keeps recording together.
 *
 * "Frequent urination, increased thirst, increased hunger, weight loss" is one presentation, typed
 * out four times a day at a diabetes-heavy OPD. Saved once as a group, it goes on with a single
 * tap. Groups belong to the store, not the platform — they are that clinic's own habits, built
 * from what its doctors actually selected rather than a list an admin guessed at.
 *
 * The terms are held the same way the visit holds them: a comma-separated list, split by
 * OpdVisit::splitTerms(). That keeps a group and a visit's complaints the same kind of thing, so
 * applying one is a plain merge with no translation.
 */
class OpdComplaintGroup extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'name', 'terms', 'created_by'];

    /** Added in place on first use — no migration files (see CLAUDE.md). */
    public static function ensureSchema(): void
    {
        if (Schema::hasTable('opd_complaint_groups')) {
            return;
        }

        DB::statement("CREATE TABLE `opd_complaint_groups` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `store_id` BIGINT UNSIGNED NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `terms` TEXT NULL,
            `created_by` BIGINT UNSIGNED NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `ocg_store_name` (`store_id`, `name`),
            KEY `ocg_store` (`store_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /** The group's complaints as a list, split exactly as a visit's are. */
    public function getTermListAttribute(): array
    {
        return OpdVisit::splitTerms($this->terms);
    }

    /** Every group this store has, newest name order — small lists, so no paging. */
    public static function listFor(int $storeId)
    {
        static::ensureSchema();

        return static::forStore($storeId)
            ->orderBy('name')
            ->get(['id', 'name', 'terms']);
    }
}
