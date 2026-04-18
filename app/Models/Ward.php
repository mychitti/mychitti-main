<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    protected $fillable = [
        'store_id', 'ward_name', 'ward_type', 'floor',
        'total_beds', 'daily_charge', 'is_active',
    ];

    protected $casts = ['daily_charge' => 'float', 'is_active' => 'boolean'];

    const TYPES = [
        'general'      => 'General',
        'ac'           => 'AC',
        'non_ac'       => 'Non-AC',
        'icu'          => 'ICU',
        'private'      => 'Private',
        'semi_private' => 'Semi-Private',
        'nicu'         => 'NICU',
        'picu'         => 'PICU',
    ];

    public function beds()
    {
        return $this->hasMany(Bed::class);
    }

    public function availableBeds()
    {
        return $this->hasMany(Bed::class)->where('status', 'available');
    }
}
