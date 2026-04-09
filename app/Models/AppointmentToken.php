<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentToken extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'appointment_id', 'token_number', 'token_date', 'doctor_profile_id', 'called_at',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor()
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_profile_id');
    }
}
