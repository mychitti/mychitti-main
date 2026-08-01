<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorProfile extends Model
{
    protected $fillable = [
        'emp_id', 'store_id', 'specialization', 'qualification',
        'registration_number', 'department', 'opd_room',
        'consultation_fee', 'available_days', 'available_from',
        'available_to', 'bio', 'rebook_days',
    ];

    public function employee()
    {
        return $this->belongsTo(VendorEmployee::class, 'emp_id');
    }

    public function slots()
    {
        return $this->hasMany(DoctorSlot::class, 'doctor_profile_id');
    }
    public function services()
    {
        return $this->hasMany(DoctorService::class, 'doctor_profile_id');
    }
}
