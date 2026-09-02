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
     * Next queue number for the store on a given day. The queue is shared across every
     * doctor in the store, so two patients seeing different doctors never hold the same
     * token. doctor_profile_id is still recorded, but only as metadata.
     */
    public static function issue(int $doctorProfileId, string $date, int $appointmentId): int
    {
        $storeId = Appointment::where('id', $appointmentId)->value('store_id');

        $query = static::where('token_date', $date);

        // Without a store we cannot scope the queue, so fall back to the day's global max —
        // a higher number is harmless, a duplicate is not.
        if ($storeId) {
            $query->whereHas('appointment', fn ($q) => $q->where('store_id', $storeId));
        }

        $tokenNumber = ($query->max('token_number') ?? 0) + 1;

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
