<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorProfile extends Model
{
    protected $fillable = [
        'emp_id', 'vendor_id', 'specialization', 'qualification',
        'registration_number', 'department', 'opd_room',
        'consultation_fee', 'available_days', 'available_from',
        'available_to', 'bio',
    ];

    public function employee()
    {
        return $this->belongsTo(VendorEmployee::class, 'emp_id');
    }

    public function slots()
    {
        return $this->hasMany(DoctorSlot::class, 'doctor_profile_id');
    }
}
