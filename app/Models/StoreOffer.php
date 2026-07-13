<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
 
/**
 * Local Offers Engine (Phase 3 §3.5) — time-limited vendor offers surfaced in AI Search
 * result cards and on store/listing pages.
 */
class StoreOffer extends Model
{
    protected $fillable = [
        'store_id', 'category_id', 'title', 'description',
        'discount_type', 'discount_value', 'start_date', 'end_date', 'status',
    ];

    protected $casts = [ 
        'start_date'     => 'date',
        'end_date'       => 'date',
        'discount_value' => 'float',
        'status'         => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /** Active = enabled and within its (optional) date window as of today. */
    public function scopeActive(Builder $q): Builder
    {
        $today = now()->toDateString();
        return $q->where('status', 1)
            ->where(fn($w) => $w->whereNull('start_date')->orWhere('start_date', '<=', $today))
            ->where(fn($w) => $w->whereNull('end_date')->orWhere('end_date', '>=', $today));
    }

    public function getLabelAttribute(): string
    {
        if ($this->discount_type === 'percent' && $this->discount_value) {
            return rtrim(rtrim(number_format($this->discount_value, 2), '0'), '.') . '% off';
        }
        if ($this->discount_type === 'flat' && $this->discount_value) {
            return '₹' . rtrim(rtrim(number_format($this->discount_value, 2), '0'), '.') . ' off';
        }
        return $this->title;
    }
}
