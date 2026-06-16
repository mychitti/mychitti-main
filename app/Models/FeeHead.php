<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeHead extends Model
{
    protected $fillable = ['store_id', 'branch_id', 'name', 'gst_percent', 'status'];

    protected $casts = ['gst_percent' => 'float'];
}
