<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NursingMarOrder extends Model
{
    protected $fillable = [
        'store_id', 'patient_id', 'ipd_admission_id',
        'medicine_name', 'dose', 'route', 'frequency', 'schedule_times', 'is_active',
    ];

    protected $casts = [
        'schedule_times' => 'array',
        'is_active'      => 'boolean',
    ];

    public function admins()
    {
        return $this->hasMany(NursingMarAdmin::class);
    }
}
