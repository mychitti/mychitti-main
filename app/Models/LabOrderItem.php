<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabOrderItem extends Model
{
    protected $fillable = [
        'lab_order_id', 'lab_test_id', 'test_name', 'department', 'price', 'status', 'interpretation',
    ];

    protected $casts = [
        'price' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id');
    }

    public function test()
    {
        return $this->belongsTo(LabTest::class, 'lab_test_id');
    }

    public function results()
    {
        return $this->hasMany(LabOrderResult::class)->orderBy('sort_order');
    }
}
