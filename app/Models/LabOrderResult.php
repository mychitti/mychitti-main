<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabOrderResult extends Model
{
    protected $fillable = [
        'lab_order_id', 'lab_order_item_id', 'parameter_name', 'unit',
        'normal_low', 'normal_high', 'ref_range_text', 'critical_low', 'critical_high',
        'result_value', 'result_flag', 'is_critical',
        'critical_notified_at', 'critical_notified_to', 'sort_order',
    ];

    protected $casts = [
        'normal_low'           => 'float',
        'normal_high'          => 'float',
        'critical_low'         => 'float',
        'critical_high'        => 'float',
        'is_critical'          => 'boolean',
        'critical_notified_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id');
    }

    public function item()
    {
        return $this->belongsTo(LabOrderItem::class, 'lab_order_item_id');
    }
}
