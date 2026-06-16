<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSession extends Model
{
    protected $fillable = [
        'store_id', 'branch_id', 'name', 'start_date', 'end_date', 'is_current', 'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_current' => 'boolean',
    ];
}
