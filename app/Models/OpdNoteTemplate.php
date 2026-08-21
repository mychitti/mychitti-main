<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One reusable consultation-note phrase, kept per store.
 *
 * Consultation notes are picked as chips, exactly like chief complaints — the phrase *is* the
 * chip, so a row here is both the dropdown entry and the text that lands on the visit. "Advised
 * rest for three days", "Review after one week", "Explained procedure and risks" get typed once
 * and picked from then on.
 *
 * Phrases belong to the store, not the platform: they are that clinic's own wording, learned
 * from what its doctors actually typed rather than a list an admin guessed at. Anything typed
 * into the picker is absorbed by remember() on save, which is what keeps the list growing
 * without anyone maintaining it.
 */
class OpdNoteTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'name', 'body', 'created_by'];

    /** Added in place on first use — no migration files (see CLAUDE.md). */
    public static function ensureSchema(): void
    {
        if (Schema::hasTable('opd_note_templates')) {
            // The first cut of this table held prose templates keyed by a short name. Notes are
            // chips now, so the phrase itself lives in `name` and needs the room.
            try {
                $type = DB::selectOne("SHOW COLUMNS FROM `opd_note_templates` WHERE Field = 'name'");
                if ($type && stripos($type->Type, 'varchar(100)') !== false) {
                    DB::statement("ALTER TABLE `opd_note_templates` MODIFY `name` VARCHAR(190) NOT NULL");
                }
            } catch (\Throwable $e) {
                // Best effort — a short column still works, it just caps a long phrase.
            }

            return;
        }

        DB::statement("CREATE TABLE `opd_note_templates` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `store_id` BIGINT UNSIGNED NOT NULL,
            `name` VARCHAR(190) NOT NULL,
            `body` TEXT NULL,
            `created_by` BIGINT UNSIGNED NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `ont_store_name` (`store_id`, `name`),
            KEY `ont_store` (`store_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /** Every phrase this store has, alphabetical — small lists, so no paging. */
    public static function listFor(int $storeId)
    {
        static::ensureSchema();

        return static::forStore($storeId)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Absorb whatever the doctor typed into the picker, so a phrase entered once is offered
     * from then on. Case-insensitive: "Review after one week" must not join the list twice
     * because someone capitalised it differently.
     */
    public static function remember(int $storeId, array $phrases): void
    {
        static::ensureSchema();

        $phrases = collect($phrases)
            ->map(fn($phrase) => trim((string) $phrase))
            ->filter()
            ->unique(fn($phrase) => mb_strtolower($phrase))
            ->values();

        if ($phrases->isEmpty()) {
            return;
        }

        $known = static::forStore($storeId)
            ->pluck('name')
            ->map(fn($name) => mb_strtolower($name))
            ->all();

        foreach ($phrases as $phrase) {
            if (in_array(mb_strtolower($phrase), $known, true)) {
                continue;
            }

            // firstOrCreate rather than create: two clinicians saving the same new phrase at the
            // same moment would otherwise collide on the unique key.
            static::firstOrCreate(
                ['store_id' => $storeId, 'name' => mb_substr($phrase, 0, 190)],
                ['created_by' => auth('vendor_employee')->id() ?? auth('vendor')->id()]
            );
        }
    }
}
