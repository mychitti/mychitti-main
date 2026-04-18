<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpdAdmission extends Model
{
    protected $fillable = [
        'store_id', 'patient_id', 'bed_id', 'ward_id',
        'doctor_profile_id', 'admission_number', 'admission_date',
        'discharge_date', 'diagnosis', 'admission_reason',
        'discharge_summary', 'discharge_type', 'status',
        'admitted_by', 'discharged_by', 'daily_charge',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'discharge_date' => 'date',
        'daily_charge'   => 'float',
    ];

    const DISCHARGE_TYPES = [
        'normal'   => 'Normal Discharge',
        'lama'     => 'LAMA (Left Against Medical Advice)',
        'transfer' => 'Transfer to Another Facility',
        'death'    => 'Death',
        'absconded'=> 'Absconded',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function admittedBy()
    {
        return $this->belongsTo(VendorEmployee::class, 'admitted_by');
    }

    public function dischargedBy()
    {
        return $this->belongsTo(VendorEmployee::class, 'discharged_by');
    }
}
