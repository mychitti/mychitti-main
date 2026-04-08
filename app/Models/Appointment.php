<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'patient_id',
        'doctor_profile_id',
        'slot_id',
        'appointment_date',
        'appointment_time',
        'booking_type',
        'status',
        'reason',
        'cancel_reason',
        'rescheduled_from',
        'booked_by',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_profile_id');
    }
}
