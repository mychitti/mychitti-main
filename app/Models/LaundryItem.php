<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LaundryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'category',
        'price',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'price'     => 'float',
        'is_active' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function orderItems()
    {
        return $this->hasMany(LaundryOrderItem::class);
    }

    public function challanItems()
    {
        return $this->hasMany(LaundryChallanItem::class);
    }
}
