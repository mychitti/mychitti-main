<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorSlot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'doctor_profile_id', 'day_of_week', 'slot_start',
        'slot_end', 'slot_duration_minutes', 'max_patients', 'is_active',
    ];

    const DAYS = [
        0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday',
        3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
    ];

    public function doctor()
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_profile_id');
    }
}
