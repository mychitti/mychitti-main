<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetAlotment extends Model
{
    use HasFactory;

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
    public function employee()
    {
        return $this->belongsTo(VendorEmployee::class);
    }
}
