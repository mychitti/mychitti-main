<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreopConsent extends Model
{
    protected $fillable = [
        'preop_case_id', 'name', 'status', 'signed_by', 'signed_at', 'meta', 'is_optional',
    ];

    protected $casts = [
        'signed_at'   => 'datetime',
        'is_optional' => 'boolean',
    ];
}
