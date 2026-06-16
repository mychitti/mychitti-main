<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabReagent extends Model
{
    protected $fillable = [
        'store_id', 'name', 'machine', 'for_test', 'expiry_date',
        'stock', 'min_level', 'unit_label',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'stock'       => 'float',
        'min_level'   => 'float',
    ];

    public function statusLevel(): string
    {
        if ($this->stock <= 0) {
            return 'out';
        }
        if ($this->min_level > 0 && $this->stock <= $this->min_level * 0.25) {
            return 'critical';
        }
        if ($this->min_level > 0 && $this->stock <= $this->min_level) {
            return 'low';
        }
        return 'ok';
    }
}
