<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreopResult extends Model
{
    // status: normal | borderline | abnormal
    protected $fillable = [
        'preop_case_id', 'name', 'value', 'ref_range', 'status', 'sort_order',
    ];
}
