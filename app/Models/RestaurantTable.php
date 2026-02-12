<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    protected $table = 'restaurant_tables';

    protected $fillable = [
        'store_id',
        'name',
        'capacity',
        'room_type',
        'status',
    ];
    public function openOrder()
    {
        return $this->hasOne(PosToken::class, 'table_id')
            ->where('order_status', 'open');
    }
}
