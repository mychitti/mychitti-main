<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
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

    protected $casts = [
        'appointment_date' => 'date',
    ];

    const STATUSES = ['scheduled', 'checked_in', 'consulting', 'completed', 'cancelled', 'no_show'];

    const STATUS_TRANSITIONS = [
        'scheduled'  => ['checked_in', 'cancelled', 'no_show'],
        'checked_in' => ['consulting', 'cancelled'],
        'consulting' => ['completed'],
        'completed'  => [],
        'cancelled'  => [],
        'no_show'    => [],
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_profile_id');
    }

    public function slot()
    {
        return $this->belongsTo(DoctorSlot::class, 'slot_id');
    }

    public function token()
    {
        return $this->hasOne(AppointmentToken::class, 'appointment_id');
    }

    public function rescheduledFrom()
    {
        return $this->belongsTo(Appointment::class, 'rescheduled_from');
    }
}
