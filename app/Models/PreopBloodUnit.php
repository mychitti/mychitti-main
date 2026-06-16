<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreopBloodUnit extends Model
{
    protected $fillable = [
        'preop_case_id', 'unit_id', 'component', 'blood_group', 'expiry_date', 'status',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];
}
