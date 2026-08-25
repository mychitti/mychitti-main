<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Something a store typed that the pool does not have yet — one row per distinct product,
 * counted rather than duplicated. See App\Services\CatalogPool::suggest().
 */
class CatalogSuggestion extends Model
{
    public const STATUS_PENDING   = 'pending';   // not looked at yet
    public const STATUS_READY     = 'ready';     // AI says it is a real, new product
    public const STATUS_DUPLICATE = 'duplicate'; // AI matched it to an existing pool row
    public const STATUS_UNSURE    = 'unsure';    // AI could not decide — needs a human
    public const STATUS_APPROVED  = 'approved';  // added to the pool
    public const STATUS_MERGED    = 'merged';    // folded into an existing pool row
    public const STATUS_REJECTED  = 'rejected';  // not a real product

    /** Still waiting on an admin, whatever the AI thought. */
    public const OPEN_STATUSES = [
        self::STATUS_PENDING, self::STATUS_READY, self::STATUS_DUPLICATE, self::STATUS_UNSURE,
    ];

    protected $fillable = [
        'domain', 'store_id', 'inventory_item_id', 'raw_name', 'raw_brand', 'raw_strength', 'raw_form',
        'normalized_key', 'request_count', 'status', 'match_catalog_item_id', 'catalog_item_id',
        'ai_verdict', 'ai_confidence', 'ai_reason', 'ai_checked_at', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'request_count'  => 'integer',
        'ai_confidence'  => 'float',
        'ai_checked_at'  => 'datetime',
        'reviewed_at'    => 'datetime',
    ];

    public function match()
    {
        return $this->belongsTo(CatalogItem::class, 'match_catalog_item_id');
    }

    public function catalogItem()
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function getLabelAttribute(): string
    {
        return trim(implode(' ', array_filter([$this->raw_name, $this->raw_strength, $this->raw_form])));
    }

    public function getMetaLabelAttribute(): string
    {
        return implode(' · ', array_filter([$this->raw_brand ?: 'Generic', $this->raw_form]));
    }
}
