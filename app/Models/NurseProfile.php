<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NurseProfile extends Model
{
    protected $fillable = [
        'store_id', 'emp_id', 'qualification', 'ward_id',
        'department', 'shift', 'registration_number', 'notes',
    ];

    const SHIFTS = [
        'day'     => 'Day',
        'evening' => 'Evening',
        'night'   => 'Night',
        'rotating'=> 'Rotating',
    ];

    public function employee()
    {
        return $this->belongsTo(VendorEmployee::class, 'emp_id');
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    // IPD admissions this nurse is assigned to (pivot: ipd_admission_nurses).
    public function admissions()
    {
        return $this->belongsToMany(IpdAdmission::class, 'ipd_admission_nurses', 'nurse_profile_id', 'ipd_admission_id')
            ->withTimestamps();
    }
}
