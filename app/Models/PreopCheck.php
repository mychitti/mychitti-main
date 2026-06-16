<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreopCheck extends Model
{
    // category: quick | prep | handover | investigation
    protected $fillable = [
        'preop_case_id', 'category', 'label', 'status', 'meta', 'sort_order',
    ];
}
