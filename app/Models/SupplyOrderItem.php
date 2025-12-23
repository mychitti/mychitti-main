<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplyOrderItem extends Model
{
    use HasFactory;

    public function order()
    {
        return $this->belongsTo(SupplyOrder::class, 'order_table_id', 'id');
    }
    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
