<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What a hospital last charged for a given treatment.
 *
 * There is no treatment price list anywhere in the module — treatments are free text a doctor
 * types into the Diagnosis & Treatment card — so the price is learnt instead: whatever was put
 * against "Root Canal" on the last visit is what the next one is offered. A doctor who charges
 * something else simply types over it, and that becomes the new memory.
 *
 * One row per store per term. Terms are matched case-insensitively on a normalised key so
 * "Root Canal" and "root canal" do not each keep their own price.
 */
class OpdTreatmentPrice extends Model
{
    protected $fillable = ['store_id', 'term_key', 'term', 'amount', 'discount', 'is_active'];

    protected $casts = [
        'amount'    => 'float',
        'discount'  => 'float',
        'is_active' => 'boolean',
    ];

    public static function ensureSchema(): void
    {
        if (Schema::hasTable('opd_treatment_prices')) {
            return;
        }

        DB::statement("CREATE TABLE `opd_treatment_prices` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `store_id` BIGINT UNSIGNED NOT NULL,
            `term_key` VARCHAR(190) NOT NULL,
            `term` VARCHAR(190) NOT NULL,
            `amount` DECIMAL(12,2) NULL,
            `discount` DECIMAL(12,2) NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `otp_store_term` (`store_id`, `term_key`),
            KEY `otp_store` (`store_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /** Same normalisation OpdTermInsights uses, so the two agree on what one term is. */
    public static function key(string $term): string
    {
        return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($term)));
    }

    /** Remember what this treatment was charged at. Called only when an amount was actually set. */
    public static function remember(int $storeId, string $term, $amount, $discount = null): void
    {
        $term = trim($term);
        if ($term === '' || $amount === null || $amount === '') {
            return;
        }

        self::ensureSchema();

        self::updateOrCreate(
            ['store_id' => $storeId, 'term_key' => self::key($term)],
            ['term' => $term, 'amount' => (float) $amount, 'discount' => $discount === null || $discount === '' ? null : (float) $discount]
        );
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /** term => ['amount' => float, 'discount' => float|null] for the terms asked about. */
    public static function mapFor(int $storeId, array $terms): array
    {
        if (!$terms || !Schema::hasTable('opd_treatment_prices')) {
            return [];
        }

        $keys = collect($terms)->map(fn($term) => self::key($term))->filter()->unique()->values();
        if ($keys->isEmpty()) {
            return [];
        }

        $rows = self::where('store_id', $storeId)->where('is_active', 1)
            ->whereIn('term_key', $keys)->get()->keyBy('term_key');

        $map = [];
        foreach ($terms as $term) {
            $row = $rows->get(self::key($term));
            if ($row) {
                $map[$term] = ['amount' => $row->amount, 'discount' => $row->discount];
            }
        }

        return $map;
    }
}
