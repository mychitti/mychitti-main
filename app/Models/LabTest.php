<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabTest extends Model
{
    protected $fillable = [
        'store_id', 'name', 'code', 'department', 'sample_type',
        'price', 'tat_text', 'is_active',
    ];

    protected $casts = [
        'price'     => 'float',
        'is_active' => 'boolean',
    ];

    public function parameters()
    {
        return $this->hasMany(LabTestParameter::class)->orderBy('sort_order');
    }
}
