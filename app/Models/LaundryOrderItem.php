<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LaundryOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'laundry_order_id',
        'laundry_item_id',
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

    public function laundryItem()
    {
        return $this->belongsTo(LaundryItem::class, 'laundry_item_id');
    }
}
