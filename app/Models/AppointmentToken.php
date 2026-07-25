<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentToken extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'appointment_id', 'token_number', 'token_date', 'doctor_profile_id', 'called_at',
    ];

    /**
     * Next queue number for a doctor on a given day.
     */
    public static function issue(int $doctorProfileId, string $date, int $appointmentId): int
    {
        $last = static::where('doctor_profile_id', $doctorProfileId)
            ->where('token_date', $date)
            ->max('token_number');

        $tokenNumber = ($last ?? 0) + 1;

        static::create([
            'appointment_id'    => $appointmentId,
            'token_number'      => $tokenNumber,
            'token_date'        => $date,
            'doctor_profile_id' => $doctorProfileId,
        ]);

        return $tokenNumber;
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor()
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_profile_id');
    }
}
