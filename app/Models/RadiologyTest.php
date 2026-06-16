<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadiologyTest extends Model
{
    protected $fillable = [
        'store_id', 'name', 'modality', 'body_part', 'price', 'tat_text', 'is_active',
    ];

    protected $casts = [
        'price'     => 'float',
        'is_active' => 'boolean',
    ];
}
