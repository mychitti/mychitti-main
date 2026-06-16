<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NursingVital extends Model
{
    protected $fillable = [
        'store_id', 'patient_id', 'ipd_admission_id',
        'bp_systolic', 'bp_diastolic', 'hr', 'temp', 'spo2', 'rr', 'pain',
        'recorded_by', 'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];
}
