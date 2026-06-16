<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NursingFluidEntry extends Model
{
    protected $fillable = [
        'store_id', 'patient_id', 'ipd_admission_id',
        'entry_date', 'entry_time', 'description', 'type', 'volume_ml', 'recorded_by',
    ];

    protected $casts = [
        'volume_ml' => 'float',
    ];
}
