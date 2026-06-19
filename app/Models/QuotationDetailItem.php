<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationDetailItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function unitId()
    {
        return $this->belongsTo(Unit::class, 'unit');
    }
}
