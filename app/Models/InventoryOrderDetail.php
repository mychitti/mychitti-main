<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class InventoryOrderDetail extends Model
{
    use HasFactory;

    public function order()
    {
        return $this->belongsTo(InventoryOrder::class, 'order_id', 'order_id');
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
