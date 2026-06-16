<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabSample extends Model
{
    protected $fillable = [
        'lab_order_id', 'sample_no', 'sample_type', 'status',
        'collected_by', 'collected_at', 'received_at', 'rejected_reason',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'received_at'  => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id');
    }
}
