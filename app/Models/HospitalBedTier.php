<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalBedTier extends Model
{
    protected $fillable = [
        'tier_name',
        'min_beds',
        'max_beds',
        'price_monthly',
        'price_yearly',
        'is_custom',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'min_beds'      => 'integer',
        'max_beds'      => 'integer',
        'price_monthly' => 'float',
        'price_yearly'  => 'float',
        'is_custom'     => 'boolean',
        'is_active'     => 'boolean',
        'sort_order'    => 'integer',
    ];

    /**
     * Find the matching active tier for a given bed count.
     */
    public static function forBedCount(int $count): ?self
    {
        return self::where('is_active', true)
            ->where('min_beds', '<=', $count)
            ->where(function ($q) use ($count) {
                $q->whereNull('max_beds')->orWhere('max_beds', '>=', $count);
            })
            ->orderBy('min_beds', 'desc')
            ->first();
    }

    public function getBedRangeAttribute(): string
    {
        if (is_null($this->max_beds)) {
            return $this->min_beds . '+ beds';
        }
        return $this->min_beds . '–' . $this->max_beds . ' beds';
    }
}
