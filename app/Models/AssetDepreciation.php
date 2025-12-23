<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDepreciation extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'depreciation_date',
        'opening_value',
        'depreciation_amount',
        'closing_value',
        'store_id',
    ];

    public function asset(){
        return $this->belongsTo(StoreAsset::class, 'asset_id');
        
    }
}
