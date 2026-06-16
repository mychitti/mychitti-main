<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabTestParameter extends Model
{
    protected $fillable = [
        'lab_test_id', 'name', 'unit', 'normal_low', 'normal_high',
        'ref_range_text', 'critical_low', 'critical_high', 'sort_order',
    ];

    protected $casts = [
        'normal_low'    => 'float',
        'normal_high'   => 'float',
        'critical_low'  => 'float',
        'critical_high' => 'float',
    ];

    public function test()
    {
        return $this->belongsTo(LabTest::class, 'lab_test_id');
    }
}
