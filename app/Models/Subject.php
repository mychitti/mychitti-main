<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'store_id', 'branch_id', 'code', 'name', 'status',
    ];
}
