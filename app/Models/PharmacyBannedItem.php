<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyBannedItem extends Model
{
    protected $table = 'pharmacy_banned_items';

    protected $fillable = ['store_id', 'name', 'reason', 'source', 'status'];

    // Active banned/blocked medicine names (lowercased) for the store — used for warn-on-entry checks.
    public static function activeNames($storeId)
    {
        return static::where('store_id', $storeId)
            ->where('status', 1)
            ->pluck('name')
            ->map(fn($n) => mb_strtolower(trim($n)))
            ->values();
    }
}
