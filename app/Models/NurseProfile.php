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
}
