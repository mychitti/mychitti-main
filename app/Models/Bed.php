<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bed extends Model
{
    protected $fillable = [
        'store_id', 'ward_id', 'bed_number', 'bed_type', 'status', 'daily_charge',
    ];

    protected $casts = ['daily_charge' => 'float'];

    const TYPES = ['general' => 'General', 'private' => 'Private', 'semi_private' => 'Semi-Private', 'icu' => 'ICU'];

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function activeAdmission()
    {
        return $this->hasOne(IpdAdmission::class)->where('status', 'admitted');
    }
}
