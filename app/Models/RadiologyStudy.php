<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadiologyStudy extends Model
{
    protected $fillable = [
        'store_id', 'study_no', 'patient_id', 'ipd_admission_id', 'doctor_profile_id',
        'modality', 'study_name', 'body_part', 'priority', 'status', 'source', 'department',
        'referred_by', 'clinical_history', 'price', 'radiology_equipment_id',
        'scheduled_at', 'started_at', 'reported_at',
        'findings', 'impression', 'recommendations', 'radiologist',
        'is_critical', 'critical_notified_at', 'critical_notified_to',
        'created_by', 'created_by_type',
    ];

    protected $casts = [
        'price'                => 'float',
        'scheduled_at'         => 'datetime',
        'started_at'           => 'datetime',
        'reported_at'          => 'datetime',
        'is_critical'          => 'boolean',
        'critical_notified_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function equipment()
    {
        return $this->belongsTo(RadiologyEquipment::class, 'radiology_equipment_id');
    }
}
