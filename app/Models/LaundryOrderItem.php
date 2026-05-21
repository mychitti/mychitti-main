<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'laundry_order_id',
        'inventory_item_id',
        'item_name',
        'qty',
        'rate',
        'amount',
        'notes',
    ];

    protected $casts = [
        'qty'    => 'integer',
        'rate'   => 'float',
        'amount' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(LaundryOrder::class, 'laundry_order_id');
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
