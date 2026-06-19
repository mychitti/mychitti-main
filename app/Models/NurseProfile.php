<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NurseProfile extends Model
{
    protected $fillable = [
        'store_id', 'emp_id', 'qualification', 'ward_id',
        'department', 'registration_number', 'notes',
    ];

    // Shift is managed centrally via the staff member's StoreShift (employee->storeShift),
    // not a nurse-specific field. Use $nurse->employee->storeShift everywhere.

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
