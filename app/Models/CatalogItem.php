<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One curated record in the shared item pool — see App\Services\CatalogPool.
 */
class CatalogItem extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_MERGED = 'merged';
    public const STATUS_RETIRED = 'retired';

    /**
     * How tightly a store's copy stays bound to this row.
     *
     * strict   — the pool owns identity; admin corrections reach every store (medicines, lab
     *            tests, books, packaged goods: the product is objectively the same everywhere).
     * seed     — copied once at adopt time, then the store's row is its own (a restaurant's
     *            biryani is not another restaurant's biryani).
     * taxonomy — canonical name and unit only, nothing else (loose commodities).
     */
    public const LINK_STRICT   = 'strict';
    public const LINK_SEED     = 'seed';
    public const LINK_TAXONOMY = 'taxonomy';
    public const LINK_MODES = [self::LINK_STRICT, self::LINK_SEED, self::LINK_TAXONOMY];

    protected $fillable = [
        'domain', 'name', 'brand', 'strength_text', 'strength_value', 'strength_unit', 'form',
        'image', 'normalized_key', 'link_mode', 'status', 'merged_into_id', 'source', 'usage_count',
    ];

    protected $casts = [
        'strength_value' => 'float',
        'usage_count'    => 'integer',
    ];

    public function mergedInto()
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    /**
     * The row that should actually be used: a merged record hands off to its target so store
     * links made before the merge keep resolving to something real.
     */
    public function resolved(): self
    {
        $seen = [];
        $item = $this;

        while ($item->status === self::STATUS_MERGED && $item->merged_into_id && !isset($seen[$item->id])) {
            $seen[$item->id] = true;
            $next = self::find($item->merged_into_id);
            if (!$next) {
                break;
            }
            $item = $next;
        }

        return $item;
    }

    /**
     * "Pantoprazole 40 mg Tablet" — what a pharmacist expects to read, and what fills the store's
     * item name. The form is part of it on purpose: without it a hospital stocking both the
     * tablet and the syrup ends up with two identically named rows on their own shelf.
     */
    public function getLabelAttribute(): string
    {
        return trim(implode(' ', array_filter([$this->name, $this->strength_text, $this->form])));
    }

    /** Brand and form, as the secondary line under the name. */
    public function getMetaLabelAttribute(): string
    {
        return implode(' · ', array_filter([$this->brand ?: 'Generic', $this->form]));
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/app/public/catalog-item/' . $this->image) : null;
    }
}
