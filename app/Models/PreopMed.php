<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreopMed extends Model
{
    protected $fillable = [
        'preop_case_id', 'name', 'detail', 'dose', 'route_time', 'purpose', 'status',
    ];
}
