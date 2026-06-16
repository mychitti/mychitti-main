<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NursingHandover extends Model
{
    protected $fillable = [
        'store_id', 'ward_id', 'handover_date', 'shift',
        'outgoing_nurse', 'incoming_nurse', 'notes', 'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];
}
