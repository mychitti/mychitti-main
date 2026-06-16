<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    protected $fillable = [
        'store_id', 'branch_id', 'academic_session_id', 'school_class_id', 'items',
    ];

    // items: [{head_id, head_name, amount, frequency}]
    protected $casts = ['items' => 'array'];
}
