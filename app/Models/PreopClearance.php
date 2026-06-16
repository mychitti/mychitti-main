<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreopClearance extends Model
{
    protected $fillable = [
        'preop_case_id', 'type_label', 'by_label', 'status', 'note', 'sort_order',
    ];
}
