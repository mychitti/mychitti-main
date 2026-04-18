<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpdVisit extends Model
{
    protected $fillable = [
        'store_id', 'patient_id', 'doctor_profile_id', 'appointment_id',
        'visit_date', 'token_number', 'visit_type', 'chief_complaint',
        'bp_systolic', 'bp_diastolic', 'temperature', 'weight',
        'height', 'spo2', 'pulse_rate', 'respiratory_rate', 'notes', 'recorded_by', 'status',
    ];

    protected $casts = [
        'visit_date'       => 'date',
        'bp_systolic'      => 'integer',
        'bp_diastolic'     => 'integer',
        'temperature'      => 'float',
        'weight'           => 'float',
        'height'           => 'float',
        'spo2'             => 'integer',
        'pulse_rate'       => 'integer',
        'respiratory_rate' => 'integer',
    ];

    const VISIT_TYPES = [
        'new'      => 'New Visit',
        'followup' => 'Follow-Up',
        'emergency'=> 'Emergency',
        'review'   => 'Review',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function recorder()
    {
        return $this->belongsTo(VendorEmployee::class, 'recorded_by');
    }
}
