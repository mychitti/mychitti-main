<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NursingMarAdmin extends Model
{
    protected $table = 'nursing_mar_admins';

    protected $fillable = [
        'nursing_mar_order_id', 'admin_date', 'scheduled_time', 'status',
        'administered_by', 'administered_at', 'missed_reason',
    ];

    protected $casts = [
        'administered_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(NursingMarOrder::class, 'nursing_mar_order_id');
    }
}
