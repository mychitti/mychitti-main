<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NursingTask extends Model
{
    protected $fillable = [
        'store_id', 'ward_id', 'ipd_admission_id', 'bed_label',
        'task_date', 'due_time', 'description', 'priority', 'shift',
        'status', 'done_by', 'done_at',
    ];

    protected $casts = [
        'done_at' => 'datetime',
    ];
}
